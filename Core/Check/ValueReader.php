<?php
declare(strict_types=1);

namespace WpTypeSafety\Core\Check;

use WpTypeSafety\Core\Check\Data\BoolMode;
use WpTypeSafety\Core\Check\Data\NumericMode;
use WpTypeSafety\Internal\TreeTypeReader\ValueReader as InternalValueReader;

/** 単独の値を、設定した基準に従って型付きの値へ正規化します。 */
class ValueReader extends InternalValueReader
{
	public function __construct(
		NumericMode|BoolMode|ValueRules|null $modeOrRules = null,
	) {
		parent::__construct(
			$modeOrRules instanceof ValueRules
				? $modeOrRules
				: ($modeOrRules === null
					? ValueRules::Strict()
					: ValueRules::FromModes($modeOrRules)),
		);
	}

	public function WithModes(NumericMode|BoolMode ...$modes): static
	{
		return $this->copyWithRules(ValueRules::FromModes(...$modes));
	}

	public function WithRules(ValueRules $rules): static
	{
		return $this->copyWithRules($rules);
	}
}
