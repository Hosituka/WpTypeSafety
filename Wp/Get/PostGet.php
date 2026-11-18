<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\PostProps;
use WpTypeSafety\Wp\Internal\DtoFactory;

/** WP_Postから投稿のPropsまたはDataを生成します。 */
final class PostGet
{
	private function __construct() {}

	/**
	 * メインクエリの表示対象を取得する。ループ中の投稿とは独立する。
	 * @template TPost of PostData
	 * @param null|\Closure(\WP_Post): TPost $postFactory
	 * @return ($postFactory is null ? PostData|null : TPost|null)
	 */
	public static function Current(?\Closure $postFactory = null): ?PostData
	{
		return DtoFactory::CurrentPost($postFactory);
	}

	/**
	 * グローバル$postの現在値を取得する。ループ外での有効性は保証しない。
	 * @template TPost of PostData
	 * @param null|\Closure(\WP_Post): TPost $postFactory
	 * @return ($postFactory is null ? PostData|null : TPost|null)
	 */
	public static function Loop(?\Closure $postFactory = null): ?PostData
	{
		return DtoFactory::LoopPost($postFactory);
	}

	/** 投稿の構築用の値を取得する。 */
	public static function Props(\WP_Post $post): PostProps
	{
		return DtoFactory::PostPropsFromWpPost($post);
	}

	/** @return PostData<PostData, \WpTypeSafety\Wp\Data\WpUserData> */
	public static function From(\WP_Post $post): PostData
	{
		return DtoFactory::PostFromWpPost($post);
	}

	/** @return PostData<PostData, \WpTypeSafety\Wp\Data\WpUserData>|null */
	public static function FromId(int $postId): ?PostData
	{
		if ($postId <= 0)
		{return null;}

		$post = \get_post($postId);
		return $post instanceof \WP_Post ? self::From($post) : null;
	}
}
