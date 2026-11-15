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
use WpTypeSafety\Internal\TreeTypeReader\TreeGet as InternalTreeGet;

/**
 * 配列とオブジェクトが混在する木構造を、型を自動判定しながら取得します。
 * @template-covariant TArrayReader of ArrayGet = ArrayGet
 * @template-covariant TObjectReader of ObjectGet = ObjectGet
 * @extends InternalTreeGet<TArrayReader, TObjectReader>
 */
class TreeGet extends InternalTreeGet
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

	/** @return TObjectReader 独自の型引数を指定する派生クラスは上書きする。 */
	protected function createObjectReader(mixed $source): ObjectGet
	{
		/** @var TObjectReader $reader 型引数が既定値のときの生成処理。 */
		$reader = new ObjectGet($source, $this->publicRulesSnapshot());
		return $reader;
	}

	/** @return TArrayReader|null */
	public function AsArrayGet(): ?ArrayGet
	{
		return parent::AsArrayGet();
	}

	/** @return TObjectReader|null */
	public function AsObjectGet(): ?ObjectGet
	{
		return parent::AsObjectGet();
	}

	/** @return TArrayReader */
	public function NestedArray(int|string $segment): ArrayGet
	{
		return $this->createArrayReader($this->value($segment));
	}

	/** @return TObjectReader */
	public function NestedObject(int|string $segment): ObjectGet
	{
		return $this->createObjectReader($this->value($segment));
	}

	/** @return list<TArrayReader> */
	public function NestedArrayList(int|string $segment): array
	{
		return parent::NestedArrayList($segment);
	}

	/** @return list<TObjectReader> */
	public function NestedObjectList(int|string $segment): array
	{
		return parent::NestedObjectList($segment);
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

	/** @param (callable(DateData): bool)|null $predicate */
	public function DateData(int|string $segment, ?callable $predicate = null): ?DateData
	{
		return $this->Instance($segment, DateData::class, $predicate);
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

	/**
	 * @param (callable(DateData): bool)|null $predicate
	 * @return list<DateData>
	 */
	public function DateDataList(int|string $segment, ?callable $predicate = null): array
	{
		return $this->InstanceList($segment, DateData::class, $predicate);
	}
}
