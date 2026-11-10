<?php
declare(strict_types=1);

namespace WpTypeSafety\Core\Check;

use WpTypeSafety\Core\Data\ChoiceData;
use WpTypeSafety\Core\Data\FileData;
use WpTypeSafety\Core\Data\ImgData;
use WpTypeSafety\Core\Data\LinkData;
use WpTypeSafety\Core\Data\TimeData;
use WpTypeSafety\Core\Internal\ResolvedValue;
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
	 * @return T|null
	 */
	public function Instance(int|string $segment, string $className): ?object
	{
		return TypeCheck::Instance($this->value($segment), $className);
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @return list<T>
	 */
	public function InstanceList(int|string $segment, string $className): array
	{
		return TypeCheck::InstanceList($this->value($segment), $className) ?? [];
	}

	/** @return list<string> */
	public function StringList(int|string $segment): array
	{
		$resolved = $this->resolveValue($segment);
		return $resolved->Exists
			? ($this->rules->NormalizeStringList($resolved->Value) ?? [])
			: [];
	}

	/** @return list<int> */
	public function IntList(int|string $segment): array
	{
		$resolved = $this->resolveValue($segment);
		return $resolved->Exists
			? ($this->rules->NormalizeIntList($resolved->Value) ?? [])
			: [];
	}

	/** @return list<float> */
	public function FloatList(int|string $segment): array
	{
		$resolved = $this->resolveValue($segment);
		return $resolved->Exists
			? ($this->rules->NormalizeFloatList($resolved->Value) ?? [])
			: [];
	}

	/** @return list<bool> */
	public function BoolList(int|string $segment): array
	{
		$resolved = $this->resolveValue($segment);
		return $resolved->Exists
			? ($this->rules->NormalizeBoolList($resolved->Value) ?? [])
			: [];
	}

	public function String(int|string $segment): string
	{
		$resolved = $this->resolveValue($segment);
		return $resolved->Exists
			? ($this->rules->NormalizeString($resolved->Value) ?? '')
			: '';
	}

	public function Int(int|string $segment): ?int
	{
		$resolved = $this->resolveValue($segment);
		return $resolved->Exists
			? $this->rules->NormalizeInt($resolved->Value)
			: null;
	}

	public function Float(int|string $segment): ?float
	{
		$resolved = $this->resolveValue($segment);
		return $resolved->Exists
			? $this->rules->NormalizeFloat($resolved->Value)
			: null;
	}

	public function Bool(int|string $segment): ?bool
	{
		$resolved = $this->resolveValue($segment);
		return $resolved->Exists
			? $this->rules->NormalizeBool($resolved->Value)
			: null;
	}

	/**
	 * @template T of \BackedEnum
	 * @param class-string<T> $enumClass
	 * @return T|null
	 */
	public function BackedEnum(int|string $segment, string $enumClass): ?\BackedEnum
	{
		$resolved = $this->resolveValue($segment);
		return $resolved->Exists
			? $this->rules->NormalizeBackedEnum($resolved->Value, $enumClass)
			: null;
	}

	/**
	 * @template T of \BackedEnum
	 * @param class-string<T> $enumClass
	 * @return list<T>
	 */
	public function BackedEnumList(int|string $segment, string $enumClass): array
	{
		$resolved = $this->resolveValue($segment);
		return $resolved->Exists
			? ($this->rules->NormalizeBackedEnumList($resolved->Value, $enumClass) ?? [])
			: [];
	}

	public function Array(int|string $segment): array
	{
		return TypeCheck::Array($this->value($segment)) ?? [];
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

	public function ImgData(int|string $segment): ?ImgData
	{
		return $this->Instance($segment, ImgData::class);
	}

	public function LinkData(int|string $segment): ?LinkData
	{
		return $this->Instance($segment, LinkData::class);
	}

	public function ChoiceData(int|string $segment): ?ChoiceData
	{
		return $this->Instance($segment, ChoiceData::class);
	}

	public function FileData(int|string $segment): ?FileData
	{
		return $this->Instance($segment, FileData::class);
	}

	public function TimeData(int|string $segment): ?TimeData
	{
		return $this->Instance($segment, TimeData::class);
	}

	/** @return list<ImgData> */
	public function ImgDataList(int|string $segment): array
	{
		return $this->InstanceList($segment, ImgData::class);
	}

	/** @return list<LinkData> */
	public function LinkDataList(int|string $segment): array
	{
		return $this->InstanceList($segment, LinkData::class);
	}

	/** @return list<ChoiceData> */
	public function ChoiceDataList(int|string $segment): array
	{
		return $this->InstanceList($segment, ChoiceData::class);
	}

	/** @return list<FileData> */
	public function FileDataList(int|string $segment): array
	{
		return $this->InstanceList($segment, FileData::class);
	}

	/** @return list<TimeData> */
	public function TimeDataList(int|string $segment): array
	{
		return $this->InstanceList($segment, TimeData::class);
	}
}
