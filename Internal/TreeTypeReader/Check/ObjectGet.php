<?php
declare(strict_types=1);

namespace WpTypeSafety\Internal\TreeTypeReader\Check;

use WpTypeSafety\Internal\TreeTypeReader\ResolvedValue;
use WpTypeSafety\Internal\TreeTypeReader\Internal\ValueResolver;

/** @internal publicインスタンスプロパティから値を取得する汎用実装です。 */
class ObjectGet extends TreeTypeReader
{
	private ?object $source;

	public function __construct(
		mixed $source,
		ValueMode|ValueRules $modeOrRules = ValueMode::Strict,
	) {
		$this->source = is_object($source) ? $source : null;
		$this->initialize($modeOrRules);
	}

	protected function resolveValue(int|string $segment): ResolvedValue
	{
		return ValueResolver::Resolve($this->source, $segment);
	}

	protected function createChild(mixed $source): static
	{
		$child = clone $this;
		$child->source = is_object($source) ? $source : null;
		return $child;
	}

	/** @param callable(mixed): mixed $transformer */
	public function Transform(string $propertyName, callable $transformer): static
	{
		return $this->transformValue($propertyName, $transformer);
	}

	public function Exists(string $propertyName): bool
	{
		return $this->valueExists($propertyName);
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @param (callable(T): bool)|null $predicate
	 * @return T|null
	 */
	public function Instance(string $propertyName, string $className, ?callable $predicate = null): ?object
	{
		return $this->readInstance($propertyName, $className, $predicate);
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @param (callable(T): bool)|null $predicate
	 * @return list<T>
	 */
	public function InstanceList(string $propertyName, string $className, ?callable $predicate = null): array
	{
		return $this->readInstanceList($propertyName, $className, $predicate);
	}

	/** @param (callable(string): bool)|null $predicate */
	public function String(string $propertyName, ?callable $predicate = null): string
	{
		return $this->readString($propertyName, $predicate);
	}

	/** @param (callable(int): bool)|null $predicate */
	public function Int(string $propertyName, ?callable $predicate = null): ?int
	{
		return $this->readInt($propertyName, $predicate);
	}

	/** @param (callable(float): bool)|null $predicate */
	public function Float(string $propertyName, ?callable $predicate = null): ?float
	{
		return $this->readFloat($propertyName, $predicate);
	}

	/** @param (callable(bool): bool)|null $predicate */
	public function Bool(string $propertyName, ?callable $predicate = null): ?bool
	{
		return $this->readBool($propertyName, $predicate);
	}

	/**
	 * @template T of \BackedEnum
	 * @param class-string<T> $enumClass
	 * @param (callable(T): bool)|null $predicate
	 * @return T|null
	 */
	public function BackedEnum(string $propertyName, string $enumClass, ?callable $predicate = null): ?\BackedEnum
	{
		return $this->readBackedEnum($propertyName, $enumClass, $predicate);
	}

	/**
	 * @param (callable(string): bool)|null $predicate
	 * @return list<string>
	 */
	public function StringList(string $propertyName, ?callable $predicate = null): array
	{
		return $this->readStringList($propertyName, $predicate);
	}

	/**
	 * @param (callable(int): bool)|null $predicate
	 * @return list<int>
	 */
	public function IntList(string $propertyName, ?callable $predicate = null): array
	{
		return $this->readIntList($propertyName, $predicate);
	}

	/**
	 * @param (callable(float): bool)|null $predicate
	 * @return list<float>
	 */
	public function FloatList(string $propertyName, ?callable $predicate = null): array
	{
		return $this->readFloatList($propertyName, $predicate);
	}

	/**
	 * @param (callable(bool): bool)|null $predicate
	 * @return list<bool>
	 */
	public function BoolList(string $propertyName, ?callable $predicate = null): array
	{
		return $this->readBoolList($propertyName, $predicate);
	}

	/**
	 * @template T of \BackedEnum
	 * @param class-string<T> $enumClass
	 * @param (callable(T): bool)|null $predicate
	 * @return list<T>
	 */
	public function BackedEnumList(string $propertyName, string $enumClass, ?callable $predicate = null): array
	{
		return $this->readBackedEnumList($propertyName, $enumClass, $predicate);
	}

	/** @param (callable(array<array-key, mixed>): bool)|null $predicate */
	public function Array(string $propertyName, ?callable $predicate = null): array
	{
		return $this->readArray($propertyName, $predicate);
	}

	public function Nested(string $propertyName): static
	{
		return $this->nestedValue($propertyName);
	}

	public function NestedPath(string $propertyName, string ...$propertyNames): static
	{
		return $this->nestedValuePath($propertyName, ...$propertyNames);
	}

	public function ExistsPath(string $propertyName, string ...$propertyNames): bool
	{
		return ValueResolver::ResolveObjectPath(
			$this->source,
			$propertyName,
			...$propertyNames,
		)->Exists;
	}

	public function NestedArray(string $propertyName): ArrayGet
	{
		return new ArrayGet($this->value($propertyName), $this->rulesSnapshot());
	}

	/** @return list<static> */
	public function NestedList(string $propertyName): array
	{
		$items = [];
		foreach ($this->Array($propertyName) as $value) {
			if (! is_object($value))
			{continue;}

			$items[] = $this->createChild($value);
		}

		return $items;
	}

	/** @return list<ArrayGet> */
	public function NestedArrayList(string $propertyName): array
	{
		$items = [];
		foreach ($this->Array($propertyName) as $value) {
			if (! is_array($value))
			{continue;}

			$items[] = new ArrayGet($value, $this->rulesSnapshot());
		}

		return $items;
	}
}
