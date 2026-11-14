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

	/**
	 * @template TNode of MenuNodeData
	 * @param null|\Closure(\WP_Post, list<TNode>): ?TNode $nodeFactory
	 * @return ($nodeFactory is null ? list<MenuNodeData> : list<TNode>)
	 */
	public static function TreeFromLocation(string $location, ?\Closure $nodeFactory = null): array
	{
		return MenuAdapter::TreeFromLocation($location, $nodeFactory);
	}

	/**
	 * @template TNode of MenuNodeData
	 * @param list<TNode> $nodes
	 * @return TNode|null
	 */
	public static function FindByPageId(array $nodes, int $pageId): ?MenuNodeData
	{
		return MenuAdapter::FindByPageId($nodes, $pageId);
	}

	/**
	 * @template TNode of MenuNodeData
	 * @param list<TNode> $nodes
	 * @return TNode|null
	 */
	public static function FindById(array $nodes, int $menuItemId): ?MenuNodeData
	{
		return MenuAdapter::FindById($nodes, $menuItemId);
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
		return MenuAdapter::FindByObject($nodes, $type, $object, $objectId);
	}

	/**
	 * @template TNode of MenuNodeData
	 * @param list<TNode> $nodes
	 * @return TNode|null
	 */
	public static function FindByUrl(array $nodes, string $url): ?MenuNodeData
	{
		return MenuAdapter::FindByUrl($nodes, $url);
	}

	/**
	 * @template TNode of MenuNodeData
	 * @param list<TNode> $nodes
	 * @return list<TNode>
	 */
	public static function PathToId(array $nodes, int $menuItemId): array
	{
		return MenuAdapter::PathToId($nodes, $menuItemId);
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
		return MenuAdapter::PathToObject($nodes, $type, $object, $objectId);
	}

	/**
	 * @template TNode of MenuNodeData
	 * @param list<TNode> $nodes
	 * @return list<TNode>
	 */
	public static function PathToUrl(array $nodes, string $url): array
	{
		return MenuAdapter::PathToUrl($nodes, $url);
	}
}
