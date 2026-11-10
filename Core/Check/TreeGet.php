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

/** 配列とオブジェクトが混在する木構造を、型を自動判定しながら取得します。 */
final class TreeGet
{
	private readonly mixed $source;
	private readonly ValueRules $rules;

	public function __construct(
		mixed $source,
		ValueMode|ValueRules $modeOrRules = ValueMode::Strict,
	) {
		$this->source = $source;
		$this->rules = $modeOrRules instanceof ValueRules
			? clone $modeOrRules
			: ValueRules::FromMode($modeOrRules);
	}

	private function value(int|string $segment): mixed
	{
		return $this->resolveValue($segment)->Value;
	}

	private function resolveValue(int|string $segment): ResolvedValue
	{
		return ValueResolver::Resolve($this->source, $segment);
	}

	public function WithMode(ValueMode $mode): self
	{
		return new self($this->source, $mode);
	}

	public function WithRules(ValueRules $rules): self
	{
		return new self($this->source, $rules);
	}

	/**
	 * 指定セグメントの値を変換し、戻り値を新しい探索対象として使用します。
	 *
	 * @param callable(mixed): mixed $transformer
	 */
	public function Transform(int|string $segment, callable $transformer): self
	{
		$resolved = $this->resolveValue($segment);
		$transformed = $resolved->Exists
			? $transformer($resolved->Value)
			: null;

		return new self($transformed, $this->rules);
	}

	public function Exists(int|string $segment): bool
	{
		return $this->resolveValue($segment)->Exists;
	}

	public function ExistsPath(int|string $segment, int|string ...$segments): bool
	{
		return ValueResolver::ResolvePath(
			$this->source,
			$segment,
			...$segments,
		)->Exists;
	}

	public function AsArrayGet(): ?ArrayGet
	{
		return is_array($this->source)
			? new ArrayGet($this->source, $this->rules)
			: null;
	}

	public function AsObjectGet(): ?ObjectGet
	{
		return is_object($this->source)
			? new ObjectGet($this->source, $this->rules)
			: null;
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @param (callable(T): bool)|null $predicate
	 * @return T|null
	 */
	public function Instance(int|string $segment, string $className, ?callable $predicate = null): ?object
	{
		$normalized = TypeCheck::Instance($this->value($segment), $className);
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
	public function InstanceList(int|string $segment, string $className, ?callable $predicate = null): array
	{
		$normalized = TypeCheck::InstanceList($this->value($segment), $className);
		return $normalized !== null
			? ValuePredicate::Filter($normalized, $predicate)
			: [];
	}

	/**
	 * @param (callable(string): bool)|null $predicate
	 * @return list<string>
	 */
	public function StringList(int|string $segment, ?callable $predicate = null): array
	{
		$resolved = $this->resolveValue($segment);
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
	public function IntList(int|string $segment, ?callable $predicate = null): array
	{
		$resolved = $this->resolveValue($segment);
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
	public function FloatList(int|string $segment, ?callable $predicate = null): array
	{
		$resolved = $this->resolveValue($segment);
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
	public function BoolList(int|string $segment, ?callable $predicate = null): array
	{
		$resolved = $this->resolveValue($segment);
		$normalized = $resolved->Exists
			? $this->rules->NormalizeBoolList($resolved->Value)
			: null;
		return $normalized !== null
			? ValuePredicate::Filter($normalized, $predicate)
			: [];
	}

	/** @param (callable(string): bool)|null $predicate */
	public function String(int|string $segment, ?callable $predicate = null): string
	{
		$resolved = $this->resolveValue($segment);
		$normalized = $resolved->Exists
			? $this->rules->NormalizeString($resolved->Value)
			: null;
		return $normalized !== null && ValuePredicate::Passes($normalized, $predicate)
			? $normalized
			: '';
	}

	/** @param (callable(int): bool)|null $predicate */
	public function Int(int|string $segment, ?callable $predicate = null): ?int
	{
		$resolved = $this->resolveValue($segment);
		$normalized = $resolved->Exists
			? $this->rules->NormalizeInt($resolved->Value)
			: null;
		return $normalized !== null && ValuePredicate::Passes($normalized, $predicate)
			? $normalized
			: null;
	}

	/** @param (callable(float): bool)|null $predicate */
	public function Float(int|string $segment, ?callable $predicate = null): ?float
	{
		$resolved = $this->resolveValue($segment);
		$normalized = $resolved->Exists
			? $this->rules->NormalizeFloat($resolved->Value)
			: null;
		return $normalized !== null && ValuePredicate::Passes($normalized, $predicate)
			? $normalized
			: null;
	}

	/** @param (callable(bool): bool)|null $predicate */
	public function Bool(int|string $segment, ?callable $predicate = null): ?bool
	{
		$resolved = $this->resolveValue($segment);
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
	public function BackedEnum(int|string $segment, string $enumClass, ?callable $predicate = null): ?\BackedEnum
	{
		$resolved = $this->resolveValue($segment);
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
	public function BackedEnumList(int|string $segment, string $enumClass, ?callable $predicate = null): array
	{
		$resolved = $this->resolveValue($segment);
		$normalized = $resolved->Exists
			? $this->rules->NormalizeBackedEnumList($resolved->Value, $enumClass)
			: null;
		return $normalized !== null
			? ValuePredicate::Filter($normalized, $predicate)
			: [];
	}

	/** @param (callable(array<array-key, mixed>): bool)|null $predicate */
	public function Array(int|string $segment, ?callable $predicate = null): array
	{
		$resolved = $this->resolveValue($segment);
		$normalized = $resolved->Exists
			? TypeCheck::Array($resolved->Value)
			: null;
		return $normalized !== null && ValuePredicate::Passes($normalized, $predicate)
			? $normalized
			: [];
	}

	public function Nested(int|string $segment): self
	{
		$resolved = $this->resolveValue($segment);
		return new self(
			$resolved->Exists ? $resolved->Value : null,
			$this->rules,
		);
	}

	public function NestedPath(int|string $segment, int|string ...$segments): self
	{
		$nestedGet = $this->Nested($segment);
		foreach ($segments as $nestedSegment) {
			$nestedGet = $nestedGet->Nested($nestedSegment);
		}

		return $nestedGet;
	}

	public function NestedArray(int|string $segment): ArrayGet
	{
		return new ArrayGet($this->value($segment), $this->rules);
	}

	public function NestedObject(int|string $segment): ObjectGet
	{
		return new ObjectGet($this->value($segment), $this->rules);
	}

	/** @return list<self> */
	public function NestedList(int|string $segment): array
	{
		$items = [];
		foreach ($this->Array($segment) as $value) {
			if (! is_array($value) && ! is_object($value)) {
				continue;
			}

			$items[] = new self($value, $this->rules);
		}

		return $items;
	}

	/** @return list<ArrayGet> */
	public function NestedArrayList(int|string $segment): array
	{
		$items = [];
		foreach ($this->Array($segment) as $value) {
			if (! is_array($value)) {
				continue;
			}

			$items[] = new ArrayGet($value, $this->rules);
		}

		return $items;
	}

	/** @return list<ObjectGet> */
	public function NestedObjectList(int|string $segment): array
	{
		$items = [];
		foreach ($this->Array($segment) as $value) {
			if (! is_object($value)) {
				continue;
			}

			$items[] = new ObjectGet($value, $this->rules);
		}

		return $items;
	}

	/** @param (callable(ImgData): bool)|null $predicate */
	public function ImgData(int|string $segment, ?callable $predicate = null): ?ImgData
	{
		return $this->Instance($segment, ImgData::class, $predicate);
	}

	/** @param (callable(LinkData): bool)|null $predicate */
	public function LinkData(int|string $segment, ?callable $predicate = null): ?LinkData
	{
		return $this->Instance($segment, LinkData::class, $predicate);
	}

	/** @param (callable(ChoiceData): bool)|null $predicate */
	public function ChoiceData(int|string $segment, ?callable $predicate = null): ?ChoiceData
	{
		return $this->Instance($segment, ChoiceData::class, $predicate);
	}

	/** @param (callable(FileData): bool)|null $predicate */
	public function FileData(int|string $segment, ?callable $predicate = null): ?FileData
	{
		return $this->Instance($segment, FileData::class, $predicate);
	}

	/** @param (callable(TimeData): bool)|null $predicate */
	public function TimeData(int|string $segment, ?callable $predicate = null): ?TimeData
	{
		return $this->Instance($segment, TimeData::class, $predicate);
	}

	/**
	 * @param (callable(ImgData): bool)|null $predicate
	 * @return list<ImgData>
	 */
	public function ImgDataList(int|string $segment, ?callable $predicate = null): array
	{
		return $this->InstanceList($segment, ImgData::class, $predicate);
	}

	/**
	 * @param (callable(LinkData): bool)|null $predicate
	 * @return list<LinkData>
	 */
	public function LinkDataList(int|string $segment, ?callable $predicate = null): array
	{
		return $this->InstanceList($segment, LinkData::class, $predicate);
	}

	/**
	 * @param (callable(ChoiceData): bool)|null $predicate
	 * @return list<ChoiceData>
	 */
	public function ChoiceDataList(int|string $segment, ?callable $predicate = null): array
	{
		return $this->InstanceList($segment, ChoiceData::class, $predicate);
	}

	/**
	 * @param (callable(FileData): bool)|null $predicate
	 * @return list<FileData>
	 */
	public function FileDataList(int|string $segment, ?callable $predicate = null): array
	{
		return $this->InstanceList($segment, FileData::class, $predicate);
	}

	/**
	 * @param (callable(TimeData): bool)|null $predicate
	 * @return list<TimeData>
	 */
	public function TimeDataList(int|string $segment, ?callable $predicate = null): array
	{
		return $this->InstanceList($segment, TimeData::class, $predicate);
	}
}
