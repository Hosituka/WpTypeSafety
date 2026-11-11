<?php
declare(strict_types=1);

namespace WpTypeSafety\Core\Check;

use WpTypeSafety\Internal\TreeTypeReader\Check\DictCheck as InternalDictCheck;

/** 文字列キーを保ったまま、辞書全体の値の型を確認します。 */
class DictCheck extends InternalDictCheck
{
	private function __construct()
	{
	}
}
