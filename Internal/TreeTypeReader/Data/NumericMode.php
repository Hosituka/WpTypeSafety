<?php
declare(strict_types=1);

namespace WpTypeSafety\Internal\TreeTypeReader\Data;

/** @internal 数値Getterが追加で受け入れる入力表現です。 */
enum NumericMode
{
	case Str;
}
