<?php
declare(strict_types=1);

namespace WpTypeSafety\Internal\TreeTypeReader\Data;

/** 取得対象の存在状態と値を区別して保持する、取得元拡張用の値です。 */
final readonly class ResolvedValue
{
	public function __construct(
		public bool $Exists,
		public mixed $Value,
	) {}
}
