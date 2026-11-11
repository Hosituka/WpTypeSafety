<?php
declare(strict_types=1);

namespace WpTypeSafety\Core\Check;

use WpTypeSafety\Internal\TreeTypeReader\Check\ValueReader as InternalValueReader;

/** 単独の値を、設定した基準に従って型付きの値へ正規化します。 */
class ValueReader extends InternalValueReader
{
	public function __construct(
		ValueMode|ValueRules $modeOrRules = ValueMode::Strict,
	) {
		parent::__construct(
			$modeOrRules instanceof ValueMode
				? ValueRules::FromMode($modeOrRules)
				: $modeOrRules,
		);
	}

	public function WithMode(ValueMode $mode): static
	{
		return $this->copyWithRules(ValueRules::FromMode($mode));
	}

	public function WithRules(ValueRules $rules): static
	{
		return $this->copyWithRules($rules);
	}
}
