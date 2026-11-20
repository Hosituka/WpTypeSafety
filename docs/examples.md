# 利用例

この文書は複数領域を組み合わせる代表例だけを扱います。メソッドごとの契約は[ACF](acf.md)、[WordPress](wordpress.md)、[Data](data.md)、[Core](core.md)を正本とします。

## 通常のテンプレートで使う

テーマ構成や`get_template_part()`を変更する必要はありません。

```php
use WpTypeSafety\Acf\Get\AcfGet;

$get = new AcfGet($post);
$title = $get->Text('title');
$image = $get->Img('main_image');
?>
<article>
	<h2><?= esc_html($title) ?></h2>
	<?php if ($image !== null): ?>
		<img
			src="<?= esc_url($image->Url) ?>"
			alt="<?= esc_attr($image->Alt) ?>"
		>
	<?php endif; ?>
</article>
```

WpTypeSafetyは取得値をHTML向けに自動エスケープしません。

## テンプレート引数を読む

```php
// 呼び出し側
get_template_part(
	'template-parts/card',
	null,
	['title' => $title, 'post' => $postData],
);
```

```php
// template-parts/card.php
use WpTypeSafety\Core\Check\ArrayGet;
use WpTypeSafety\Wp\Data\PostData;

$argsGet = new ArrayGet($args ?? []);
$title = $argsGet->String('title');
$post = $argsGet->Instance('post', PostData::class);

if ($post === null) {
	return;
}
```

`ArrayGet`の詳しい契約は[Core](core.md)を参照してください。

## GroupとRepeaterを読む

```php
use WpTypeSafety\Acf\Get\AcfGet;

$get = new AcfGet($post);
$content = $get->Nested('content');
?>
<section>
	<h2><?= esc_html($content->Text('title')) ?></h2>

	<?php foreach ($content->Repeater('sections') as $section): ?>
		<section>
			<h3><?= esc_html($section->Text('heading')) ?></h3>

			<?php foreach ($section->Repeater('items') as $item): ?>
				<p><?= esc_html($item->Text('label')) ?></p>
			<?php endforeach; ?>
		</section>
	<?php endforeach; ?>
</section>
```

各行は取得元とACFフィールド定義を引き継いだ`AcfGet`です。包み直す必要はありません。詳しくは[ACFのGroup・Repeater](acf.md#リピーター行の取得)を参照してください。

## 案件固有Dataを作る

共通の投稿情報はprotected staticの`getProps()`で調整し、追加値は`initialize()`で設定します。Fromとコンストラクターはfinalです。他のDataの構築契約は変更しません。完全な継承契約は[Dataと派生クラス](data.md)を参照してください。

```php
use WpTypeSafety\Acf\Get\AcfGet;
use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\PostProps;
use WpTypeSafety\Wp\Get\PostGet;

/** @extends PostData<ArticleData, \WpTypeSafety\Wp\Data\WpUserData> */
final class ArticleData extends PostData
{
	public readonly string $Subtitle;

	protected static function getProps(\WP_Post $post): PostProps
	{
		$props = parent::getProps($post);
		$props->Title = '記事：' . $props->Title;
		return $props;
	}

	protected function createRelatedPost(\WP_Post $post): ?self
	{
		return self::From($post);
	}

	protected function initialize(\WP_Post $post): void
	{
		parent::initialize($post);
		$postGet = new AcfGet($post);
		$this->Subtitle = $postGet->Text('subtitle');
	}
}

$article = ArticleData::From($post);
$loopArticle = PostGet::Loop(dataClass: ArticleData::class);
```

## ACFブロックを描画する

```php
use WpTypeSafety\Acf\Get\AcfDataFactory;
use WpTypeSafety\Acf\Get\AcfGet;

$blockData = AcfDataFactory::BlockFromAcf($block, $is_preview);
$acfGet = new AcfGet();
$title = $acfGet->Text('title');
?>
<section <?= $blockData->GetWrapperAttributes('c-notice') ?>>
	<h2><?= esc_html($title) ?></h2>
</section>
```

`GetWrapperAttributes()`の出力契約とプレビュー時の扱いは[ACFブロックの描画情報](acf.md#acfブロックの描画情報)を参照してください。
