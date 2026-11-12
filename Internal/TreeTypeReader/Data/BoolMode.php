<?php
declare(strict_types=1);

namespace WpTypeSafety\Internal\TreeTypeReader\Data;

/** @internal Bool Getterが追加で受け入れる入力表現です。 */
enum BoolMode
{
	case Str;
	case TrueFalseStr;
	case OneZeroStr;
	case OnOffStr;
	case YesNoStr;
	case OneZeroInt;
	case EmptyStrAsFalse;
}
