<?php
declare(strict_types=1);

// APIスタブによる検証。WordPress・DBには接続しない。
if (PHP_SAPI !== 'cli') {
	exit;
}

use WpTypeSafety\Acf\Data\FlexibleNode;
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
require_once __DIR__ . '/../Acf/Data/FlexibleNode.php';
require_once __DIR__ . '/../Acf/Get/AcfGet.php';
require_once __DIR__ . '/../Acf/Internal/TemporalValue.php';
require_once __DIR__ . '/../Acf/Internal/AcfAdapter.php';

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
	'schedule' => [
		'type' => 'group',
		'value' => [
			'field_schedule_date' => '20261108',
			'field_schedule_start' => '2026-11-08 16:30:45',
			'field_schedule_time' => '10:15:30',
			'field_schedule_text' => '20261108',
			'field_schedule_details' => [
				'field_details_date' => '20261109',
			],
			'field_schedule_events' => [
				['field_schedule_event_date' => '20261112'],
			],
			'field_schedule_blocks' => [
				['acf_fc_layout' => 'event', 'field_schedule_block_date' => '20261113'],
			],
		],
		'formatted_value' => [
			'event_date' => '2026年11月8日',
			'event_start' => '2026年11月8日 16時30分',
			'opening_time' => '10:15 am',
			'date_text' => '20261108',
			'details' => [
				'event_date' => '2026年11月9日',
			],
			'events' => [
				['event_date' => '2026年11月12日'],
			],
			'blocks' => [
				['acf_fc_layout' => 'event', 'event_date' => '2026年11月13日'],
			],
		],
		'sub_fields' => [
			['key' => 'field_schedule_date', 'name' => 'schedule_event_date', '_name' => 'event_date', 'type' => 'date_picker'],
			['key' => 'field_schedule_start', 'name' => 'schedule_event_start', '_name' => 'event_start', 'type' => 'date_time_picker'],
			['key' => 'field_schedule_time', 'name' => 'schedule_opening_time', '_name' => 'opening_time', 'type' => 'time_picker'],
			['key' => 'field_schedule_text', 'name' => 'schedule_date_text', '_name' => 'date_text', 'type' => 'text'],
			[
				'key' => 'field_schedule_details',
				'name' => 'schedule_details',
				'_name' => 'details',
				'type' => 'group',
				'sub_fields' => [
					['key' => 'field_details_date', 'name' => 'details_event_date', '_name' => 'event_date', 'type' => 'date_picker'],
				],
			],
			[
				'key' => 'field_schedule_events',
				'name' => 'schedule_events',
				'_name' => 'events',
				'type' => 'repeater',
				'sub_fields' => [
					['key' => 'field_schedule_event_date', 'name' => 'event_date', '_name' => 'event_date', 'type' => 'date_picker'],
				],
			],
			[
				'key' => 'field_schedule_blocks',
				'name' => 'schedule_blocks',
				'_name' => 'blocks',
				'type' => 'flexible_content',
				'layouts' => [
					[
						'name' => 'event',
						'sub_fields' => [
							['key' => 'field_schedule_block_date', 'name' => 'event_date', '_name' => 'event_date', 'type' => 'date_picker'],
						],
					],
				],
			],
		],
	],
	'events' => [
		'type' => 'repeater',
		'value' => [
			[
				'field_event_date' => '20261110',
				'field_event_start' => '2026-11-10 14:30:45',
				'field_event_time' => '08:45:30',
				'field_event_details' => [
					'field_event_details_date' => '20261111',
				],
			],
		],
		'formatted_value' => [
			[
				'event_date' => '2026年11月10日',
				'event_start' => '2026年11月10日 14時30分',
				'opening_time' => '8:45 am',
				'details' => [
					'event_date' => '2026年11月11日',
				],
			],
		],
		'sub_fields' => [
			['key' => 'field_event_date', 'name' => 'event_date', '_name' => 'event_date', 'type' => 'date_picker'],
			['key' => 'field_event_start', 'name' => 'event_start', '_name' => 'event_start', 'type' => 'date_time_picker'],
			['key' => 'field_event_time', 'name' => 'opening_time', '_name' => 'opening_time', 'type' => 'time_picker'],
			[
				'key' => 'field_event_details',
				'name' => 'details',
				'_name' => 'details',
				'type' => 'group',
				'sub_fields' => [
					['key' => 'field_event_details_date', 'name' => 'event_date', '_name' => 'event_date', 'type' => 'date_picker'],
				],
			],
		],
	],
	'content_blocks' => [
		'type' => 'flexible_content',
		'value' => [
			['acf_fc_layout' => 'event', 'field_block_date' => '20261114'],
		],
		'formatted_value' => [
			['acf_fc_layout' => 'event', 'event_date' => '2026年11月14日'],
		],
		'layouts' => [
			[
				'name' => 'event',
				'sub_fields' => [
					['key' => 'field_block_date', 'name' => 'event_date', '_name' => 'event_date', 'type' => 'date_picker'],
				],
			],
		],
	],
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
$check((new AcfGet(['group' => ['event_date' => '20261107']]))->Nested('group')->Date('event_date') === null, 'array Nested source is unsupported');

$scheduleGet = $get->Nested('schedule');
$check($scheduleGet->Text('event_date') === '2026年11月8日', 'Nested keeps formatted values for display');
$check($scheduleGet->Date('event_date')?->format('Y-m-d') === '2026-11-08', 'Nested Date uses raw group value');
$check($scheduleGet->DateTime('event_start')?->format('Y-m-d H:i:s') === '2026-11-08 16:30:45', 'Nested DateTime uses raw group value');
$check($scheduleGet->Time('opening_time')?->Format() === '10:15:30', 'Nested Time uses raw group value');
$check($scheduleGet->Date('date_text') === null, 'Nested validates temporal field type');
$check($scheduleGet->Nested('details')->Date('event_date')?->format('Y-m-d') === '2026-11-09', 'multiple Nested levels retain raw values');
$check($scheduleGet->Repeater('events')[0]->Date('event_date')?->format('Y-m-d') === '2026-11-12', 'Nested Repeater retains temporal context');
$check($scheduleGet->Flexible('blocks')[0]->Get->Date('event_date')?->format('Y-m-d') === '2026-11-13', 'Nested Flexible retains temporal context');

$eventGet = $get->Repeater('events')[0];
$check($eventGet->Text('event_date') === '2026年11月10日', 'Repeater keeps formatted values for display');
$check($eventGet->Date('event_date')?->format('Y-m-d') === '2026-11-10', 'Repeater Date uses raw row value');
$check($eventGet->DateTime('event_start')?->format('Y-m-d H:i:s') === '2026-11-10 14:30:45', 'Repeater DateTime uses raw row value');
$check($eventGet->Time('opening_time')?->Format() === '08:45:30', 'Repeater Time uses raw row value');
$check($eventGet->Nested('details')->Date('event_date')?->format('Y-m-d') === '2026-11-11', 'Repeater Nested retains temporal context');

$block = $get->Flexible('content_blocks')[0] ?? null;
$check($block instanceof FlexibleNode && $block->Layout === 'event', 'Flexible keeps layout');
$check($block?->Get->Text('event_date') === '2026年11月14日', 'Flexible keeps formatted values for display');
$check($block?->Get->Date('event_date')?->format('Y-m-d') === '2026-11-14', 'Flexible Date uses raw row value');

$arrayEventGet = (new AcfGet(['events' => $GLOBALS['temporalFields']['events']['formatted_value']]))->Repeater('events')[0];
$check($arrayEventGet->Date('event_date') === null, 'array Repeater source remains unsupported');
$arrayBlock = (new AcfGet(['content_blocks' => $GLOBALS['temporalFields']['content_blocks']['formatted_value']]))->Flexible('content_blocks')[0];
$check($arrayBlock->Get->Date('event_date') === null, 'array Flexible source remains unsupported');

echo "Temporal accessors: {$checks} checks passed.\n";
