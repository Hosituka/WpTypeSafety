<?php
declare(strict_types=1);

namespace WpTypeSafety\Acf\Data;

use WpTypeSafety\Acf\Get\AcfGet;

/**
 * フレキシブルコンテンツのレイアウト名と行内フィールドを保持する。
 *
 * @template TGet of AcfGet
 */
final readonly class FlexibleNode
{
	/** @param TGet $Get */
	public function __construct(
		public string $Layout,
		public AcfGet $Get,
	) {}
}
