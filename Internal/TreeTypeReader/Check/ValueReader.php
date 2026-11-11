<?php
declare(strict_types=1);

namespace WpTypeSafety\Internal\TreeTypeReader\Check;

use WpTypeSafety\Internal\TreeTypeReader\Internal\ValuePredicate;

/** 単独の値を、設定した基準に従って型付きの値へ正規化します。 */
class ValueReader
{
	private ValueRules $rules;

	public function __construct(
		ValueMode|ValueRules $modeOrRules = ValueMode::Strict,
	) {
		$this->rules = $modeOrRules instanceof ValueRules
			? clone $modeOrRules
			: ValueRules::FromInternalMode($modeOrRules);
	}

	protected function copyWithMode(ValueMode $mode): static
	{
		$reader = clone $this;
		$reader->rules = ValueRules::FromInternalMode($mode);
		return $reader;
	}

	protected function copyWithRules(ValueRules $rules): static
	{
		$reader = clone $this;
		$reader->rules = clone $rules;
		return $reader;
	}

	/** @param (callable(string): bool)|null $predicate */
	public function String(mixed $value, ?callable $predicate = null): ?string
	{
		$normalized = $this->rules->NormalizeString($value);
		return $normalized !== null && ValuePredicate::Passes($normalized, $predicate)
			? $normalized
			: null;
	}

	/** @param (callable(int): bool)|null $predicate */
	public function Int(mixed $value, ?callable $predicate = null): ?int
	{
		$normalized = $this->rules->NormalizeInt($value);
		return $normalized !== null && ValuePredicate::Passes($normalized, $predicate)
			? $normalized
			: null;
	}

	/** @param (callable(float): bool)|null $predicate */
	public function Float(mixed $value, ?callable $predicate = null): ?float
	{
		$normalized = $this->rules->NormalizeFloat($value);
		return $normalized !== null && ValuePredicate::Passes($normalized, $predicate)
			? $normalized
			: null;
	}

	/** @param (callable(bool): bool)|null $predicate */
	public function Bool(mixed $value, ?callable $predicate = null): ?bool
	{
		$normalized = $this->rules->NormalizeBool($value);
		return $normalized !== null && ValuePredicate::Passes($normalized, $predicate)
			? $normalized
			: null;
	}

	/**
	 * @template T of \BackedEnum
	 * @param class-string<T> $enumClass
	 * @param (callable(T): bool)|null $predicate
	 * @return T|null
	 */
	public function BackedEnum(mixed $value, string $enumClass, ?callable $predicate = null): ?\BackedEnum
	{
		$normalized = $this->rules->NormalizeBackedEnum($value, $enumClass);
		return $normalized !== null && ValuePredicate::Passes($normalized, $predicate)
			? $normalized
			: null;
	}

	/**
	 * @param (callable(string): bool)|null $predicate
	 * @return list<string>|null
	 */
	public function StringList(mixed $value, ?callable $predicate = null): ?array
	{
		$normalized = $this->rules->NormalizeStringList($value);
		return $normalized !== null
			? ValuePredicate::Filter($normalized, $predicate)
			: null;
	}

	/**
	 * @param (callable(int): bool)|null $predicate
	 * @return list<int>|null
	 */
	public function IntList(mixed $value, ?callable $predicate = null): ?array
	{
		$normalized = $this->rules->NormalizeIntList($value);
		return $normalized !== null
			? ValuePredicate::Filter($normalized, $predicate)
			: null;
	}

	/**
	 * @param (callable(float): bool)|null $predicate
	 * @return list<float>|null
	 */
	public function FloatList(mixed $value, ?callable $predicate = null): ?array
	{
		$normalized = $this->rules->NormalizeFloatList($value);
		return $normalized !== null
			? ValuePredicate::Filter($normalized, $predicate)
			: null;
	}

	/**
	 * @param (callable(bool): bool)|null $predicate
	 * @return list<bool>|null
	 */
	public function BoolList(mixed $value, ?callable $predicate = null): ?array
	{
		$normalized = $this->rules->NormalizeBoolList($value);
		return $normalized !== null
			? ValuePredicate::Filter($normalized, $predicate)
			: null;
	}

	/**
	 * @template T of \BackedEnum
	 * @param class-string<T> $enumClass
	 * @param (callable(T): bool)|null $predicate
	 * @return list<T>|null
	 */
	public function BackedEnumList(mixed $value, string $enumClass, ?callable $predicate = null): ?array
	{
		$normalized = $this->rules->NormalizeBackedEnumList($value, $enumClass);
		return $normalized !== null
			? ValuePredicate::Filter($normalized, $predicate)
			: null;
	}

	/** @param (callable(array<array-key, mixed>): bool)|null $predicate */
	public function Array(mixed $value, ?callable $predicate = null): ?array
	{
		$normalized = TypeCheck::Array($value);
		return $normalized !== null && ValuePredicate::Passes($normalized, $predicate)
			? $normalized
			: null;
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @param (callable(T): bool)|null $predicate
	 * @return T|null
	 */
	public function Instance(mixed $value, string $className, ?callable $predicate = null): ?object
	{
		$normalized = TypeCheck::Instance($value, $className);
		return $normalized !== null && ValuePredicate::Passes($normalized, $predicate)
			? $normalized
			: null;
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @param (callable(T): bool)|null $predicate
	 * @return list<T>|null
	 */
	public function InstanceList(mixed $value, string $className, ?callable $predicate = null): ?array
	{
		$normalized = TypeCheck::InstanceList($value, $className);
		return $normalized !== null
			? ValuePredicate::Filter($normalized, $predicate)
			: null;
	}
}
