<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Data\TaxonomyData;
use WpTypeSafety\Wp\Data\TaxonomyMembers;
use WpTypeSafety\Wp\Internal\DefinitionAdapter;

/** タクソノミーの登録定義を取得・変換する公開入口。 */
final class TaxonomyFactory
{
	private function __construct() {}

	public static function Members(\WP_Taxonomy $taxonomy): TaxonomyMembers
	{
		return DefinitionAdapter::TaxonomyMembersFromWp($taxonomy);
	}

	public static function From(\WP_Taxonomy $taxonomy): TaxonomyData
	{
		return DefinitionAdapter::TaxonomyFromWp($taxonomy);
	}

	/** 未登録の場合はnull。登録完了後に呼び出す。 */
	public static function FromName(string $name): ?TaxonomyData
	{
		return DefinitionAdapter::TaxonomyFromName($name);
	}
}
