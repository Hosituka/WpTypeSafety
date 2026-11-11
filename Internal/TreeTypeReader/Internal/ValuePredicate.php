<?php
declare(strict_types=1);

namespace WpTypeSafety\Internal\TreeTypeReader\Internal;

/** @internal 正規化済みの値へ利用者指定の条件を適用します。 */
final class ValuePredicate
{
	private function __construct()
	{
	}

	/** @param (callable(mixed): bool)|null $predicate */
	public static function Passes(mixed $value, ?callable $predicate): bool
	{
		if ($predicate === null) {
			return true;
		}

		$result = $predicate($value);
		if (! is_bool($result)) {
			throw new \UnexpectedValueException('Predicate must return bool.');
		}

		return $result;
	}

	/**
	 * @template T
	 * @param list<T> $values
	 * @param (callable(T): bool)|null $predicate
	 * @return list<T>
	 */
	public static function Filter(array $values, ?callable $predicate): array
	{
		if ($predicate === null) {
			return $values;
		}

		$result = [];
		foreach ($values as $value) {
			if (self::Passes($value, $predicate)) {
				$result[] = $value;
			}
		}

		return $result;
	}
}
