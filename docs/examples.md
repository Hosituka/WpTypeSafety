# 利用例

この文書では、案件固有の名前やフィールドを避けた最小構成の例を紹介します。`autoload.php`は`functions.php`などで読み込み済みであることを前提とします。

三つの構成は優劣や成熟度を表すものではありません。同じテーマ内で併用でき、複雑になった箇所だけDataやViewクラスへ移せます。

## 1. WordPress標準の構成を保つ

ネイティブAPIの結果を`TypeCheck`で確認し、`get_template_part()`へ渡した`$args`を`ArrayGet`で読み取ります。テンプレートパーツの配置や呼び出し方は変更しません。

```php
<?php
use WpTypeSafety\Core\Check\TypeCheck;

$title = TypeCheck::String(get_field('title', $post->ID)) ?? '';

get_template_part(
	'template-parts/post-card',
	null,
	['title' => $title],
);
```

呼び出された`template-parts/post-card.php`では、配列の値をそのまま信用せず、用途に対応する型で取得します。

```php
<?php
use WpTypeSafety\Core\Check\ArrayGet;

$argsGet = new ArrayGet($args);
$title = $argsGet->String('title');
?>
<article>
	<h2><?= esc_html($title) ?></h2>
</article>
```

## 2. 取得と変換をDataへ分離する

取得処理が複雑になった箇所では、`WpDataFactory`と`AcfGet`を使ってテーマ固有Dataを生成します。次の`ArticleData`は、この文書の[PostDataの案件用派生クラス](#postdataの案件用派生クラス)で定義します。

```php
<?php
$article = ArticleData::FromWpPost($post);

get_template_part(
	'template-parts/article-card',
	null,
	['article' => $article],
);
```

テンプレートパーツでは`ArrayGet::Instance()`を使い、期待するDataだけを受け取れます。

```php
<?php
use WpTypeSafety\Core\Check\ArrayGet;

$article = (new ArrayGet($args))->Instance('article', ArticleData::class);
if ($article === null)
	{return;}
?>
<article>
	<h2><?= esc_html($article->Title) ?></h2>
	<p><?= esc_html($article->Subtitle) ?></p>
</article>
```

## 3. Viewの参照もクラスで管理する

テンプレートファイルの文字列パスも管理対象にしたい場合は、任意PSR-4読み込みでViewクラスを読み込み、クラスとメソッドを参照します。

```php
<?php
use Theme\Parts\ArticleCardView;

$article = ArticleData::FromWpPost($post);
ArticleCardView::Render($article);
```

View側では入力型をメソッド引数として宣言できます。

```php
<?php
namespace Theme\Parts;

final class ArticleCardView
{
	private function __construct() {}

	public static function Render(\ArticleData $article): void
	{
		?>
		<article>
			<h2><?= esc_html($article->Title) ?></h2>
			<p><?= esc_html($article->Subtitle) ?></p>
		</article>
		<?php
	}
}
```

PSR-4の登録方法と保証範囲は[読み込みと任意PSR-4](autoload.md)を参照してください。Viewクラス化は必須ではなく、第1・第2段階の`get_template_part()`と併用できます。

## 応用例

### Nestedと入れ子のRepeater

グループは`Nested()`、リピーターは`Repeater()`でたどります。リピーターの各行はすでに`AcfGet`なので、`new AcfGet($row)`で包み直す必要はありません。

```php
<?php
use WpTypeSafety\Acf\Get\AcfGet;

$postGet = new AcfGet($post);
$contentGet = $postGet->Nested('content');
?>
<section>
	<h2><?= esc_html($contentGet->Text('title')) ?></h2>

	<?php foreach ($contentGet->Repeater('sections') as $sectionGet): ?>
		<section>
			<h3><?= esc_html($sectionGet->Text('heading')) ?></h3>

			<ul>
				<?php foreach ($sectionGet->Repeater('items') as $itemGet): ?>
					<li><?= esc_html($itemGet->Text('label')) ?></li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endforeach; ?>
</section>
```

グループやリピーターが未入力・型違いの場合も、`Nested()`は空の取得元、`Repeater()`は空配列を返します。フィールドごとの詳しい契約は[ACFの取得API](acf.md)を参照してください。

### PostDataの案件用派生クラス

共通の投稿情報は`WpDataFactory`で作り、案件固有の値を加えた派生クラスへ渡せます。派生クラスのコンストラクターではWordPressやACFへ問い合わせず、正規化済みの値を受け取ります。

```php
<?php
use WpTypeSafety\Acf\Get\AcfGet;
use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Get\WpDataFactory;

final readonly class ArticleData extends PostData
{
	public function __construct(
		int $PostId,
		string $Url,
		string $Title,
		string $Content,
		public string $Subtitle,
		?\DateTimeImmutable $PublishedAt = null,
		?\DateTimeImmutable $ModifiedAt = null,
		?\Closure $thumbnailResolver = null,
	) {
		parent::__construct(
			PostId: $PostId,
			Url: $Url,
			Title: $Title,
			Content: $Content,
			PublishedAt: $PublishedAt,
			ModifiedAt: $ModifiedAt,
			thumbnailResolver: $thumbnailResolver,
		);
	}

	public static function FromWpPost(\WP_Post $post): self
	{
		$common = WpDataFactory::PostFromWpPost($post);

		return new self(
			PostId: $common->PostId,
			Url: $common->Url,
			Title: $common->Title,
			Content: $common->Content,
			Subtitle: (new AcfGet($post))->Text('subtitle'),
			PublishedAt: $common->PublishedAt,
			ModifiedAt: $common->ModifiedAt,
			thumbnailResolver: $common->GetThumbnail(...),
		);
	}
}

$article = ArticleData::FromWpPost($post);
```

この例では必要な`thumbnailResolver`だけを引き継いでいます。投稿者、抜粋、子投稿なども使う場合は、対応する親コンストラクター引数へResolverを渡します。詳しい継承例は[Dataと派生クラス](data.md)を参照してください。

### ACFブロックの描画

ACFブロックの描画テンプレートへ渡される`$block`と`$is_preview`は、`AcfDataFactory`で`AcfBlockData`へ変換できます。

```php
<?php
use WpTypeSafety\Acf\Get\AcfDataFactory;
use WpTypeSafety\Acf\Get\AcfGet;

$blockData = AcfDataFactory::BlockFromAcf(
	block: $block,
	isPreview: $is_preview,
);
$blockGet = new AcfGet();
$title = $blockGet->Text('title');
$wrapperAttributes = $blockData->GetWrapperAttributes('c-notice');
?>
<section <?= $wrapperAttributes ?>>
	<?php if ($title !== ''): ?>
		<h2><?= esc_html($title) ?></h2>
	<?php endif; ?>
</section>
```

`GetWrapperAttributes()`は、Factoryが注入した処理からエスケープ済みの属性文字列を返すため、そのまま属性位置へ出力します。`AcfBlockData`を直接`new`してResolverを渡していない場合、このメソッドは`LogicException`を投げます。プレビュー時の扱いなどは[ACFブロックの描画情報](acf.md#acfブロックの描画情報)を参照してください。
