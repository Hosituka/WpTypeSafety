<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\PostMembers;
use WpTypeSafety\Wp\Data\WpUserData;
use WpTypeSafety\Wp\Internal\DtoFactory;

/** WP_Postから投稿のMembersまたはDataを生成します。 */
final class PostFactory
{
	private function __construct() {}

	/**
	 * @param null|\Closure(\WP_Post): ?PostData $relatedPostFactory
	 * @param null|\Closure(\WP_User): ?WpUserData $authorFactory
	 */
	public static function Members(
		\WP_Post $post,
		?\Closure $relatedPostFactory = null,
		?\Closure $authorFactory = null,
	): PostMembers
	{
		return DtoFactory::PostMembersFromWpPost($post, $relatedPostFactory, $authorFactory);
	}

	/**
	 * @param null|\Closure(\WP_Post): ?PostData $relatedPostFactory
	 * @param null|\Closure(\WP_User): ?WpUserData $authorFactory
	 */
	public static function From(
		\WP_Post $post,
		?\Closure $relatedPostFactory = null,
		?\Closure $authorFactory = null,
	): PostData
	{
		return DtoFactory::PostFromWpPost($post, $relatedPostFactory, $authorFactory);
	}

	/**
	 * @param null|\Closure(\WP_Post): ?PostData $relatedPostFactory
	 * @param null|\Closure(\WP_User): ?WpUserData $authorFactory
	 */
	public static function FromId(
		int $postId,
		?\Closure $relatedPostFactory = null,
		?\Closure $authorFactory = null,
	): ?PostData
	{
		if ($postId <= 0) {
			return null;
		}

		$post = \get_post($postId);

		return $post instanceof \WP_Post
			? self::From($post, $relatedPostFactory, $authorFactory)
			: null;
	}
}
