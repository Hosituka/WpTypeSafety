<?php
declare(strict_types=1);

namespace WpTypeSafety\Core\Check\Data;

/** 数値Getterが追加で受け入れる入力表現です。 */
enum NumericMode
{
	/** 数値文字列など、対応する数値型へ変換可能な値を受け入れます。 */
	case Str;
}
