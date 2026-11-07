<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Internal;

/** @internal */
final class PageAdapter
{
	private function __construct() {}

	public static function CurrentPageNumber(): int
	{
		return max(1, (int) \get_query_var('paged'));
	}
}
