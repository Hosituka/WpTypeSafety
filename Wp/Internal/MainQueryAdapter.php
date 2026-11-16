<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Internal;

/** @internal */
final class MainQueryAdapter
{
	private function __construct() {}

	/** メインクエリ未設定時はnull。取得対象の種類は呼び出し元で確認する。 */
	public static function QueriedObject(): ?object
	{
		global $wp_the_query;

		return $wp_the_query instanceof \WP_Query ? $wp_the_query->get_queried_object() : null;
	}

	public static function CurrentPageNumber(): int
	{
		$query = self::mainQuery();
		$key = $query->is_front_page() && $query->is_page() ? 'page' : 'paged';
		return QueryPagination::PageNumber($query->get($key));
	}

	public static function MaxPageCount(): int
	{
		return max(0, (int) self::mainQuery()->max_num_pages);
	}

	public static function FoundPostCount(): int
	{
		return max(0, (int) self::mainQuery()->found_posts);
	}

	private static function mainQuery(): \WP_Query
	{
		global $wp_the_query;

		if (! $wp_the_query instanceof \WP_Query)
		{throw new \LogicException('MainQueryGetはメインクエリ確定後に使用してください。');}

		return $wp_the_query;
	}
}
