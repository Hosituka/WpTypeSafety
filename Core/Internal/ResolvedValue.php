<?php
declare(strict_types=1);

namespace WpTypeSafety\Core\Internal;

/** @internal 取得対象の存在状態と値を区別して保持します。 */
final readonly class ResolvedValue
{
	public function __construct(
		public bool $Exists,
		public mixed $Value,
	) {}
}
