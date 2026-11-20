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
	 * @template TPost of PostData = PostData
	 * @param array<string, mixed> $args
	 * @param class-string<TPost> $dataClass
	 * @return PostQueryData<TPost>
	 */
	public static function Get(array $args = [], string $dataClass = PostData::class): PostQueryData
	{
		return PostQueryAdapter::Get($args, $dataClass);
	}

	/**
	 * @template TPost of PostData = PostData
	 * @param class-string<TPost> $dataClass
	 * @return PostQueryData<TPost>
	 */
	public static function From(\WP_Query $query, string $dataClass = PostData::class): PostQueryData
	{
		return PostQueryAdapter::From($query, $dataClass);
	}
}
