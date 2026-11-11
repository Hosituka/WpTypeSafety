<?php
declare(strict_types=1);

namespace WpTypeSafety\Internal\TreeTypeReader;

use WpTypeSafety\Internal\TreeTypeReader\Data\ResolvedValue;
use WpTypeSafety\Internal\TreeTypeReader\Internal\ValueResolver;

/** @internal 配列とオブジェクトが混在する木構造を読む汎用実装です。 */
class TreeGet extends TreeTypeReader
{
	private mixed $source;

	public function __construct(
		mixed $source,
		ValueMode|ValueRules $modeOrRules = ValueMode::Strict,
	) {
		$this->source = $source;
		$this->initialize($modeOrRules);
	}

	protected function resolveValue(int|string $segment): ResolvedValue
	{
		return ValueResolver::Resolve($this->source, $segment);
	}

	protected function createChild(mixed $source): static
	{
		$child = clone $this;
		$child->source = $source;
		return $child;
	}

	protected function sourceValue(): mixed
	{
		return $this->source;
	}

	/** 派生取得口が、同じインスタンスの探索元だけを差し替えるために使用します。 */
	protected function replaceSource(mixed $source): void
	{
		$this->source = $source;
	}

	/** @param callable(mixed): mixed $transformer */
	public function Transform(int|string $segment, callable $transformer): static
	{
		return $this->transformValue($segment, $transformer);
	}

	public function Exists(int|string $segment): bool
	{
		return $this->valueExists($segment);
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @param (callable(T): bool)|null $predicate
	 * @return T|null
	 */
	public function Instance(int|string $segment, string $className, ?callable $predicate = null): ?object
	{
		return $this->readInstance($segment, $className, $predicate);
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @param (callable(T): bool)|null $predicate
	 * @return list<T>
	 */
	public function InstanceList(int|string $segment, string $className, ?callable $predicate = null): array
	{
		return $this->readInstanceList($segment, $className, $predicate);
	}

	/** @param (callable(string): bool)|null $predicate */
	public function String(int|string $segment, ?callable $predicate = null): string
	{
		return $this->readString($segment, $predicate);
	}

	/** @param (callable(int): bool)|null $predicate */
	public function Int(int|string $segment, ?callable $predicate = null): ?int
	{
		return $this->readInt($segment, $predicate);
	}

	/** @param (callable(float): bool)|null $predicate */
	public function Float(int|string $segment, ?callable $predicate = null): ?float
	{
		return $this->readFloat($segment, $predicate);
	}

	/** @param (callable(bool): bool)|null $predicate */
	public function Bool(int|string $segment, ?callable $predicate = null): ?bool
	{
		return $this->readBool($segment, $predicate);
	}

	/**
	 * @template T of \BackedEnum
	 * @param class-string<T> $enumClass
	 * @param (callable(T): bool)|null $predicate
	 * @return T|null
	 */
	public function BackedEnum(int|string $segment, string $enumClass, ?callable $predicate = null): ?\BackedEnum
	{
		return $this->readBackedEnum($segment, $enumClass, $predicate);
	}

	/**
	 * @param (callable(string): bool)|null $predicate
	 * @return list<string>
	 */
	public function StringList(int|string $segment, ?callable $predicate = null): array
	{
		return $this->readStringList($segment, $predicate);
	}

	/**
	 * @param (callable(int): bool)|null $predicate
	 * @return list<int>
	 */
	public function IntList(int|string $segment, ?callable $predicate = null): array
	{
		return $this->readIntList($segment, $predicate);
	}

	/**
	 * @param (callable(float): bool)|null $predicate
	 * @return list<float>
	 */
	public function FloatList(int|string $segment, ?callable $predicate = null): array
	{
		return $this->readFloatList($segment, $predicate);
	}

	/**
	 * @param (callable(bool): bool)|null $predicate
	 * @return list<bool>
	 */
	public function BoolList(int|string $segment, ?callable $predicate = null): array
	{
		return $this->readBoolList($segment, $predicate);
	}

	/**
	 * @template T of \BackedEnum
	 * @param class-string<T> $enumClass
	 * @param (callable(T): bool)|null $predicate
	 * @return list<T>
	 */
	public function BackedEnumList(int|string $segment, string $enumClass, ?callable $predicate = null): array
	{
		return $this->readBackedEnumList($segment, $enumClass, $predicate);
	}

	/** @param (callable(array<array-key, mixed>): bool)|null $predicate */
	public function Array(int|string $segment, ?callable $predicate = null): array
	{
		return $this->readArray($segment, $predicate);
	}

	public function Nested(int|string $segment): static
	{
		return $this->nestedValue($segment);
	}

	public function NestedPath(int|string $segment, int|string ...$segments): static
	{
		return $this->nestedValuePath($segment, ...$segments);
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
			? new ArrayGet($this->source, $this->rulesSnapshot())
			: null;
	}

	public function AsObjectGet(): ?ObjectGet
	{
		return is_object($this->source)
			? new ObjectGet($this->source, $this->rulesSnapshot())
			: null;
	}

	public function NestedArray(int|string $segment): ArrayGet
	{
		return new ArrayGet($this->value($segment), $this->rulesSnapshot());
	}

	public function NestedObject(int|string $segment): ObjectGet
	{
		return new ObjectGet($this->value($segment), $this->rulesSnapshot());
	}

	/** @return list<static> */
	public function NestedList(int|string $segment): array
	{
		$items = [];
		foreach ($this->Array($segment) as $value) {
			if (! is_array($value) && ! is_object($value))
			{continue;}

			$items[] = $this->createChild($value);
		}

		return $items;
	}

	/** @return list<ArrayGet> */
	public function NestedArrayList(int|string $segment): array
	{
		$items = [];
		foreach ($this->Array($segment) as $value) {
			if (! is_array($value))
			{continue;}

			$items[] = new ArrayGet($value, $this->rulesSnapshot());
		}

		return $items;
	}

	/** @return list<ObjectGet> */
	public function NestedObjectList(int|string $segment): array
	{
		$items = [];
		foreach ($this->Array($segment) as $value) {
			if (! is_object($value))
			{continue;}

			$items[] = new ObjectGet($value, $this->rulesSnapshot());
		}

		return $items;
	}
}
