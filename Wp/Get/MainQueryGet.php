<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\PostQueryData;
use WpTypeSafety\Wp\Internal\MainQueryAdapter;

/** クエリ確定後、元のメインクエリの情報を追加クエリなしで取得します。 */
final class MainQueryGet
{
	private function __construct() {}

	/**
	 * 実行済みのメインクエリを変換する。呼び出しごとにDataを生成する。
	 * @template TPost of PostData = PostData
	 * @param class-string<TPost> $dataClass
	 * @return PostQueryData<TPost>
	 */
	public static function Current(string $dataClass = PostData::class): PostQueryData
	{
		return MainQueryAdapter::Current($dataClass);
	}

	public static function CurrentPageNumber(): int
	{
		return MainQueryAdapter::CurrentPageNumber();
	}

	public static function MaxPageCount(): int
	{
		return MainQueryAdapter::MaxPageCount();
	}

	public static function FoundPostCount(): int
	{
		return MainQueryAdapter::FoundPostCount();
	}
}
