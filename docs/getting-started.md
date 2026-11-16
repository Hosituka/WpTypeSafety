# はじめてのWpTypeSafety

この文書は、まだ使うAPIを決めていない人向けの導入ガイドです。最初から全機能を覚える必要はありません。いま値を取得している場所に合う入口を1つだけ選び、必要になった時点でほかの入口を追加します。

## 最初に知っておくこと

WpTypeSafetyは、WordPressやACFが返す値を取得する場所で型を確認し、以降のコードで扱いやすい形へそろえるライブラリです。

たとえばACFのテキストフィールドは、未入力や想定外の型だった場合も`Text()`なら必ず`string`を返します。

```php
<?php
use WpTypeSafety\Acf\Get\AcfGet;

$postGet = new AcfGet($post);
$heading = $postGet->Text('heading');
```

この例では、`$heading`は常に`string`です。テンプレート側で毎回`is_string()`を書く必要がなくなります。

WpTypeSafetyが行うのは、取得時の型確認と正規化です。HTMLへ出力するときのエスケープは引き続き必要です。

```php
<h1><?= esc_html($heading) ?></h1>
```

## どこから始めるか

まず、型をそろえたい値がどこから来たかを選びます。

| いま扱っているもの | 最初に使う入口 | 典型的なコード |
|---|---|---|
| ACFのフィールド | `AcfGet` | `$postGet->Text('heading')` |
| 投稿などのmeta | `MetaGet` | `$metaGet->Int('view_count')` |
| `$args`や任意のPHP配列 | `ArrayGet` | `$argsGet->String('heading')` |
| `WP_Post`などのWordPressオブジェクト | 対象別`Get` | `PostGet::From($post)` |
| WordPressの検索結果 | 対象別`Query` | `PostQuery::Get($args)` |

迷った場合は、テーマでACFを使っているなら`AcfGet`、使っていないなら現在の`get_post_meta()`を`MetaGet`へ置き換えるところから始めるのがおすすめです。`Get`や`Query`は、取得した投稿全体を同じデータ形式で扱いたくなってから導入できます。

## 1. インストールする

PHP 8.2以上が必要です。

Composerを使用する場合は、テーマまたはプロジェクトで次を実行します。

```bash
composer require hosituka/wp-type-safety
```

プロジェクトですでに`vendor/autoload.php`を読み込んでいれば、追加設定は不要です。

Composerを使用しない場合は、リポジトリ一式を配置し、ルートの`autoload.php`を`functions.php`などから一度だけ読み込みます。

```php
<?php
require_once __DIR__ . '/WpTypeSafety/autoload.php';
```

配置方法の詳細は[Autoload](autoload.md)を参照してください。

## 2. ひとつのテンプレートで試す

### ACFフィールドを読む

現在の投稿から、テキスト、画像、関連投稿を取得する例です。

```php
<?php
use WpTypeSafety\Acf\Get\AcfGet;

$post = get_post();
if (! $post instanceof WP_Post)
{return;}

$postGet = new AcfGet($post);
$heading = $postGet->Text('heading');
$mainImg = $postGet->Img('main_img');
$relatedPosts = $postGet->Posts('related_posts');
?>

<h1><?= esc_html($heading) ?></h1>

<?php if ($mainImg !== null): ?>
	<img
		src="<?= esc_url($mainImg->Url) ?>"
		width="<?= esc_attr($mainImg->Width) ?>"
		height="<?= esc_attr($mainImg->Height) ?>"
		alt="<?= esc_attr($mainImg->Alt) ?>"
	>
<?php endif; ?>

<?php foreach ($relatedPosts as $relatedPost): ?>
	<a href="<?= esc_url($relatedPost->Url) ?>">
		<?= esc_html($relatedPost->Title) ?>
	</a>
<?php endforeach; ?>
```

最初に覚える戻り値規則は次の3つです。

| 取得形式 | 例 | 取得できない場合 |
|---|---|---|
| 非nullable値 | `Text()` | 空文字などの既定値 |
| 単一のData | `Img()` | `null` |
| 複数取得 | `Posts()` | 空配列 |

Group、Repeater、Flexible Content、選択フィールドを使う場合は[ACFの取得API](acf.md)へ進んでください。

### WordPress metaを読む

`get_post_meta()`で取得していた値には`MetaGet`を使います。

```php
<?php
use WpTypeSafety\Wp\Get\MetaGet;

$postMetaGet = new MetaGet($post);
$subtitle = $postMetaGet->String('subtitle');
$viewCount = $postMetaGet->Int('view_count');
$hasSubtitle = $postMetaGet->Exists('subtitle');
```

WordPressは数値を文字列として保存することがあります。`MetaGet`はその保存形式を考慮して`Int()`などへ正規化します。詳しくは[WordPressの取得API](wordpress.md#wordpressメタを型付きで取得する)を参照してください。

### テンプレート引数やPHP配列を読む

`get_template_part()`へ渡した`$args`など、通常のPHP配列には`ArrayGet`を使います。WordPressやACFは不要です。

```php
<?php
use WpTypeSafety\Core\Check\ArrayGet;

$argsGet = new ArrayGet($args);
$heading = $argsGet->String('heading');
$itemIds = $argsGet->IntList('item_ids');
```

値そのものを1回だけ確認したい場合は`TypeCheck`、配列の複数項目を読む場合は`ArrayGet`が入口です。厳密な変換規則や入れ子配列は[Core](core.md)を参照してください。

## 3. 必要になったらDataを使う

フィールド単位ではなく、投稿のタイトル、URL、日時、サムネイルなどをひとまとまりで扱いたい場合は`PostGet`を使います。

```php
<?php
use WpTypeSafety\Wp\Get\PostGet;

$postData = PostGet::From($post);
if ($postData === null)
{return;}

echo esc_html($postData->Title);
echo esc_url($postData->Url);
```

投稿一覧を取得する処理では`PostQuery`を使うと、クエリ結果が最初から`PostData`のリストになります。

```php
<?php
use WpTypeSafety\Wp\Get\PostQuery;

$result = PostQuery::Get([
	'post_type' => 'post',
	'posts_per_page' => 10,
]);

foreach ($result->Posts as $postData) {
	echo esc_html($postData->Title);
}
```

ユーザー、ターム、コメントにも対応する`Get`と`Query`があります。ページング情報や派生Dataが必要になったら[WordPressの取得API](wordpress.md)と[Data](data.md)を参照してください。

## よくある迷いどころ

### 似たAPIを使い分ける

| 状況 | 使用するAPI | 戻り値・特徴 |
|---|---|---|
| ACFフィールドの用途が分かる | `AcfGet` | 画像、リンク、選択肢、関連投稿などを用途別に変換 |
| 取得済みの任意配列やテンプレート引数を読む | `ArrayGet` | ACF固有の解釈は行わない |
| テンプレートで投稿のタイトルやURLを使う | `Post()` | `PostData`へ変換 |
| WordPressやプラグインの関数へ投稿を渡す | `WpPost()` | `WP_Post`へ変換 |
| ACFの戻り値を変換せず取得する | `Native()` | ACFネイティブ値をそのまま返す |

### 空文字や`null`になった理由を知りたい

型付き取得口は、欠損と型違いを安全な既定値へまとめることがあります。保存の有無を区別する必要があるmetaでは`Exists()`を併用してください。入力不備をエラーとして扱う項目は、案件固有の検証も追加します。

### エスケープは必要か

必要です。WpTypeSafetyは値の型をそろえますが、HTMLの出力文脈までは決めません。

| 出力先 | WordPress関数 |
|---|---|
| 通常のテキスト | `esc_html()` |
| URL | `esc_url()` |
| HTML属性 | `esc_attr()` |

## 次に読む文書

最初のコードが動いたら、いま必要な文書だけを選んでください。

| やりたいこと | 文書 |
|---|---|
| ACFのGroup、Repeater、画像、選択肢、参照先を扱う | [ACF](acf.md) |
| 投稿、meta、Query、メニュー、タームを扱う | [WordPress](wordpress.md) |
| PHP配列の入れ子や変換規則を扱う | [Core](core.md) |
| Dataを継承して案件固有の型を作る | [Data](data.md) |
| 実際の組み合わせ例を見る | [利用例](examples.md) |

内部構造やすべての高度なAPIを先に読む必要はありません。取得元に合う入口と、その戻り値の規則が分かれば導入できます。
