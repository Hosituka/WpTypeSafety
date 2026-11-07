<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Internal;

use WpTypeSafety\Core\Data\FileData;
use WpTypeSafety\Core\Data\ImgData;
use WpTypeSafety\Core\Internal\SvgDimensions;
use WpTypeSafety\Core\Internal\UrlCheck;
use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\WpUserData;

/** @internal WordPressの値からCoreのDTOを組み立てる。 */
final class DtoFactory
{
	private function __construct() {}

	public static function PostFromWpPost(\WP_Post $post): PostData
	{
		$postId = (int) $post->ID;
		$authorId = (int) $post->post_author;
		$url = \get_permalink($post);
		$publishedAt = \get_post_datetime($post);
		$modifiedAt = \get_post_datetime($post, 'modified');

		return new PostData(
			PostId: $postId,
			Url: is_string($url) ? $url : '',
			Title: (string) \get_the_title($post),
			Content: (string) $post->post_content,
			PublishedAt: $publishedAt instanceof \DateTimeImmutable ? $publishedAt : null,
			ModifiedAt: $modifiedAt instanceof \DateTimeImmutable ? $modifiedAt : null,
			authorResolver: static function () use ($authorId): ?WpUserData {
				if ($authorId <= 0) {
					return null;
				}
				$user = \get_userdata($authorId);
				return $user instanceof \WP_User ? self::UserFromWpUser($user) : null;
			},
			excerptResolver: static function () use ($postId): string {
				$current = \get_post($postId);
				return $current instanceof \WP_Post ? \wp_strip_all_tags($current->post_excerpt) : '';
			},
			excerptPresenceResolver: static function () use ($postId): bool {
				$current = \get_post($postId);
				return $current instanceof \WP_Post && \has_excerpt($current);
			},
			thumbnailResolver: static fn (string|array $size): ?ImgData =>
				self::ImgFromAttachmentId((int) \get_post_thumbnail_id($postId), $size),
			childrenResolver: static fn (string|array $orderby, string $order): array =>
				self::postChildren($postId, $orderby, $order),
			previousPostResolver: static fn (): ?PostData => self::adjacentPost($postId, true),
			nextPostResolver: static fn (): ?PostData => self::adjacentPost($postId, false),
			templateSlugResolver: static function () use ($postId): string {
				$slug = \get_page_template_slug($postId);
				return is_string($slug) ? $slug : '';
			},
		);
	}

	/** @return list<PostData> */
	private static function postChildren(int $postId, string|array $orderby, string $order): array
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
				$children[] = self::PostFromWpPost($child);
			}
		}
		return $children;
	}

	private static function adjacentPost(int $postId, bool $previous): ?PostData
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
			return $adjacent instanceof \WP_Post ? self::PostFromWpPost($adjacent) : null;
		} finally {
			if ($hadGlobal) {
				$GLOBALS['post'] = $savedPost;
			} else {
				unset($GLOBALS['post']);
			}
		}
	}

	public static function UserFromWpUser(\WP_User $user, int $avatarSize = 96): ?WpUserData
	{
		if (! $user->exists()) {
			return null;
		}
		$userId = (int) $user->ID;
		$authorUrl = \get_author_posts_url($userId);
		$description = $user->description ?? '';
		return new WpUserData(
			Id: $userId,
			DisplayName: (string) $user->display_name,
			AuthorUrl: UrlCheck::IsHttpUrl($authorUrl) ? $authorUrl : '',
			Description: is_string($description) ? $description : '',
			avatarSize: $avatarSize,
			avatarResolver: static function (int $size) use ($userId): ?ImgData {
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
