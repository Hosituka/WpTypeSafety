<?php
declare(strict_types=1);

namespace WpTypeSafety\Core\Check;

use WpTypeSafety\Core\Data\ChoiceData;
use WpTypeSafety\Core\Data\FileData;
use WpTypeSafety\Core\Data\ImgData;
use WpTypeSafety\Core\Data\LinkData;
use WpTypeSafety\Core\Data\TimeData;
use WpTypeSafety\Internal\TreeTypeReader\ObjectGet as InternalObjectGet;

/** publicインスタンスプロパティを動的に読み、設定した基準で値を取得します。 */
class ObjectGet extends InternalObjectGet
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

	private function publicRulesSnapshot(): ValueRules
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

	public function NestedArray(string $propertyName): ArrayGet
	{
		return new ArrayGet($this->value($propertyName), $this->publicRulesSnapshot());
	}

	/** @return list<ArrayGet> */
	public function NestedArrayList(string $propertyName): array
	{
		$items = [];
		foreach ($this->Array($propertyName) as $value) {
			if (! is_array($value))
			{continue;}

			$items[] = new ArrayGet($value, $this->publicRulesSnapshot());
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
