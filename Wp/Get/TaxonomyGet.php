<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Data\TaxonomyData;
use WpTypeSafety\Wp\Data\TaxonomyProps;
use WpTypeSafety\Wp\Internal\DefinitionAdapter;

/** タクソノミーの登録定義を取得・変換する公開入口。 */
final class TaxonomyGet
{
	private function __construct() {}

	public static function Props(\WP_Taxonomy $taxonomy): TaxonomyProps
	{
		return DefinitionAdapter::TaxonomyPropsFromWp($taxonomy);
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
