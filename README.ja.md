# WpTypeSafety

[![Latest Stable Version](https://img.shields.io/packagist/v/hosituka/wp-type-safety.svg)](https://packagist.org/packages/hosituka/wp-type-safety)
[![PHP Version](https://img.shields.io/packagist/dependency-v/hosituka/wp-type-safety/php.svg)](https://packagist.org/packages/hosituka/wp-type-safety)
[![License](https://img.shields.io/packagist/l/hosituka/wp-type-safety.svg)](https://packagist.org/packages/hosituka/wp-type-safety)

[English](README.md) | **日本語**

WordPress・ACFから返る不定形な値を、用途が分かる予測可能な戻り値で取得するPHPライブラリです。WordPressに依存しないCoreは、ACFのネイティブ値、テンプレート引数、その他のPHP配列をデータ境界で読む用途にも利用できます。

```php
<?php
use WpTypeSafety\Acf\Get\AcfGet;

$postGet = new AcfGet($post);

$title = $postGet->Text('title');       // string
$mainImg = $postGet->Img('main_img');   // ?ImgData
$author = $postGet->User('author');     // ?WpUserData
```

欠損や型違いの確認をテンプレートごとに書かず、取得口へ集約します。テーマ全体を作り直す必要はなく、`TypeCheck`を1回使うところから、ACF、WordPress meta、Data、Queryまで必要な範囲だけ導入できます。

WpTypeSafetyはフレームワークではありません。通常のPHPテンプレート、WordPressのテンプレート階層、`get_template_part()`を維持したまま、データ取得境界として利用できます。

## 何が変わるか

```php
<?php
$raw = get_field('title', $post->ID);
$title = is_string($raw) && trim($raw) !== '' ? $raw : '';
```

```php
<?php
use WpTypeSafety\Acf\Get\AcfGet;

$postGet = new AcfGet($post);
$title = $postGet->Text('title'); // 未入力・型違いなら''
```

保証するのは、公開取得口のPHP戻り値型と、欠損・型違い時の戻り値規則です。ACFフィールド定義や文字列で指定したフィールド名そのものを静的検査するものではありません。

公開メソッドと公開プロパティは、ライブラリ全体でUpperCamelCaseに統一しています。

## インストール

PHP 8.2以上が必要です。

### Composerを使用する

```bash
composer require hosituka/wp-type-safety
```

プロジェクトの起動処理でComposerのAutoloaderを読み込みます。すでに読み込んでいる場合、追加の処理は不要です。

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';
```

### Composerを使用しない

リポジトリ一式をテーマ等へ配置し、`autoload.php`を一度読み込みます。次の例はテーマ直下の`WpTypeSafety/`へ配置した場合です。

```php
<?php
// functions.php
require_once __DIR__ . '/WpTypeSafety/autoload.php';
```

Composerコマンドや`vendor/`は不要です。詳しくは[Autoload](docs/autoload.md)を参照してください。

## クイックスタート

ACFフィールドを読む最小例です。取得元ごとの入口の選び方と、テンプレートでの出力例は[はじめてのWpTypeSafety](docs/getting-started.md)にまとめています。

```php
<?php
use WpTypeSafety\Acf\Get\AcfGet;

$post = get_post();
if (! $post instanceof WP_Post)
{return;}

$postGet = new AcfGet($post);
$title = $postGet->Text('title');
$mainImg = $postGet->Img('main_img');
```

`Text()`は必ず`string`、`Img()`は`?ImgData`を返します。欠損・型違い時の規則はメソッドごとに固定です。

HTMLへ出力するときは、これまでどおり`esc_html()`・`esc_url()`等を出力先に合わせて使用してください。WpTypeSafetyはHTMLを自動エスケープしません。

Core、WordPress meta、対象別Get、Queryの入口も利用できます。[導入ガイド](docs/getting-started.md#どこから始めるか)で取得元に合うものを選んでください。

現在の表示対象は`PostGet::Current()`・`TermGet::Current()`、ループ中の投稿は`PostGet::Loop()`、ログインユーザーは`UserGet::LoggedIn()`からDataとして取得できます。[コンテキストからの取得](docs/wordpress.md#現在の表示対象ループ投稿ログインユーザー)も参照してください。

投稿タイプ・タクソノミーの登録定義は、`PostTypeGet`・`TaxonomyGet`から取得できます。[定義情報の取得API](docs/wordpress.md#投稿タイプタクソノミーの登録定義)を参照してください。

## WpTypeSafety DataとWordPressネイティブ型を選ぶ

ACFやmetaの参照値は、用途に応じてWpTypeSafetyのDataまたはWordPressネイティブ型として取得できます。

```php
$related = $postGet->Post('related_post');       // ?PostData
$wpRelated = $postGet->WpPost('related_post');   // ?WP_Post
$custom = $postGet->Native('custom_field');      // mixed
```

- `Post()`：`PostData`へ正規化
- `WpPost()`：`WP_Post`へ正規化
- `Native()`：WpTypeSafetyで変換していない値を返す

## 値条件

正規化後の値へ追加条件が必要な場合は、Coreと`MetaGet`の対応Getterで`predicate`を使用できます。

```php
$positiveCount = $metaGet->Int(
	'view_count',
	predicate: static fn(int $value): bool => $value > 0,
);
```

Coreは厳密な型確認を既定値とします。`NumericMode`・`BoolMode`・`ValueRules`・`Transform()`・オブジェクトや混在ツリーの探索・取得口の継承は、[Core](docs/core.md)と[TreeTypeReaderの詳細](Internal/TreeTypeReader/README.md)にまとめています。

## 代表的な戻り値規則

| 形式 | 例 | 欠損・型違い |
|---|---|---|
| 非nullable値 | `AcfGet::Text(): string`、`ArrayGet::String(): string` | `''` |
| nullable値 | `Int(): ?int` | `null` |
| リスト | `Posts(): list<PostData>` | `[]` |
| Data | `Post(): ?PostData` | `null` |
| WordPressネイティブ型 | `WpPost(): ?WP_Post` | `null` |
| 未変換値 | `Native(): mixed` | 各APIの契約を参照 |

空文字・空配列・`null`だけでは、未入力と不正型を区別できない場合があります。欠損を障害として扱う値は、`Exists()`、案件固有の検証、PHPStan・Psalm等を併用してください。

## 要件と検証状況

- PHP 8.2以上
- `Wp`にはWordPressが必要
- `Acf`にはWordPressとACFが必要
- 国際化ドメイン名のURL検証にはPHPの`intl`拡張を推奨

実案件で継続的に利用し、次の環境で安定動作を確認しています。

- WordPress 7.0.8
- Advanced Custom Fields PRO 6.8.6

これは利用実績であり、対応バージョンの上下限を保証するものではありません。自動テストはWordPress・ACFのAPIスタブを使用しており、実環境での結合テストとは区別してください。

## 機能ごとの依存関係

| 使用領域 | PHP | WordPress | ACF |
|---|---:|---:|---:|
| `Core` | 必須 | 不要 | 不要 |
| `Wp` | 必須 | 必須 | 不要 |
| `Acf` | 必須 | 必須 | 必須 |
| 任意PSR-4読み込み | 必須 | 不要 | 不要 |

## 詳細ドキュメント

| 内容 | 文書 |
|---|---|
| 初めての導入、入口の選び方、最小構成の実例 | [はじめてのWpTypeSafety](docs/getting-started.md) |
| ACFの取得口、グループ、リピーター、参照先、ブロック | [ACF](docs/acf.md) |
| WordPressの取得口、meta、対象別Get、Query | [WordPress](docs/wordpress.md) |
| 型確認、値正規化、配列・オブジェクト・混在ツリー | [Core](docs/core.md) |
| 汎用型付きツリーエンジンと拡張契約 | [TreeTypeReader](Internal/TreeTypeReader/README.md) |
| Data・Props・派生クラス | [Data](docs/data.md) |
| `autoload.php`と任意PSR-4読み込み | [Autoload](docs/autoload.md) |
| 入れ子のACF、派生Data、ACFブロックの実装例 | [利用例](docs/examples.md) |
| 内部構造、テスト、検証手順 | [Architecture](docs/architecture.md) |

## WpTypeSafetyが行わないこと

- ACFフィールド名の静的検査
- 現行版におけるACFフィールド定義からのPHP型生成
- PHPStan・Psalmなど静的解析ツールの代替
- HTML出力の自動エスケープ
- テーマ全体のアーキテクチャ強制
- Dataへ直接渡した値の意味的妥当性の一律保証
- すべてのWordPress・ACFバージョンとの結合動作保証

## ライセンス

WpTypeSafety本体は[MIT License](LICENSE)です。同梱したComposerの`ClassLoader.php`は[別のライセンス表記](Autoload/Internal/Composer/LICENSE)を保持しています。
