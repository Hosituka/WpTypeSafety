<?php
declare(strict_types=1);

namespace WpTypeSafety\Core\Data;

/** 日付を含まない時刻。 */
final readonly class TimeData
{
	public function __construct(
		public int $Hour,
		public int $Minute,
		public int $Second,
	) {
	}

	public function Format(string $format = 'H:i:s'): string
	{
		$value = sprintf('%02d:%02d:%02d', $this->Hour, $this->Minute, $this->Second);
		$dateTime = \DateTimeImmutable::createFromFormat('!H:i:s', $value);
		if ($dateTime === false || $dateTime->format('H:i:s') !== $value)
		{return '';}

		return $dateTime->format($format);
	}
}
