<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Data\CommentData;
use WpTypeSafety\Wp\Data\CommentQueryData;
use WpTypeSafety\Wp\Internal\CommentQueryAdapter;

/** WP_Comment_Queryを実行または受け取り、コメントDataへ変換します。 */
final class CommentQuery
{
	private function __construct() {}

	/**
	 * @template TComment of CommentData
	 * @param array<string, mixed> $args
	 * @param null|\Closure(\WP_Comment, int): TComment $commentFactory
	 * @return ($commentFactory is null ? CommentQueryData<CommentData> : CommentQueryData<TComment>)
	 */
	public static function Get(
		array $args = [],
		int $avatarSize = 96,
		?\Closure $commentFactory = null,
	): CommentQueryData
	{
		return CommentQueryAdapter::Get($args, $avatarSize, $commentFactory);
	}

	/**
	 * @template TComment of CommentData
	 * @param null|\Closure(\WP_Comment, int): TComment $commentFactory
	 * @return ($commentFactory is null ? CommentQueryData<CommentData> : CommentQueryData<TComment>)
	 */
	public static function From(
		\WP_Comment_Query $query,
		int $avatarSize = 96,
		?\Closure $commentFactory = null,
	): CommentQueryData
	{
		return CommentQueryAdapter::From($query, $avatarSize, $commentFactory);
	}
}
