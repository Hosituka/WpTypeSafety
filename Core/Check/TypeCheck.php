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
	public static function ImgData(int|string $key): ?ImgData
	{
		return self::Instance($key, ImgData::class);
	}

	public static function LinkData(int|string $key): ?LinkData
	{
		return self::Instance($key, LinkData::class);
	}

	public static function ChoiceData(int|string $key): ?ChoiceData
	{
		return self::Instance($key, ChoiceData::class);
	}

	public static function FileData(int|string $key): ?FileData
	{
		return self::Instance($key, FileData::class);
	}

	public static function TimeData(int|string $key): ?TimeData
	{
		return self::Instance($key, TimeData::class);
	}

	/**
	 * @return list<ImgData>
	 */
	public static function ImgDataList(int|string $key): array
	{
		return self::InstanceList($key, ImgData::class);
	}

	/**
	 * @return list<LinkData>
	 */
	public static function LinkDataList(int|string $key): array
	{
		return self::InstanceList($key, LinkData::class);
	}

	/**
	 * @return list<ChoiceData>
	 */
	public static function ChoiceDataList(int|string $key): array
	{
		return self::InstanceList($key, ChoiceData::class);
	}

	/**
	 * @return list<FileData>
	 */
	public static function FileDataList(int|string $key): array
	{
		return self::InstanceList($key, FileData::class);
	}

	/**
	 * @return list<TimeData>
	 */
	public static function TimeDataList(int|string $key): array
	{
		return self::InstanceList($key, TimeData::class);
	}

}
