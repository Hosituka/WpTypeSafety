<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Internal\DtoFactory;
use WpTypeSafety\Wp\Internal\PostAdapter;

/** 表示コンテキスト・識別子から投稿を取得する公開入口。 */
final class PostGet
{
	private function __construct() {}

	/**
	 * メインクエリの表示対象を取得する。ループ中の投稿とは独立する。
	 * @template TPost of PostData = PostData
	 * @param class-string<TPost> $dataClass
	 * @return TPost|null
	 */
	public static function Current(string $dataClass = PostData::class): ?PostData
	{
		return DtoFactory::CurrentPost($dataClass);
	}

	/**
	 * グローバル$postの現在値を取得する。ループ外での有効性は保証しない。
	 * @template TPost of PostData = PostData
	 * @param class-string<TPost> $dataClass
	 * @return TPost|null
	 */
	public static function Loop(string $dataClass = PostData::class): ?PostData
	{
		return DtoFactory::LoopPost($dataClass);
	}

	/**
	 * @template TPost of PostData = PostData
	 * @param class-string<TPost> $dataClass
	 * @return TPost|null
	 */
	public static function ById(int $postId, string $dataClass = PostData::class): ?PostData
	{
		return PostAdapter::ById($postId, $dataClass);
	}

	/**
	 * 親子のslug列から取得する。URL全体・クエリ・フラグメントは受け付けない。
	 * @template TPost of PostData = PostData
	 * @param class-string<TPost> $dataClass
	 * @return TPost|null
	 */
	public static function ByPath(string $path, string $postType = 'page', string $dataClass = PostData::class): ?PostData
	{
		return PostAdapter::ByPath($path, $postType, $dataClass);
	}
}
