<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Internal;

use WpTypeSafety\Wp\Data\MenuItemType;
use WpTypeSafety\Wp\Data\MenuData;
use WpTypeSafety\Wp\Data\MenuProps;
use WpTypeSafety\Wp\Data\MenuNodeData;
use WpTypeSafety\Wp\Data\MenuNodeProps;

/** @internal */
final class MenuAdapter
{
	private function __construct() {}

	/** @param string|array{0: int, 1: int} $size */
	public static function Thumbnail(MenuItemType $type, string $object, int $objectId, string|array $size): ?\WpTypeSafety\Core\Data\ImgData
	{
		return $type === MenuItemType::PostType && $object === 'page' && $objectId > 0
			? DtoFactory::ImgFromAttachmentId((int) \get_post_thumbnail_id($objectId), $size)
			: null;
	}


	/**
	 * @template TMenu of MenuData = MenuData
	 * @param class-string<TMenu> $dataClass
	 * @return TMenu|null
	 */
	public static function ById(int $menuId, string $dataClass = MenuData::class): ?MenuData
	{
		DataClass::Validate($dataClass, MenuData::class);
		if ($menuId <= 0)
		{return null;}
		$term = \get_term($menuId, 'nav_menu');
		return $term instanceof \WP_Term ? $dataClass::From($term) : null;
	}

	/**
	 * @template TMenu of MenuData = MenuData
	 * @param class-string<TMenu> $dataClass
	 * @return TMenu|null
	 */
	public static function BySlug(string $slug, string $dataClass = MenuData::class): ?MenuData
	{
		DataClass::Validate($dataClass, MenuData::class);
		if ($slug === '')
		{return null;}
		$term = \get_term_by('slug', $slug, 'nav_menu');
		return $term instanceof \WP_Term ? $dataClass::From($term) : null;
	}

	/**
	 * @template TMenu of MenuData = MenuData
	 * @param class-string<TMenu> $dataClass
	 * @return TMenu|null
	 */
	public static function ByLocation(string $location, string $dataClass = MenuData::class): ?MenuData
	{
		DataClass::Validate($dataClass, MenuData::class);
		if ($location === '' || !array_key_exists($location, \get_registered_nav_menus()))
		{return null;}
		$locations = \get_nav_menu_locations();
		$menuId = (int) ($locations[$location] ?? 0);
		return self::ById($menuId, $dataClass);
	}

	/**
	 * @template TNode of MenuNodeData
	 * @param class-string<TNode> $nodeClass
	 * @return MenuProps<TNode>|null
	 */
	public static function PropsFromWpTerm(\WP_Term $term, string $nodeClass = MenuNodeData::class): ?MenuProps
	{
		DataClass::Validate($nodeClass, MenuNodeData::class);
		if ($term->taxonomy !== 'nav_menu' || (int) $term->term_id <= 0)
		{return null;}
		$items = \wp_get_nav_menu_items((int) $term->term_id);
		if (!is_array($items))
		{return null;}

		// WPオブジェクトのキャッシュを現在位置の判定で書き換えない。
		$prepared = [];
		foreach ($items as $item) {
			if (!$item instanceof \WP_Post || self::PropsFromWpPost($item) === null)
			{continue;}
			$copy = clone $item;
			$copy->current = false;
			$prepared[] = $copy;
		}
		// WPのメニューと同じ判定。クエリ確定前は現在項目なしとする。
		if ($prepared !== [] && ($GLOBALS['wp_query'] ?? null) instanceof \WP_Query) {
			\_wp_menu_item_classes_by_context($prepared);
		}
		return new MenuProps(
			Id: (int) $term->term_id,
			Name: (string) $term->name,
			Slug: (string) $term->slug,
			Description: (string) $term->description,
			Nodes: self::buildNodes($prepared, $nodeClass),
		);
	}

	/**
	 * @template TNode of MenuNodeData
	 * @param list<TNode> $nodes
	 * @return TNode|null
	 */
	public static function FindCurrent(array $nodes): ?MenuNodeData
	{
		$path = self::pathTo($nodes, static fn (MenuNodeData $node): bool => $node->IsCurrent);
		return $path !== [] ? $path[array_key_last($path)] : null;
	}

	/**
	 * @template TNode of MenuNodeData
	 * @param list<TNode> $nodes
	 * @return TNode|null
	 */
	public static function FindByPageId(array $nodes, int $pageId): ?MenuNodeData
	{
		if ($pageId <= 0) {
			return null;
		}
		return self::FindByObject($nodes, MenuItemType::PostType, 'page', $pageId);
	}

	/**
	 * @template TNode of MenuNodeData
	 * @param list<TNode> $nodes
	 * @return TNode|null
	 */
	public static function FindById(array $nodes, int $menuItemId): ?MenuNodeData
	{
		$path = self::PathToId($nodes, $menuItemId);
		return $path !== [] ? $path[array_key_last($path)] : null;
	}

	/**
	 * @template TNode of MenuNodeData
	 * @param list<TNode> $nodes
	 * @return TNode|null
	 */
	public static function FindByObject(
		array $nodes,
		MenuItemType $type,
		string $object,
		int $objectId,
	): ?MenuNodeData {
		$path = self::PathToObject($nodes, $type, $object, $objectId);
		return $path !== [] ? $path[array_key_last($path)] : null;
	}

	/**
	 * @template TNode of MenuNodeData
	 * @param list<TNode> $nodes
	 * @return TNode|null
	 */
	public static function FindByUrl(array $nodes, string $url): ?MenuNodeData
	{
		$path = self::PathToUrl($nodes, $url);
		return $path !== [] ? $path[array_key_last($path)] : null;
	}

	/**
	 * @template TNode of MenuNodeData
	 * @param list<TNode> $nodes
	 * @return list<TNode>
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
	 * @template TNode of MenuNodeData
	 * @param list<TNode> $nodes
	 * @return list<TNode>
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
	 * @template TNode of MenuNodeData
	 * @param list<TNode> $nodes
	 * @return list<TNode>
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
	 * @template TNode of MenuNodeData
	 * @param list<TNode> $nodes
	 * @param \Closure(TNode): bool $matches
	 * @return list<TNode>
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
			// MenuData<TNode>は全階層が同じノード型のツリーを扱う。
			/** @var list<TNode> $children */
			$children = $node->Children;
			$childPath = self::pathTo($children, $matches);
			if ($childPath !== []) {
				return [$node, ...$childPath];
			}
		}
		return [];
	}

	/**
	 * @template TChild of MenuNodeData
	 * @param list<TChild> $children
	 * @return MenuNodeProps<TChild>|null
	 */
	public static function PropsFromWpPost(\WP_Post $item, array $children = []): ?MenuNodeProps
	{
		// 未セットアップのWP_Postからメタ値を暗黙取得しない。
		$values = get_object_vars($item);
		if (($values['post_type'] ?? null) !== 'nav_menu_item' || (int) $item->ID <= 0)
		{return null;}
		foreach (['menu_item_parent', 'object_id', 'menu_order'] as $key) {
			if (!isset($values[$key]) || !is_numeric($values[$key]))
			{return null;}
		}
		foreach (['title', 'url', 'description', 'type', 'object'] as $key) {
			if (!isset($values[$key]) || !is_scalar($values[$key]))
			{return null;}
		}
		if (!isset($values['classes']) || !is_array($values['classes']))
		{return null;}
		$objectId = (int) $item->object_id;
		$type = MenuItemType::tryFrom((string) $item->type) ?? MenuItemType::Unknown;

		return new MenuNodeProps(
			Id: (int) $item->ID,
			ParentId: (int) $item->menu_item_parent,
			Title: (string) $item->title,
			Url: (string) $item->url,
			Target: is_string($values['target'] ?? null) ? $values['target'] : '',
			AttributeTitle: is_string($values['attr_title'] ?? null) ? $values['attr_title'] : '',
			Xfn: is_string($values['xfn'] ?? null) ? $values['xfn'] : '',
			Description: (string) $item->description,
			Classes: array_values(array_filter((array) $item->classes, 'is_string')),
			Order: (int) $item->menu_order,
			Type: $type,
			Object: (string) $item->object,
			ObjectId: $objectId,
			Children: $children,
			IsCurrent: ($values['current'] ?? false) === true,
		);
	}

	/**
	 * @template TChild of MenuNodeData
	 * @param list<TChild> $children
	 * @return MenuNodeData<TChild>|null
	 */
	public static function FromWpPost(\WP_Post $item, array $children = []): ?MenuNodeData
	{
		return MenuNodeData::From($item, $children);
	}

	/**
	 * @template TNode of MenuNodeData
	 * @param array<mixed> $items
	 * @param class-string<TNode> $nodeClass
	 * @return list<TNode>
	 */
	private static function buildNodes(array $items, string $nodeClass): array
	{
		$byId = [];
		$childrenByParent = [];
		foreach ($items as $item) {
			if (!$item instanceof \WP_Post || (int) $item->ID <= 0)
			{continue;}
			$id = (int) $item->ID;
			$byId[$id] = $item;
			$childrenByParent[(int) $item->menu_item_parent][] = $id;
		}
		$roots = [];
		foreach ($byId as $id => $item) {
			$parentId = (int) $item->menu_item_parent;
			if ($parentId !== 0 && isset($byId[$parentId]))
			{continue;}
			$node = self::buildNode($id, $byId, $childrenByParent, $nodeClass, []);
			if ($node !== null) {
				$roots[] = $node;
			}
		}
		return $roots;
	}

	/**
	 * @template TNode of MenuNodeData
	 * @param array<int, \WP_Post> $byId
	 * @param array<int, list<int>> $childrenByParent
	 * @param class-string<TNode> $nodeClass
	 * @param array<int, true> $seen
	 * @return TNode|null
	 */
	private static function buildNode(
		int $id,
		array $byId,
		array $childrenByParent,
		string $nodeClass,
		array $seen,
	): ?MenuNodeData {
		if (isset($seen[$id]) || !isset($byId[$id]))
		{return null;}
		$seen[$id] = true;
		$children = [];
		foreach ($childrenByParent[$id] ?? [] as $childId) {
			$child = self::buildNode($childId, $byId, $childrenByParent, $nodeClass, $seen);
			if ($child !== null) {
				$children[] = $child;
			}
		}
		$data = $nodeClass::From($byId[$id], $children);
		return $data;
	}
}
