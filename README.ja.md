# WpTypeSafety

[English](README.md) | **日本語**

WpTypeSafetyは、WordPress・ACFから取得した不定形な値を、戻り値の型が決まったAPIで安全に扱うためのPHPライブラリです。

`string`、`?ImgData`、`list<AcfGet>`などの型付きの値やDataオブジェクトとして取得でき、必要な箇所だけ段階的に導入できます。既存APIと併用でき、サイト全体の書き換えや、すべての値のData化は必要ありません。

## 何が楽になるか

ACFの `get_field()` は、フィールド設定や未入力状態によって異なる型の値を返します。文字列が必要な場所では、通常は取得後に確認します。

```php
<?php
$raw = get_field('title', $post->ID);
$title = is_string($raw) && trim($raw) !== '' ? $raw : '';
```

WpTypeSafetyでは、最初に投稿を取得対象として指定し、用途ごとに戻り値型を固定した取得メソッド（以下、取得口）から値を読みます。

```php
<?php
use WpTypeSafety\Acf\Get\AcfGet;

$postGet = new AcfGet($post);
$title = $postGet->Text('title'); // string。未入力・型違いなら ''
$mainImg = $postGet->Img('main_img'); // ?ImgData。取得できなければ null
```

特に、複数階層のACFグループを読む場合に、各階層の配列確認を繰り返す必要がありません。

```php
<?php
$title = $postGet
	->Nested('first_view')
	->Nested('heading')
	->Text('title');
```

`Nested()`は、指定したグループが未入力・配列以外でも空の取得元を返します。そのため、途中の階層ごとに`isset()`や`is_array()`で分岐せず、最後まで同じ形でたどれます。
投稿などACFの取得先からグループをたどる場合は、未整形値と子フィールド定義も引き継ぐため、グループ内でも`Date()`・`DateTime()`・`Time()`を利用できます。
`AcfGet`の派生型から`Nested()`・`Repeater()`・`Flexible()`を呼ぶと、子の取得口にも同じ派生型と注入済みの依存が引き継がれます。

リピーターの各行も、最初から`AcfGet`として扱えます。行内のグループやリピーターも同じ方法で読み取れます。

```php
<?php
foreach ($postGet->Repeater('sections') as $sectionGet) {
	$title = $sectionGet->Nested('heading')->Text('title');
	$mainImg = $sectionGet->Nested('media')->Img('main_img');
}
```

単純なフィールドから複雑な入れ子まで、ACFの構造に沿って安全にたどり、各地点で戻り値の型が固定された値を受け取れます。必要な箇所では、WordPress・ACFのネイティブAPIを直接使うこともできます。

- フィールドの戻り値形式や未入力・型違いの扱いを、取得口へ集約できます。
- テンプレート側は、生のWordPressオブジェクトやACF配列を毎回検証せず、固定された戻り値の契約を利用できます。
- 必要に応じてDataへ変換し、取得処理と表示処理を分離できます。

## 特徴

- `Text(): string`、`Number(): ?float`、`Int(): ?int`、`Img(): ?ImgData`、`Posts(): list<PostData>`、`Terms(): list<TermData>`、`Users(): list<WpUserData>`、`Repeater(): list<AcfGet>`、`Flexible(): list<FlexibleNode>`など、用途ごとに戻り値型を固定した取得口。
- 既存のWordPress・ACF APIと併用しながら、必要な取得処理から段階的に置き換えられる。Dataは直接`new`でも生成できる。
- WordPress・ACFに依存しないCoreの型確認と配列取得。
- Composerコマンドや`vendor`ディレクトリを必要とせず配置できる。テーマ側Data用のPSR-4読み込みは任意。

## インストールと読み込み

PHP 8.2以上が必要です。WordPress向け機能にはWordPress、ACF向け機能にはACFが必要です。日本語ドメインなどの国際化ドメイン名をURLとして検証する場合は、PHPの`intl`拡張を推奨します。自動テストはAPIスタブを使用しており、特定のWordPress・ACFバージョンでの結合動作を保証するものではありません。

### Composer経由で導入する

Composerを使用するプロジェクトでは、プロジェクトのルートで次を実行します。

```bash
composer require hosituka/wp-type-safety
```

プロジェクトの起動時に、Composerが生成した`vendor/autoload.php`を読み込みます。既に読み込み済みの場合は追加不要です。

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';
```

Composer経由では`WpTypeSafety/autoload.php`を追加で読み込む必要はありません。ComposerのPSR-4 Autoloaderが`WpTypeSafety\`のクラスを解決します。

### Composerを使わずに導入する

リポジトリの`WpTypeSafety/`ディレクトリ全体をテーマなどの利用先に配置し、起動時に`autoload.php`を一度読み込みます。次はテーマ直下に置いた例です。配置場所に合わせてパスを変更してください。

```php
<?php
// functions.php
require_once __DIR__ . '/WpTypeSafety/autoload.php';
```

`autoload.php`が`WpTypeSafety\`のPSR-4設定を登録します。Core・Wp・Acfの各クラスは、参照された時点で対応ファイルだけが読み込まれます。通常の利用にComposerコマンドや`vendor/autoload.php`は不要です。詳しい読み込み処理は[読み込みと任意PSR-4](docs/autoload.md)を参照してください。

### 実案件での利用実績

WpTypeSafetyは、実際のWordPress案件で継続的に利用・改善してきたライブラリです。実装で必要になった取得処理や型の境界を整理し、一定の動作実績とAPIの成熟が得られたため公開しています。

公開時点では、次の環境で安定した動作を確認しています。

- WordPress 7.0.8
- Advanced Custom Fields PRO 6.8.6

これは実案件での利用実績であり、対応バージョンの上限・下限を保証する互換性表ではありません。自動テストは引き続きAPIスタブを使用しているため、ほかのWordPress・ACFバージョンで利用する場合は導入先でも確認してください。

## クイックスタート

Composerまたは同梱の`autoload.php`でクラスを読み込み可能にしたテンプレートでは、次のように利用できます。

```php
<?php
use WpTypeSafety\Acf\Get\AcfGet;

$postGet = new AcfGet($post);
$title = $postGet->Text('title'); // string。未入力・型違いなら ''
$mainImg = $postGet->Img('main_img'); // ?ImgData。取得できなければ null

foreach ($postGet->Repeater('items') as $rowGet) {
	$label = $rowGet->Text('label'); // string
}

echo esc_html($title);
```

WordPressの値からDataを得るにはFactoryを使います。

```php
<?php
use WpTypeSafety\Wp\Get\WpDataFactory;

$postData = WpDataFactory::PostFromWpPost($post); // PostData
$commentData = WpDataFactory::CommentFromWpComment($comment); // CommentData
```

ACFフィールドは用途に対応する`AcfGet`で正規化できます。ネイティブAPIの結果だけ型確認したい場合や、テンプレートパーツの`$args`を読む場合はCoreを使えます。

```php
<?php
use WpTypeSafety\Core\Check\ArrayGet;
use WpTypeSafety\Acf\Get\AcfGet;
use WpTypeSafety\Core\Check\TypeCheck;

$count = (new AcfGet($post))->Int('count');             // ?int
$postObject = TypeCheck::Instance(get_post(123), WP_Post::class); // ?WP_Post
$title = (new ArrayGet($args))->String('title');         // string
```

`AcfGet::Int()`は整数・整数として表現できるfloat・整数形式の数値文字列を`int`へ正規化します。`TypeCheck`は変換を行わず、入力がもともと指定したPHP型である場合だけ値を返します。`ArrayGet::String()`は型違い・キーなしで空文字を返します。空文字だけでは「未入力」と「取得失敗」を区別できません。

## 入口の選び方

| やりたいこと | 主な入口 |
|---|---|
| ACFフィールドを型付きで取得する | `AcfGet` |
| WordPressオブジェクトをDataへ変換する | `WpDataFactory` |
| ACFブロック情報をDataへ変換する | `AcfDataFactory` |
| 単独の値を型確認する | `TypeCheck` |
| 配列の値を型付きで取得する | `ArrayGet` |

## 構成を段階的に選べます

WpTypeSafetyは、既存のWordPressテーマを一度に独自構成へ変更する必要はありません。用途と複雑さに応じて、次の構成を同じテーマ内で併用できます。数字は優劣や成熟度ではなく、扱う範囲の違いを表します。単純な箇所まで専用DataやViewクラスへ移す必要はありません。

| 構成 | 目的 | 主な入口 |
|---|---|---|
| 1. WordPress標準＋型確認 | テンプレート構造を変えず、生の値と`$args`を安全に扱う | `TypeCheck`、`ArrayGet` |
| 2. Get／Factory＋テーマ固有Data | 取得・確認・変換をテンプレートから分離する | `AcfGet`、`WpDataFactory`、テーマ固有Data |
| 3. Data＋Viewクラス | Dataの入力型に加え、テンプレートパスもクラス参照で管理する | `Psr4Autoload`、`XxxView::Render()` |

第1段階では、`get_template_part()`やWordPressのテンプレート階層を維持したまま、値を受け渡す境界だけを防御できます。第2段階では複雑な取得処理をData生成へ分離し、第3段階では必要なViewだけをクラス参照へ置き換えられます。WordPress標準構成を使い続けることも正式な利用方法です。各構成のコード例は[利用例](docs/examples.md)を参照してください。

## 設計方針と保証範囲

取得口は外部の値を確認・正規化します。DataのコンストラクターはPHPの型宣言で表せる型を受け取りますが、URLの形式、IDや寸法の正負などを一律には検証しません。直接Dataを作る場合は、呼び出し元が値を確認してください。画像・ファイルがURLだけで返る設定や、取得済み配列のフィールド定義を確認できない場合など、取得口ごとの制約は詳細文書に記載しています。

HTMLの組み立てと出力時のエスケープは利用側で行います。通常の文字列には`esc_html()`、URLの出力には`esc_url()`など、出力先に合う処理を使ってください。

## 発展的な使い方

第3段階のViewクラス構成では、任意PSR-4読み込みを案件側のDataやViewへ利用できます。設定方法とView構成例は[読み込みと任意PSR-4](docs/autoload.md)を参照してください。

## 詳細ドキュメント

| 内容 | 文書 | 主な対象 |
|---|---|---|
| ACFの取得口、グループ、リピーター、参照先、ブロック | [ACF](docs/acf.md) | 利用者 |
| WordPressの取得口とFactory | [WordPress](docs/wordpress.md) | 利用者 |
| 型確認と配列からの取得 | [Core](docs/core.md) | 利用者 |
| Data、Resolver、派生クラス | [Data](docs/data.md) | 利用者 |
| `autoload.php`と任意PSR-4読み込み | [Autoload](docs/autoload.md) | 利用者 |
| テーマ開発の補助機能 | [Support](docs/support.md) | 利用者 |
| 入れ子のACF、派生Data、ACFブロックの実装例 | [利用例](docs/examples.md) | 利用者 |
| 内部構造と検証手順 | [Architecture](docs/architecture.md) | 保守者 |
| 旧APIからの移行 | [Migration](docs/migration.md) | 移行時 |

## テスト

`tests/`のPHPファイルは独立したAPIスタブのテストです。実WordPress・ACF環境での結合テストとは区別してください。全件実行と構文チェックの手順は[内部構造と検証](docs/architecture.md)にあります。

## ライセンス

WpTypeSafety本体は[MIT License](LICENSE)です。同梱したComposerの`ClassLoader.php`は[別のライセンス表記](Autoload/Internal/Composer/LICENSE)を保持しています。
