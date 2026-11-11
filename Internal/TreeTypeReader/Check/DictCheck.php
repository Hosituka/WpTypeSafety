<?php
declare(strict_types=1);

namespace WpTypeSafety\Internal\TreeTypeReader\Check;

/** 文字列キーを保ったまま、辞書全体の値の型を確認します。 */
class DictCheck
{
	private function __construct()
	{
	}

	/**
	 * 型違いのキー・値が一つでもあればnullです。空の辞書は有効です。
	 *
	 * @template T of object
	 * @param class-string<T> $className
	 * @return array<string, T>|null
	 */
	public static function Instances(mixed $value, string $className): ?array
	{
		$dictionary = TypeCheck::Array($value);
		if ($dictionary === null) {
			return null;
		}

		foreach ($dictionary as $key => $item) {
			if (! is_string($key) || ! $item instanceof $className) {
				return null;
			}
		}

		return $dictionary;
	}

	/**
	 * 各値は同じクラスのインスタンスのリストです。
	 * 型違いのキー・値・要素が一つでもあればnullです。空の辞書・空のリストは有効です。
	 *
	 * @template T of object
	 * @param class-string<T> $className
	 * @return array<string, list<T>>|null
	 */
	public static function InstanceLists(mixed $value, string $className): ?array
	{
		$dictionary = TypeCheck::Array($value);
		if ($dictionary === null) {
			return null;
		}

		foreach ($dictionary as $key => $items) {
			if (! is_string($key) || ! is_array($items) || ! array_is_list($items)) {
				return null;
			}

			foreach ($items as $item) {
				if (! $item instanceof $className) {
					return null;
				}
			}
		}

		return $dictionary;
	}
}
