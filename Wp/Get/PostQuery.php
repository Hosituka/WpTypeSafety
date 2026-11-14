<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\PostQueryData;
use WpTypeSafety\Wp\Internal\PostQueryAdapter;

/** WP_Queryを実行または受け取り、投稿Dataへ変換します。 */
final class PostQuery
{
	private function __construct() {}

	/**
	 * @template TPost of PostData
	 * @param array<string, mixed> $args
	 * @param null|\Closure(\WP_Post): TPost $postFactory
	 * @return ($postFactory is null ? PostQueryData<PostData> : PostQueryData<TPost>)
	 */
	public static function Get(array $args = [], ?\Closure $postFactory = null): PostQueryData
	{
		return PostQueryAdapter::Get($args, $postFactory);
	}

	/**
	 * @template TPost of PostData
	 * @param null|\Closure(\WP_Post): TPost $postFactory
	 * @return ($postFactory is null ? PostQueryData<PostData> : PostQueryData<TPost>)
	 */
	public static function From(\WP_Query $query, ?\Closure $postFactory = null): PostQueryData
	{
		return PostQueryAdapter::From($query, $postFactory);
	}
}
