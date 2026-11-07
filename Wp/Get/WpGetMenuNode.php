<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Data\MenuNodeData;

/** メニューツリー取得・検索の公開クラス。 */
final class WpGetMenuNode
{
	/** @var null|\Closure(string): list<MenuNodeData> */
	private static ?\Closure $treeFromLocation = null;

	/** @var null|\Closure(list<MenuNodeData>, int): ?MenuNodeData */
	private static ?\Closure $findByPageId = null;

	private function __construct() {}

	/** @internal */
	public static function Register(\Closure $treeFromLocation, \Closure $findByPageId): void
	{
		if (self::$treeFromLocation !== null) {
			throw new \LogicException('WpGetMenuNode implementation is already registered.');
		}
		self::$treeFromLocation = $treeFromLocation;
		self::$findByPageId = $findByPageId;
	}

	/** @return list<MenuNodeData> */
	public static function TreeFromLocation(string $location): array
	{
		$resolver = self::$treeFromLocation ?? throw new \LogicException('WpGetMenuNode implementation is not registered.');
		return $resolver($location);
	}

	/** @param list<MenuNodeData> $nodes */
	public static function FindByPageId(array $nodes, int $pageId): ?MenuNodeData
	{
		$resolver = self::$findByPageId ?? throw new \LogicException('WpGetMenuNode implementation is not registered.');
		return $resolver($nodes, $pageId);
	}
}
