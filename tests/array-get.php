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
	'objects' => [
		(object) ['title' => 'First object'],
		'invalid',
		(object) ['title' => 'Second object'],
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

$items = $get->NestedList('items');
$check(count($items) === 2, 'NestedList filters non-array items');
$check($items[0] instanceof ArrayGet, 'NestedList returns ArrayGet instances');
$check($items[0]->String('title') === 'First', 'NestedList reads the first item');
$check($items[1]->String('title') === 'Second', 'NestedList preserves array item order');
$check($get->NestedList('missing') === [], 'NestedList returns an empty list for a missing key');
$check($get->NestedList('invalid_items') === [], 'NestedList returns an empty list for a non-array value');

$objects = $get->NestedObjectList('objects');
$check(count($objects) === 2, 'NestedObjectList filters non-object items');
$check($objects[0]->String('title') === 'First object', 'NestedObjectList reads the first object');
$check($objects[1]->String('title') === 'Second object', 'NestedObjectList preserves object order');
$check($get->NestedObjectList('missing') === [], 'NestedObjectList returns an empty list for a missing key');
$check($get->TimeData('opening_time') === $openingTime, 'TimeData returns a TimeData instance');
$check($get->TimeData('missing') === null, 'TimeData returns null for a missing key');
$check($get->TimeDataList('times') === [$openingTime, $closingTime], 'TimeDataList filters non-TimeData items');
$check($get->TimeDataList('missing') === [], 'TimeDataList returns an empty list for a missing key');

echo "ArrayGet: {$checks} checks passed\n";
