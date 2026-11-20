<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Data\MenuData;
use WpTypeSafety\Wp\Internal\MenuAdapter;

/** クラシックメニュー全体の取得・変換の公開入口。 */
final class MenuGet
{
	private function __construct() {}

	/**
	 * @template TMenu of MenuData = MenuData
	 * @param class-string<TMenu> $dataClass
	 * @return TMenu|null
	 */
	public static function ById(int $menuId, string $dataClass = MenuData::class): ?MenuData
	{
		return MenuAdapter::ById($menuId, $dataClass);
	}

	/**
	 * @template TMenu of MenuData = MenuData
	 * @param class-string<TMenu> $dataClass
	 * @return TMenu|null
	 */
	public static function BySlug(string $slug, string $dataClass = MenuData::class): ?MenuData
	{
		return MenuAdapter::BySlug($slug, $dataClass);
	}

	/**
	 * @template TMenu of MenuData = MenuData
	 * @param class-string<TMenu> $dataClass
	 * @return TMenu|null
	 */
	public static function ByLocation(string $location, string $dataClass = MenuData::class): ?MenuData
	{
		return MenuAdapter::ByLocation($location, $dataClass);
	}

}
