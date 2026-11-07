<?php
declare(strict_types=1);

namespace WpTypeSafety\Acf\Get;

use WpTypeSafety\Acf\Data\AcfBlockData;
use WpTypeSafety\Acf\Internal\BlockAdapter;

/** ACFの値からデータクラスを生成する公開入口。 */
final class AcfDataFactory
{
	private function __construct() {}

	/** @param array<array-key, mixed> $block ACFの描画テンプレートへ渡された$block */
	public static function BlockFromAcf(array $block, bool $isPreview = false): AcfBlockData
	{
		return BlockAdapter::FromAcf($block, $isPreview);
	}
}
