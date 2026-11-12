<?php
declare(strict_types=1);

namespace WpTypeSafety\Internal\TreeTypeReader;

use WpTypeSafety\Internal\TreeTypeReader\Data\BoolMode;
use WpTypeSafety\Internal\TreeTypeReader\Data\NumericMode;

/** ArrayGetとObjectGetがプリミティブ値を受け入れる基準を保持します。 */
class ValueRules
{
	/** @var \Closure(mixed): ?string */
	public \Closure $StringNormalizer;

	/** @var \Closure(mixed): ?int */
	public \Closure $IntNormalizer;

	/** @var \Closure(mixed): ?float */
	public \Closure $FloatNormalizer;

	/** @var \Closure(mixed): ?bool */
	public \Closure $BoolNormalizer;

	/**
	 * @param \Closure(mixed): ?string $StringNormalizer
	 * @param \Closure(mixed): ?int $IntNormalizer
	 * @param \Closure(mixed): ?float $FloatNormalizer
	 * @param \Closure(mixed): ?bool $BoolNormalizer
	 */
	protected function __construct(
		\Closure $StringNormalizer,
		\Closure $IntNormalizer,
		\Closure $FloatNormalizer,
		\Closure $BoolNormalizer,
	) {
		$this->StringNormalizer = $StringNormalizer;
		$this->IntNormalizer = $IntNormalizer;
		$this->FloatNormalizer = $FloatNormalizer;
		$this->BoolNormalizer = $BoolNormalizer;
	}

	public static function Strict(): static
	{
		return new static(
			StringNormalizer: static fn(mixed $value): ?string => TypeCheck::String($value),
			IntNormalizer: static fn(mixed $value): ?int => TypeCheck::Int($value),
			FloatNormalizer: static fn(mixed $value): ?float => TypeCheck::Float($value),
			BoolNormalizer: static fn(mixed $value): ?bool => TypeCheck::Bool($value),
		);
	}

	public static function FromInternalModes(NumericMode|BoolMode ...$modes): static
	{
		$rules = static::Strict();
		foreach ($modes as $mode) {
			match ($mode) {
				NumericMode::Str => $rules->applyNumericStr(),
				BoolMode::Str => $rules->applyBoolStringValues([
					'true' => true,
					'false' => false,
					'1' => true,
					'0' => false,
					'on' => true,
					'off' => false,
					'yes' => true,
					'no' => false,
				]),
				BoolMode::TrueFalseStr => $rules->applyBoolStringValues([
					'true' => true,
					'false' => false,
				]),
				BoolMode::OneZeroStr => $rules->applyBoolStringValues([
					'1' => true,
					'0' => false,
				]),
				BoolMode::OnOffStr => $rules->applyBoolStringValues([
					'on' => true,
					'off' => false,
				]),
				BoolMode::YesNoStr => $rules->applyBoolStringValues([
					'yes' => true,
					'no' => false,
				]),
				BoolMode::OneZeroInt => $rules->applyBoolInt(),
				BoolMode::EmptyStrAsFalse => $rules->applyEmptyStrAsFalse(),
			};
		}

		return $rules;
	}

	public function NormalizeString(mixed $value): ?string
	{
		return ($this->StringNormalizer)($value);
	}

	public function NormalizeInt(mixed $value): ?int
	{
		return ($this->IntNormalizer)($value);
	}

	public function NormalizeFloat(mixed $value): ?float
	{
		return ($this->FloatNormalizer)($value);
	}

	public function NormalizeBool(mixed $value): ?bool
	{
		return ($this->BoolNormalizer)($value);
	}

	/**
	 * @template T of \BackedEnum
	 * @param class-string<T> $enumClass
	 * @return T|null
	 */
	public function NormalizeBackedEnum(mixed $value, string $enumClass): ?\BackedEnum
	{
		if (! enum_exists($enumClass) || ! is_subclass_of($enumClass, \BackedEnum::class)) {
			return null;
		}

		if ($value instanceof $enumClass) {
			return $value;
		}

		$backingType = (new \ReflectionEnum($enumClass))->getBackingType()?->getName();
		$backingValue = match ($backingType) {
			'int' => $this->NormalizeInt($value),
			'string' => $this->NormalizeString($value),
			default => null,
		};

		return $backingValue === null
			? null
			: $enumClass::tryFrom($backingValue);
	}

	/** @return list<string>|null */
	public function NormalizeStringList(mixed $value): ?array
	{
		return $this->normalizeList(
			$value,
			fn(mixed $item): ?string => $this->NormalizeString($item),
		);
	}

	/** @return list<int>|null */
	public function NormalizeIntList(mixed $value): ?array
	{
		return $this->normalizeList(
			$value,
			fn(mixed $item): ?int => $this->NormalizeInt($item),
		);
	}

	/** @return list<float>|null */
	public function NormalizeFloatList(mixed $value): ?array
	{
		return $this->normalizeList(
			$value,
			fn(mixed $item): ?float => $this->NormalizeFloat($item),
		);
	}

	/** @return list<bool>|null */
	public function NormalizeBoolList(mixed $value): ?array
	{
		return $this->normalizeList(
			$value,
			fn(mixed $item): ?bool => $this->NormalizeBool($item),
		);
	}

	/**
	 * @template T of \BackedEnum
	 * @param class-string<T> $enumClass
	 * @return list<T>|null
	 */
	public function NormalizeBackedEnumList(mixed $value, string $enumClass): ?array
	{
		return $this->normalizeList(
			$value,
			fn(mixed $item): ?\BackedEnum => $this->NormalizeBackedEnum($item, $enumClass),
		);
	}

	/**
	 * @param \Closure(mixed): mixed $normalizer
	 * @return list<mixed>|null
	 */
	private function normalizeList(mixed $value, \Closure $normalizer): ?array
	{
		if (! is_array($value)) {
			return null;
		}

		$items = [];
		foreach ($value as $item) {
			$normalized = $normalizer($item);
			if ($normalized === null) {
				continue;
			}

			$items[] = $normalized;
		}

		return $items;
	}

	private function applyNumericStr(): void
	{
		$this->IntNormalizer = self::normalizeNumericInt(...);
		$this->FloatNormalizer = static fn(mixed $value): ?float => is_numeric($value)
			? (float) $value
			: null;
	}

	/** @param array<string, bool> $values */
	private function applyBoolStringValues(array $values): void
	{
		$previous = $this->BoolNormalizer;
		$this->BoolNormalizer = static function (mixed $value) use ($previous, $values): ?bool {
			if (! is_string($value)) {
				return $previous($value);
			}

			$normalizedValue = strtolower($value);
			return array_key_exists($normalizedValue, $values)
				? $values[$normalizedValue]
				: $previous($value);
		};
	}

	private function applyBoolInt(): void
	{
		$previous = $this->BoolNormalizer;
		$this->BoolNormalizer = static fn(mixed $value): ?bool => match ($value) {
			1 => true,
			0 => false,
			default => $previous($value),
		};
	}

	private function applyEmptyStrAsFalse(): void
	{
		$previous = $this->BoolNormalizer;
		$this->BoolNormalizer = static fn(mixed $value): ?bool => $value === ''
			? false
			: $previous($value);
	}

	private static function normalizeNumericInt(mixed $value): ?int
	{
		if (is_int($value)) {
			return $value;
		}

		if (is_float($value)) {
			if (! is_finite($value) || floor($value) !== $value) {
				return null;
			}

			$integer = (int) $value;
			return (float) $integer === $value ? $integer : null;
		}

		if (! is_string($value)) {
			return null;
		}

		$normalizedValue = trim($value, " \t\n\r\v\f");
		if (preg_match('/^[+-]?\d+$/D', $normalizedValue) !== 1) {
			return null;
		}

		$negative = str_starts_with($normalizedValue, '-');
		$hasSign = $negative || str_starts_with($normalizedValue, '+');
		$digits = ltrim($hasSign ? substr($normalizedValue, 1) : $normalizedValue, '0');
		$digits = $digits === '' ? '0' : $digits;
		$limit = $negative ? ltrim((string) PHP_INT_MIN, '-') : (string) PHP_INT_MAX;
		if (
			strlen($digits) > strlen($limit)
			|| (strlen($digits) === strlen($limit) && strcmp($digits, $limit) > 0)
		) {
			return null;
		}

		return (int) (($negative ? '-' : '') . $digits);
	}
}
