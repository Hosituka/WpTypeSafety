<?php
declare(strict_types=1);

namespace WpTypeSafety\Core\Data;

/** 時刻とタイムゾーンを含まない日付。 */
final readonly class DateData
{
	public function __construct(
		public int $Year,
		public int $Month,
		public int $Day,
	) {
	}

	public function Format(string $format = 'Y-m-d'): string
	{
		$value = sprintf('%04d-%02d-%02d', $this->Year, $this->Month, $this->Day);
		$dateTime = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
		if ($dateTime === false || $dateTime->format('Y-m-d') !== $value)
		{return '';}

		return $dateTime->format($format);
	}
}
