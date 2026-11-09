<?php
declare(strict_types=1);

namespace WpTypeSafety\Core\Check;

use WpTypeSafety\Core\Data\ChoiceData;
use WpTypeSafety\Core\Data\FileData;
use WpTypeSafety\Core\Data\ImgData;
use WpTypeSafety\Core\Data\LinkData;
use WpTypeSafety\Core\Data\TimeData;

/** publicインスタンスプロパティから値を動的に読み、型を確認します。値の変換は行いません。 */
final class ObjectGet
{
	private readonly ?object $source;

	public function __construct(mixed $source)
	{
		$this->source = is_object($source) ? $source : null;
	}

	private function value(string $propertyName): mixed
	{
		if ($this->source === null) {
			return null;
		}

		$reflection = new \ReflectionObject($this->source);
		if (! $reflection->hasProperty($propertyName)) {
			return null;
		}

		$property = $reflection->getProperty($propertyName);
		if (! $property->isPublic() || $property->isStatic()) {
			return null;
		}

		if (! $property->isInitialized($this->source)) {
			return null;
		}

		if (
			method_exists($property, 'isVirtual')
			&& $property->isVirtual()
			&& ! isset($property->getHooks()['get'])
		) {
			return null;
		}

		return $property->getValue($this->source);
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
		return TypeCheck::StringList($this->value($propertyName)) ?? [];
	}

	/** @return list<int> */
	public function IntList(string $propertyName): array
	{
		return TypeCheck::IntList($this->value($propertyName)) ?? [];
	}

	/** @return list<float> */
	public function FloatList(string $propertyName): array
	{
		return TypeCheck::FloatList($this->value($propertyName)) ?? [];
	}

	/** @return list<bool> */
	public function BoolList(string $propertyName): array
	{
		return TypeCheck::BoolList($this->value($propertyName)) ?? [];
	}

	public function String(string $propertyName): string
	{
		return TypeCheck::String($this->value($propertyName)) ?? '';
	}

	public function Int(string $propertyName): ?int
	{
		return TypeCheck::Int($this->value($propertyName));
	}

	public function Float(string $propertyName): ?float
	{
		return TypeCheck::Float($this->value($propertyName));
	}

	public function Bool(string $propertyName): ?bool
	{
		return TypeCheck::Bool($this->value($propertyName));
	}

	public function Array(string $propertyName): array
	{
		return TypeCheck::Array($this->value($propertyName)) ?? [];
	}

	public function Nested(string $propertyName): self
	{
		return new self($this->value($propertyName));
	}

	public function NestedArray(string $propertyName): ArrayGet
	{
		return new ArrayGet($this->value($propertyName));
	}

	/** @return list<self> */
	public function NestedList(string $propertyName): array
	{
		$items = [];
		foreach ($this->Array($propertyName) as $value) {
			if (! is_object($value)) {
				continue;
			}

			$items[] = new self($value);
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

			$items[] = new ArrayGet($value);
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
