<?php
declare(strict_types=1);

namespace WpTypeSafety\Core\Check;

use WpTypeSafety\Internal\TreeTypeReader\ValueMode as InternalValueMode;

/** 型付きReaderがプリミティブ値を受け入れる組み込み基準です。 */
enum ValueMode
{
	/** 元のPHP型が一致する値だけを受け入れます。 */
	case Strict;

	/** Int・Floatで、対応する数値型へ変換可能な値も受け入れます。 */
	case NumericStr;

	/** Boolで、true・false・1・0の文字列表現も受け入れます。 */
	case BoolStr;

	/** Boolで、整数1をtrue、整数0をfalseとして受け入れます。 */
	case BoolInt;

	/** Boolで、空文字をfalseとして受け入れます。 */
	case EmptyStrAsFalse;

	/** @internal TreeTypeReader側の組み込みモードへ変換します。 */
	public function ToInternal(): InternalValueMode
	{
		return match ($this) {
			self::Strict => InternalValueMode::Strict,
			self::NumericStr => InternalValueMode::NumericStr,
			self::BoolStr => InternalValueMode::BoolStr,
			self::BoolInt => InternalValueMode::BoolInt,
			self::EmptyStrAsFalse => InternalValueMode::EmptyStrAsFalse,
		};
	}
}
