<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Internal;

use WpTypeSafety\Core\Check\ValueMode;
use WpTypeSafety\Core\Check\ValueRules;

/** @internal WordPressクエリのページ番号と件数を正規化します。 */
final class QueryPagination
{
	private function __construct() {}

	public static function PageNumber(mixed $value): int
	{
		return self::PositiveInt($value) ?? 1;
	}

	public static function PositiveInt(mixed $value): ?int
	{
		$number = self::numericRules()->NormalizeInt($value);
		return $number !== null && $number > 0 ? $number : null;
	}

	public static function MaxPageCount(int $totalCount, mixed $number): int
	{
		$limit = self::PositiveInt($number);
		if ($limit === null)
		{return $totalCount > 0 ? 1 : 0;}

		return (int) ceil($totalCount / $limit);
	}

	private static function numericRules(): ValueRules
	{
		static $rules;
		return $rules ??= ValueRules::FromMode(ValueMode::NumericStr);
	}
}
