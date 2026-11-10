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

	/**
	 * 指定プロパティの値を変換し、戻り値を新しい探索対象として使用します。
	 *
	 * @param callable(mixed): mixed $transformer
	 */
	public function Transform(string $propertyName, callable $transformer): self
	{
		$resolved = $this->resolveValue($propertyName);
		$transformed = $resolved->Exists
			? $transformer($resolved->Value)
			: null;

		return new self($transformed, $this->rules);
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
	 * @param (callable(T): bool)|null $predicate
	 * @return T|null
	 */
	public function Instance(string $propertyName, string $className, ?callable $predicate = null): ?object
	{
		$normalized = TypeCheck::Instance($this->value($propertyName), $className);
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
	public function InstanceList(string $propertyName, string $className, ?callable $predicate = null): array
	{
		$normalized = TypeCheck::InstanceList($this->value($propertyName), $className);
		return $normalized !== null
			? ValuePredicate::Filter($normalized, $predicate)
			: [];
	}

	/**
	 * @param (callable(string): bool)|null $predicate
	 * @return list<string>
	 */
	public function StringList(string $propertyName, ?callable $predicate = null): array
	{
		$resolved = $this->resolveValue($propertyName);
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
	public function IntList(string $propertyName, ?callable $predicate = null): array
	{
		$resolved = $this->resolveValue($propertyName);
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
	public function FloatList(string $propertyName, ?callable $predicate = null): array
	{
		$resolved = $this->resolveValue($propertyName);
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
	public function BoolList(string $propertyName, ?callable $predicate = null): array
	{
		$resolved = $this->resolveValue($propertyName);
		$normalized = $resolved->Exists
			? $this->rules->NormalizeBoolList($resolved->Value)
			: null;
		return $normalized !== null
			? ValuePredicate::Filter($normalized, $predicate)
			: [];
	}

	/** @param (callable(string): bool)|null $predicate */
	public function String(string $propertyName, ?callable $predicate = null): string
	{
		$resolved = $this->resolveValue($propertyName);
		$normalized = $resolved->Exists
			? $this->rules->NormalizeString($resolved->Value)
			: null;
		return $normalized !== null && ValuePredicate::Passes($normalized, $predicate)
			? $normalized
			: '';
	}

	/** @param (callable(int): bool)|null $predicate */
	public function Int(string $propertyName, ?callable $predicate = null): ?int
	{
		$resolved = $this->resolveValue($propertyName);
		$normalized = $resolved->Exists
			? $this->rules->NormalizeInt($resolved->Value)
			: null;
		return $normalized !== null && ValuePredicate::Passes($normalized, $predicate)
			? $normalized
			: null;
	}

	/** @param (callable(float): bool)|null $predicate */
	public function Float(string $propertyName, ?callable $predicate = null): ?float
	{
		$resolved = $this->resolveValue($propertyName);
		$normalized = $resolved->Exists
			? $this->rules->NormalizeFloat($resolved->Value)
			: null;
		return $normalized !== null && ValuePredicate::Passes($normalized, $predicate)
			? $normalized
			: null;
	}

	/** @param (callable(bool): bool)|null $predicate */
	public function Bool(string $propertyName, ?callable $predicate = null): ?bool
	{
		$resolved = $this->resolveValue($propertyName);
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
	public function BackedEnum(string $propertyName, string $enumClass, ?callable $predicate = null): ?\BackedEnum
	{
		$resolved = $this->resolveValue($propertyName);
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
	public function BackedEnumList(string $propertyName, string $enumClass, ?callable $predicate = null): array
	{
		$resolved = $this->resolveValue($propertyName);
		$normalized = $resolved->Exists
			? $this->rules->NormalizeBackedEnumList($resolved->Value, $enumClass)
			: null;
		return $normalized !== null
			? ValuePredicate::Filter($normalized, $predicate)
			: [];
	}

	/** @param (callable(array<array-key, mixed>): bool)|null $predicate */
	public function Array(string $propertyName, ?callable $predicate = null): array
	{
		$resolved = $this->resolveValue($propertyName);
		$normalized = $resolved->Exists
			? TypeCheck::Array($resolved->Value)
			: null;
		return $normalized !== null && ValuePredicate::Passes($normalized, $predicate)
			? $normalized
			: [];
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

	/** @param (callable(ImgData): bool)|null $predicate */
	public function ImgData(string $propertyName, ?callable $predicate = null): ?ImgData
	{
		return $this->Instance($propertyName, ImgData::class, $predicate);
	}

	/** @param (callable(LinkData): bool)|null $predicate */
	public function LinkData(string $propertyName, ?callable $predicate = null): ?LinkData
	{
		return $this->Instance($propertyName, LinkData::class, $predicate);
	}

	/** @param (callable(ChoiceData): bool)|null $predicate */
	public function ChoiceData(string $propertyName, ?callable $predicate = null): ?ChoiceData
	{
		return $this->Instance($propertyName, ChoiceData::class, $predicate);
	}

	/** @param (callable(FileData): bool)|null $predicate */
	public function FileData(string $propertyName, ?callable $predicate = null): ?FileData
	{
		return $this->Instance($propertyName, FileData::class, $predicate);
	}

	/** @param (callable(TimeData): bool)|null $predicate */
	public function TimeData(string $propertyName, ?callable $predicate = null): ?TimeData
	{
		return $this->Instance($propertyName, TimeData::class, $predicate);
	}

	/**
	 * @param (callable(ImgData): bool)|null $predicate
	 * @return list<ImgData>
	 */
	public function ImgDataList(string $propertyName, ?callable $predicate = null): array
	{
		return $this->InstanceList($propertyName, ImgData::class, $predicate);
	}

	/**
	 * @param (callable(LinkData): bool)|null $predicate
	 * @return list<LinkData>
	 */
	public function LinkDataList(string $propertyName, ?callable $predicate = null): array
	{
		return $this->InstanceList($propertyName, LinkData::class, $predicate);
	}

	/**
	 * @param (callable(ChoiceData): bool)|null $predicate
	 * @return list<ChoiceData>
	 */
	public function ChoiceDataList(string $propertyName, ?callable $predicate = null): array
	{
		return $this->InstanceList($propertyName, ChoiceData::class, $predicate);
	}

	/**
	 * @param (callable(FileData): bool)|null $predicate
	 * @return list<FileData>
	 */
	public function FileDataList(string $propertyName, ?callable $predicate = null): array
	{
		return $this->InstanceList($propertyName, FileData::class, $predicate);
	}

	/**
	 * @param (callable(TimeData): bool)|null $predicate
	 * @return list<TimeData>
	 */
	public function TimeDataList(string $propertyName, ?callable $predicate = null): array
	{
		return $this->InstanceList($propertyName, TimeData::class, $predicate);
	}
}
