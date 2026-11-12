<?php
declare(strict_types=1);

namespace WpTypeSafety\Core\Check;

use WpTypeSafety\Core\Check\Data\BoolMode;
use WpTypeSafety\Core\Check\Data\NumericMode;
use WpTypeSafety\Internal\TreeTypeReader\Data\BoolMode as InternalBoolMode;
use WpTypeSafety\Internal\TreeTypeReader\Data\NumericMode as InternalNumericMode;
use WpTypeSafety\Internal\TreeTypeReader\ValueRules as InternalValueRules;

/** 型付きReaderがプリミティブ値を受け入れる基準を保持します。 */
class ValueRules extends InternalValueRules
{
	public static function Strict(): static
	{
		return parent::Strict();
	}

	public static function FromModes(NumericMode|BoolMode ...$modes): static
	{
		$internalModes = array_map(
			static fn(NumericMode|BoolMode $mode): InternalNumericMode|InternalBoolMode => match ($mode) {
				NumericMode::Str => InternalNumericMode::Str,
				BoolMode::Str => InternalBoolMode::Str,
				BoolMode::TrueFalseStr => InternalBoolMode::TrueFalseStr,
				BoolMode::OneZeroStr => InternalBoolMode::OneZeroStr,
				BoolMode::OnOffStr => InternalBoolMode::OnOffStr,
				BoolMode::YesNoStr => InternalBoolMode::YesNoStr,
				BoolMode::OneZeroInt => InternalBoolMode::OneZeroInt,
				BoolMode::EmptyStrAsFalse => InternalBoolMode::EmptyStrAsFalse,
			},
			$modes,
		);

		return parent::FromInternalModes(...$internalModes);
	}

	/** @internal 内部Rulesを公開Facadeへ変換します。 */
	public static function FromInternal(InternalValueRules $rules): static
	{
		if ($rules instanceof static) {
			return clone $rules;
		}

		$publicRules = static::Strict();
		$publicRules->StringNormalizer = $rules->StringNormalizer;
		$publicRules->IntNormalizer = $rules->IntNormalizer;
		$publicRules->FloatNormalizer = $rules->FloatNormalizer;
		$publicRules->BoolNormalizer = $rules->BoolNormalizer;
		return $publicRules;
	}
}
