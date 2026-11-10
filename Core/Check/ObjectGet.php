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

/** publicインスタンスプロパティを動的に読み、設定した基準で値を取得します。 */
final class ObjectGet
{
	private readonly ?object $source;
	private readonly ValueRules $rules;

	public function __construct(
		mixed $source,
		ValueMode|ValueRules $modeOrRules = ValueMode::Strict,
	)
	{
		$this->source = is_object($source) ? $source : null;
		$this->rules = $modeOrRules instanceof ValueRules
			? clone $modeOrRules
			: ValueRules::FromMode($modeOrRules);
	}

	private function value(string $propertyName): mixed
	{
		return $this->resolveValue($propertyName)->Value;
	}

	private function resolveValue(string $propertyName): ResolvedValue
	{
		return ValueResolver::ResolveObject($this->source, $propertyName);
	}

	public function WithMode(ValueMode $mode): self
	{
		return new self($this->source, $mode);
	}

	public function WithRules(ValueRules $rules): self
	{
		return new self($this->source, $rules);
	}

	public function Exists(string $propertyName): bool
	{
		return $this->resolveValue($propertyName)->Exists;
	}

	public function ExistsPath(string $propertyName, string ...$propertyNames): bool
	{
		return ValueResolver::ResolveObjectPath(
			$this->source,
			$propertyName,
			...$propertyNames,
		)->Exists;
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @return T|null
	 */
	public function Instance(string $propertyName, string $className): ?object
	{
		return TypeCheck::Instance($this->value($propertyName), $className);
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @return list<T>
	 */
	public function InstanceList(string $propertyName, string $className): array
	{
		return TypeCheck::InstanceList($this->value($propertyName), $className) ?? [];
	}

	/** @return list<string> */
	public function StringList(string $propertyName): array
	{
		$resolved = $this->resolveValue($propertyName);
		return $resolved->Exists
			? ($this->rules->NormalizeStringList($resolved->Value) ?? [])
			: [];
	}

	/** @return list<int> */
	public function IntList(string $propertyName): array
	{
		$resolved = $this->resolveValue($propertyName);
		return $resolved->Exists
			? ($this->rules->NormalizeIntList($resolved->Value) ?? [])
			: [];
	}

	/** @return list<float> */
	public function FloatList(string $propertyName): array
	{
		$resolved = $this->resolveValue($propertyName);
		return $resolved->Exists
			? ($this->rules->NormalizeFloatList($resolved->Value) ?? [])
			: [];
	}

	/** @return list<bool> */
	public function BoolList(string $propertyName): array
	{
		$resolved = $this->resolveValue($propertyName);
		return $resolved->Exists
			? ($this->rules->NormalizeBoolList($resolved->Value) ?? [])
			: [];
	}

	public function String(string $propertyName): string
	{
		$resolved = $this->resolveValue($propertyName);
		return $resolved->Exists
			? ($this->rules->NormalizeString($resolved->Value) ?? '')
			: '';
	}

	public function Int(string $propertyName): ?int
	{
		$resolved = $this->resolveValue($propertyName);
		return $resolved->Exists
			? $this->rules->NormalizeInt($resolved->Value)
			: null;
	}

	public function Float(string $propertyName): ?float
	{
		$resolved = $this->resolveValue($propertyName);
		return $resolved->Exists
			? $this->rules->NormalizeFloat($resolved->Value)
			: null;
	}

	public function Bool(string $propertyName): ?bool
	{
		$resolved = $this->resolveValue($propertyName);
		return $resolved->Exists
			? $this->rules->NormalizeBool($resolved->Value)
			: null;
	}

	/**
	 * @template T of \BackedEnum
	 * @param class-string<T> $enumClass
	 * @return T|null
	 */
	public function BackedEnum(string $propertyName, string $enumClass): ?\BackedEnum
	{
		$resolved = $this->resolveValue($propertyName);
		return $resolved->Exists
			? $this->rules->NormalizeBackedEnum($resolved->Value, $enumClass)
			: null;
	}

	/**
	 * @template T of \BackedEnum
	 * @param class-string<T> $enumClass
	 * @return list<T>
	 */
	public function BackedEnumList(string $propertyName, string $enumClass): array
	{
		$resolved = $this->resolveValue($propertyName);
		return $resolved->Exists
			? ($this->rules->NormalizeBackedEnumList($resolved->Value, $enumClass) ?? [])
			: [];
	}

	public function Array(string $propertyName): array
	{
		return TypeCheck::Array($this->value($propertyName)) ?? [];
	}

	public function Nested(string $propertyName): self
	{
		return new self($this->value($propertyName), $this->rules);
	}

	public function NestedPath(string $propertyName, string ...$propertyNames): self
	{
		$nestedGet = $this->Nested($propertyName);
		foreach ($propertyNames as $nestedPropertyName) {
			$nestedGet = $nestedGet->Nested($nestedPropertyName);
		}

		return $nestedGet;
	}

	public function NestedArray(string $propertyName): ArrayGet
	{
		return new ArrayGet($this->value($propertyName), $this->rules);
	}

	/** @return list<self> */
	public function NestedList(string $propertyName): array
	{
		$items = [];
		foreach ($this->Array($propertyName) as $value) {
			if (! is_object($value)) {
				continue;
			}

			$items[] = new self($value, $this->rules);
		}

		return $items;
	}

	/** @return list<ArrayGet> */
	public function NestedArrayList(string $propertyName): array
	{
		$items = [];
		foreach ($this->Array($propertyName) as $value) {
			if (! is_array($value)) {
				continue;
			}

			$items[] = new ArrayGet($value, $this->rules);
		}

		return $items;
	}

	public function ImgData(string $propertyName): ?ImgData
	{
		return $this->Instance($propertyName, ImgData::class);
	}

	public function LinkData(string $propertyName): ?LinkData
	{
		return $this->Instance($propertyName, LinkData::class);
	}

	public function ChoiceData(string $propertyName): ?ChoiceData
	{
		return $this->Instance($propertyName, ChoiceData::class);
	}

	public function FileData(string $propertyName): ?FileData
	{
		return $this->Instance($propertyName, FileData::class);
	}

	public function TimeData(string $propertyName): ?TimeData
	{
		return $this->Instance($propertyName, TimeData::class);
	}

	/** @return list<ImgData> */
	public function ImgDataList(string $propertyName): array
	{
		return $this->InstanceList($propertyName, ImgData::class);
	}

	/** @return list<LinkData> */
	public function LinkDataList(string $propertyName): array
	{
		return $this->InstanceList($propertyName, LinkData::class);
	}

	/** @return list<ChoiceData> */
	public function ChoiceDataList(string $propertyName): array
	{
		return $this->InstanceList($propertyName, ChoiceData::class);
	}

	/** @return list<FileData> */
	public function FileDataList(string $propertyName): array
	{
		return $this->InstanceList($propertyName, FileData::class);
	}

	/** @return list<TimeData> */
	public function TimeDataList(string $propertyName): array
	{
		return $this->InstanceList($propertyName, TimeData::class);
	}
}
