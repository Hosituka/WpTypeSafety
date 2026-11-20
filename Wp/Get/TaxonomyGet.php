<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Data\TaxonomyData;
use WpTypeSafety\Wp\Internal\DefinitionAdapter;

/** タクソノミーの登録定義を取得・変換する公開入口。 */
final class TaxonomyGet
{
	private function __construct() {}

	/**
	 * 未登録の場合はnull。登録完了後に呼び出す。
	 * @template TData of TaxonomyData = TaxonomyData
	 * @param class-string<TData> $dataClass
	 * @return TData|null
	 */
	public static function ByName(string $name, string $dataClass = TaxonomyData::class): ?TaxonomyData
	{
		return DefinitionAdapter::TaxonomyFromName($name, $dataClass);
	}
}
