<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/autoload.php';

use WpTypeSafety\Core\Check\ArrayGet;
use WpTypeSafety\Core\Data\TimeData;

$checks = 0;
$check = static function (bool $condition, string $message) use (&$checks): void {
	if (!$condition) {
		throw new RuntimeException($message);
	}
	$checks++;
};

$openingTime = new TimeData(Hour: 9, Minute: 30, Second: 0);
$closingTime = new TimeData(Hour: 18, Minute: 0, Second: 0);
$get = new ArrayGet([
	'group' => [
		'title' => 'Group',
	],
	'object' => (object) [
		'title' => 'Object',
	],
	'items' => [
		['title' => 'First'],
		'invalid',
		['title' => 'Second'],
	],
	'invalid_items' => 'invalid',
	'opening_time' => $openingTime,
	'times' => [
		$openingTime,
		'invalid',
		$closingTime,
	],
]);

$check($get->Nested('group')->String('title') === 'Group', 'Nested reads an associative array');
$check($get->NestedObject('object')->String('title') === 'Object', 'NestedObject reads an object value');
$check($get->NestedObject('group')->String('title') === '', 'NestedObject returns an empty ObjectGet for a non-object value');

$items = $get->NestedArray('items');
$check(count($items) === 2, 'NestedArray filters non-array items');
$check($items[0] instanceof ArrayGet, 'NestedArray returns ArrayGet instances');
$check($items[0]->String('title') === 'First', 'NestedArray reads the first item');
$check($items[1]->String('title') === 'Second', 'NestedArray preserves array item order');
$check($get->NestedArray('missing') === [], 'NestedArray returns an empty list for a missing key');
$check($get->NestedArray('invalid_items') === [], 'NestedArray returns an empty list for a non-array value');
$check($get->TimeData('opening_time') === $openingTime, 'TimeData returns a TimeData instance');
$check($get->TimeData('missing') === null, 'TimeData returns null for a missing key');
$check($get->TimeDataArray('times') === [$openingTime, $closingTime], 'TimeDataArray filters non-TimeData items');
$check($get->TimeDataArray('missing') === [], 'TimeDataArray returns an empty list for a missing key');

echo "ArrayGet: {$checks} checks passed\n";
