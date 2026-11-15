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
use WpTypeSafety\Internal\TreeTypeReader\ArrayGet as InternalArrayGet;

/**
 * 連想配列からキーを指定し、設定した基準で値を取得します。
 * @template-covariant TObjectReader of ObjectGet = ObjectGet
 * @extends InternalArrayGet<TObjectReader>
 */
class ArrayGet extends InternalArrayGet
{
	public function __construct(
		mixed $values,
		NumericMode|BoolMode|ValueRules|null $modeOrRules = null,
	) {
		parent::__construct(
			$values,
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

	/** @return TObjectReader 独自の型引数を指定する派生クラスは上書きする。 */
	protected function createObjectReader(mixed $source): ObjectGet
	{
		/** @var TObjectReader $reader 型引数が既定値のときの生成処理。 */
		$reader = new ObjectGet($source, $this->publicRulesSnapshot());
		return $reader;
	}

	/** @return TObjectReader */
	public function NestedObject(int|string $key): ObjectGet
	{
		return $this->createObjectReader($this->value($key));
	}

	/** @return list<TObjectReader> */
	public function NestedObjectList(int|string $key): array
	{
		return parent::NestedObjectList($key);
	}

	/** @param (callable(ImgData): bool)|null $predicate */
	public function ImgData(int|string $key, ?callable $predicate = null): ?ImgData
	{
		return $this->Instance($key, ImgData::class, $predicate);
	}

	/** @param (callable(LinkData): bool)|null $predicate */
	public function LinkData(int|string $key, ?callable $predicate = null): ?LinkData
	{
		return $this->Instance($key, LinkData::class, $predicate);
	}

	/** @param (callable(ChoiceData): bool)|null $predicate */
	public function ChoiceData(int|string $key, ?callable $predicate = null): ?ChoiceData
	{
		return $this->Instance($key, ChoiceData::class, $predicate);
	}

	/** @param (callable(FileData): bool)|null $predicate */
	public function FileData(int|string $key, ?callable $predicate = null): ?FileData
	{
		return $this->Instance($key, FileData::class, $predicate);
	}

	/** @param (callable(TimeData): bool)|null $predicate */
	public function TimeData(int|string $key, ?callable $predicate = null): ?TimeData
	{
		return $this->Instance($key, TimeData::class, $predicate);
	}

	/** @param (callable(DateData): bool)|null $predicate */
	public function DateData(int|string $key, ?callable $predicate = null): ?DateData
	{
		return $this->Instance($key, DateData::class, $predicate);
	}

	/**
	 * @param (callable(ImgData): bool)|null $predicate
	 * @return list<ImgData>
	 */
	public function ImgDataList(int|string $key, ?callable $predicate = null): array
	{
		return $this->InstanceList($key, ImgData::class, $predicate);
	}

	/**
	 * @param (callable(LinkData): bool)|null $predicate
	 * @return list<LinkData>
	 */
	public function LinkDataList(int|string $key, ?callable $predicate = null): array
	{
		return $this->InstanceList($key, LinkData::class, $predicate);
	}

	/**
	 * @param (callable(ChoiceData): bool)|null $predicate
	 * @return list<ChoiceData>
	 */
	public function ChoiceDataList(int|string $key, ?callable $predicate = null): array
	{
		return $this->InstanceList($key, ChoiceData::class, $predicate);
	}

	/**
	 * @param (callable(FileData): bool)|null $predicate
	 * @return list<FileData>
	 */
	public function FileDataList(int|string $key, ?callable $predicate = null): array
	{
		return $this->InstanceList($key, FileData::class, $predicate);
	}

	/**
	 * @param (callable(TimeData): bool)|null $predicate
	 * @return list<TimeData>
	 */
	public function TimeDataList(int|string $key, ?callable $predicate = null): array
	{
		return $this->InstanceList($key, TimeData::class, $predicate);
	}

	/**
	 * @param (callable(DateData): bool)|null $predicate
	 * @return list<DateData>
	 */
	public function DateDataList(int|string $key, ?callable $predicate = null): array
	{
		return $this->InstanceList($key, DateData::class, $predicate);
	}
}
