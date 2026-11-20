<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Data\PostTypeData;
use WpTypeSafety\Wp\Internal\DefinitionAdapter;

/** 投稿タイプの登録定義を取得・変換する公開入口。 */
final class PostTypeGet
{
	private function __construct() {}

	/**
	 * 元のメインクエリの登録定義。それ以外はnull。
	 * @template TData of PostTypeData = PostTypeData
	 * @param class-string<TData> $dataClass
	 * @return TData|null
	 */
	public static function Current(string $dataClass = PostTypeData::class): ?PostTypeData
	{
		return DefinitionAdapter::CurrentPostType($dataClass);
	}

	/**
	 * 未登録の場合はnull。登録完了後に呼び出す。
	 * @template TData of PostTypeData = PostTypeData
	 * @param class-string<TData> $dataClass
	 * @return TData|null
	 */
	public static function FromName(string $name, string $dataClass = PostTypeData::class): ?PostTypeData
	{
		return DefinitionAdapter::PostTypeFromName($name, $dataClass);
	}
}
