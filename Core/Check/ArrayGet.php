<?php
declare(strict_types=1);

namespace WpTypeSafety\Core\Check;

use WpTypeSafety\Core\Data\ChoiceData;
use WpTypeSafety\Core\Data\FileData;
use WpTypeSafety\Core\Data\ImgData;
use WpTypeSafety\Core\Data\LinkData;
use WpTypeSafety\Core\Data\TimeData;

/** 連想配列からキーを指定して値の型を確認します。値の変換は行いません。 */
final class ArrayGet
{
	/** @var array<array-key, mixed> */
	private readonly array $values;

	public function __construct(mixed $values)
	{
		$this->values = TypeCheck::Array($values) ?? [];
	}

	private function value(string $key): mixed
	{
		return $this->values[$key] ?? null;
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
		return TypeCheck::StringList($this->value($key)) ?? [];
	}

	/** @return list<int> */
	public function IntList(string $key): array
	{
		return TypeCheck::IntList($this->value($key)) ?? [];
	}

	/** @return list<float> */
	public function FloatList(string $key): array
	{
		return TypeCheck::FloatList($this->value($key)) ?? [];
	}

	/** @return list<bool> */
	public function BoolList(string $key): array
	{
		return TypeCheck::BoolList($this->value($key)) ?? [];
	}

	public function String(string $key): string
	{
		return TypeCheck::String($this->value($key)) ?? '';
	}

	public function Int(string $key): ?int
	{
		return TypeCheck::Int($this->value($key));
	}

	public function Float(string $key): ?float
	{
		return TypeCheck::Float($this->value($key));
	}

	public function Bool(string $key): ?bool
	{
		return TypeCheck::Bool($this->value($key));
	}

	public function Array(string $key): array
	{
		return TypeCheck::Array($this->value($key)) ?? [];
	}

	public function Nested(string $key): self
	{
		return new self($this->value($key));
	}

	public function NestedObject(string $key): ObjectGet
	{
		return new ObjectGet($this->value($key));
	}

	/** @return list<self> */
	public function NestedList(string $key): array
	{
		$items = [];
		foreach ($this->Array($key) as $value) {
			if (!is_array($value))
			{continue;}

			$items[] = new self($value);
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

			$items[] = new ObjectGet($value);
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
