<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Internal;

use WpTypeSafety\Wp\Data\MenuItemType;
use WpTypeSafety\Wp\Data\MenuNodeData;
use WpTypeSafety\Wp\Data\MenuNodeMembers;

/** @internal */
final class MenuAdapter
{
	/** @var array<string, list<MenuNodeData>> */
	private static array $treeCache = [];

	private function __construct() {}

	/** @return list<MenuNodeData> */
	public static function TreeFromLocation(string $location): array
	{
		if (array_key_exists($location, self::$treeCache)) {
			return self::$treeCache[$location];
		}
		$locations = \get_nav_menu_locations();
		$menuId = is_array($locations) ? (int) ($locations[$location] ?? 0) : 0;
		if ($menuId <= 0) {
			return self::$treeCache[$location] = [];
		}
		$items = \wp_get_nav_menu_items($menuId);
		return self::$treeCache[$location] = is_array($items) ? self::buildTree($items) : [];
	}

	/**
	 * @param list<MenuNodeData> $nodes
	 */
	public static function FindByPageId(array $nodes, int $pageId): ?MenuNodeData
	{
		if ($pageId <= 0) {
			return null;
		}
		return self::FindByObject($nodes, MenuItemType::PostType, 'page', $pageId);
	}

	/** @param list<MenuNodeData> $nodes */
	public static function FindById(array $nodes, int $menuItemId): ?MenuNodeData
	{
		$path = self::PathToId($nodes, $menuItemId);
		return $path !== [] ? $path[array_key_last($path)] : null;
	}

	/** @param list<MenuNodeData> $nodes */
	public static function FindByObject(
		array $nodes,
		MenuItemType $type,
		string $object,
		int $objectId,
	): ?MenuNodeData {
		$path = self::PathToObject($nodes, $type, $object, $objectId);
		return $path !== [] ? $path[array_key_last($path)] : null;
	}

	/** @param list<MenuNodeData> $nodes */
	public static function FindByUrl(array $nodes, string $url): ?MenuNodeData
	{
		$path = self::PathToUrl($nodes, $url);
		return $path !== [] ? $path[array_key_last($path)] : null;
	}

	/**
	 * @param list<MenuNodeData> $nodes
	 * @return list<MenuNodeData>
	 */
	public static function PathToId(array $nodes, int $menuItemId): array
	{
		if ($menuItemId <= 0) {
			return [];
		}
		return self::pathTo(
			$nodes,
			static fn (MenuNodeData $node): bool => $node->Id === $menuItemId,
		);
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
		return self::pathTo(
			$nodes,
			static fn (MenuNodeData $node): bool =>
				$node->Type === $type
				&& $node->Object === $object
				&& $node->ObjectId === $objectId,
		);
	}

	/**
	 * @param list<MenuNodeData> $nodes
	 * @return list<MenuNodeData>
	 */
	public static function PathToUrl(array $nodes, string $url): array
	{
		if ($url === '') {
			return [];
		}
		return self::pathTo(
			$nodes,
			static fn (MenuNodeData $node): bool => $node->Url === $url,
		);
	}

	/**
	 * @param list<MenuNodeData> $nodes
	 * @param \Closure(MenuNodeData): bool $matches
	 * @return list<MenuNodeData>
	 */
	private static function pathTo(array $nodes, \Closure $matches): array
	{
		foreach ($nodes as $node) {
			if (! $node instanceof MenuNodeData) {
				continue;
			}
			if ($matches($node)) {
				return [$node];
			}
			$childPath = self::pathTo($node->Children, $matches);
			if ($childPath !== []) {
				return [$node, ...$childPath];
			}
		}
		return [];
	}

	/** @param list<MenuNodeData> $children */
	public static function MembersFromWpPost(\WP_Post $item, array $children = []): MenuNodeMembers
	{
		$objectId = (int) $item->object_id;
		$type = MenuItemType::tryFrom((string) $item->type) ?? MenuItemType::Unknown;

		return new MenuNodeMembers(
			Id: (int) $item->ID,
			ParentId: (int) $item->menu_item_parent,
			Title: (string) $item->title,
			Url: (string) $item->url,
			Target: is_string($item->target ?? null) ? $item->target : '',
			AttributeTitle: is_string($item->attr_title ?? null) ? $item->attr_title : '',
			Xfn: is_string($item->xfn ?? null) ? $item->xfn : '',
			Description: (string) $item->description,
			Classes: array_values(array_filter((array) $item->classes, 'is_string')),
			Order: (int) $item->menu_order,
			Type: $type,
			Object: (string) $item->object,
			ObjectId: $objectId,
			Children: $children,
			ThumbnailResolver: static fn (string|array $size) =>
				$type === MenuItemType::PostType && (string) $item->object === 'page' && $objectId > 0
					? DtoFactory::ImgFromAttachmentId((int) \get_post_thumbnail_id($objectId), $size)
					: null,
		);
	}

	/** @param list<MenuNodeData> $children */
	public static function FromWpPost(\WP_Post $item, array $children = []): MenuNodeData
	{
		return new MenuNodeData(self::MembersFromWpPost($item, $children));
	}

	/**
	 * @param array<mixed> $items
	 * @return list<MenuNodeData>
	 */
	private static function buildTree(array $items): array
	{
		$byId = [];
		$childrenByParent = [];
		foreach ($items as $item) {
			if (! $item instanceof \WP_Post) {
				continue;
			}
			$id = (int) $item->ID;
			if ($id <= 0) {
				continue;
			}
			$byId[$id] = $item;
			$parentId = (int) $item->menu_item_parent;
			$childrenByParent[$parentId][] = $id;
		}

		$build = static function (int $id, array $seen) use (&$build, $byId, $childrenByParent): ?MenuNodeData {
			if (isset($seen[$id]) || ! isset($byId[$id])) {
				return null;
			}
			$seen[$id] = true;
			$item = $byId[$id];
			$children = [];
			foreach ($childrenByParent[$id] ?? [] as $childId) {
				$child = $build($childId, $seen);
				if ($child !== null) {
					$children[] = $child;
				}
			}
			return self::FromWpPost($item, $children);
		};

		$roots = [];
		foreach ($byId as $id => $item) {
			$parentId = (int) $item->menu_item_parent;
			if ($parentId !== 0 && isset($byId[$parentId])) {
				continue;
			}
			$node = $build($id, []);
			if ($node !== null) {
				$roots[] = $node;
			}
		}
		return $roots;
	}
}
