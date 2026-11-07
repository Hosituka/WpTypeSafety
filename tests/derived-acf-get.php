<?php
declare(strict_types=1);

// 派生AcfGetが子取得口でも維持されることをAPIスタブで検証する。
if (PHP_SAPI !== 'cli') {
	exit;
}

use WpTypeSafety\Acf\Data\FlexibleNode;
use WpTypeSafety\Acf\Get\AcfGet;

class WP_Post { public function __construct(public int $ID) {} }

function wp_timezone(): DateTimeZone
{
	return new DateTimeZone('Asia/Tokyo');
}

function get_field(string $name, mixed $source = null): mixed
{
	return $GLOBALS['derivedFields'][$name]['formatted_value'] ?? null;
}

function get_field_object(
	string $name,
	mixed $source = null,
	bool $formatValue = true,
	bool $loadValue = true,
): mixed {
	return $GLOBALS['derivedFields'][$name] ?? false;
}

require_once __DIR__ . '/../Core/Data/TimeData.php';
require_once __DIR__ . '/../Acf/Data/FlexibleNode.php';
require_once __DIR__ . '/../Acf/Get/AcfGet.php';
require_once __DIR__ . '/../Acf/Internal/TemporalValue.php';
require_once __DIR__ . '/../Acf/Internal/AcfAdapter.php';

final class ProjectAcfGet extends AcfGet
{
	public function __construct(int $postId, private readonly string $dependency)
	{
		parent::__construct($postId);
	}

	public function Dependency(): string
	{
		return $this->dependency;
	}
}


$GLOBALS['derivedFields'] = [
	'settings' => [
		'type' => 'group',
		'value' => [
			'field_event_date' => '20261110',
		],
		'formatted_value' => [
			'event_date' => '2026年11月10日',
		],
		'sub_fields' => [
			['key' => 'field_event_date', 'name' => 'settings_event_date', '_name' => 'event_date', 'type' => 'date_picker'],
		],
	],
	'rows' => [
		'type' => 'repeater',
		'value' => [
			['field_row_date' => '20261111'],
		],
		'formatted_value' => [
			['title' => 'Row', 'event_date' => '2026年11月11日'],
		],
		'sub_fields' => [
			['key' => 'field_row_date', 'name' => 'event_date', '_name' => 'event_date', 'type' => 'date_picker'],
		],
	],
	'contents' => [
		'type' => 'flexible_content',
		'value' => [
			['acf_fc_layout' => 'text', 'field_flexible_date' => '20261112'],
		],
		'formatted_value' => [
			['acf_fc_layout' => 'text', 'title' => 'Flexible', 'event_date' => '2026年11月12日'],
		],
		'layouts' => [
			[
				'name' => 'text',
				'sub_fields' => [
					['key' => 'field_flexible_date', 'name' => 'event_date', '_name' => 'event_date', 'type' => 'date_picker'],
				],
			],
		],
	],
];

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};

$get = new ProjectAcfGet(123, 'injected dependency');

$nestedGet = $get->Nested('settings');
$check($nestedGet instanceof ProjectAcfGet, 'Nested preserves derived type');
$check($nestedGet->Dependency() === 'injected dependency', 'Nested preserves injected dependency');
$check($nestedGet->Date('event_date')?->format('Y-m-d') === '2026-11-10', 'Nested preserves temporal context');

$rows = $get->Repeater('rows');
$check(($rows[0] ?? null) instanceof ProjectAcfGet, 'Repeater preserves derived type');
$check($rows[0]->Dependency() === 'injected dependency', 'Repeater preserves injected dependency');
$check($rows[0]->Text('title') === 'Row', 'Repeater child reads row');
$check($rows[0]->Date('event_date')?->format('Y-m-d') === '2026-11-11', 'Repeater preserves temporal context');

$nodes = $get->Flexible('contents');
$check(($nodes[0] ?? null) instanceof FlexibleNode, 'Flexible returns node');
$check($nodes[0]->Get instanceof ProjectAcfGet, 'Flexible Get preserves derived type');
$check($nodes[0]->Get->Dependency() === 'injected dependency', 'Flexible preserves injected dependency');
$check($nodes[0]->Get->Text('title') === 'Flexible', 'Flexible child reads row');
$check($nodes[0]->Get->Date('event_date')?->format('Y-m-d') === '2026-11-12', 'Flexible preserves temporal context');

echo "Derived AcfGet: {$checks} checks passed.\n";
