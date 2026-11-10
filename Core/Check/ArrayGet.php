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
	 * @return T|null
	 */
	public function Instance(int|string $key, string $className): ?object
	{
		return TypeCheck::Instance($this->value($key), $className);
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @return list<T>
	 */
	public function InstanceList(int|string $key, string $className): array
	{
		return TypeCheck::InstanceList($this->value($key), $className) ?? [];
	}

	/** @return list<string> */
	public function StringList(int|string $key): array
	{
		$resolved = $this->resolveValue($key);
		return $resolved->Exists
			? ($this->rules->NormalizeStringList($resolved->Value) ?? [])
			: [];
	}

	/** @return list<int> */
	public function IntList(int|string $key): array
	{
		$resolved = $this->resolveValue($key);
		return $resolved->Exists
			? ($this->rules->NormalizeIntList($resolved->Value) ?? [])
			: [];
	}

	/** @return list<float> */
	public function FloatList(int|string $key): array
	{
		$resolved = $this->resolveValue($key);
		return $resolved->Exists
			? ($this->rules->NormalizeFloatList($resolved->Value) ?? [])
			: [];
	}

	/** @return list<bool> */
	public function BoolList(int|string $key): array
	{
		$resolved = $this->resolveValue($key);
		return $resolved->Exists
			? ($this->rules->NormalizeBoolList($resolved->Value) ?? [])
			: [];
	}

	public function String(int|string $key): string
	{
		$resolved = $this->resolveValue($key);
		return $resolved->Exists
			? ($this->rules->NormalizeString($resolved->Value) ?? '')
			: '';
	}

	public function Int(int|string $key): ?int
	{
		$resolved = $this->resolveValue($key);
		return $resolved->Exists
			? $this->rules->NormalizeInt($resolved->Value)
			: null;
	}

	public function Float(int|string $key): ?float
	{
		$resolved = $this->resolveValue($key);
		return $resolved->Exists
			? $this->rules->NormalizeFloat($resolved->Value)
			: null;
	}

	public function Bool(int|string $key): ?bool
	{
		$resolved = $this->resolveValue($key);
		return $resolved->Exists
			? $this->rules->NormalizeBool($resolved->Value)
			: null;
	}

	/**
	 * @template T of \BackedEnum
	 * @param class-string<T> $enumClass
	 * @return T|null
	 */
	public function BackedEnum(int|string $key, string $enumClass): ?\BackedEnum
	{
		$resolved = $this->resolveValue($key);
		return $resolved->Exists
			? $this->rules->NormalizeBackedEnum($resolved->Value, $enumClass)
			: null;
	}

	/**
	 * @template T of \BackedEnum
	 * @param class-string<T> $enumClass
	 * @return list<T>
	 */
	public function BackedEnumList(int|string $key, string $enumClass): array
	{
		$resolved = $this->resolveValue($key);
		return $resolved->Exists
			? ($this->rules->NormalizeBackedEnumList($resolved->Value, $enumClass) ?? [])
			: [];
	}

	public function Array(int|string $key): array
	{
		return TypeCheck::Array($this->value($key)) ?? [];
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

	public function ImgData(int|string $key): ?ImgData
	{
		return $this->Instance($key, ImgData::class);
	}

	public function LinkData(int|string $key): ?LinkData
	{
		return $this->Instance($key, LinkData::class);
	}

	public function ChoiceData(int|string $key): ?ChoiceData
	{
		return $this->Instance($key, ChoiceData::class);
	}

	public function FileData(int|string $key): ?FileData
	{
		return $this->Instance($key, FileData::class);
	}

	public function TimeData(int|string $key): ?TimeData
	{
		return $this->Instance($key, TimeData::class);
	}

	/** @return list<ImgData> */
	public function ImgDataList(int|string $key): array
	{
		return $this->InstanceList($key, ImgData::class);
	}

	/** @return list<LinkData> */
	public function LinkDataList(int|string $key): array
	{
		return $this->InstanceList($key, LinkData::class);
	}

	/** @return list<ChoiceData> */
	public function ChoiceDataList(int|string $key): array
	{
		return $this->InstanceList($key, ChoiceData::class);
	}

	/** @return list<FileData> */
	public function FileDataList(int|string $key): array
	{
		return $this->InstanceList($key, FileData::class);
	}

	/** @return list<TimeData> */
	public function TimeDataList(int|string $key): array
	{
		return $this->InstanceList($key, TimeData::class);
	}
}
