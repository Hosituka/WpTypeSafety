<?php
declare(strict_types=1);

namespace WpTypeSafety\Core\Check;

use WpTypeSafety\Core\Data\ChoiceData;
use WpTypeSafety\Core\Data\FileData;
use WpTypeSafety\Core\Data\ImgData;
use WpTypeSafety\Core\Data\LinkData;
use WpTypeSafety\Core\Data\TimeData;
use WpTypeSafety\Core\Internal\ValueResolver;

/** 配列とオブジェクトが混在する木構造を、型を自動判定しながら取得します。 */
final class TreeGet
{
	private readonly mixed $source;
	private readonly ValueRules $rules;
	private readonly ArrayGet|ObjectGet|null $get;

	public function __construct(
		mixed $source,
		ValueMode|ValueRules $modeOrRules = ValueMode::Strict,
	) {
		$this->source = $source;
		$this->rules = $modeOrRules instanceof ValueRules
			? clone $modeOrRules
			: ValueRules::FromMode($modeOrRules);
		$this->get = match (true) {
			is_array($source) => new ArrayGet($source, $this->rules),
			is_object($source) => new ObjectGet($source, $this->rules),
			default => null,
		};
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
	 * @template T of object
	 * @param class-string<T> $className
	 * @return T|null
	 */
	public function Instance(string $segment, string $className): ?object
	{
		return $this->get?->Instance($segment, $className);
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @return list<T>
	 */
	public function InstanceList(string $segment, string $className): array
	{
		return $this->get?->InstanceList($segment, $className) ?? [];
	}

	/** @return list<string> */
	public function StringList(string $segment): array
	{
		return $this->get?->StringList($segment) ?? [];
	}

	/** @return list<int> */
	public function IntList(string $segment): array
	{
		return $this->get?->IntList($segment) ?? [];
	}

	/** @return list<float> */
	public function FloatList(string $segment): array
	{
		return $this->get?->FloatList($segment) ?? [];
	}

	/** @return list<bool> */
	public function BoolList(string $segment): array
	{
		return $this->get?->BoolList($segment) ?? [];
	}

	public function String(string $segment): string
	{
		return $this->get?->String($segment) ?? '';
	}

	public function Int(string $segment): ?int
	{
		return $this->get?->Int($segment);
	}

	public function Float(string $segment): ?float
	{
		return $this->get?->Float($segment);
	}

	public function Bool(string $segment): ?bool
	{
		return $this->get?->Bool($segment);
	}

	/**
	 * @template T of \BackedEnum
	 * @param class-string<T> $enumClass
	 * @return T|null
	 */
	public function BackedEnum(string $segment, string $enumClass): ?\BackedEnum
	{
		return $this->get?->BackedEnum($segment, $enumClass);
	}

	/**
	 * @template T of \BackedEnum
	 * @param class-string<T> $enumClass
	 * @return list<T>
	 */
	public function BackedEnumList(string $segment, string $enumClass): array
	{
		return $this->get?->BackedEnumList($segment, $enumClass) ?? [];
	}

	public function Array(string $segment): array
	{
		return $this->get?->Array($segment) ?? [];
	}

	public function Nested(string $segment): self
	{
		$resolved = ValueResolver::Resolve($this->source, $segment);
		return new self(
			$resolved->Exists ? $resolved->Value : null,
			$this->rules,
		);
	}

	public function NestedPath(string $segment, string ...$segments): self
	{
		$nestedGet = $this->Nested($segment);
		foreach ($segments as $nestedSegment) {
			$nestedGet = $nestedGet->Nested($nestedSegment);
		}

		return $nestedGet;
	}

	/** @return list<self> */
	public function NestedList(string $segment): array
	{
		$items = [];
		foreach ($this->Array($segment) as $value) {
			if (! is_array($value) && ! is_object($value)) {
				continue;
			}

			$items[] = new self($value, $this->rules);
		}

		return $items;
	}

	public function ImgData(string $segment): ?ImgData
	{
		return $this->Instance($segment, ImgData::class);
	}

	public function LinkData(string $segment): ?LinkData
	{
		return $this->Instance($segment, LinkData::class);
	}

	public function ChoiceData(string $segment): ?ChoiceData
	{
		return $this->Instance($segment, ChoiceData::class);
	}

	public function FileData(string $segment): ?FileData
	{
		return $this->Instance($segment, FileData::class);
	}

	public function TimeData(string $segment): ?TimeData
	{
		return $this->Instance($segment, TimeData::class);
	}

	/** @return list<ImgData> */
	public function ImgDataList(string $segment): array
	{
		return $this->InstanceList($segment, ImgData::class);
	}

	/** @return list<LinkData> */
	public function LinkDataList(string $segment): array
	{
		return $this->InstanceList($segment, LinkData::class);
	}

	/** @return list<ChoiceData> */
	public function ChoiceDataList(string $segment): array
	{
		return $this->InstanceList($segment, ChoiceData::class);
	}

	/** @return list<FileData> */
	public function FileDataList(string $segment): array
	{
		return $this->InstanceList($segment, FileData::class);
	}

	/** @return list<TimeData> */
	public function TimeDataList(string $segment): array
	{
		return $this->InstanceList($segment, TimeData::class);
	}
}
