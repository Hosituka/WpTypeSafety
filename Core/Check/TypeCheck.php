<?php
declare(strict_types=1);

namespace WpTypeSafety\Core\Check;

use WpTypeSafety\Core\Data\ChoiceData;
use WpTypeSafety\Core\Data\FileData;
use WpTypeSafety\Core\Data\ImgData;
use WpTypeSafety\Core\Data\LinkData;
use WpTypeSafety\Core\Data\TimeData;


use WpTypeSafety\Internal\TreeTypeReader\TypeCheck as InternalTypeCheck;

/** 取得済みの値を変換せず、指定した型だけを通します。型違いはnullです。 */
class TypeCheck extends InternalTypeCheck
{
	private function __construct()
	{
	}
	public static function ImgData(mixed $value): ?ImgData
	{
		return self::Instance($value, ImgData::class);
	}

	public static function LinkData(mixed $value): ?LinkData
	{
		return self::Instance($value, LinkData::class);
	}

	public static function ChoiceData(mixed $value): ?ChoiceData
	{
		return self::Instance($value, ChoiceData::class);
	}

	public static function FileData(mixed $value): ?FileData
	{
		return self::Instance($value, FileData::class);
	}

	public static function TimeData(mixed $value): ?TimeData
	{
		return self::Instance($value, TimeData::class);
	}

	/**
	 * @return list<ImgData>|null
	 */
	public static function ImgDataList(mixed $value): ?array
	{
		return self::InstanceList($value, ImgData::class);
	}

	/**
	 * @return list<LinkData>|null
	 */
	public static function LinkDataList(mixed $value): ?array
	{
		return self::InstanceList($value, LinkData::class);
	}

	/**
	 * @return list<ChoiceData>|null
	 */
	public static function ChoiceDataList(mixed $value): ?array
	{
		return self::InstanceList($value, ChoiceData::class);
	}

	/**
	 * @return list<FileData>|null
	 */
	public static function FileDataList(mixed $value): ?array
	{
		return self::InstanceList($value, FileData::class);
	}

	/**
	 * @return list<TimeData>|null
	 */
	public static function TimeDataList(mixed $value): ?array
	{
		return self::InstanceList($value, TimeData::class);
	}

}
