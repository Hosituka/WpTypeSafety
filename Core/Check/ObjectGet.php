<?php
declare(strict_types=1);

namespace WpTypeSafety\Core\Check;

use WpTypeSafety\Core\Check\Data\BoolMode;
use WpTypeSafety\Core\Check\Data\NumericMode;
use WpTypeSafety\Core\Data\ChoiceData;
use WpTypeSafety\Core\Data\DateData;
use WpTypeSafety\Core\Data\FileData;
use WpTypeSafety\Core\Data\ImgData;
use WpTypeSafety\Core\Data\LinkData;
use WpTypeSafety\Core\Data\TimeData;
use WpTypeSafety\Internal\TreeTypeReader\ObjectGet as InternalObjectGet;

/**
 * publicインスタンスプロパティを動的に読み、設定した基準で値を取得します。
 * @template-covariant TArrayReader of ArrayGet = ArrayGet
 * @extends InternalObjectGet<TArrayReader>
 */
class ObjectGet extends InternalObjectGet
{
	public function __construct(
		mixed $source,
		NumericMode|BoolMode|ValueRules|null $modeOrRules = null,
	) {
		parent::__construct(
			$source,
			$modeOrRules instanceof ValueRules
				? $modeOrRules
				: ($modeOrRules === null
					? ValueRules::Strict()
					: ValueRules::FromModes($modeOrRules)),
		);
	}

	protected function publicRulesSnapshot(): ValueRules
	{
		return ValueRules::FromInternal($this->rulesSnapshot());
	}

	public function WithModes(NumericMode|BoolMode ...$modes): static
	{
		return $this->copyWithRules(ValueRules::FromModes(...$modes));
	}

	public function WithRules(ValueRules $rules): static
	{
		return $this->copyWithRules($rules);
	}

	/** @return TArrayReader 独自の型引数を指定する派生クラスは上書きする。 */
	protected function createArrayReader(mixed $source): ArrayGet
	{
		/** @var TArrayReader $reader 型引数が既定値のときの生成処理。 */
		$reader = new ArrayGet($source, $this->publicRulesSnapshot());
		return $reader;
	}

	/** @return TArrayReader */
	public function NestedArray(string $propertyName): ArrayGet
	{
		return $this->createArrayReader($this->value($propertyName));
	}

	/** @return list<TArrayReader> */
	public function NestedArrayList(string $propertyName): array
	{
		return parent::NestedArrayList($propertyName);
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

	/** @param (callable(DateData): bool)|null $predicate */
	public function DateData(string $propertyName, ?callable $predicate = null): ?DateData
	{
		return $this->Instance($propertyName, DateData::class, $predicate);
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

	/**
	 * @param (callable(DateData): bool)|null $predicate
	 * @return list<DateData>
	 */
	public function DateDataList(string $propertyName, ?callable $predicate = null): array
	{
		return $this->InstanceList($propertyName, DateData::class, $predicate);
	}
}
