<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Internal;

use WpTypeSafety\Core\Check\Data\BoolMode;
use WpTypeSafety\Core\Check\Data\NumericMode;
use WpTypeSafety\Core\Check\ValueRules;

/** @internal WordPressが文字列化する値に対応した既定の正規化規則を作ります。 */
final class WpValueRules
{
	private function __construct() {}

	public static function From(NumericMode|BoolMode|ValueRules|null $modeOrRules): ValueRules
	{
		if ($modeOrRules instanceof ValueRules)
		{return clone $modeOrRules;}

		return $modeOrRules instanceof NumericMode || $modeOrRules instanceof BoolMode
			? ValueRules::FromModes($modeOrRules)
			: ValueRules::FromModes(
				NumericMode::Str,
				BoolMode::TrueFalseStr,
				BoolMode::OneZeroStr,
				BoolMode::OneZeroInt,
				BoolMode::EmptyStrAsFalse,
			);
	}
}
