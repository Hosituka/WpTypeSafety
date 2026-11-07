<?php
declare(strict_types=1);

namespace WpTypeSafety\Acf\Data;

use WpTypeSafety\Acf\Get\AcfGet;

/** フレキシブルコンテンツのレイアウト名と行内フィールドを保持する。 */
final readonly class FlexibleNode
{
	public function __construct(
		public string $Layout,
		public AcfGet $Get,
	) {}
}
