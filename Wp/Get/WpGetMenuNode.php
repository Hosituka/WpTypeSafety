<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

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
}
