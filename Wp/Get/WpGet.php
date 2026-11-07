<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Internal\PageAdapter;

/** WordPressのページ番号を取得します。 */
final class WpGet
{
	private function __construct() {}

	public static function CurrentPageNumber(): int
	{
		return PageAdapter::CurrentPageNumber();
	}
}
