<?php

declare(strict_types=1);

namespace WpTypeSafety\Support;

final class EnqueueAssets
{
	/** 投稿編集・サイトエディター用のアセット読み込み処理を登録する。 */
	public static function OnBlockEditor(callable $enqueue): void
	{
		\add_action('enqueue_block_editor_assets', static function () use ($enqueue): void {
			$screen = \get_current_screen();
			if (
				! $screen ||
				! $screen->is_block_editor() ||
				! in_array($screen->base, ['post', 'site-editor'], true)
			) {
				return;
			}

			$enqueue();
		});
	}
}
