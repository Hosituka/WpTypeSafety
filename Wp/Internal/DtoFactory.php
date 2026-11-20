<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Internal;

use WpTypeSafety\Core\Data\FileData;
use WpTypeSafety\Core\Data\ImgData;
use WpTypeSafety\Core\Internal\SvgDimensions;
use WpTypeSafety\Core\Internal\UrlCheck;
use WpTypeSafety\Wp\Data\CommentData;
use WpTypeSafety\Wp\Data\CommentProps;
use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\PostProps;
use WpTypeSafety\Wp\Data\UserProps;
use WpTypeSafety\Wp\Data\WpUserData;

/** @internal WordPressの値からCoreのDTOを組み立てる。 */
final class DtoFactory
{
	private function __construct() {}

	/**
	 * @template TPost of PostData = PostData
	 * @param class-string<TPost> $dataClass
	 * @return TPost|null
	 */
	public static function CurrentPost(string $dataClass = PostData::class): ?PostData
	{
		DataClass::Validate($dataClass, PostData::class);
		return self::contextPost(MainQueryAdapter::QueriedObject(), $dataClass);
	}

	/**
	 * @template TPost of PostData = PostData
	 * @param class-string<TPost> $dataClass
	 * @return TPost|null
	 */
	public static function LoopPost(string $dataClass = PostData::class): ?PostData
	{
		DataClass::Validate($dataClass, PostData::class);
		global $post;

		return self::contextPost($post ?? null, $dataClass);
	}

	/**
	 * @template TPost of PostData = PostData
	 * @param class-string<TPost> $dataClass
	 * @return TPost|null
	 */
	private static function contextPost(mixed $post, string $dataClass): ?PostData
	{
		if (! $post instanceof \WP_Post)
		{return null;}

		return $dataClass::From($post);
	}

	/**
	 * @template TUser of WpUserData = WpUserData
	 * @param class-string<TUser> $dataClass
	 * @return TUser|null
	 */
	public static function LoggedInUser(int $avatarSize = 96, string $dataClass = WpUserData::class): ?WpUserData
	{
		DataClass::Validate($dataClass, WpUserData::class);
		$user = \wp_get_current_user();
		if (! $user->exists())
		{return null;}

		return $dataClass::From($user, $avatarSize);
	}

	/**
	 * @template TUser of WpUserData = WpUserData
	 * @param class-string<TUser> $dataClass
	 * @return TUser|null
	 */
	public static function CurrentUser(int $avatarSize = 96, string $dataClass = WpUserData::class): ?WpUserData
	{
		DataClass::Validate($dataClass, WpUserData::class);
		$user = MainQueryAdapter::QueriedObject();
		if (! $user instanceof \WP_User || ! $user->exists())
		{return null;}

		return $dataClass::From($user, $avatarSize);
	}

	/** @return CommentData<CommentData, WpUserData, PostData> */
	public static function CommentFromWpComment(\WP_Comment $comment, int $avatarSize = 96): CommentData
	{
		return CommentData::From($comment, $avatarSize);
	}

	public static function CommentPropsFromWpComment(\WP_Comment $comment, int $avatarSize = 96): CommentProps
	{
		$url = \get_comment_link($comment);
		return new CommentProps(
			Id: (int) $comment->comment_ID,
			PostId: (int) $comment->comment_post_ID,
			ParentId: (int) $comment->comment_parent,
			AuthorUserId: (int) $comment->user_id,
			AuthorName: (string) $comment->comment_author,
			WebsiteUrl: UrlCheck::IsHttpUrl($comment->comment_author_url) ? $comment->comment_author_url : '',
			Url: is_string($url) ? $url : '',
			Content: (string) $comment->comment_content,
			Type: self::commentType((string) $comment->comment_type),
			Status: self::commentStatus((string) $comment->comment_approved),
			CreatedAt: self::commentDateTime($comment->comment_date),
			AvatarSize: $avatarSize,
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

	/** @return PostData<PostData, WpUserData> */
	public static function PostFromWpPost(\WP_Post $post): PostData
	{
		return PostData::From($post);
	}

	public static function PostPropsFromWpPost(\WP_Post $post): PostProps
	{
		$url = \get_permalink($post);
		$publishedAt = \get_post_datetime($post);
		$modifiedAt = \get_post_datetime($post, 'modified');

		return new PostProps(
			Id: (int) $post->ID,
			Url: is_string($url) ? $url : '',
			Title: (string) \get_the_title($post),
			RawContent: (string) $post->post_content,
			PublishedAt: $publishedAt instanceof \DateTimeImmutable ? $publishedAt : null,
			ModifiedAt: $modifiedAt instanceof \DateTimeImmutable ? $modifiedAt : null,
			Status: (string) $post->post_status,
			PostTypeName: (string) $post->post_type,
			Slug: (string) $post->post_name,
			IsPasswordProtected: (string) $post->post_password !== '',
			IsPubliclyViewable: \is_post_publicly_viewable($post),
			ParentId: max(0, (int) ($post->post_parent ?? 0)),
			AuthorId: (int) $post->post_author,
			RawExcerpt: (string) ($post->post_excerpt ?? ''),
		);
	}

	/**
	 * @template TPost of PostData = PostData
	 * @param null|\Closure(\WP_Post): (TPost|null) $postFactory
	 * @return TPost|null
	 */
	public static function RelatedPost(
		\WP_Post $post,
		?\Closure $postFactory,
	): ?PostData
	{
		$data = $postFactory !== null
			? $postFactory($post)
			: self::PostFromWpPost($post);
		if ($data !== null && ! $data instanceof PostData) {
			throw new \UnexpectedValueException('Related post factory must return PostData or null.');
		}
		return $data;
	}

	public static function UserFromWpUser(\WP_User $user, int $avatarSize = 96): ?WpUserData
	{
		return WpUserData::From($user, $avatarSize);
	}

	public static function UserPropsFromWpUser(\WP_User $user, int $avatarSize = 96): ?UserProps
	{
		if (! $user->exists()) {
			return null;
		}
		$userId = (int) $user->ID;
		$archiveUrl = \get_author_posts_url($userId);
		$websiteUrl = $user->user_url ?? '';
		$description = $user->description ?? '';
		return new UserProps(
			Id: $userId,
			DisplayName: (string) $user->display_name,
			ArchiveUrl: UrlCheck::IsHttpUrl($archiveUrl) ? $archiveUrl : '',
			WebsiteUrl: UrlCheck::IsHttpUrl($websiteUrl) ? $websiteUrl : '',
			Description: is_string($description) ? $description : '',
			AvatarSize: $avatarSize,
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
