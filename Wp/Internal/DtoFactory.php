<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Internal;

use WpTypeSafety\Core\Data\FileData;
use WpTypeSafety\Core\Data\ImgData;
use WpTypeSafety\Core\Internal\SvgDimensions;
use WpTypeSafety\Core\Internal\UrlCheck;
use WpTypeSafety\Wp\Data\CommentData;
use WpTypeSafety\Wp\Data\CommentMembers;
use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\PostMembers;
use WpTypeSafety\Wp\Data\UserMembers;
use WpTypeSafety\Wp\Data\WpUserData;

/** @internal WordPressの値からCoreのDTOを組み立てる。 */
final class DtoFactory
{
	private function __construct() {}

	/**
	 * @param null|\Closure(\WP_Comment): ?CommentData $relatedCommentFactory
	 * @param null|\Closure(\WP_User): ?WpUserData $userFactory
	 * @param null|\Closure(\WP_Post): ?PostData $postFactory
	 */
	public static function CommentFromWpComment(
		\WP_Comment $comment,
		int $avatarSize = 96,
		?\Closure $relatedCommentFactory = null,
		?\Closure $userFactory = null,
		?\Closure $postFactory = null,
	): CommentData
	{
		return new CommentData(self::CommentMembersFromWpComment(
			$comment,
			$avatarSize,
			$relatedCommentFactory,
			$userFactory,
			$postFactory,
		));
	}

	/**
	 * @param null|\Closure(\WP_Comment): ?CommentData $relatedCommentFactory
	 * @param null|\Closure(\WP_User): ?WpUserData $userFactory
	 * @param null|\Closure(\WP_Post): ?PostData $postFactory
	 */
	public static function CommentMembersFromWpComment(
		\WP_Comment $comment,
		int $avatarSize = 96,
		?\Closure $relatedCommentFactory = null,
		?\Closure $userFactory = null,
		?\Closure $postFactory = null,
	): CommentMembers
	{
		$commentId = (int) $comment->comment_ID;
		$postId = (int) $comment->comment_post_ID;
		$parentId = (int) $comment->comment_parent;
		$authorUserId = (int) $comment->user_id;
		$url = \get_comment_link($comment);

		return new CommentMembers(
			Id: $commentId,
			PostId: $postId,
			ParentId: $parentId,
			AuthorUserId: $authorUserId,
			AuthorName: (string) $comment->comment_author,
			AuthorUrl: UrlCheck::IsHttpUrl($comment->comment_author_url) ? $comment->comment_author_url : '',
			Url: is_string($url) ? $url : '',
			Content: (string) $comment->comment_content,
			Type: self::commentType((string) $comment->comment_type),
			Status: self::commentStatus((string) $comment->comment_approved),
			CreatedAt: self::commentDateTime($comment->comment_date),
			AvatarSize: $avatarSize,
			UserResolver: static function () use ($authorUserId, $userFactory): ?WpUserData {
				if ($authorUserId <= 0) {
					return null;
				}
				$user = \get_userdata($authorUserId);
				return $user instanceof \WP_User ? self::relatedUser($user, $userFactory) : null;
			},
			AvatarResolver: static function (int $size) use ($comment): ?ImgData {
				$url = \get_avatar_url($comment, ['size' => $size]);
				return is_string($url) && UrlCheck::IsHttpUrl($url)
					? new ImgData(Url: $url, Width: $size, Height: $size)
					: null;
			},
			PostResolver: static function () use ($postId, $postFactory): ?PostData {
				$post = $postId > 0 ? \get_post($postId) : null;
				return $post instanceof \WP_Post ? self::relatedPost($post, $postFactory) : null;
			},
			ParentResolver: static function () use (
				$parentId,
				$avatarSize,
				$relatedCommentFactory,
				$userFactory,
				$postFactory,
			): ?CommentData {
				$parent = $parentId > 0 ? \get_comment($parentId) : null;
				return $parent instanceof \WP_Comment
					? self::relatedComment(
						$parent,
						$avatarSize,
						$relatedCommentFactory,
						$userFactory,
						$postFactory,
					)
					: null;
			},
			ChildrenResolver: static fn (string $status, string $order): array => self::commentChildren(
				$commentId,
				$avatarSize,
				$status,
				$order,
				$relatedCommentFactory,
				$userFactory,
				$postFactory,
			),
		);
	}

	private static function commentType(string $type): string
	{
		return $type === '' ? 'comment' : $type;
	}

	private static function commentStatus(string $status): string
	{
		return match ($status) {
			'1' => 'approved',
			'0' => 'unapproved',
			default => $status,
		};
	}

	private static function commentDateTime(mixed $value): ?\DateTimeImmutable
	{
		if (! is_string($value) || $value === '' || $value === '0000-00-00 00:00:00') {
			return null;
		}
		$dateTime = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value, \wp_timezone());
		$errors = \DateTimeImmutable::getLastErrors();
		if ($dateTime === false || (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
			return null;
		}
		return $dateTime;
	}

	/**
	 * @param null|\Closure(\WP_Comment): ?CommentData $relatedCommentFactory
	 * @param null|\Closure(\WP_User): ?WpUserData $userFactory
	 * @param null|\Closure(\WP_Post): ?PostData $postFactory
	 * @return list<CommentData>
	 */
	private static function commentChildren(
		int $commentId,
		int $avatarSize,
		string $status,
		string $order,
		?\Closure $relatedCommentFactory,
		?\Closure $userFactory,
		?\Closure $postFactory,
	): array
	{
		if ($commentId <= 0) {
			return [];
		}
		$comments = \get_comments([
			'parent' => $commentId,
			'status' => $status,
			'orderby' => 'comment_date_gmt comment_ID',
			'order' => $order,
			'hierarchical' => false,
		]);
		$children = [];
		foreach (is_array($comments) ? $comments : [] as $child) {
			if ($child instanceof \WP_Comment) {
				$data = self::relatedComment(
					$child,
					$avatarSize,
					$relatedCommentFactory,
					$userFactory,
					$postFactory,
				);
				if ($data !== null) {
					$children[] = $data;
				}
			}
		}
		return $children;
	}

	/**
	 * @param null|\Closure(\WP_Post): ?PostData $relatedPostFactory
	 * @param null|\Closure(\WP_User): ?WpUserData $authorFactory
	 */
	public static function PostFromWpPost(
		\WP_Post $post,
		?\Closure $relatedPostFactory = null,
		?\Closure $authorFactory = null,
	): PostData
	{
		return new PostData(self::PostMembersFromWpPost($post, $relatedPostFactory, $authorFactory));
	}

	/**
	 * @param null|\Closure(\WP_Post): ?PostData $relatedPostFactory
	 * @param null|\Closure(\WP_User): ?WpUserData $authorFactory
	 */
	public static function PostMembersFromWpPost(
		\WP_Post $post,
		?\Closure $relatedPostFactory = null,
		?\Closure $authorFactory = null,
	): PostMembers
	{
		$postId = (int) $post->ID;
		$authorId = (int) $post->post_author;
		$url = \get_permalink($post);
		$publishedAt = \get_post_datetime($post);
		$modifiedAt = \get_post_datetime($post, 'modified');

		return new PostMembers(
			Id: $postId,
			Url: is_string($url) ? $url : '',
			Title: (string) \get_the_title($post),
			Content: (string) $post->post_content,
			PublishedAt: $publishedAt instanceof \DateTimeImmutable ? $publishedAt : null,
			ModifiedAt: $modifiedAt instanceof \DateTimeImmutable ? $modifiedAt : null,
			Status: (string) $post->post_status,
			PostType: (string) $post->post_type,
			Slug: (string) $post->post_name,
			IsPasswordProtected: (string) $post->post_password !== '',
			IsPubliclyViewable: \is_post_publicly_viewable($post),
			AuthorResolver: static function () use ($authorId, $authorFactory): ?WpUserData {
				if ($authorId <= 0) {
					return null;
				}
				$user = \get_userdata($authorId);
				return $user instanceof \WP_User ? self::relatedUser($user, $authorFactory) : null;
			},
			ExcerptResolver: static function () use ($postId): string {
				$current = \get_post($postId);
				return $current instanceof \WP_Post ? \wp_strip_all_tags($current->post_excerpt) : '';
			},
			ExcerptPresenceResolver: static function () use ($postId): bool {
				$current = \get_post($postId);
				return $current instanceof \WP_Post && \has_excerpt($current);
			},
			ThumbnailResolver: static fn (string|array $size): ?ImgData =>
				self::ImgFromAttachmentId((int) \get_post_thumbnail_id($postId), $size),
			ChildrenResolver: static fn (string|array $orderby, string $order): array =>
				self::postChildren(
					$postId,
					$orderby,
					$order,
					$relatedPostFactory,
					$authorFactory,
				),
			PreviousPostResolver: static fn (): ?PostData =>
				self::adjacentPost($postId, true, $relatedPostFactory, $authorFactory),
			NextPostResolver: static fn (): ?PostData =>
				self::adjacentPost($postId, false, $relatedPostFactory, $authorFactory),
			TemplateSlugResolver: static function () use ($postId): string {
				$slug = \get_page_template_slug($postId);
				return is_string($slug) ? $slug : '';
			},
		);
	}

	/**
	 * @param null|\Closure(\WP_Post): ?PostData $relatedPostFactory
	 * @param null|\Closure(\WP_User): ?WpUserData $authorFactory
	 * @return list<PostData>
	 */
	private static function postChildren(
		int $postId,
		string|array $orderby,
		string $order,
		?\Closure $relatedPostFactory,
		?\Closure $authorFactory,
	): array
	{
		$parent = $postId > 0 ? \get_post($postId) : null;
		if (! $parent instanceof \WP_Post || ! \is_post_type_hierarchical($parent->post_type)) {
			return [];
		}
		$posts = \get_posts([
			'post_type' => $parent->post_type,
			'post_parent' => $postId,
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'orderby' => $orderby,
			'order' => $order,
			'no_found_rows' => true,
		]);
		$children = [];
		foreach (is_array($posts) ? $posts : [] as $child) {
			if ($child instanceof \WP_Post) {
				$data = self::relatedPost($child, $relatedPostFactory, $authorFactory);
				if ($data !== null) {
					$children[] = $data;
				}
			}
		}
		return $children;
	}

	/**
	 * @param null|\Closure(\WP_Post): ?PostData $relatedPostFactory
	 * @param null|\Closure(\WP_User): ?WpUserData $authorFactory
	 */
	private static function adjacentPost(
		int $postId,
		bool $previous,
		?\Closure $relatedPostFactory,
		?\Closure $authorFactory,
	): ?PostData
	{
		$post = $postId > 0 ? \get_post($postId) : null;
		if (! $post instanceof \WP_Post) {
			return null;
		}
		$hadGlobal = array_key_exists('post', $GLOBALS);
		$savedPost = $GLOBALS['post'] ?? null;
		try {
			$GLOBALS['post'] = $post;
			$adjacent = \get_adjacent_post(false, '', $previous);
			return $adjacent instanceof \WP_Post
				? self::relatedPost($adjacent, $relatedPostFactory, $authorFactory)
				: null;
		} finally {
			if ($hadGlobal) {
				$GLOBALS['post'] = $savedPost;
			} else {
				unset($GLOBALS['post']);
			}
		}
	}

	/**
	 * @param null|\Closure(\WP_Comment): ?CommentData $relatedCommentFactory
	 * @param null|\Closure(\WP_User): ?WpUserData $userFactory
	 * @param null|\Closure(\WP_Post): ?PostData $postFactory
	 */
	private static function relatedComment(
		\WP_Comment $comment,
		int $avatarSize,
		?\Closure $relatedCommentFactory,
		?\Closure $userFactory,
		?\Closure $postFactory,
	): ?CommentData {
		$data = $relatedCommentFactory !== null
			? $relatedCommentFactory($comment)
			: self::CommentFromWpComment(
				$comment,
				$avatarSize,
				null,
				$userFactory,
				$postFactory,
			);
		if ($data !== null && ! $data instanceof CommentData) {
			throw new \UnexpectedValueException('Related comment factory must return CommentData or null.');
		}
		return $data;
	}

	/**
	 * @param null|\Closure(\WP_Post): ?PostData $postFactory
	 * @param null|\Closure(\WP_User): ?WpUserData $authorFactory
	 */
	private static function relatedPost(
		\WP_Post $post,
		?\Closure $postFactory,
		?\Closure $authorFactory = null,
	): ?PostData
	{
		$data = $postFactory !== null
			? $postFactory($post)
			: self::PostFromWpPost($post, authorFactory: $authorFactory);
		if ($data !== null && ! $data instanceof PostData) {
			throw new \UnexpectedValueException('Related post factory must return PostData or null.');
		}
		return $data;
	}

	/** @param null|\Closure(\WP_User): ?WpUserData $userFactory */
	private static function relatedUser(\WP_User $user, ?\Closure $userFactory): ?WpUserData
	{
		$data = $userFactory !== null ? $userFactory($user) : self::UserFromWpUser($user);
		if ($data !== null && ! $data instanceof WpUserData) {
			throw new \UnexpectedValueException('Related user factory must return WpUserData or null.');
		}
		return $data;
	}

	public static function UserFromWpUser(\WP_User $user, int $avatarSize = 96): ?WpUserData
	{
		$members = self::UserMembersFromWpUser($user, $avatarSize);
		return $members !== null ? new WpUserData($members) : null;
	}

	public static function UserMembersFromWpUser(\WP_User $user, int $avatarSize = 96): ?UserMembers
	{
		if (! $user->exists()) {
			return null;
		}
		$userId = (int) $user->ID;
		$authorUrl = \get_author_posts_url($userId);
		$description = $user->description ?? '';
		return new UserMembers(
			Id: $userId,
			DisplayName: (string) $user->display_name,
			AuthorUrl: UrlCheck::IsHttpUrl($authorUrl) ? $authorUrl : '',
			Description: is_string($description) ? $description : '',
			AvatarSize: $avatarSize,
			AvatarResolver: static function (int $size) use ($userId): ?ImgData {
				$url = \get_avatar_url($userId, ['size' => $size]);
				return is_string($url) && UrlCheck::IsHttpUrl($url)
					? new ImgData(Url: $url, Width: $size, Height: $size)
					: null;
			},
		);
	}

	public static function ImgFromAttachmentId(int $attachmentId, string|array $size = 'full'): ?ImgData
	{
		if ($attachmentId <= 0) {
			return null;
		}
		$mimeType = \get_post_mime_type($attachmentId);
		if (is_string($mimeType) && str_contains(strtolower($mimeType), 'svg')) {
			return self::svgImgFromAttachmentId($attachmentId);
		}
		$source = \wp_get_attachment_image_src($attachmentId, $size === 'original' ? 'full' : $size);
		if (! is_array($source)) {
			return null;
		}
		$url = $source[0] ?? null;
		$width = $source[1] ?? null;
		$height = $source[2] ?? null;
		if ($size === 'original') {
			$originalUrl = \wp_get_original_image_url($attachmentId);
			$originalPath = \wp_get_original_image_path($attachmentId);
			$originalSize = is_string($originalPath) ? \wp_getimagesize($originalPath) : false;
			if (is_string($originalUrl) && $originalUrl !== '') {
				if (! is_array($originalSize)) {
					return null;
				}
				$url = $originalUrl;
			}
			if (is_array($originalSize)) {
				$width = $originalSize[0] ?? null;
				$height = $originalSize[1] ?? null;
			}
		}
		if (! UrlCheck::IsHttpUrl($url) || ! is_numeric($width) || ! is_numeric($height)) {
			return null;
		}
		$width = (int) $width;
		$height = (int) $height;
		if ($width <= 0 || $height <= 0) {
			return null;
		}
		return new ImgData(
			Url: $url,
			Width: $width,
			Height: $height,
			Alt: (string) \get_post_meta($attachmentId, '_wp_attachment_image_alt', true),
		);
	}

	private static function svgImgFromAttachmentId(int $attachmentId): ?ImgData
	{
		$url = \wp_get_attachment_url($attachmentId);
		$path = \get_attached_file($attachmentId);
		if (! UrlCheck::IsHttpUrl($url) || ! is_string($path)) {
			return null;
		}
		$dimensions = SvgDimensions::FromFile($path);
		if ($dimensions === null) {
			return null;
		}
		return new ImgData(
			Url: $url,
			Width: $dimensions[0],
			Height: $dimensions[1],
			Alt: (string) \get_post_meta($attachmentId, '_wp_attachment_image_alt', true),
		);
	}

	public static function FileFromAttachmentId(int $attachmentId): ?FileData
	{
		if ($attachmentId <= 0 || \get_post_type($attachmentId) !== 'attachment') {
			return null;
		}
		$url = \wp_get_attachment_url($attachmentId);
		if (! UrlCheck::IsHttpUrl($url)) {
			return null;
		}
		$path = \get_attached_file($attachmentId);
		$metadata = \wp_get_attachment_metadata($attachmentId);
		$fileSize = self::fileSize(is_array($metadata) ? ($metadata['filesize'] ?? null) : null);
		if ($fileSize === null && is_string($path) && is_file($path)) {
			$fileSize = self::fileSize(\wp_filesize($path));
		}
		if ($fileSize === null) {
			return null;
		}
		$urlPath = \wp_parse_url($url, PHP_URL_PATH);
		$name = is_string($path) && $path !== ''
			? \wp_basename($path)
			: (is_string($urlPath) ? \wp_basename($urlPath) : '');
		$mimeType = \get_post_mime_type($attachmentId);
		return new FileData(
			Id: $attachmentId,
			Url: $url,
			FileName: $name,
			MimeType: is_string($mimeType) ? $mimeType : '',
			FileSize: $fileSize,
		);
	}

	private static function fileSize(mixed $value): ?int
	{
		return is_numeric($value) && $value >= 0 ? (int) $value : null;
	}

}
