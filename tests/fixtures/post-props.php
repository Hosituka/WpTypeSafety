<?php
declare(strict_types=1);

// Test-only fixtures enter through the real final From/constructor/initialize pipeline.
// No production constructor bypass or public Props API is required.
use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\PostProps;

if (! class_exists('WP_Post')) {
	class WP_Post {}
}

trait TestPostProps
{
	public static function FromProps(PostProps $props): static
	{
		$post = (new ReflectionClass(WP_Post::class))->newInstanceWithoutConstructor();
		$GLOBALS['testPostProps'] ??= new WeakMap();
		$GLOBALS['testPostProps'][$post] = $props;
		try {
			return static::From($post);
		} finally {
			unset($GLOBALS['testPostProps'][$post]);
		}
	}

	protected static function getProps(WP_Post $post): PostProps
	{
		return $GLOBALS['testPostProps'][$post] ?? parent::getProps($post);
	}
}

class TestPostData extends PostData
{
	use TestPostProps;
}
