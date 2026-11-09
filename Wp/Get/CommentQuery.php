<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Data\CommentQueryData;
use WpTypeSafety\Wp\Internal\CommentQueryAdapter;

/** WP_Comment_Queryを実行または受け取り、コメントDataへ変換します。 */
final class CommentQuery
{
	private function __construct() {}

	/** @param array<string, mixed> $args */
	public static function Get(array $args = [], int $avatarSize = 96): CommentQueryData
	{
		return CommentQueryAdapter::Get($args, $avatarSize);
	}

	public static function From(\WP_Comment_Query $query, int $avatarSize = 96): CommentQueryData
	{
		return CommentQueryAdapter::From($query, $avatarSize);
	}
}
