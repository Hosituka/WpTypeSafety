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
	 * @template TComment of CommentData = CommentData
	 * @param array<string, mixed> $args
	 * @param class-string<TComment> $dataClass
	 * @return CommentQueryData<TComment>
	 */
	public static function Get(
		array $args = [],
		int $avatarSize = 96,
		string $dataClass = CommentData::class,
	): CommentQueryData
	{
		return CommentQueryAdapter::Get($args, $avatarSize, $dataClass);
	}

	/**
	 * @template TComment of CommentData = CommentData
	 * @param class-string<TComment> $dataClass
	 * @return CommentQueryData<TComment>
	 */
	public static function From(
		\WP_Comment_Query $query,
		int $avatarSize = 96,
		string $dataClass = CommentData::class,
	): CommentQueryData
	{
		return CommentQueryAdapter::From($query, $avatarSize, $dataClass);
	}
}
