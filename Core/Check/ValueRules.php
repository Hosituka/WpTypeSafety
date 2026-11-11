<?php
declare(strict_types=1);

namespace WpTypeSafety\Core\Check;

use WpTypeSafety\Internal\TreeTypeReader\ValueMode as InternalValueMode;
use WpTypeSafety\Internal\TreeTypeReader\ValueRules as InternalValueRules;

/** 型付きReaderがプリミティブ値を受け入れる基準を保持します。 */
class ValueRules extends InternalValueRules
{
	public static function FromMode(ValueMode $mode): static
	{
		return static::FromModes($mode);
	}

	public static function FromModes(ValueMode ...$modes): static
	{
		$internalModes = array_map(
			static fn(ValueMode $mode): InternalValueMode => $mode->ToInternal(),
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

		$publicRules = static::FromMode(ValueMode::Strict);
		$publicRules->StringNormalizer = $rules->StringNormalizer;
		$publicRules->IntNormalizer = $rules->IntNormalizer;
		$publicRules->FloatNormalizer = $rules->FloatNormalizer;
		$publicRules->BoolNormalizer = $rules->BoolNormalizer;
		return $publicRules;
	}
}
