<?php
declare(strict_types=1);

namespace WpTypeSafety\Core\Check;

use WpTypeSafety\Core\Data\ChoiceData;
use WpTypeSafety\Core\Data\FileData;
use WpTypeSafety\Core\Data\ImgData;
use WpTypeSafety\Core\Data\LinkData;
use WpTypeSafety\Core\Data\TimeData;
use WpTypeSafety\Internal\TreeTypeReader\Check\TreeGet as InternalTreeGet;

/** 配列とオブジェクトが混在する木構造を、型を自動判定しながら取得します。 */
class TreeGet extends InternalTreeGet
{
	public function __construct(
		mixed $source,
		ValueMode|ValueRules $modeOrRules = ValueMode::Strict,
	) {
		parent::__construct(
			$source,
			$modeOrRules instanceof ValueMode
				? ValueRules::FromMode($modeOrRules)
				: $modeOrRules,
		);
	}

	protected function publicRulesSnapshot(): ValueRules
	{
		return ValueRules::FromInternal($this->rulesSnapshot());
	}

	public function WithMode(ValueMode $mode): static
	{
		return $this->copyWithRules(ValueRules::FromMode($mode));
	}

	public function WithRules(ValueRules $rules): static
	{
		return $this->copyWithRules($rules);
	}

	public function AsArrayGet(): ?ArrayGet
	{
		$source = $this->sourceValue();
		return is_array($source)
			? new ArrayGet($source, $this->publicRulesSnapshot())
			: null;
	}

	public function AsObjectGet(): ?ObjectGet
	{
		$source = $this->sourceValue();
		return is_object($source)
			? new ObjectGet($source, $this->publicRulesSnapshot())
			: null;
	}

	public function NestedArray(int|string $segment): ArrayGet
	{
		return new ArrayGet($this->value($segment), $this->publicRulesSnapshot());
	}

	public function NestedObject(int|string $segment): ObjectGet
	{
		return new ObjectGet($this->value($segment), $this->publicRulesSnapshot());
	}

	/** @return list<ArrayGet> */
	public function NestedArrayList(int|string $segment): array
	{
		$items = [];
		foreach ($this->Array($segment) as $value) {
			if (! is_array($value))
			{continue;}

			$items[] = new ArrayGet($value, $this->publicRulesSnapshot());
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

			$items[] = new ObjectGet($value, $this->publicRulesSnapshot());
		}

		return $items;
	}

	/** @param (callable(ImgData): bool)|null $predicate */
	public function ImgData(int|string $segment, ?callable $predicate = null): ?ImgData
	{
		return $this->Instance($segment, ImgData::class, $predicate);
	}

	/** @param (callable(LinkData): bool)|null $predicate */
	public function LinkData(int|string $segment, ?callable $predicate = null): ?LinkData
	{
		return $this->Instance($segment, LinkData::class, $predicate);
	}

	/** @param (callable(ChoiceData): bool)|null $predicate */
	public function ChoiceData(int|string $segment, ?callable $predicate = null): ?ChoiceData
	{
		return $this->Instance($segment, ChoiceData::class, $predicate);
	}

	/** @param (callable(FileData): bool)|null $predicate */
	public function FileData(int|string $segment, ?callable $predicate = null): ?FileData
	{
		return $this->Instance($segment, FileData::class, $predicate);
	}

	/** @param (callable(TimeData): bool)|null $predicate */
	public function TimeData(int|string $segment, ?callable $predicate = null): ?TimeData
	{
		return $this->Instance($segment, TimeData::class, $predicate);
	}

	/**
	 * @param (callable(ImgData): bool)|null $predicate
	 * @return list<ImgData>
	 */
	public function ImgDataList(int|string $segment, ?callable $predicate = null): array
	{
		return $this->InstanceList($segment, ImgData::class, $predicate);
	}

	/**
	 * @param (callable(LinkData): bool)|null $predicate
	 * @return list<LinkData>
	 */
	public function LinkDataList(int|string $segment, ?callable $predicate = null): array
	{
		return $this->InstanceList($segment, LinkData::class, $predicate);
	}

	/**
	 * @param (callable(ChoiceData): bool)|null $predicate
	 * @return list<ChoiceData>
	 */
	public function ChoiceDataList(int|string $segment, ?callable $predicate = null): array
	{
		return $this->InstanceList($segment, ChoiceData::class, $predicate);
	}

	/**
	 * @param (callable(FileData): bool)|null $predicate
	 * @return list<FileData>
	 */
	public function FileDataList(int|string $segment, ?callable $predicate = null): array
	{
		return $this->InstanceList($segment, FileData::class, $predicate);
	}

	/**
	 * @param (callable(TimeData): bool)|null $predicate
	 * @return list<TimeData>
	 */
	public function TimeDataList(int|string $segment, ?callable $predicate = null): array
	{
		return $this->InstanceList($segment, TimeData::class, $predicate);
	}
}
