<?php
declare(strict_types=1);

namespace WpTypeSafety\Core\Check\Data;

/** Bool Getterが追加で受け入れる入力表現です。 */
enum BoolMode
{
	/** true・false・1・0・on・off・yes・noの文字列表現を受け入れます。 */
	case Str;

	/** true・falseの文字列表現を受け入れます。 */
	case TrueFalseStr;

	/** 1・0の文字列表現を受け入れます。 */
	case OneZeroStr;

	/** on・offの文字列表現を受け入れます。 */
	case OnOffStr;

	/** yes・noの文字列表現を受け入れます。 */
	case YesNoStr;

	/** 整数1をtrue、整数0をfalseとして受け入れます。 */
	case OneZeroInt;

	/** 空文字列をfalseとして受け入れます。 */
	case EmptyStrAsFalse;
}
