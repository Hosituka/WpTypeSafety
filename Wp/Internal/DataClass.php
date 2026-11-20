<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Internal;

/** @internal 検索前に、指定された生成クラスの系統と具体性を検証する。 */
final class DataClass
{
	private function __construct() {}

	/** @param class-string $baseClass */
	public static function Validate(string $dataClass, string $baseClass): void
	{
		if (!is_a($dataClass, $baseClass, true) || (new \ReflectionClass($dataClass))->isAbstract())
		{throw new \InvalidArgumentException('dataClass must be a concrete ' . $baseClass . ' class.');}
	}
}
