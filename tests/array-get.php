<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/autoload.php';

use WpTypeSafety\Core\Check\ArrayGet;
use WpTypeSafety\Core\Check\TypeCheck;
use WpTypeSafety\Core\Check\Data\BoolMode;
use WpTypeSafety\Core\Check\Data\NumericMode;
use WpTypeSafety\Core\Check\ValueRules;
use WpTypeSafety\Core\Data\TimeData;

enum ArrayGetPublishStatus: string
{
	case Draft = 'draft';
	case Published = 'published';
}

enum ArrayGetPriority: int
{
	case Low = 0;
	case High = 1;
}

final class DerivedArrayGet extends ArrayGet
{
	public function Label(int|string $key): string
	{
		return $this->String($key);
	}
}

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
	0 => 'Zero index',
	-1 => [
		'title' => 'Negative index',
	],
	1 => ['First index item', 2, 'Second index item'],
	2 => ArrayGetPublishStatus::Published,
	3 => $openingTime,
	'group' => [
		'title' => 'Group',
	],
	'deep_group' => [
		'middle' => [
			'inner' => [
				'title' => 'Deep group',
				'nullable' => null,
			],
			'object' => (object) ['title' => 'Object in array path'],
		],
	],
	'indexed_path' => [
		[
			'inner' => [
				'title' => 'Indexed path',
			],
		],
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

$check($get->String(0) === 'Zero index', 'String reads an integer array key');
$check($get->Exists(0), 'Exists accepts an integer array key');
$check($get->Exists('group'), 'Exists returns true for an existing key');
$check(! $get->Exists('missing'), 'Exists returns false for a missing key');
$check($get->ExistsPath('deep_group', 'middle', 'inner', 'title'), 'ExistsPath returns true for an existing array path');
$check($get->ExistsPath('deep_group', 'middle', 'inner', 'nullable'), 'ExistsPath returns true for an explicit null at the path endpoint');
$check(! $get->ExistsPath('deep_group', 'missing', 'inner'), 'ExistsPath returns false for a missing intermediate key');
$check(! $get->ExistsPath('deep_group', 'middle', 'object', 'title'), 'ExistsPath does not cross from an array to an object');
$check($get->ExistsPath('indexed_path', 0, 'inner', 'title'), 'ExistsPath accepts an integer path segment');
$check($get->Nested(-1)->String('title') === 'Negative index', 'Nested reads a negative integer array key');
$check($get->StringList(1) === ['First index item', 'Second index item'], 'StringList reads an integer array key');
$check($get->BackedEnum(2, ArrayGetPublishStatus::class) === ArrayGetPublishStatus::Published, 'BackedEnum reads an integer array key');
$check($get->TimeData(3) === $openingTime, 'Core Data accessors read an integer array key');
$check($get->NestedPath('indexed_path', 0, 'inner')->String('title') === 'Indexed path', 'NestedPath accepts an integer path segment');
$check($get->Nested('group')->String('title') === 'Group', 'Nested reads an associative array');
$check($get->NestedPath('deep_group', 'middle', 'inner')->String('title') === 'Deep group', 'NestedPath reads a path containing only arrays');
$check($get->NestedPath('deep_group', 'middle', 'object')->String('title') === '', 'NestedPath does not cross from an array to an object');
$check($get->NestedPath('deep_group', 'missing', 'inner')->String('title') === '', 'NestedPath returns an empty getter after a missing segment');
$check($get->NestedObject('object')->String('title') === 'Object', 'NestedObject reads an object value');
$check($get->NestedObject('group')->String('title') === '', 'NestedObject returns an empty ObjectGet for a non-object value');

$transformGet = new ArrayGet([
	'payload' => '{"title":"Transformed","count":"12"}',
	'nullable' => null,
	'nested' => [
		'payload' => '{"child":{"title":"Nested transform"}}',
	],
	0 => ['title' => 'Integer key'],
], NumericMode::Str);
$transformCalls = 0;
$transformedGet = $transformGet->Transform(
	'payload',
	static function (mixed $value) use (&$transformCalls): mixed {
		$transformCalls++;
		return is_string($value) ? json_decode($value, true) : null;
	},
);
$check($transformCalls === 1, 'Transform calls the transformer exactly once');
$check($transformedGet->String('title') === 'Transformed', 'Transform uses an array result as the new source');
$check($transformedGet->Int('count') === 12, 'Transform inherits ValueRules');
$check($transformGet->String('title') === '', 'Transform does not change the original ArrayGet source');
$check(
	$transformGet->Transform(0, static fn(mixed $value): mixed => $value)->String('title') === 'Integer key',
	'Transform accepts an integer key',
);
$check(
	$transformGet
		->Nested('nested')
		->Transform('payload', static fn(mixed $value): mixed => is_string($value) ? json_decode($value, true) : null)
		->Nested('child')
		->String('title') === 'Nested transform',
	'Transform can continue a nested exploration chain',
);

$receivedNull = false;
$nullTransformedGet = $transformGet->Transform(
	'nullable',
	static function (mixed $value) use (&$receivedNull): array {
		$receivedNull = $value === null;
		return ['received' => true];
	},
);
$check($receivedNull, 'Transform passes an explicitly stored null to the transformer');
$check($nullTransformedGet->Bool('received') === true, 'Transform can use the result produced from an explicit null');

$missingTransformCalls = 0;
$missingTransformedGet = $transformGet->Transform(
	'missing',
	static function (mixed $value) use (&$missingTransformCalls): array {
		$missingTransformCalls++;
		return ['unexpected' => $value];
	},
);
$check($missingTransformCalls === 0, 'Transform does not call the transformer for a missing key');
$check($missingTransformedGet->String('unexpected') === '', 'Transform returns an empty getter for a missing key');
$check(
	$transformGet->Transform('payload', static fn(mixed $value): mixed => 'invalid')->String('title') === '',
	'Transform returns an empty getter for a non-array result',
);

$transformException = new RuntimeException('Transform failure');
$caughtTransformException = null;
try {
	$transformGet->Transform(
		'payload',
		static fn(mixed $value): mixed => throw $transformException,
	);
} catch (RuntimeException $exception) {
	$caughtTransformException = $exception;
}
$check($caughtTransformException === $transformException, 'Transform propagates the transformer exception unchanged');

$predicateGet = new ArrayGet([
	'age' => '20',
	'name' => '',
	'enabled' => false,
	'prices' => ['100', -50, 'invalid', 200, 0],
	'settings' => ['color' => 'blue'],
	'invalid_array' => 'invalid',
	'item' => $openingTime,
	'item_list' => [$openingTime, 'invalid', $closingTime],
	'status' => 'published',
	'statuses' => ['draft', 'invalid', 'published'],
], NumericMode::Str);
$receivedAge = null;
$check(
	$predicateGet->Int(
		'age',
		predicate: static function (int $value) use (&$receivedAge): bool {
			$receivedAge = $value;
			return $value >= 18;
		},
	) === 20,
	'ArrayGet applies a predicate after ValueRules normalization',
);
$check($receivedAge === 20, 'ArrayGet passes the normalized value to a predicate');
$check($predicateGet->Int('age', predicate: static fn(int $value): bool => $value > 20) === null, 'ArrayGet returns null when an Int predicate rejects the value');
$check($predicateGet->String('name', predicate: static fn(string $value): bool => $value === '') === '', 'ArrayGet passes a valid empty string to a predicate');
$check($predicateGet->Bool('enabled', predicate: static fn(bool $value): bool => ! $value) === false, 'ArrayGet passes a valid false to a predicate');
$check(
	$predicateGet->IntList('prices', predicate: static fn(int $value): bool => $value > 0) === [100, 200],
	'ArrayGet filters normalized list values with a predicate and reindexes them',
);
$check($predicateGet->Array('settings', predicate: static fn(array $value): bool => array_key_exists('color', $value)) === ['color' => 'blue'], 'ArrayGet applies a predicate to an entire array');
$check($predicateGet->Array('settings', predicate: static fn(array $value): bool => false) === [], 'ArrayGet returns an empty array when an Array predicate rejects the value');

$skippedPredicateCalls = 0;
$predicateGet->Int('missing', predicate: static function (int $value) use (&$skippedPredicateCalls): bool {
	$skippedPredicateCalls++;
	return true;
});
$predicateGet->Array('invalid_array', predicate: static function (array $value) use (&$skippedPredicateCalls): bool {
	$skippedPredicateCalls++;
	return true;
});
$check($skippedPredicateCalls === 0, 'ArrayGet does not call predicates for missing or wrong-type values');

$check($predicateGet->Instance('item', TimeData::class, predicate: static fn(TimeData $value): bool => $value->Hour < 12) === $openingTime, 'ArrayGet applies a predicate to an instance');
$check($predicateGet->TimeData('item', predicate: static fn(TimeData $value): bool => $value->Hour >= 12) === null, 'ArrayGet Data accessors delegate a predicate to Instance');
$check($predicateGet->TimeDataList('item_list', predicate: static fn(TimeData $value): bool => $value->Hour >= 12) === [$closingTime], 'ArrayGet Data list accessors delegate a predicate to InstanceList');
$check($predicateGet->BackedEnum('status', ArrayGetPublishStatus::class, predicate: static fn(ArrayGetPublishStatus $value): bool => $value === ArrayGetPublishStatus::Published) === ArrayGetPublishStatus::Published, 'ArrayGet applies a predicate after backed Enum normalization');
$check($predicateGet->BackedEnumList('statuses', ArrayGetPublishStatus::class, predicate: static fn(ArrayGetPublishStatus $value): bool => $value === ArrayGetPublishStatus::Published) === [ArrayGetPublishStatus::Published], 'ArrayGet filters a backed Enum list with a predicate');

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

$numericGet = new ArrayGet([
	'int' => '12',
	'negative_int' => '-12',
	'leading_zero_int' => '01',
	'plus_int' => '+1',
	'plus_max_int' => '+' . PHP_INT_MAX,
	'negative_zero_int' => '-0',
	'max_int' => (string) PHP_INT_MAX,
	'min_int' => (string) PHP_INT_MIN,
	'integral_float' => 12.0,
	'infinite_float' => INF,
	'nan_float' => NAN,
	'decimal_int' => '12.5',
	'one_dot_zero_int' => '1.0',
	'exponent_int' => '1e2',
	'spaced_int' => ' 12 ',
	'spaced_negative_int' => "\t-12\n",
	'internal_space_int' => '1 2',
	'overflow_int' => (string) PHP_INT_MAX . '0',
	'plus_overflow_int' => '+' . PHP_INT_MAX . '0',
	'huge_float' => '1e400',
	'float' => '12.5',
	'float_from_int' => 12,
	'bool' => '1',
	'string' => 12,
	'ints' => [1, '2', '2.5', 3.0, false],
	'floats' => [1.5, '2.5', 3, 'invalid'],
	'invalid_ints' => ['invalid', false, null],
	'nested' => ['count' => '4'],
	'nested_path' => ['middle' => ['count' => '8']],
	'nested_list' => [
		['count' => '5'],
	],
	'nested_object' => (object) ['count' => '6'],
	'nested_object_list' => [
		(object) ['count' => '7'],
	],
], NumericMode::Str);

$check($numericGet->Int('int') === 12, 'NumericStr Int normalizes an integer string');
$check($numericGet->Int('negative_int') === -12, 'NumericStr Int normalizes a negative integer string');
$check($numericGet->Int('leading_zero_int') === 1, 'NumericStr Int accepts leading zeroes');
$check($numericGet->Int('plus_int') === 1, 'NumericStr Int accepts an explicit plus sign');
$check($numericGet->Int('plus_max_int') === PHP_INT_MAX, 'NumericStr Int accepts a plus-signed PHP_INT_MAX');
$check($numericGet->Int('negative_zero_int') === 0, 'NumericStr Int normalizes negative zero');
$check($numericGet->Int('max_int') === PHP_INT_MAX, 'NumericStr Int accepts PHP_INT_MAX');
$check($numericGet->Int('min_int') === PHP_INT_MIN, 'NumericStr Int accepts PHP_INT_MIN');
$check($numericGet->Int('integral_float') === 12, 'NumericStr Int normalizes an exactly representable integral float');
$check($numericGet->Int('infinite_float') === null, 'NumericStr Int rejects INF');
$check($numericGet->Int('nan_float') === null, 'NumericStr Int rejects NAN');
$check($numericGet->Int('decimal_int') === null, 'NumericStr Int rejects a decimal string');
$check($numericGet->Int('one_dot_zero_int') === null, 'NumericStr Int rejects a decimal-formatted integer string');
$check($numericGet->Int('exponent_int') === null, 'NumericStr Int rejects exponent notation');
$check($numericGet->Int('spaced_int') === 12, 'NumericStr Int accepts surrounding ASCII whitespace');
$check($numericGet->Int('spaced_negative_int') === -12, 'NumericStr Int accepts surrounding ASCII whitespace around a negative integer');
$check($numericGet->Int('internal_space_int') === null, 'NumericStr Int rejects whitespace inside digits');
$check($numericGet->Int('overflow_int') === null, 'NumericStr Int rejects an out-of-range integer string');
$check($numericGet->Int('plus_overflow_int') === null, 'NumericStr Int rejects a plus-signed out-of-range integer string');
$check($numericGet->Float('float') === 12.5, 'NumericStr Float normalizes a numeric string');
$check($numericGet->Float('float_from_int') === 12.0, 'NumericStr Float normalizes an integer');
$check($numericGet->Float('exponent_int') === 100.0, 'NumericStr Float follows PHP exponent conversion');
$check($numericGet->Float('spaced_int') === 12.0, 'NumericStr Float follows PHP numeric-string whitespace handling');
$check(is_infinite($numericGet->Float('infinite_float') ?? 0.0), 'NumericStr Float preserves INF');
$check(is_nan($numericGet->Float('nan_float') ?? 0.0), 'NumericStr Float preserves NAN');
$check(is_infinite($numericGet->Float('huge_float') ?? 0.0), 'NumericStr Float can overflow a numeric string to INF');
$check($numericGet->Bool('bool') === null, 'NumericStr keeps Bool strict');
$check($numericGet->String('string') === '', 'NumericStr keeps String strict');
$check($numericGet->IntList('ints') === [1, 2, 3], 'NumericStr IntList normalizes valid elements and filters invalid elements');
$check($numericGet->FloatList('floats') === [1.5, 2.5, 3.0], 'NumericStr FloatList normalizes valid elements and filters invalid elements');
$check($numericGet->IntList('invalid_ints') === [], 'IntList returns an empty list when every element is rejected');
$check($numericGet->Nested('nested')->Int('count') === 4, 'Nested inherits NumericStr rules');
$check($numericGet->NestedPath('nested_path', 'middle')->Int('count') === 8, 'NestedPath inherits NumericStr rules');
$check($numericGet->NestedList('nested_list')[0]->Int('count') === 5, 'NestedList inherits NumericStr rules');
$check($numericGet->NestedObject('nested_object')->Int('count') === 6, 'NestedObject inherits NumericStr rules');
$check($numericGet->NestedObjectList('nested_object_list')[0]->Int('count') === 7, 'NestedObjectList inherits NumericStr rules');

$strictGet = new ArrayGet(['int' => '12', 'float' => 12]);
$check($strictGet->Int('int') === null, 'Strict remains the default mode');
$check($strictGet->Float('float') === null, 'Strict Float does not normalize an integer');

$rules = ValueRules::Strict();
$rules->IntNormalizer = static fn(mixed $value): ?int => $value === 'custom' ? 42 : null;
$customGet = new ArrayGet([
	'value' => 'custom',
	'nested' => ['value' => 'custom'],
], $rules);
$rules->IntNormalizer = static fn(mixed $value): ?int => 99;

$check($customGet->Int('value') === 42, 'ValueRules can partially override Int normalization');
$check($customGet->Nested('nested')->Int('value') === 42, 'Nested inherits a custom rule snapshot');
$check($customGet->Int('missing') === null, 'A missing key keeps the accessor fallback');

$nullRules = ValueRules::Strict();
$nullRules->IntNormalizer = static fn(mixed $value): ?int => $value === null
	? 100
	: TypeCheck::Int($value);
$nullGet = new ArrayGet([
	'null_value' => null,
	'null_items' => [1, null, 2],
], $nullRules);
$check($nullGet->Exists('null_value'), 'Exists returns true for an explicitly stored null');
$check($nullGet->Int('missing') === null, 'A missing key bypasses the custom normalizer');
$check($nullGet->Int('null_value') === 100, 'An explicitly stored null is passed to the custom normalizer');
$check($nullGet->IntList('null_items') === [1, 100, 2], 'An explicit null list element is passed to the custom normalizer');

$switchGet = new ArrayGet([
	'count' => '10',
	'nested' => ['count' => '11'],
]);
$numericSwitchGet = $switchGet->WithModes(NumericMode::Str);
$check($switchGet->Int('count') === null, 'WithModes does not mutate the original ArrayGet');
$check($numericSwitchGet->Int('count') === 10, 'WithModes changes the value mode without ending the chain');
$check($numericSwitchGet->Nested('nested')->Int('count') === 11, 'A mode selected with WithModes is inherited by Nested');
$check($numericSwitchGet->WithModes()->Int('count') === null, 'WithModes can return a chain to Strict');
$check($switchGet->WithRules($rules)->Int('count') === 99, 'WithRules replaces the current rules without mutating the source getter');

$booleanGet = new ArrayGet([
	'true' => 'true',
	'false' => 'false',
	'one' => '1',
	'zero' => '0',
	'int_one' => 1,
	'int_zero' => 0,
	'yes' => 'yes',
	'uppercase_true' => 'TRUE',
	'spaced_false' => ' false ',
	'native_true' => true,
	'native_false' => false,
], BoolMode::Str);
$check($booleanGet->Bool('true') === true, 'BoolStr normalizes the true string');
$check($booleanGet->Bool('false') === false, 'BoolStr normalizes the false string');
$check($booleanGet->Bool('one') === true, 'BoolStr normalizes the one string');
$check($booleanGet->Bool('zero') === false, 'BoolStr normalizes the zero string');
$check($booleanGet->Bool('int_one') === null, 'BoolStr does not normalize an integer');
$check($booleanGet->Bool('int_zero') === null, 'BoolStr does not normalize integer zero');
$check($booleanGet->Bool('yes') === true, 'BoolStr accepts the yes string');
$check($booleanGet->Bool('uppercase_true') === true, 'BoolStr compares ASCII text case-insensitively');
$check($booleanGet->Bool('spaced_false') === null, 'BoolStr rejects surrounding whitespace');
$check($booleanGet->Bool('native_true') === true, 'BoolStr preserves native true');
$check($booleanGet->Bool('native_false') === false, 'BoolStr preserves native false');

$booleanIntGet = new ArrayGet([
	'one' => 1,
	'zero' => 0,
	'true_string' => 'true',
	'one_string' => '1',
	'two' => 2,
	'native_true' => true,
	'native_false' => false,
], BoolMode::OneZeroInt);
$check($booleanIntGet->Bool('one') === true, 'BoolInt normalizes integer one');
$check($booleanIntGet->Bool('zero') === false, 'BoolInt normalizes integer zero');
$check($booleanIntGet->Bool('true_string') === null, 'BoolInt does not normalize a boolean string');
$check($booleanIntGet->Bool('one_string') === null, 'BoolInt does not normalize a numeric string');
$check($booleanIntGet->Bool('two') === null, 'BoolInt rejects integers other than zero and one');
$check($booleanIntGet->Bool('native_true') === true, 'BoolInt preserves native true');
$check($booleanIntGet->Bool('native_false') === false, 'BoolInt preserves native false');

$combinedRules = ValueRules::FromModes(
	NumericMode::Str,
	BoolMode::Str,
	BoolMode::OneZeroInt,
);
$combinedGet = new ArrayGet([
	'count' => '30',
	'enabled' => 'true',
	'published' => 1,
	'nested' => [
		'ratio' => '1.5',
		'visible' => '0',
	],
], $combinedRules);
$check($combinedGet->Int('count') === 30, 'FromModes composes NumericStr normalization');
$check($combinedGet->Bool('enabled') === true, 'FromModes composes BoolStr normalization');
$check($combinedGet->Bool('published') === true, 'FromModes composes BoolInt normalization');
$check($combinedGet->Nested('nested')->Float('ratio') === 1.5, 'Nested inherits composed numeric rules');
$check($combinedGet->Nested('nested')->Bool('visible') === false, 'Nested inherits composed boolean rules');

$emptyStringGet = new ArrayGet([
	'empty' => '',
	'false_string' => 'false',
], BoolMode::EmptyStrAsFalse);
$check($emptyStringGet->Bool('empty') === false, 'EmptyStrAsFalse normalizes an empty string');
$check($emptyStringGet->Bool('false_string') === null, 'EmptyStrAsFalse does not include BoolStr rules');
$check($emptyStringGet->Bool('missing') === null, 'EmptyStrAsFalse keeps a missing value as null');

$booleanThenEmptyGet = new ArrayGet([
	'empty' => '',
	'true' => 'true',
], ValueRules::FromModes(
	BoolMode::Str,
	BoolMode::EmptyStrAsFalse,
));
$emptyThenBooleanGet = new ArrayGet([
	'empty' => '',
	'true' => 'true',
], ValueRules::FromModes(
	BoolMode::EmptyStrAsFalse,
	BoolMode::Str,
));
$check($booleanThenEmptyGet->Bool('empty') === false, 'BoolStr then EmptyStrAsFalse accepts an empty string');
$check($booleanThenEmptyGet->Bool('true') === true, 'BoolStr then EmptyStrAsFalse accepts a boolean string');
$check($emptyThenBooleanGet->Bool('empty') === false, 'EmptyStrAsFalse then BoolStr accepts an empty string');
$check($emptyThenBooleanGet->Bool('true') === true, 'EmptyStrAsFalse then BoolStr accepts a boolean string');

$duplicateModeGet = new ArrayGet([
	'enabled' => 'true',
	'count' => '50',
], ValueRules::FromModes(
	BoolMode::Str,
	BoolMode::Str,
	NumericMode::Str,
));
$check($duplicateModeGet->Bool('enabled') === true, 'Applying the same mode twice is idempotent');
$check($duplicateModeGet->Int('count') === 50, 'Duplicate modes do not reset previously composed modes');

$enumGet = new ArrayGet([
	'status' => 'published',
	'instance' => ArrayGetPublishStatus::Draft,
	'invalid' => 'archived',
	'priority' => 1,
	'priority_string' => '1',
	'statuses' => ['draft', 'invalid', ArrayGetPublishStatus::Published],
	'priorities' => [0, 1, 2, '1'],
]);
$check($enumGet->BackedEnum('status', ArrayGetPublishStatus::class) === ArrayGetPublishStatus::Published, 'BackedEnum normalizes a string backing value');
$check($enumGet->BackedEnum('instance', ArrayGetPublishStatus::class) === ArrayGetPublishStatus::Draft, 'BackedEnum preserves an existing enum instance');
$check($enumGet->BackedEnum('invalid', ArrayGetPublishStatus::class) === null, 'BackedEnum returns null for an unknown backing value');
$check($enumGet->BackedEnum('missing', ArrayGetPublishStatus::class) === null, 'BackedEnum returns null for a missing key');
$check($enumGet->BackedEnum('priority', ArrayGetPriority::class) === ArrayGetPriority::High, 'BackedEnum normalizes an integer backing value');
$check($enumGet->BackedEnum('priority_string', ArrayGetPriority::class) === null, 'Strict BackedEnum rejects a numeric string for an int-backed enum');
$check($enumGet->BackedEnumList('statuses', ArrayGetPublishStatus::class) === [ArrayGetPublishStatus::Draft, ArrayGetPublishStatus::Published], 'BackedEnumList filters unknown backing values and preserves instances');
$check($enumGet->BackedEnumList('priorities', ArrayGetPriority::class) === [ArrayGetPriority::Low, ArrayGetPriority::High], 'Strict BackedEnumList filters values with the wrong backing type');
$check($enumGet->BackedEnumList('missing', ArrayGetPublishStatus::class) === [], 'BackedEnumList returns an empty list for a missing key');

$numericEnumGet = new ArrayGet([
	'priority' => '1',
	'priorities' => ['0', '1', 'invalid'],
], NumericMode::Str);
$check($numericEnumGet->BackedEnum('priority', ArrayGetPriority::class) === ArrayGetPriority::High, 'NumericStr applies to an int-backed enum');
$check($numericEnumGet->BackedEnumList('priorities', ArrayGetPriority::class) === [ArrayGetPriority::Low, ArrayGetPriority::High], 'NumericStr applies to an int-backed enum list');

$invalidRules = ValueRules::Strict();
$invalidRules->IntNormalizer = static fn(mixed $value): mixed => 'invalid';
$normalizerTypeErrorWasThrown = false;
try {
	(new ArrayGet(['value' => 1], $invalidRules))->Int('value');
} catch (TypeError) {
	$normalizerTypeErrorWasThrown = true;
}
$check($normalizerTypeErrorWasThrown, 'NormalizeInt return type rejects an invalid custom normalizer result at runtime');

$normalizerListTypeErrorWasThrown = false;
try {
	(new ArrayGet(['values' => [1]], $invalidRules))->IntList('values');
} catch (TypeError) {
	$normalizerListTypeErrorWasThrown = true;
}
$check($normalizerListTypeErrorWasThrown, 'IntList rejects an invalid custom normalizer element at runtime');

$derivedGet = new DerivedArrayGet([
	'group' => ['label' => 'Nested'],
	'payload' => '{"label":"Transformed"}',
	'items' => [
		['label' => 'First'],
		['label' => 'Second'],
	],
	'count' => '12',
]);
$check($derivedGet->Nested('group') instanceof DerivedArrayGet, 'Nested preserves an ArrayGet subclass');
$check($derivedGet->NestedPath('group')->Label('label') === 'Nested', 'NestedPath preserves subclass accessors');
$check(
	$derivedGet->Transform(
		'payload',
		static fn(mixed $value): array => is_string($value)
			? (json_decode($value, true) ?: [])
			: [],
	) instanceof DerivedArrayGet,
	'Transform preserves an ArrayGet subclass',
);
$check($derivedGet->WithModes(NumericMode::Str) instanceof DerivedArrayGet, 'WithModes preserves an ArrayGet subclass');
$check($derivedGet->WithRules(ValueRules::FromModes(NumericMode::Str)) instanceof DerivedArrayGet, 'WithRules preserves an ArrayGet subclass');
$check(
	array_reduce(
		$derivedGet->NestedList('items'),
		static fn(bool $result, ArrayGet $item): bool => $result && $item instanceof DerivedArrayGet,
		true,
	),
	'NestedList preserves an ArrayGet subclass for every child',
);

echo "ArrayGet: {$checks} checks passed\n";
