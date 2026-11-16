<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Data\CommentData;
use WpTypeSafety\Wp\Data\CommentMembers;
use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\WpUserData;
use WpTypeSafety\Wp\Internal\DtoFactory;

/** WP_CommentからコメントのMembersまたはDataを生成します。 */
final class CommentGet
{
	private function __construct() {}

	/**
	 * @template TRelatedComment of CommentData = CommentData
	 * @template TUser of WpUserData = WpUserData
	 * @template TPost of PostData = PostData
	 * @param null|\Closure(\WP_Comment): (TRelatedComment|null) $relatedCommentFactory
	 * @param null|\Closure(\WP_User): (TUser|null) $userFactory
	 * @param null|\Closure(\WP_Post): (TPost|null) $postFactory
	 * @return CommentMembers<TRelatedComment, TUser, TPost>
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
	 * @template TRelatedComment of CommentData = CommentData
	 * @template TUser of WpUserData = WpUserData
	 * @template TPost of PostData = PostData
	 * @param null|\Closure(\WP_Comment): (TRelatedComment|null) $relatedCommentFactory
	 * @param null|\Closure(\WP_User): (TUser|null) $userFactory
	 * @param null|\Closure(\WP_Post): (TPost|null) $postFactory
	 * @return CommentData<TRelatedComment, TUser, TPost>
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
