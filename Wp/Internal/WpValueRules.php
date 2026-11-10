<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Internal;

use WpTypeSafety\Core\Check\ValueMode;
use WpTypeSafety\Core\Check\ValueRules;

/** @internal WordPressが文字列化する値に対応した既定の正規化規則を作ります。 */
final class WpValueRules
{
	private function __construct() {}

	public static function From(ValueMode|ValueRules|null $modeOrRules): ValueRules
	{
		if ($modeOrRules instanceof ValueRules)
		{return clone $modeOrRules;}

		return $modeOrRules instanceof ValueMode
			? ValueRules::FromMode($modeOrRules)
			: ValueRules::FromModes(
				ValueMode::NumericStr,
				ValueMode::BoolStr,
				ValueMode::BoolInt,
				ValueMode::EmptyStrAsFalse,
			);
	}
}
