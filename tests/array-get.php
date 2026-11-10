<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/autoload.php';

use WpTypeSafety\Core\Check\ArrayGet;
use WpTypeSafety\Core\Check\TypeCheck;
use WpTypeSafety\Core\Check\ValueMode;
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

$numericGet = new ArrayGet([
	'int' => '12',
	'negative_int' => '-12',
	'leading_zero_int' => '01',
	'plus_int' => '+1',
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
	'overflow_int' => (string) PHP_INT_MAX . '0',
	'huge_float' => '1e400',
	'float' => '12.5',
	'float_from_int' => 12,
	'bool' => '1',
	'string' => 12,
	'ints' => [1, '2', '2.5', 3.0, false],
	'floats' => [1.5, '2.5', 3, 'invalid'],
	'invalid_ints' => ['invalid', false, null],
	'nested' => ['count' => '4'],
	'nested_list' => [
		['count' => '5'],
	],
	'nested_object' => (object) ['count' => '6'],
	'nested_object_list' => [
		(object) ['count' => '7'],
	],
], ValueMode::NumericStr);

$check($numericGet->Int('int') === 12, 'NumericStr Int normalizes an integer string');
$check($numericGet->Int('negative_int') === -12, 'NumericStr Int normalizes a negative integer string');
$check($numericGet->Int('leading_zero_int') === 1, 'NumericStr Int accepts leading zeroes');
$check($numericGet->Int('plus_int') === null, 'NumericStr Int rejects an explicit plus sign');
$check($numericGet->Int('negative_zero_int') === 0, 'NumericStr Int normalizes negative zero');
$check($numericGet->Int('max_int') === PHP_INT_MAX, 'NumericStr Int accepts PHP_INT_MAX');
$check($numericGet->Int('min_int') === PHP_INT_MIN, 'NumericStr Int accepts PHP_INT_MIN');
$check($numericGet->Int('integral_float') === 12, 'NumericStr Int normalizes an exactly representable integral float');
$check($numericGet->Int('infinite_float') === null, 'NumericStr Int rejects INF');
$check($numericGet->Int('nan_float') === null, 'NumericStr Int rejects NAN');
$check($numericGet->Int('decimal_int') === null, 'NumericStr Int rejects a decimal string');
$check($numericGet->Int('one_dot_zero_int') === null, 'NumericStr Int rejects a decimal-formatted integer string');
$check($numericGet->Int('exponent_int') === null, 'NumericStr Int rejects exponent notation');
$check($numericGet->Int('spaced_int') === null, 'NumericStr Int rejects surrounding whitespace');
$check($numericGet->Int('overflow_int') === null, 'NumericStr Int rejects an out-of-range integer string');
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
$check($numericGet->NestedList('nested_list')[0]->Int('count') === 5, 'NestedList inherits NumericStr rules');
$check($numericGet->NestedObject('nested_object')->Int('count') === 6, 'NestedObject inherits NumericStr rules');
$check($numericGet->NestedObjectList('nested_object_list')[0]->Int('count') === 7, 'NestedObjectList inherits NumericStr rules');

$strictGet = new ArrayGet(['int' => '12', 'float' => 12]);
$check($strictGet->Int('int') === null, 'Strict remains the default mode');
$check($strictGet->Float('float') === null, 'Strict Float does not normalize an integer');

$rules = ValueRules::FromMode(ValueMode::Strict);
$rules->IntNormalizer = static fn(mixed $value): ?int => $value === 'custom' ? 42 : null;
$customGet = new ArrayGet([
	'value' => 'custom',
	'nested' => ['value' => 'custom'],
], $rules);
$rules->IntNormalizer = static fn(mixed $value): ?int => 99;

$check($customGet->Int('value') === 42, 'ValueRules can partially override Int normalization');
$check($customGet->Nested('nested')->Int('value') === 42, 'Nested inherits a custom rule snapshot');
$check($customGet->Int('missing') === null, 'A missing key keeps the accessor fallback');

$nullRules = ValueRules::FromMode(ValueMode::Strict);
$nullRules->IntNormalizer = static fn(mixed $value): ?int => $value === null
	? 100
	: TypeCheck::Int($value);
$nullGet = new ArrayGet([
	'null_value' => null,
	'null_items' => [1, null, 2],
], $nullRules);
$check($nullGet->Int('missing') === null, 'A missing key bypasses the custom normalizer');
$check($nullGet->Int('null_value') === 100, 'An explicitly stored null is passed to the custom normalizer');
$check($nullGet->IntList('null_items') === [1, 100, 2], 'An explicit null list element is passed to the custom normalizer');

$switchGet = new ArrayGet([
	'count' => '10',
	'nested' => ['count' => '11'],
]);
$numericSwitchGet = $switchGet->WithMode(ValueMode::NumericStr);
$check($switchGet->Int('count') === null, 'WithMode does not mutate the original ArrayGet');
$check($numericSwitchGet->Int('count') === 10, 'WithMode changes the value mode without ending the chain');
$check($numericSwitchGet->Nested('nested')->Int('count') === 11, 'A mode selected with WithMode is inherited by Nested');
$check($numericSwitchGet->WithMode(ValueMode::Strict)->Int('count') === null, 'WithMode can return a chain to Strict');
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
], ValueMode::BoolStr);
$check($booleanGet->Bool('true') === true, 'BoolStr normalizes the true string');
$check($booleanGet->Bool('false') === false, 'BoolStr normalizes the false string');
$check($booleanGet->Bool('one') === true, 'BoolStr normalizes the one string');
$check($booleanGet->Bool('zero') === false, 'BoolStr normalizes the zero string');
$check($booleanGet->Bool('int_one') === null, 'BoolStr does not normalize an integer');
$check($booleanGet->Bool('int_zero') === null, 'BoolStr does not normalize integer zero');
$check($booleanGet->Bool('yes') === null, 'BoolStr rejects an unspecified truthy string');
$check($booleanGet->Bool('uppercase_true') === null, 'BoolStr rejects uppercase text');
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
], ValueMode::BoolInt);
$check($booleanIntGet->Bool('one') === true, 'BoolInt normalizes integer one');
$check($booleanIntGet->Bool('zero') === false, 'BoolInt normalizes integer zero');
$check($booleanIntGet->Bool('true_string') === null, 'BoolInt does not normalize a boolean string');
$check($booleanIntGet->Bool('one_string') === null, 'BoolInt does not normalize a numeric string');
$check($booleanIntGet->Bool('two') === null, 'BoolInt rejects integers other than zero and one');
$check($booleanIntGet->Bool('native_true') === true, 'BoolInt preserves native true');
$check($booleanIntGet->Bool('native_false') === false, 'BoolInt preserves native false');

$combinedRules = ValueRules::FromModes(
	ValueMode::NumericStr,
	ValueMode::Strict,
	ValueMode::BoolStr,
	ValueMode::BoolInt,
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
], ValueMode::EmptyStrAsFalse);
$check($emptyStringGet->Bool('empty') === false, 'EmptyStrAsFalse normalizes an empty string');
$check($emptyStringGet->Bool('false_string') === null, 'EmptyStrAsFalse does not include BoolStr rules');
$check($emptyStringGet->Bool('missing') === null, 'EmptyStrAsFalse keeps a missing value as null');

$booleanThenEmptyGet = new ArrayGet([
	'empty' => '',
	'true' => 'true',
], ValueRules::FromModes(
	ValueMode::BoolStr,
	ValueMode::EmptyStrAsFalse,
));
$emptyThenBooleanGet = new ArrayGet([
	'empty' => '',
	'true' => 'true',
], ValueRules::FromModes(
	ValueMode::EmptyStrAsFalse,
	ValueMode::BoolStr,
));
$check($booleanThenEmptyGet->Bool('empty') === false, 'BoolStr then EmptyStrAsFalse accepts an empty string');
$check($booleanThenEmptyGet->Bool('true') === true, 'BoolStr then EmptyStrAsFalse accepts a boolean string');
$check($emptyThenBooleanGet->Bool('empty') === false, 'EmptyStrAsFalse then BoolStr accepts an empty string');
$check($emptyThenBooleanGet->Bool('true') === true, 'EmptyStrAsFalse then BoolStr accepts a boolean string');

$duplicateModeGet = new ArrayGet([
	'enabled' => 'true',
	'count' => '50',
], ValueRules::FromModes(
	ValueMode::BoolStr,
	ValueMode::BoolStr,
	ValueMode::NumericStr,
	ValueMode::Strict,
));
$check($duplicateModeGet->Bool('enabled') === true, 'Applying the same mode twice is idempotent');
$check($duplicateModeGet->Int('count') === 50, 'Strict at the end does not reset previously composed modes');

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
], ValueMode::NumericStr);
$check($numericEnumGet->BackedEnum('priority', ArrayGetPriority::class) === ArrayGetPriority::High, 'NumericStr applies to an int-backed enum');
$check($numericEnumGet->BackedEnumList('priorities', ArrayGetPriority::class) === [ArrayGetPriority::Low, ArrayGetPriority::High], 'NumericStr applies to an int-backed enum list');

$invalidRules = ValueRules::FromMode(ValueMode::Strict);
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

echo "ArrayGet: {$checks} checks passed\n";
