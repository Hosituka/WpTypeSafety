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

	private function value(string $key): mixed
	{
		return $this->resolveValue($key)->Value;
	}

	private function resolveValue(string $key): ResolvedValue
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
	 * @template T of object
	 * @param class-string<T> $className
	 * @return T|null
	 */
	public function Instance(string $key, string $className): ?object
	{
		return TypeCheck::Instance($this->value($key), $className);
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @return list<T>
	 */
	public function InstanceList(string $key, string $className): array
	{
		return TypeCheck::InstanceList($this->value($key), $className) ?? [];
	}

	/** @return list<string> */
	public function StringList(string $key): array
	{
		$resolved = $this->resolveValue($key);
		return $resolved->Exists
			? ($this->rules->NormalizeStringList($resolved->Value) ?? [])
			: [];
	}

	/** @return list<int> */
	public function IntList(string $key): array
	{
		$resolved = $this->resolveValue($key);
		return $resolved->Exists
			? ($this->rules->NormalizeIntList($resolved->Value) ?? [])
			: [];
	}

	/** @return list<float> */
	public function FloatList(string $key): array
	{
		$resolved = $this->resolveValue($key);
		return $resolved->Exists
			? ($this->rules->NormalizeFloatList($resolved->Value) ?? [])
			: [];
	}

	/** @return list<bool> */
	public function BoolList(string $key): array
	{
		$resolved = $this->resolveValue($key);
		return $resolved->Exists
			? ($this->rules->NormalizeBoolList($resolved->Value) ?? [])
			: [];
	}

	public function String(string $key): string
	{
		$resolved = $this->resolveValue($key);
		return $resolved->Exists
			? ($this->rules->NormalizeString($resolved->Value) ?? '')
			: '';
	}

	public function Int(string $key): ?int
	{
		$resolved = $this->resolveValue($key);
		return $resolved->Exists
			? $this->rules->NormalizeInt($resolved->Value)
			: null;
	}

	public function Float(string $key): ?float
	{
		$resolved = $this->resolveValue($key);
		return $resolved->Exists
			? $this->rules->NormalizeFloat($resolved->Value)
			: null;
	}

	public function Bool(string $key): ?bool
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
	public function BackedEnum(string $key, string $enumClass): ?\BackedEnum
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
	public function BackedEnumList(string $key, string $enumClass): array
	{
		$resolved = $this->resolveValue($key);
		return $resolved->Exists
			? ($this->rules->NormalizeBackedEnumList($resolved->Value, $enumClass) ?? [])
			: [];
	}

	public function Array(string $key): array
	{
		return TypeCheck::Array($this->value($key)) ?? [];
	}

	public function Nested(string $key): self
	{
		return new self($this->value($key), $this->rules);
	}

	public function NestedPath(string $key, string ...$keys): self
	{
		$nestedGet = $this->Nested($key);
		foreach ($keys as $nestedKey) {
			$nestedGet = $nestedGet->Nested($nestedKey);
		}

		return $nestedGet;
	}

	public function NestedObject(string $key): ObjectGet
	{
		return new ObjectGet($this->value($key), $this->rules);
	}

	/** @return list<self> */
	public function NestedList(string $key): array
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
	public function NestedObjectList(string $key): array
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

	public function ImgData(string $key): ?ImgData
	{
		return $this->Instance($key, ImgData::class);
	}

	public function LinkData(string $key): ?LinkData
	{
		return $this->Instance($key, LinkData::class);
	}

	public function ChoiceData(string $key): ?ChoiceData
	{
		return $this->Instance($key, ChoiceData::class);
	}

	public function FileData(string $key): ?FileData
	{
		return $this->Instance($key, FileData::class);
	}

	public function TimeData(string $key): ?TimeData
	{
		return $this->Instance($key, TimeData::class);
	}

	/** @return list<ImgData> */
	public function ImgDataList(string $key): array
	{
		return $this->InstanceList($key, ImgData::class);
	}

	/** @return list<LinkData> */
	public function LinkDataList(string $key): array
	{
		return $this->InstanceList($key, LinkData::class);
	}

	/** @return list<ChoiceData> */
	public function ChoiceDataList(string $key): array
	{
		return $this->InstanceList($key, ChoiceData::class);
	}

	/** @return list<FileData> */
	public function FileDataList(string $key): array
	{
		return $this->InstanceList($key, FileData::class);
	}

	/** @return list<TimeData> */
	public function TimeDataList(string $key): array
	{
		return $this->InstanceList($key, TimeData::class);
	}
}
