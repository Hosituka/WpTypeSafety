<?php
declare(strict_types=1);

namespace WpTypeSafety\Internal\TreeTypeReader;

use WpTypeSafety\Internal\TreeTypeReader\Data\ResolvedValue;

/** @internal 木構造の探索口に共通する型付きGetterを実装します。 */
abstract class TreeTypeReader
{
	private ValueRules $rules;
	private ValueReader $reader;

	protected function initialize(ValueMode|ValueRules $modeOrRules): void
	{
		$this->rules = $modeOrRules instanceof ValueRules
			? clone $modeOrRules
			: ValueRules::FromInternalMode($modeOrRules);
		$this->reader = new ValueReader($this->rules);
	}

	abstract protected function resolveValue(int|string $segment): ResolvedValue;

	abstract protected function createChild(mixed $source): static;

	/**
	 * List Getter用の取得元を解決します。
	 * 通常の木構造では単一値と同じノードを使い、派生取得口だけ取得方法を差し替えます。
	 */
	protected function resolveListValue(int|string $segment): ResolvedValue
	{
		return $this->resolveValue($segment);
	}

	protected function value(int|string $segment): mixed
	{
		return $this->resolveValue($segment)->Value;
	}

	protected function rulesSnapshot(): ValueRules
	{
		return clone $this->rules;
	}

	protected function copyWithMode(ValueMode $mode): static
	{
		$reader = clone $this;
		$reader->initialize($mode);
		return $reader;
	}

	protected function copyWithRules(ValueRules $rules): static
	{
		$reader = clone $this;
		$reader->initialize($rules);
		return $reader;
	}

	/**
	 * 指定ノードの値を変換し、戻り値を新しい探索対象として使用します。
	 *
	 * @param callable(mixed): mixed $transformer
	 */
	protected function transformValue(int|string $segment, callable $transformer): static
	{
		$resolved = $this->resolveValue($segment);
		$transformed = $resolved->Exists
			? $transformer($resolved->Value)
			: null;

		return $this->createChild($transformed);
	}

	protected function valueExists(int|string $segment): bool
	{
		return $this->resolveValue($segment)->Exists;
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @param (callable(T): bool)|null $predicate
	 * @return T|null
	 */
	protected function readInstance(int|string $segment, string $className, ?callable $predicate = null): ?object
	{
		$resolved = $this->resolveValue($segment);
		return $resolved->Exists
			? $this->reader->Instance($resolved->Value, $className, $predicate)
			: null;
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @param (callable(T): bool)|null $predicate
	 * @return list<T>
	 */
	protected function readInstanceList(int|string $segment, string $className, ?callable $predicate = null): array
	{
		$resolved = $this->resolveListValue($segment);
		return $resolved->Exists
			? ($this->reader->InstanceList($resolved->Value, $className, $predicate) ?? [])
			: [];
	}

	/** @param (callable(string): bool)|null $predicate */
	protected function readString(int|string $segment, ?callable $predicate = null): string
	{
		$resolved = $this->resolveValue($segment);
		return $resolved->Exists
			? ($this->reader->String($resolved->Value, $predicate) ?? '')
			: '';
	}

	/** @param (callable(int): bool)|null $predicate */
	protected function readInt(int|string $segment, ?callable $predicate = null): ?int
	{
		$resolved = $this->resolveValue($segment);
		return $resolved->Exists
			? $this->reader->Int($resolved->Value, $predicate)
			: null;
	}

	/** @param (callable(float): bool)|null $predicate */
	protected function readFloat(int|string $segment, ?callable $predicate = null): ?float
	{
		$resolved = $this->resolveValue($segment);
		return $resolved->Exists
			? $this->reader->Float($resolved->Value, $predicate)
			: null;
	}

	/** @param (callable(bool): bool)|null $predicate */
	protected function readBool(int|string $segment, ?callable $predicate = null): ?bool
	{
		$resolved = $this->resolveValue($segment);
		return $resolved->Exists
			? $this->reader->Bool($resolved->Value, $predicate)
			: null;
	}

	/**
	 * @template T of \BackedEnum
	 * @param class-string<T> $enumClass
	 * @param (callable(T): bool)|null $predicate
	 * @return T|null
	 */
	protected function readBackedEnum(int|string $segment, string $enumClass, ?callable $predicate = null): ?\BackedEnum
	{
		$resolved = $this->resolveValue($segment);
		return $resolved->Exists
			? $this->reader->BackedEnum($resolved->Value, $enumClass, $predicate)
			: null;
	}

	/**
	 * @param (callable(string): bool)|null $predicate
	 * @return list<string>
	 */
	protected function readStringList(int|string $segment, ?callable $predicate = null): array
	{
		$resolved = $this->resolveListValue($segment);
		return $resolved->Exists
			? ($this->reader->StringList($resolved->Value, $predicate) ?? [])
			: [];
	}

	/**
	 * @param (callable(int): bool)|null $predicate
	 * @return list<int>
	 */
	protected function readIntList(int|string $segment, ?callable $predicate = null): array
	{
		$resolved = $this->resolveListValue($segment);
		return $resolved->Exists
			? ($this->reader->IntList($resolved->Value, $predicate) ?? [])
			: [];
	}

	/**
	 * @param (callable(float): bool)|null $predicate
	 * @return list<float>
	 */
	protected function readFloatList(int|string $segment, ?callable $predicate = null): array
	{
		$resolved = $this->resolveListValue($segment);
		return $resolved->Exists
			? ($this->reader->FloatList($resolved->Value, $predicate) ?? [])
			: [];
	}

	/**
	 * @param (callable(bool): bool)|null $predicate
	 * @return list<bool>
	 */
	protected function readBoolList(int|string $segment, ?callable $predicate = null): array
	{
		$resolved = $this->resolveListValue($segment);
		return $resolved->Exists
			? ($this->reader->BoolList($resolved->Value, $predicate) ?? [])
			: [];
	}

	/**
	 * @template T of \BackedEnum
	 * @param class-string<T> $enumClass
	 * @param (callable(T): bool)|null $predicate
	 * @return list<T>
	 */
	protected function readBackedEnumList(int|string $segment, string $enumClass, ?callable $predicate = null): array
	{
		$resolved = $this->resolveListValue($segment);
		return $resolved->Exists
			? ($this->reader->BackedEnumList($resolved->Value, $enumClass, $predicate) ?? [])
			: [];
	}

	/** @param (callable(array<array-key, mixed>): bool)|null $predicate */
	protected function readArray(int|string $segment, ?callable $predicate = null): array
	{
		$resolved = $this->resolveValue($segment);
		return $resolved->Exists
			? ($this->reader->Array($resolved->Value, $predicate) ?? [])
			: [];
	}

	protected function nestedValue(int|string $segment): static
	{
		$resolved = $this->resolveValue($segment);
		return $this->createChild(
			$resolved->Exists ? $resolved->Value : null,
		);
	}

	protected function nestedValuePath(int|string $segment, int|string ...$segments): static
	{
		$nestedGet = $this->nestedValue($segment);
		foreach ($segments as $nestedSegment) {
			$nestedGet = $nestedGet->nestedValue($nestedSegment);
		}

		return $nestedGet;
	}
}
