<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Internal;

use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\PostQueryData;

/** @internal */
final class MainQueryAdapter
{
	private function __construct() {}

	/**
	 * @template TPost of PostData
	 * @param null|\Closure(\WP_Post): TPost $postFactory
	 * @return ($postFactory is null ? PostQueryData<PostData> : PostQueryData<TPost>)
	 */
	public static function Current(?\Closure $postFactory = null): PostQueryData
	{
		$result = PostQueryAdapter::From(self::mainQuery(), $postFactory);

		return new PostQueryData(
			Posts: $result->Posts,
			TotalCount: $result->TotalCount,
			MaxPageCount: $result->MaxPageCount,
			CurrentPageNumber: self::CurrentPageNumber(),
		);
	}

	/** メインクエリ未設定時はnull。取得対象の種類は呼び出し元で確認する。 */
	public static function QueriedObject(): ?object
	{
		global $wp_the_query;

		return $wp_the_query instanceof \WP_Query ? $wp_the_query->get_queried_object() : null;
	}

	/** 検索・ターム・投稿一覧ページ等から投稿タイプを推測しない。 */
	public static function PostTypeName(): ?string
	{
		global $wp_the_query;

		if (! $wp_the_query instanceof \WP_Query)
		{return null;}

		if (! $wp_the_query->is_singular() && ! $wp_the_query->is_post_type_archive())
		{return null;}

		$object = $wp_the_query->get_queried_object();
		if ($wp_the_query->is_singular() && $object instanceof \WP_Post)
		{return $object->post_type;}

		if ($wp_the_query->is_post_type_archive() && $object instanceof \WP_Post_Type)
		{return $object->name;}

		return null;
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
