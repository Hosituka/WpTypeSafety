<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Internal;

use WpTypeSafety\Wp\Data\CommentData;
use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\TermData;
use WpTypeSafety\Wp\Data\WpUserData;

/** @internal WordPress参照値をネイティブオブジェクトまたはWpTypeSafetyのDataへ変換します。 */
final class ReferenceAdapter
{
	private function __construct() {}

	public static function WpPost(mixed $value): ?\WP_Post
	{
		if ($value instanceof \WP_Post)
		{return $value;}

		$postId = $value instanceof PostData ? $value->PostId : self::positiveId($value);
		if ($postId === null)
		{return null;}

		$post = \get_post($postId);
		return $post instanceof \WP_Post ? $post : null;
	}

	public static function WpUser(mixed $value): ?\WP_User
	{
		if ($value instanceof \WP_User)
		{return $value;}

		if (is_array($value))
		{$value = $value['ID'] ?? null;}

		$userId = $value instanceof WpUserData ? $value->Id : self::positiveId($value);
		if ($userId === null)
		{return null;}

		$user = \get_user_by('id', $userId);
		return $user instanceof \WP_User ? $user : null;
	}

	public static function WpTerm(mixed $value, ?string $taxonomy = null): ?\WP_Term
	{
		if ($value instanceof \WP_Term)
		{return $value;}

		if ($value instanceof TermData) {
			$taxonomy ??= $value->Taxonomy;
			$value = $value->Id;
		}

		$termId = self::positiveId($value);
		if ($termId === null)
		{return null;}

		$term = \get_term($termId, $taxonomy ?? '');
		return $term instanceof \WP_Term ? $term : null;
	}

	public static function WpComment(mixed $value): ?\WP_Comment
	{
		if ($value instanceof \WP_Comment)
		{return $value;}

		$commentId = $value instanceof CommentData ? $value->Id : self::positiveId($value);
		if ($commentId === null)
		{return null;}

		$comment = \get_comment($commentId);
		return $comment instanceof \WP_Comment ? $comment : null;
	}

	public static function Post(int $postId): ?PostData
	{
		$post = self::WpPost($postId);
		return $post !== null
			? DtoFactory::PostFromWpPost($post)
			: null;
	}

	public static function User(int $userId, int $avatarSize = 96): ?WpUserData
	{
		$user = self::WpUser($userId);
		return $user !== null
			? DtoFactory::UserFromWpUser($user, $avatarSize)
			: null;
	}

	public static function Term(int $termId, ?string $taxonomy = null): ?TermData
	{
		$term = self::WpTerm($termId, $taxonomy);
		return $term !== null
			? TermAdapter::FromWpTerm($term)
			: null;
	}

	public static function Comment(int $commentId, int $avatarSize = 96): ?CommentData
	{
		$comment = self::WpComment($commentId);
		return $comment !== null
			? DtoFactory::CommentFromWpComment($comment, $avatarSize)
			: null;
	}

	private static function positiveId(mixed $value): ?int
	{
		if (! is_int($value) && (! is_string($value) || ! ctype_digit($value)))
		{return null;}

		$id = (int) $value;
		return $id > 0 ? $id : null;
	}
}
