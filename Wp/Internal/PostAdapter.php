<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Internal;

use WpTypeSafety\Core\Data\ImgData;
use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\WpUserData;

/** @internal 投稿Dataの標準WordPress接続処理。公開拡張点ではありません。 */
final class PostAdapter
{
	private function __construct() {}

	/**
	 * @template TPost of PostData = PostData
	 * @param class-string<TPost> $dataClass
	 * @return TPost|null
	 */
	public static function ById(int $postId, string $dataClass = PostData::class): ?PostData
	{
		DataClass::Validate($dataClass, PostData::class);
		if ($postId <= 0)
		{return null;}
		$post = \get_post($postId);
		return $post instanceof \WP_Post ? $dataClass::From($post) : null;
	}

	/**
	 * @template TPost of PostData = PostData
	 * @param class-string<TPost> $dataClass
	 * @return TPost|null
	 */
	public static function ByPath(string $path, string $postType = 'page', string $dataClass = PostData::class): ?PostData
	{
		DataClass::Validate($dataClass, PostData::class);
		if (str_starts_with($path, '//'))
		{return null;}
		$path = trim($path, '/');
		if ($path === '' || $postType === '' || strpbrk($path, "?#:\\") !== false)
		{return null;}
		// 文字列指定時にネイティブが添付ファイルまで検索する挙動を避ける。
		$post = \get_page_by_path($path, OBJECT, [$postType]);
		return $post instanceof \WP_Post && $post->post_type === $postType
			? $dataClass::From($post)
			: null;
	}


	/**
	 * @template TPost of PostData = PostData
	 * @param \Closure(\WP_Post): (TPost|null) $postFactory
	 * @return TPost|null
	 */
	public static function Related(int $id, \Closure $postFactory): ?PostData
	{
		if ($id <= 0)
		{return null;}

		$post = \get_post($id);
		return $post instanceof \WP_Post ? DtoFactory::RelatedPost($post, $postFactory) : null;
	}

	public static function Author(int $id): ?WpUserData
	{
		if ($id <= 0)
		{return null;}

		$user = \get_userdata($id);
		return $user instanceof \WP_User ? DtoFactory::UserFromWpUser($user) : null;
	}

	public static function Excerpt(string $rawExcerpt): string
	{
		return \wp_strip_all_tags($rawExcerpt);
	}

	/** @param string|array{0: int, 1: int} $size */
	public static function Thumbnail(int $id, string|array $size): ?ImgData
	{
		return $id > 0 ? DtoFactory::ImgFromAttachmentId((int) \get_post_thumbnail_id($id), $size) : null;
	}

	public static function TemplateSlug(int $id): string
	{
		if ($id <= 0)
		{return '';}

		$slug = \get_page_template_slug($id);
		return is_string($slug) ? $slug : '';
	}

	/**
	 * 本文・主要な投稿値はDataを基準とする。
	 * パスワードなど未保持のWordPress描画情報は、現在の投稿から補う。
	 */
	public static function RenderedContent(PostData $data): string
	{
		$post = $data->Id > 0 ? \get_post($data->Id) : null;
		if (! $post instanceof \WP_Post)
		{return '';}

		$post = clone $post;
		$post->post_title = $data->Title;
		$post->post_excerpt = $data->RawExcerpt;
		$post->post_author = $data->AuthorId;
		$post->post_parent = $data->ParentId;
		$post->post_status = $data->Status;
		$post->post_type = $data->PostTypeName;
		$post->post_name = $data->Slug;
		if ($data->PublishedAt !== null) {
			$post->post_date = $data->PublishedAt->format('Y-m-d H:i:s');
			$post->post_date_gmt = $data->PublishedAt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
		}
		if ($data->ModifiedAt !== null) {
			$post->post_modified = $data->ModifiedAt->format('Y-m-d H:i:s');
			$post->post_modified_gmt = $data->ModifiedAt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
		}
		return PostContentRenderer::Render($post, $data->RawContent);
	}

	/**
	 * @template TRelatedPost of PostData = PostData
	 * @param \Closure(\WP_Post): (TRelatedPost|null) $createRelatedPost
	 * @return list<TRelatedPost>
	 */
	public static function Children(
		int $postId,
		string|array $orderby,
		string $order,
		\Closure $createRelatedPost,
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
				$data = DtoFactory::RelatedPost($child, $createRelatedPost);
				if ($data !== null) {
					$children[] = $data;
				}
			}
		}
		return $children;
	}

	/**
	 * @template TRelatedPost of PostData = PostData
	 * @param \Closure(\WP_Post): (TRelatedPost|null) $createRelatedPost
	 * @return TRelatedPost|null
	 */
	public static function Adjacent(
		int $postId,
		bool $previous,
		\Closure $createRelatedPost,
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
				? DtoFactory::RelatedPost($adjacent, $createRelatedPost)
				: null;
		} finally {
			if ($hadGlobal) {
				$GLOBALS['post'] = $savedPost;
			} else {
				unset($GLOBALS['post']);
			}
		}
	}

}
