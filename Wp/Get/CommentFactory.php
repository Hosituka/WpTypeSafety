<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Data\CommentData;
use WpTypeSafety\Wp\Data\CommentMembers;
use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\WpUserData;
use WpTypeSafety\Wp\Internal\DtoFactory;

/** WP_CommentからコメントのMembersまたはDataを生成します。 */
final class CommentFactory
{
	private function __construct() {}

	/**
	 * @param null|\Closure(\WP_Comment): ?CommentData $relatedCommentFactory
	 * @param null|\Closure(\WP_User): ?WpUserData $userFactory
	 * @param null|\Closure(\WP_Post): ?PostData $postFactory
	 */
	public static function Members(
		\WP_Comment $comment,
		int $avatarSize = 96,
		?\Closure $relatedCommentFactory = null,
		?\Closure $userFactory = null,
		?\Closure $postFactory = null,
	): CommentMembers
	{
		return DtoFactory::CommentMembersFromWpComment(
			$comment,
			$avatarSize,
			$relatedCommentFactory,
			$userFactory,
			$postFactory,
		);
	}

	/**
	 * @param null|\Closure(\WP_Comment): ?CommentData $relatedCommentFactory
	 * @param null|\Closure(\WP_User): ?WpUserData $userFactory
	 * @param null|\Closure(\WP_Post): ?PostData $postFactory
	 */
	public static function From(
		\WP_Comment $comment,
		int $avatarSize = 96,
		?\Closure $relatedCommentFactory = null,
		?\Closure $userFactory = null,
		?\Closure $postFactory = null,
	): CommentData
	{
		return DtoFactory::CommentFromWpComment(
			$comment,
			$avatarSize,
			$relatedCommentFactory,
			$userFactory,
			$postFactory,
		);
	}
}
