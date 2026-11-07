<?php
declare(strict_types=1);

namespace WpTypeSafety\Core\Check;

/** 取得済みの値を変換せず、指定した型だけを通します。型違いはnullです。 */
final class TypeCheck
{
	private function __construct()
	{
	}

	public static function String(mixed $value): ?string
	{
		return is_string($value) ? $value : null;
	}

	public static function Int(mixed $value): ?int
	{
		return is_int($value) ? $value : null;
	}

	public static function Float(mixed $value): ?float
	{
		return is_float($value) ? $value : null;
	}

	public static function Bool(mixed $value): ?bool
	{
		return is_bool($value) ? $value : null;
	}

	public static function Array(mixed $value): ?array
	{
		return is_array($value) ? $value : null;
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @return T|null
	 */
	public static function Instance(mixed $value, string $className): ?object
	{
		return $value instanceof $className ? $value : null;
	}

	/**
	 * 配列でない場合はnull、配列なら型違いの要素を除いたリストを返します。
	 * @template T of object
	 * @param class-string<T> $className
	 * @return list<T>|null
	 */
	public static function InstanceArray(mixed $value, string $className): ?array
	{
		if (! is_array($value)) {
			return null;
		}

		$items = [];
		foreach ($value as $item) {
			if ($item instanceof $className) {
				$items[] = $item;
			}
		}

		return $items;
	}

	/** @return list<string>|null */
	public static function StringArray(mixed $value): ?array
	{
		return is_array($value)
			? array_values(array_filter($value, 'is_string'))
			: null;
	}

	/** @return list<int>|null */
	public static function IntArray(mixed $value): ?array
	{
		return is_array($value)
			? array_values(array_filter($value, 'is_int'))
			: null;
	}

	/** @return list<float>|null */
	public static function FloatArray(mixed $value): ?array
	{
		return is_array($value)
			? array_values(array_filter($value, 'is_float'))
			: null;
	}

	/** @return list<bool>|null */
	public static function BoolArray(mixed $value): ?array
	{
		return is_array($value)
			? array_values(array_filter($value, 'is_bool'))
			: null;
	}
}
