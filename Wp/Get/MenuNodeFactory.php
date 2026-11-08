<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Data\MenuNodeData;
use WpTypeSafety\Wp\Data\MenuNodeMembers;
use WpTypeSafety\Wp\Internal\MenuAdapter;

/** WP_Post形式のメニュー項目からMembersまたはDataを生成します。 */
final class MenuNodeFactory
{
	private function __construct() {}

	/** @param list<MenuNodeData> $children */
	public static function Members(\WP_Post $item, array $children = []): MenuNodeMembers
	{
		return MenuAdapter::MembersFromWpPost($item, $children);
	}

	/** @param list<MenuNodeData> $children */
	public static function From(\WP_Post $item, array $children = []): MenuNodeData
	{
		return MenuAdapter::FromWpPost($item, $children);
	}
}
