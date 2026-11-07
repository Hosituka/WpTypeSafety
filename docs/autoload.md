# 読み込みと任意PSR-4

## 動作条件と読み込み

PHP 8.2以上。データクラスに`readonly class`を使用します。Composerは不要です。

```php
<?php
require_once __DIR__ . '/WpTypeSafety/entry.php';
```

最上位`entry.php`は`Acf/entry.php`を読み、依存先をたどってCore・Wp・Acfを準備します。各クラスはPSR-4で必要になった時点に読み込まれます。通常は最上位`entry.php`を`require_once`すれば、全機能を使用できます。

必要な範囲だけを初期化する場合は、領域別の入口を直接読み込めます。上位領域の入口は依存する下位領域を自動的に準備するため、呼び出し側で読み込み順を管理する必要はありません。

| 入口 | 読み込まれる入口 | 初期登録 |
|---|---|---|
| `autoload.php` | なし | PSR-4だけ |
| `Core/entry.php` | `autoload.php` | なし |
| `Wp/entry.php` | `Core/entry.php` | Wp Adapter |
| `Acf/entry.php` | `Wp/entry.php` | Wp＋Acf Adapter |
| `entry.php` | `Acf/entry.php` | 全機能 |

各入口は`require_once`で接続されているため、CoreからWp、WpからAcfの順に段階的に読み込んだ場合も、同じ登録処理は一度だけ実行されます。`Core/entry.php`はWp・Acfの初期登録を行いませんが、PSR-4の対応範囲そのものをCoreへ制限する入口ではありません。

公開クラスの名前空間は`WpTypeSafety\Core\Check`・`WpTypeSafety\Core\Data`などです。名前空間とディレクトリ階層を一致させ、内部処理は各領域の`Internal/`に置きます。

## テーマ側クラスの任意PSR-4読み込み

いずれかの入口を読むとWpTypeSafety自身のPSR-4設定が登録されます。案件側のDataやViewなどのクラスも自動で読み込む場合は、案件側の`composer.json`を追加で`Psr4Autoload::RegisterFromComposerJson()`へ渡します。Composerコマンドや生成済み`vendor/autoload.php`は不要です。

```php
<?php
// テーマの functions.php など、リクエストごとに一度通る場所
require_once __DIR__ . '/WpTypeSafety/entry.php';
\WpTypeSafety\Autoload\Psr4Autoload::RegisterFromComposerJson(__DIR__ . '/composer.json');
```

```json
{
  "autoload": {
    "psr-4": {
      "Theme\\Parts\\": "template-parts/"
    }
  }
}
```

この例では`Theme\Parts\PostCard\Data`を使うと、`template-parts/PostCard/Data.php`を読み込みます。ディレクトリはJSONファイルの位置を基準に解決します。同じ名前空間に複数のディレクトリを指定する場合は文字列の配列も使えます。クラスファイルの追加やJSONの変更は次のPHPリクエストから反映され、再生成コマンドは不要です。同じJSONを同じリクエスト内で再登録しても登録処理は増えません。

読み取る設定は`autoload.psr-4`のみです。`files`、`classmap`、`require`による依存パッケージの取得は行いません。JSONが不正、PSR-4の指定が不正、指定ディレクトリが存在しない場合は例外を投げます。利用者が通常のComposer autoloaderを使う場合は、この登録メソッドを呼ばずに自分の`vendor/autoload.php`を読み込んでください。

内部にはComposerの`ClassLoader.php`を同梱しています。出所とライセンスは`Autoload/Internal/Composer/LICENSE`を参照してください。

## Viewクラスへの活用

任意PSR-4読み込みはDataだけでなく、案件側のViewクラスにも利用できます。WordPress標準のテンプレートパーツでは、呼び出し側がファイルパスを文字列で指定します。

```php
<?php
get_template_part(
	'template-parts/t-post-archive/post-section',
	null,
	$args,
);
```

Viewをクラスとして構成すると、ファイルパスではなくクラスとメソッドを参照できます。

```php
<?php
use Theme\Parts\PostArchive\PostSectionView;

PostSectionView::Render($args);
```

対応するPSR-4設定を登録しておけば、クラスファイルのパス解決はAutoloaderが担当します。文字列パスの手入力を減らし、IDEの定義ジャンプ・参照検索や静的解析を利用できます。メソッドの引数型と戻り値型も宣言できます。

この構成は、存在しないクラスや配列内部の値まで自動的に保証する完全な型安全ではありません。一方で、文字列によるファイルパス参照をクラスシンボルによる参照へ置き換え、パス管理の安全性を高められます。

Viewクラス化はAutoloaderの活用例であり、WpTypeSafetyが必須とする設計ではありません。既存の`get_template_part()`と併用でき、型付きデータ取得やDataだけを部分的に導入することもできます。
