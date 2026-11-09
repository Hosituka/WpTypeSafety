<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Data\MenuItemType;
use WpTypeSafety\Wp\Data\MenuNodeData;
use WpTypeSafety\Wp\Internal\MenuAdapter;

/** メニューツリー取得・検索の公開クラス。 */
final class WpGetMenuNode
{
	private function __construct() {}

	/** @return list<MenuNodeData> */
	public static function TreeFromLocation(string $location): array
	{
		return MenuAdapter::TreeFromLocation($location);
	}

	/** @param list<MenuNodeData> $nodes */
	public static function FindByPageId(array $nodes, int $pageId): ?MenuNodeData
	{
		return MenuAdapter::FindByPageId($nodes, $pageId);
	}

	/** @param list<MenuNodeData> $nodes */
	public static function FindById(array $nodes, int $menuItemId): ?MenuNodeData
	{
		return MenuAdapter::FindById($nodes, $menuItemId);
	}

	/** @param list<MenuNodeData> $nodes */
	public static function FindByObject(
		array $nodes,
		MenuItemType $type,
		string $object,
		int $objectId,
	): ?MenuNodeData {
		return MenuAdapter::FindByObject($nodes, $type, $object, $objectId);
	}

	/** @param list<MenuNodeData> $nodes */
	public static function FindByUrl(array $nodes, string $url): ?MenuNodeData
	{
		return MenuAdapter::FindByUrl($nodes, $url);
	}

	/**
	 * @param list<MenuNodeData> $nodes
	 * @return list<MenuNodeData>
	 */
	public static function PathToId(array $nodes, int $menuItemId): array
	{
		return MenuAdapter::PathToId($nodes, $menuItemId);
	}

	/**
	 * @param list<MenuNodeData> $nodes
	 * @return list<MenuNodeData>
	 */
	public static function PathToObject(
		array $nodes,
		MenuItemType $type,
		string $object,
		int $objectId,
	): array {
		return MenuAdapter::PathToObject($nodes, $type, $object, $objectId);
	}

	/**
	 * @param list<MenuNodeData> $nodes
	 * @return list<MenuNodeData>
	 */
	public static function PathToUrl(array $nodes, string $url): array
	{
		return MenuAdapter::PathToUrl($nodes, $url);
	}
}
