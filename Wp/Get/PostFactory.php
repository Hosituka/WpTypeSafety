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
	 * @template TRelatedPost of PostData = PostData
	 * @template TAuthor of WpUserData = WpUserData
	 * @param null|\Closure(\WP_Post): (TRelatedPost|null) $relatedPostFactory
	 * @param null|\Closure(\WP_User): (TAuthor|null) $authorFactory
	 * @return PostMembers<TRelatedPost, TAuthor>
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
	 * @template TRelatedPost of PostData = PostData
	 * @template TAuthor of WpUserData = WpUserData
	 * @param null|\Closure(\WP_Post): (TRelatedPost|null) $relatedPostFactory
	 * @param null|\Closure(\WP_User): (TAuthor|null) $authorFactory
	 * @return PostData<TRelatedPost, TAuthor>
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
	 * @template TRelatedPost of PostData = PostData
	 * @template TAuthor of WpUserData = WpUserData
	 * @param null|\Closure(\WP_Post): (TRelatedPost|null) $relatedPostFactory
	 * @param null|\Closure(\WP_User): (TAuthor|null) $authorFactory
	 * @return PostData<TRelatedPost, TAuthor>|null
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
