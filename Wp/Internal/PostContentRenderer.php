<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Internal;

/** @internal */
final class PostContentRenderer
{
	private function __construct() {}

	public static function Render(\WP_Post $post, string $rawContent): string
	{
		$renderPost = clone $post;
		$renderPost->post_content = $rawContent;
		$keys = ['post', 'id', 'authordata', 'currentday', 'currentmonth', 'page', 'pages', 'multipage', 'more', 'numpages', 'preview'];
		$saved = [];
		foreach ($keys as $key) {
			if (array_key_exists($key, $GLOBALS)) {
				$saved[$key] = $GLOBALS[$key];
			}
		}
		try {
			$GLOBALS['post'] = $renderPost;
			if (! \setup_postdata($renderPost)) {
				throw new \LogicException('Rendering post content requires an initialized WordPress query.');
			}
			$content = \get_the_content(null, false, $renderPost);
			$content = \apply_filters('the_content', $content);
			if (! is_string($content)) {
				throw new \UnexpectedValueException('The the_content filter must return a string.');
			}
			return str_replace(']]>', ']]&gt;', $content);
		} finally {
			foreach ($keys as $key) {
				if (array_key_exists($key, $saved)) {
					$GLOBALS[$key] = $saved[$key];
				} else {
					unset($GLOBALS[$key]);
				}
			}
		}
	}
}
