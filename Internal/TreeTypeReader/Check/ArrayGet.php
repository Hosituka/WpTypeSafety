<?php
declare(strict_types=1);

namespace WpTypeSafety\Internal\TreeTypeReader\Check;

use WpTypeSafety\Internal\TreeTypeReader\ResolvedValue;
use WpTypeSafety\Internal\TreeTypeReader\Internal\ValueResolver;

/** @internal 連想配列からキーを指定して値を取得する汎用実装です。 */
class ArrayGet extends TreeTypeReader
{
	/** @var array<array-key, mixed> */
	private array $values;

	public function __construct(
		mixed $values,
		ValueMode|ValueRules $modeOrRules = ValueMode::Strict,
	) {
		$this->values = TypeCheck::Array($values) ?? [];
		$this->initialize($modeOrRules);
	}

	protected function resolveValue(int|string $segment): ResolvedValue
	{
		return ValueResolver::ResolveArray($this->values, $segment);
	}

	protected function createChild(mixed $source): static
	{
		$child = clone $this;
		$child->values = TypeCheck::Array($source) ?? [];
		return $child;
	}

	/** @param callable(mixed): mixed $transformer */
	public function Transform(int|string $key, callable $transformer): static
	{
		return $this->transformValue($key, $transformer);
	}

	public function Exists(int|string $key): bool
	{
		return $this->valueExists($key);
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @param (callable(T): bool)|null $predicate
	 * @return T|null
	 */
	public function Instance(int|string $key, string $className, ?callable $predicate = null): ?object
	{
		return $this->readInstance($key, $className, $predicate);
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @param (callable(T): bool)|null $predicate
	 * @return list<T>
	 */
	public function InstanceList(int|string $key, string $className, ?callable $predicate = null): array
	{
		return $this->readInstanceList($key, $className, $predicate);
	}

	/** @param (callable(string): bool)|null $predicate */
	public function String(int|string $key, ?callable $predicate = null): string
	{
		return $this->readString($key, $predicate);
	}

	/** @param (callable(int): bool)|null $predicate */
	public function Int(int|string $key, ?callable $predicate = null): ?int
	{
		return $this->readInt($key, $predicate);
	}

	/** @param (callable(float): bool)|null $predicate */
	public function Float(int|string $key, ?callable $predicate = null): ?float
	{
		return $this->readFloat($key, $predicate);
	}

	/** @param (callable(bool): bool)|null $predicate */
	public function Bool(int|string $key, ?callable $predicate = null): ?bool
	{
		return $this->readBool($key, $predicate);
	}

	/**
	 * @template T of \BackedEnum
	 * @param class-string<T> $enumClass
	 * @param (callable(T): bool)|null $predicate
	 * @return T|null
	 */
	public function BackedEnum(int|string $key, string $enumClass, ?callable $predicate = null): ?\BackedEnum
	{
		return $this->readBackedEnum($key, $enumClass, $predicate);
	}

	/**
	 * @param (callable(string): bool)|null $predicate
	 * @return list<string>
	 */
	public function StringList(int|string $key, ?callable $predicate = null): array
	{
		return $this->readStringList($key, $predicate);
	}

	/**
	 * @param (callable(int): bool)|null $predicate
	 * @return list<int>
	 */
	public function IntList(int|string $key, ?callable $predicate = null): array
	{
		return $this->readIntList($key, $predicate);
	}

	/**
	 * @param (callable(float): bool)|null $predicate
	 * @return list<float>
	 */
	public function FloatList(int|string $key, ?callable $predicate = null): array
	{
		return $this->readFloatList($key, $predicate);
	}

	/**
	 * @param (callable(bool): bool)|null $predicate
	 * @return list<bool>
	 */
	public function BoolList(int|string $key, ?callable $predicate = null): array
	{
		return $this->readBoolList($key, $predicate);
	}

	/**
	 * @template T of \BackedEnum
	 * @param class-string<T> $enumClass
	 * @param (callable(T): bool)|null $predicate
	 * @return list<T>
	 */
	public function BackedEnumList(int|string $key, string $enumClass, ?callable $predicate = null): array
	{
		return $this->readBackedEnumList($key, $enumClass, $predicate);
	}

	/** @param (callable(array<array-key, mixed>): bool)|null $predicate */
	public function Array(int|string $key, ?callable $predicate = null): array
	{
		return $this->readArray($key, $predicate);
	}

	public function Nested(int|string $key): static
	{
		return $this->nestedValue($key);
	}

	public function NestedPath(int|string $key, int|string ...$keys): static
	{
		return $this->nestedValuePath($key, ...$keys);
	}

	public function ExistsPath(int|string $key, int|string ...$keys): bool
	{
		return ValueResolver::ResolveArrayPath(
			$this->values,
			$key,
			...$keys,
		)->Exists;
	}

	public function NestedObject(int|string $key): ObjectGet
	{
		return new ObjectGet($this->value($key), $this->rulesSnapshot());
	}

	/** @return list<static> */
	public function NestedList(int|string $key): array
	{
		$items = [];
		foreach ($this->Array($key) as $value) {
			if (! is_array($value))
			{continue;}

			$items[] = $this->createChild($value);
		}
		return $items;
	}

	/** @return list<ObjectGet> */
	public function NestedObjectList(int|string $key): array
	{
		$items = [];
		foreach ($this->Array($key) as $value) {
			if (! is_object($value))
			{continue;}

			$items[] = new ObjectGet($value, $this->rulesSnapshot());
		}

		return $items;
	}
}
