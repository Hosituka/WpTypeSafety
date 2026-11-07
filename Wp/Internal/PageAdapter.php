<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Internal;

use WpTypeSafety\Wp\Get\WpGet;

/** @internal */
final class PageAdapter
{
	private function __construct() {}

	public static function Register(): void
	{
		WpGet::Register(self::currentPageNumber(...));
	}

	private static function currentPageNumber(): int
	{
		return max(1, (int) \get_query_var('paged'));
	}
}
