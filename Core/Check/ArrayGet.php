<?php
declare(strict_types=1);

namespace WpTypeSafety\Core\Check;

use WpTypeSafety\Core\Data\ChoiceData;
use WpTypeSafety\Core\Data\FileData;
use WpTypeSafety\Core\Data\ImgData;
use WpTypeSafety\Core\Data\LinkData;
use WpTypeSafety\Core\Data\TimeData;
use WpTypeSafety\Core\Internal\ResolvedValue;
use WpTypeSafety\Core\Internal\ValuePredicate;
use WpTypeSafety\Core\Internal\ValueResolver;

/** 連想配列からキーを指定し、設定した基準で値を取得します。 */
final class ArrayGet
{
	/** @var array<array-key, mixed> */
	private readonly array $values;
	private readonly ValueRules $rules;

	public function __construct(
		mixed $values,
		ValueMode|ValueRules $modeOrRules = ValueMode::Strict,
	)
	{
		$this->values = TypeCheck::Array($values) ?? [];
		$this->rules = $modeOrRules instanceof ValueRules
			? clone $modeOrRules
			: ValueRules::FromMode($modeOrRules);
	}

	private function value(int|string $key): mixed
	{
		return $this->resolveValue($key)->Value;
	}

	private function resolveValue(int|string $key): ResolvedValue
	{
		return ValueResolver::ResolveArray($this->values, $key);
	}

	public function WithMode(ValueMode $mode): self
	{
		return new self($this->values, $mode);
	}

	public function WithRules(ValueRules $rules): self
	{
		return new self($this->values, $rules);
	}

	/**
	 * 指定キーの値を変換し、戻り値を新しい探索対象として使用します。
	 *
	 * @param callable(mixed): mixed $transformer
	 */
	public function Transform(int|string $key, callable $transformer): self
	{
		$resolved = $this->resolveValue($key);
		$transformed = $resolved->Exists
			? $transformer($resolved->Value)
			: [];

		return new self($transformed, $this->rules);
	}

	public function Exists(int|string $key): bool
	{
		return $this->resolveValue($key)->Exists;
	}

	public function ExistsPath(int|string $key, int|string ...$keys): bool
	{
		return ValueResolver::ResolveArrayPath(
			$this->values,
			$key,
			...$keys,
		)->Exists;
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @param (callable(T): bool)|null $predicate
	 * @return T|null
	 */
	public function Instance(int|string $key, string $className, ?callable $predicate = null): ?object
	{
		$normalized = TypeCheck::Instance($this->value($key), $className);
		return $normalized !== null && ValuePredicate::Passes($normalized, $predicate)
			? $normalized
			: null;
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @param (callable(T): bool)|null $predicate
	 * @return list<T>
	 */
	public function InstanceList(int|string $key, string $className, ?callable $predicate = null): array
	{
		$normalized = TypeCheck::InstanceList($this->value($key), $className);
		return $normalized !== null
			? ValuePredicate::Filter($normalized, $predicate)
			: [];
	}

	/**
	 * @param (callable(string): bool)|null $predicate
	 * @return list<string>
	 */
	public function StringList(int|string $key, ?callable $predicate = null): array
	{
		$resolved = $this->resolveValue($key);
		$normalized = $resolved->Exists
			? $this->rules->NormalizeStringList($resolved->Value)
			: null;
		return $normalized !== null
			? ValuePredicate::Filter($normalized, $predicate)
			: [];
	}

	/**
	 * @param (callable(int): bool)|null $predicate
	 * @return list<int>
	 */
	public function IntList(int|string $key, ?callable $predicate = null): array
	{
		$resolved = $this->resolveValue($key);
		$normalized = $resolved->Exists
			? $this->rules->NormalizeIntList($resolved->Value)
			: null;
		return $normalized !== null
			? ValuePredicate::Filter($normalized, $predicate)
			: [];
	}

	/**
	 * @param (callable(float): bool)|null $predicate
	 * @return list<float>
	 */
	public function FloatList(int|string $key, ?callable $predicate = null): array
	{
		$resolved = $this->resolveValue($key);
		$normalized = $resolved->Exists
			? $this->rules->NormalizeFloatList($resolved->Value)
			: null;
		return $normalized !== null
			? ValuePredicate::Filter($normalized, $predicate)
			: [];
	}

	/**
	 * @param (callable(bool): bool)|null $predicate
	 * @return list<bool>
	 */
	public function BoolList(int|string $key, ?callable $predicate = null): array
	{
		$resolved = $this->resolveValue($key);
		$normalized = $resolved->Exists
			? $this->rules->NormalizeBoolList($resolved->Value)
			: null;
		return $normalized !== null
			? ValuePredicate::Filter($normalized, $predicate)
			: [];
	}

	/** @param (callable(string): bool)|null $predicate */
	public function String(int|string $key, ?callable $predicate = null): string
	{
		$resolved = $this->resolveValue($key);
		$normalized = $resolved->Exists
			? $this->rules->NormalizeString($resolved->Value)
			: null;
		return $normalized !== null && ValuePredicate::Passes($normalized, $predicate)
			? $normalized
			: '';
	}

	/** @param (callable(int): bool)|null $predicate */
	public function Int(int|string $key, ?callable $predicate = null): ?int
	{
		$resolved = $this->resolveValue($key);
		$normalized = $resolved->Exists
			? $this->rules->NormalizeInt($resolved->Value)
			: null;
		return $normalized !== null && ValuePredicate::Passes($normalized, $predicate)
			? $normalized
			: null;
	}

	/** @param (callable(float): bool)|null $predicate */
	public function Float(int|string $key, ?callable $predicate = null): ?float
	{
		$resolved = $this->resolveValue($key);
		$normalized = $resolved->Exists
			? $this->rules->NormalizeFloat($resolved->Value)
			: null;
		return $normalized !== null && ValuePredicate::Passes($normalized, $predicate)
			? $normalized
			: null;
	}

	/** @param (callable(bool): bool)|null $predicate */
	public function Bool(int|string $key, ?callable $predicate = null): ?bool
	{
		$resolved = $this->resolveValue($key);
		$normalized = $resolved->Exists
			? $this->rules->NormalizeBool($resolved->Value)
			: null;
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
	public function BackedEnum(int|string $key, string $enumClass, ?callable $predicate = null): ?\BackedEnum
	{
		$resolved = $this->resolveValue($key);
		$normalized = $resolved->Exists
			? $this->rules->NormalizeBackedEnum($resolved->Value, $enumClass)
			: null;
		return $normalized !== null && ValuePredicate::Passes($normalized, $predicate)
			? $normalized
			: null;
	}

	/**
	 * @template T of \BackedEnum
	 * @param class-string<T> $enumClass
	 * @param (callable(T): bool)|null $predicate
	 * @return list<T>
	 */
	public function BackedEnumList(int|string $key, string $enumClass, ?callable $predicate = null): array
	{
		$resolved = $this->resolveValue($key);
		$normalized = $resolved->Exists
			? $this->rules->NormalizeBackedEnumList($resolved->Value, $enumClass)
			: null;
		return $normalized !== null
			? ValuePredicate::Filter($normalized, $predicate)
			: [];
	}

	/** @param (callable(array<array-key, mixed>): bool)|null $predicate */
	public function Array(int|string $key, ?callable $predicate = null): array
	{
		$resolved = $this->resolveValue($key);
		$normalized = $resolved->Exists
			? TypeCheck::Array($resolved->Value)
			: null;
		return $normalized !== null && ValuePredicate::Passes($normalized, $predicate)
			? $normalized
			: [];
	}

	public function Nested(int|string $key): self
	{
		return new self($this->value($key), $this->rules);
	}

	public function NestedPath(int|string $key, int|string ...$keys): self
	{
		$nestedGet = $this->Nested($key);
		foreach ($keys as $nestedKey) {
			$nestedGet = $nestedGet->Nested($nestedKey);
		}

		return $nestedGet;
	}

	public function NestedObject(int|string $key): ObjectGet
	{
		return new ObjectGet($this->value($key), $this->rules);
	}

	/** @return list<self> */
	public function NestedList(int|string $key): array
	{
		$items = [];
		foreach ($this->Array($key) as $value) {
			if (!is_array($value))
			{continue;}

			$items[] = new self($value, $this->rules);
		}
		return $items;
	}

	/** @return list<ObjectGet> */
	public function NestedObjectList(int|string $key): array
	{
		$items = [];
		foreach ($this->Array($key) as $value) {
			if (! is_object($value)) {
				continue;
			}

			$items[] = new ObjectGet($value, $this->rules);
		}

		return $items;
	}

	/** @param (callable(ImgData): bool)|null $predicate */
	public function ImgData(int|string $key, ?callable $predicate = null): ?ImgData
	{
		return $this->Instance($key, ImgData::class, $predicate);
	}

	/** @param (callable(LinkData): bool)|null $predicate */
	public function LinkData(int|string $key, ?callable $predicate = null): ?LinkData
	{
		return $this->Instance($key, LinkData::class, $predicate);
	}

	/** @param (callable(ChoiceData): bool)|null $predicate */
	public function ChoiceData(int|string $key, ?callable $predicate = null): ?ChoiceData
	{
		return $this->Instance($key, ChoiceData::class, $predicate);
	}

	/** @param (callable(FileData): bool)|null $predicate */
	public function FileData(int|string $key, ?callable $predicate = null): ?FileData
	{
		return $this->Instance($key, FileData::class, $predicate);
	}

	/** @param (callable(TimeData): bool)|null $predicate */
	public function TimeData(int|string $key, ?callable $predicate = null): ?TimeData
	{
		return $this->Instance($key, TimeData::class, $predicate);
	}

	/**
	 * @param (callable(ImgData): bool)|null $predicate
	 * @return list<ImgData>
	 */
	public function ImgDataList(int|string $key, ?callable $predicate = null): array
	{
		return $this->InstanceList($key, ImgData::class, $predicate);
	}

	/**
	 * @param (callable(LinkData): bool)|null $predicate
	 * @return list<LinkData>
	 */
	public function LinkDataList(int|string $key, ?callable $predicate = null): array
	{
		return $this->InstanceList($key, LinkData::class, $predicate);
	}

	/**
	 * @param (callable(ChoiceData): bool)|null $predicate
	 * @return list<ChoiceData>
	 */
	public function ChoiceDataList(int|string $key, ?callable $predicate = null): array
	{
		return $this->InstanceList($key, ChoiceData::class, $predicate);
	}

	/**
	 * @param (callable(FileData): bool)|null $predicate
	 * @return list<FileData>
	 */
	public function FileDataList(int|string $key, ?callable $predicate = null): array
	{
		return $this->InstanceList($key, FileData::class, $predicate);
	}

	/**
	 * @param (callable(TimeData): bool)|null $predicate
	 * @return list<TimeData>
	 */
	public function TimeDataList(int|string $key, ?callable $predicate = null): array
	{
		return $this->InstanceList($key, TimeData::class, $predicate);
	}
}
