<?php
declare(strict_types=1);

namespace WpTypeSafety\Acf\Internal;

use WpTypeSafety\Core\Data\TimeData;

/** @internal ACFの未整形日時値を固定型へ変換する。 */
final class TemporalValue
{
	private function __construct() {}

	public static function Date(mixed $value): ?\DateTimeImmutable
	{
		return self::parseDateTime($value, 'Ymd');
	}

	public static function DateTime(mixed $value): ?\DateTimeImmutable
	{
		return self::parseDateTime($value, 'Y-m-d H:i:s');
	}

	public static function Time(mixed $value): ?TimeData
	{
		if (! is_string($value) || preg_match('/^(\d{2}):(\d{2}):(\d{2})$/', $value, $matches) !== 1) {
			return null;
		}

		$hour = (int) $matches[1];
		$minute = (int) $matches[2];
		$second = (int) $matches[3];
		if ($hour > 23 || $minute > 59 || $second > 59) {
			return null;
		}

		return new TimeData(
			Hour: $hour,
			Minute: $minute,
			Second: $second,
		);
	}

	private static function parseDateTime(mixed $value, string $format): ?\DateTimeImmutable
	{
		if (! is_string($value) || $value === '') {
			return null;
		}

		$dateTime = \DateTimeImmutable::createFromFormat(
			'!' . $format,
			$value,
			\wp_timezone(),
		);
		return $dateTime !== false && $dateTime->format($format) === $value
			? $dateTime
			: null;
	}
}
