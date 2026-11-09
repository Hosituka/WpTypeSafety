<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Data\PostQueryData;
use WpTypeSafety\Wp\Internal\PostQueryAdapter;

/** WP_Queryを実行または受け取り、投稿Dataへ変換します。 */
final class PostQuery
{
	private function __construct() {}

	/** @param array<string, mixed> $args */
	public static function Get(array $args = []): PostQueryData
	{
		return PostQueryAdapter::Get($args);
	}

	public static function From(\WP_Query $query): PostQueryData
	{
		return PostQueryAdapter::From($query);
	}
}
