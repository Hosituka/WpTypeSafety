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
	public function InstanceArray(string $key, string $className): array
	{
		return TypeCheck::InstanceArray($this->value($key), $className) ?? [];
	}

	/** @return list<string> */
	public function StringArray(string $key): array
	{
		return TypeCheck::StringArray($this->value($key)) ?? [];
	}

	/** @return list<int> */
	public function IntArray(string $key): array
	{
		return TypeCheck::IntArray($this->value($key)) ?? [];
	}

	/** @return list<float> */
	public function FloatArray(string $key): array
	{
		return TypeCheck::FloatArray($this->value($key)) ?? [];
	}

	/** @return list<bool> */
	public function BoolArray(string $key): array
	{
		return TypeCheck::BoolArray($this->value($key)) ?? [];
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

	/** @return list<self> */
	public function NestedArray(string $key): array
	{
		$items = [];
		foreach ($this->Array($key) as $value) {
			if (!is_array($value))
			{continue;}

			$items[] = new self($value);
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
	public function ImgDataArray(string $key): array
	{
		return $this->InstanceArray($key, ImgData::class);
	}

	/** @return list<LinkData> */
	public function LinkDataArray(string $key): array
	{
		return $this->InstanceArray($key, LinkData::class);
	}

	/** @return list<ChoiceData> */
	public function ChoiceDataArray(string $key): array
	{
		return $this->InstanceArray($key, ChoiceData::class);
	}

	/** @return list<FileData> */
	public function FileDataArray(string $key): array
	{
		return $this->InstanceArray($key, FileData::class);
	}

	/** @return list<TimeData> */
	public function TimeDataArray(string $key): array
	{
		return $this->InstanceArray($key, TimeData::class);
	}
}
