<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Internal\MainQueryAdapter;

/** クエリ確定後、元のメインクエリの情報を追加クエリなしで取得します。 */
final class MainQueryGet
{
	private function __construct() {}

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
