<?php
declare(strict_types=1);

// APIスタブによる検証。WordPress・DBには接続しない。
if (PHP_SAPI !== 'cli') {
	exit;
}

use WpTypeSafety\Acf\Get\AcfGet;
use WpTypeSafety\Core\Data\TimeData;

class WP_Post { public function __construct(public int $ID) {} }

function wp_timezone(): DateTimeZone
{
	return new DateTimeZone('Asia/Tokyo');
}

function get_field_object(
	string $name,
	mixed $source = null,
	bool $formatValue = true,
	bool $loadValue = true,
): mixed {
	$GLOBALS['lastTemporalRequest'] = [$name, $source, $formatValue, $loadValue];
	return $GLOBALS['temporalFields'][$name] ?? false;
}

function get_field(string $name, mixed $source = null): mixed
{
	return $GLOBALS['temporalFields'][$name]['formatted_value'] ?? null;
}

require_once __DIR__ . '/../Core/Data/TimeData.php';
require_once __DIR__ . '/../Acf/Get/AcfGet.php';
require_once __DIR__ . '/../Acf/Internal/AcfAdapter.php';
\WpTypeSafety\Acf\Internal\AcfAdapter::Register();

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (!$ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};

$GLOBALS['temporalFields'] = [
	'event_date' => [
		'type' => 'date_picker',
		'value' => '20261107',
		'formatted_value' => '2026年11月7日',
	],
	'event_start' => [
		'type' => 'date_time_picker',
		'value' => '2026-11-07 15:30:45',
		'formatted_value' => '2026年11月7日 15時30分',
	],
	'opening_time' => [
		'type' => 'time_picker',
		'value' => '09:05:07',
		'formatted_value' => '9:05 am',
	],
	'invalid_date' => ['type' => 'date_picker', 'value' => '20260231'],
	'invalid_date_time' => ['type' => 'date_time_picker', 'value' => '2026-11-07 25:00:00'],
	'invalid_time' => ['type' => 'time_picker', 'value' => '24:00:00'],
	'wrong_type' => ['type' => 'text', 'value' => '20261107'],
];

$get = new AcfGet(new WP_Post(123));
$date = $get->Date('event_date');
$check($date instanceof DateTimeImmutable, 'Date returns DateTimeImmutable');
$check($date?->format('Y-m-d H:i:s') === '2026-11-07 00:00:00', 'Date starts at midnight');
$check($date?->getTimezone()->getName() === 'Asia/Tokyo', 'Date uses site timezone');
$check($GLOBALS['lastTemporalRequest'] === ['event_date', 123, false, true], 'Date requests unformatted value');

$dateTime = $get->DateTime('event_start');
$check($dateTime?->format('Y-m-d H:i:s') === '2026-11-07 15:30:45', 'DateTime value');
$check($dateTime?->getTimezone()->getName() === 'Asia/Tokyo', 'DateTime uses site timezone');

$time = $get->Time('opening_time');
$check($time instanceof TimeData, 'Time returns TimeData');
$check([$time?->Hour, $time?->Minute, $time?->Second] === [9, 5, 7], 'Time components');
$check($time?->Format() === '09:05:07', 'Time default format');
$check($time?->Format('H:i') === '09:05', 'Time custom format');
$check((new TimeData(Hour: 24, Minute: 0, Second: 0))->Format() === '', 'invalid TimeData cannot be formatted');

$check($get->Date('invalid_date') === null, 'invalid date');
$check($get->DateTime('invalid_date_time') === null, 'invalid date time');
$check($get->Time('invalid_time') === null, 'invalid time');
$check($get->Date('wrong_type') === null, 'field type mismatch');
$check((new AcfGet(['event_date' => '20261107']))->Date('event_date') === null, 'array source is unsupported');
$check((new AcfGet(['group' => ['event_date' => '20261107']]))->Nested('group')->Date('event_date') === null, 'Nested source is unsupported');

echo "Temporal accessors: {$checks} checks passed.\n";
