<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Data\PostTypeData;
use WpTypeSafety\Wp\Data\PostTypeMembers;
use WpTypeSafety\Wp\Internal\DefinitionAdapter;

/** 投稿タイプの登録定義を取得・変換する公開入口。 */
final class PostTypeGet
{
	private function __construct() {}

	public static function Members(\WP_Post_Type $postType): PostTypeMembers
	{
		return DefinitionAdapter::PostTypeMembersFromWp($postType);
	}

	public static function From(\WP_Post_Type $postType): PostTypeData
	{
		return DefinitionAdapter::PostTypeFromWp($postType);
	}

	/** 未登録の場合はnull。登録完了後に呼び出す。 */
	public static function FromName(string $name): ?PostTypeData
	{
		return DefinitionAdapter::PostTypeFromName($name);
	}
}
