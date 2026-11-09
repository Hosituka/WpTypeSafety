# WpTypeSafety

[English](README.md) | **日本語**

WpTypeSafetyは、WordPress・ACFから返る不定形な値を、予測可能な戻り値型へ確認・正規化するPHPライブラリです。

既存テーマ全体の書き換えは必要ありません。

- 1個のネイティブAPIの戻り値確認だけに導入できます。
- 既存のWordPress・ACF APIと併用できます。
- WordPressのテンプレート階層と`get_template_part()`を維持できます。
- 必要な箇所だけ`AcfGet`やDataへ移行できます。
- 案件固有DataやViewクラスの導入は任意です。

WpTypeSafetyは、特定のテーマ構成を強制するフレームワークではありません。既存案件のデータ境界へ、必要な範囲で型確認と正規化を追加するためのライブラリです。

## 最小構成から導入できます

ACFネイティブAPIの戻り値を1個だけ確認できます。`TypeCheck`は型変換を行いません。

```php
<?php
use WpTypeSafety\Core\Check\TypeCheck;

$title = TypeCheck::String(get_field('title')) ?? '';
```

既存のテンプレートパーツでは、受け取った`$args`だけを確認できます。

```php
<?php
use WpTypeSafety\Core\Check\ArrayGet;

$argsGet = new ArrayGet($args);
$title = $argsGet->String('title');
```

ACFフィールド1個だけを、用途に応じた固定型へ正規化することもできます。

```php
<?php
use WpTypeSafety\Acf\Get\AcfGet;

$postGet = new AcfGet($post);
$mainImg = $postGet->Img('main_img');
```

同じテーマ内で、未導入の箇所、`TypeCheck`だけを使う箇所、`AcfGet`を使う箇所、専用Dataを使う箇所を併存させられます。単純な箇所を専用DataやViewクラスへ移す必要はありません。

## 何が楽になるか

ACFの`get_field()`は、フィールド設定や未入力状態によって異なる型の値を返します。WpTypeSafetyでは、取得後の確認と正規化を用途別の取得口へ集約できます。

```php
<?php
$raw = get_field('title', $post->ID);
$title = is_string($raw) && trim($raw) !== '' ? $raw : '';
```

```php
<?php
use WpTypeSafety\Acf\Get\AcfGet;

$postGet = new AcfGet($post);
$title = $postGet->Text('title'); // string。未入力・型違いなら ''
$mainImg = $postGet->Img('main_img'); // ?ImgData。取得できなければ null

foreach ($postGet->Repeater('items') as $rowGet) {
	$label = $rowGet->Text('label');
}
```

グループ、リピーター、Flexible Content、投稿・ユーザー・ターム参照でも同じ取得モデルを利用できます。途中の欠損を毎回`isset()`や`is_array()`で確認せず、各地点で戻り値型が固定された値を受け取れます。入れ子での動作、派生`AcfGet`、参照先切り替えの詳しい契約は[ACFの取得API](docs/acf.md)を参照してください。

## 特徴

- `Text(): string`、`Number(): ?float`、`Int(): ?int`、`Img(): ?ImgData`、`IconClass(): ?string`、`IconImg(): ?ImgData`、`IconUrl(): ?string`、`Posts(): list<PostData>`、`Terms(): list<TermData>`、`Users(): list<WpUserData>`、`Repeater(): list<AcfGet>`、`Flexible(): list<FlexibleNode>`など、用途ごとに戻り値型を固定した取得口。
- 未対応・独自ACFフィールド向けの明示的な拡張口`Native(): mixed`。通常のACF戻り値をWpTypeSafetyで変換せず返す。
- `PostData`は投稿状態・投稿タイプ・スラッグ・パスワード保護の有無・WordPressの公開閲覧可否を保持し、パスワード自体は公開しない。
- 既存のWordPress・ACF APIと併用しながら、必要な取得処理から段階的に置き換えられる。Dataは直接`new`でも生成できる。
- WordPress・ACFに依存しないCoreの型確認と、配列・オブジェクトのpublicプロパティからの取得。
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
use WpTypeSafety\Wp\Get\CommentFactory;
use WpTypeSafety\Wp\Get\PostFactory;

$postData = PostFactory::From($post); // PostData
$commentData = CommentFactory::From($comment); // CommentData
```

ACFフィールドは用途に対応する`AcfGet`で正規化できます。ネイティブAPIの結果だけ型確認したい場合や、テンプレートパーツの`$args`を読む場合はCoreを使えます。

```php
<?php
use WpTypeSafety\Core\Check\ArrayGet;
use WpTypeSafety\Acf\Get\AcfGet;
use WpTypeSafety\Core\Check\ObjectGet;
use WpTypeSafety\Core\Check\TypeCheck;

$postGet = new AcfGet($post);
$argsGet = new ArrayGet($args);
$resultGet = new ObjectGet($resultObject);

$count = $postGet->Int('count');                        // ?int
$postObject = TypeCheck::Instance(get_post(123), WP_Post::class); // ?WP_Post
$title = $argsGet->String('title');                      // string
$items = $argsGet->NestedList('items');                  // list<ArrayGet>
$status = $resultGet->String('status');                  // string
```

`AcfGet::Int()`は整数・整数として表現できるfloat・整数形式の数値文字列を`int`へ正規化します。`TypeCheck`は変換を行わず、入力がもともと指定したPHP型である場合だけ値を返します。`ArrayGet`は配列のキー、`ObjectGet`はpublicインスタンスプロパティを同じ戻り値規則で読みます。`ObjectGet`はアクセサーを呼ぶたびに同じオブジェクトの現在値を読み、private・protected・static・未初期化・マジックプロパティは対象にしません。詳しくは[Coreの型確認と配列・オブジェクト取得](docs/core.md)と[利用例](docs/examples.md)を参照してください。空文字だけでは「未入力」と「取得失敗」を区別できません。

## 入口の選び方

| やりたいこと | 主な入口 |
|---|---|
| ACFフィールドを型付きで取得する | `AcfGet` |
| WordPressオブジェクトをDataへ変換する | `PostFactory`・`CommentFactory`・`UserFactory`・`TermFactory`・`MenuNodeFactory`・`AttachmentFactory` |
| ACFブロック情報をDataへ変換する | `AcfDataFactory` |
| 単独の値を型確認する | `TypeCheck` |
| 文字列キーの辞書全体を型確認する | `DictCheck` |
| 配列の値を型付きで取得する | `ArrayGet` |
| オブジェクトのpublicプロパティを型付きで取得する | `ObjectGet` |

## データ境界の保証範囲を選ぶ

次の入口は段階ではなく、同じ案件内で併用できる選択肢です。データの複雑さと、必要な保証範囲に応じて選べます。

| 導入範囲 | 主な入口 | 用途 |
|---|---|---|
| 単独の値を確認 | `TypeCheck` | ネイティブAPIの値を変換せず型確認する |
| 配列・オブジェクト内の値を確認 | `ArrayGet`・`ObjectGet` | `$args`や外部APIの構造を既存コードのまま読む |
| 辞書全体の要素型を確認 | `DictCheck` | 文字列キーと全要素の型が揃った辞書だけを受け入れる |
| ACF値を正規化 | `AcfGet` | 画像、数値、選択、参照、入れ子などを用途別の固定型で取得する |
| WordPress値をData化 | 対象別Factory | WordPressオブジェクトを共通Dataへ変換する |
| 案件固有Dataを構築 | Members・派生Data | 複雑なコンポーネントや案件固有投稿の入力契約を表す |

各構成のコード例は[利用例](docs/examples.md)を参照してください。

## 表示構成を独立して選ぶ

表示方法は、データ境界の保証範囲とは別に選べます。`AcfGet`やDataを利用していても、Viewクラスへ移行する必要はありません。

| 表示方法 | 位置づけ |
|---|---|
| 通常のPHPテンプレート | 正式な利用方法 |
| `get_template_part()` | 正式な利用方法 |
| Dataを`$args`で渡す | 任意 |
| `XxxView::Render()` | 任意 |
| 案件側クラスのPSR-4読み込み | 任意 |

任意PSR-4読み込みの設定とView構成例は[読み込みと任意PSR-4](docs/autoload.md)を参照してください。

## 設計方針と保証範囲

WpTypeSafetyが保証するのは、公開取得口のPHP戻り値型と、欠損・型違い時の戻り値規則です。対応する取得口では、ACF値を用途に合う固定型へ正規化します。

| 項目 | 保証 |
|---|---|
| 公開取得口のPHP戻り値型 | する |
| 型違い・欠損時の戻り値 | する |
| 対応取得口によるACF値の正規化 | する |
| Dataを直接構築した値の意味的妥当性 | しない |
| 指定したフィールド名の存在 | しない |
| HTML出力の安全性 | しない |
| 特定の実環境での結合動作 | 導入先でも確認が必要 |

DataのコンストラクターはPHPの型宣言で表せる型を受け取りますが、URLの形式、IDや寸法の正負などを一律には検証しません。直接Dataを作る場合は、呼び出し元が値を確認してください。画像・ファイルがURLだけで返る設定や、取得済み配列のフィールド定義を確認できない場合など、取得口ごとの制約は詳細文書に記載しています。

HTMLの組み立てと出力時のエスケープは利用側で行います。通常の文字列には`esc_html()`、URLの出力には`esc_url()`など、出力先に合う処理を使ってください。

## WpTypeSafetyが行わないこと

- ACFフィールド名の静的検査。
- 現行版におけるACFフィールド定義からのPHP型生成。
- PHPStan・Psalmなど静的解析ツールの代替。
- HTML出力の自動エスケープ。
- テーマ全体のアーキテクチャ強制。
- すべてのWordPress・ACFバージョンとの結合動作保証。

## 機能ごとの依存関係

ライブラリ全体を配置しても、すべての領域を使う必要はありません。

| 使用領域 | PHP | WordPress | ACF |
|---|---:|---:|---:|
| `Core` | 必須 | 不要 | 不要 |
| `Wp` | 必須 | 必須 | 不要 |
| `Acf` | 必須 | 必須 | 必須 |
| `Support\Esc` | 必須 | 必須 | 不要 |
| 任意PSR-4読み込み | 必須 | 不要 | 不要 |

## 詳細ドキュメント

| 内容 | 文書 | 主な対象 |
|---|---|---|
| ACFの取得口、グループ、リピーター、参照先、ブロック | [ACF](docs/acf.md) | 利用者 |
| WordPressの取得口とFactory | [WordPress](docs/wordpress.md) | 利用者 |
| 型確認と配列・オブジェクトからの取得 | [Core](docs/core.md) | 利用者 |
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
