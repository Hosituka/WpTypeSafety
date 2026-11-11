<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/autoload.php';

use WpTypeSafety\Core\Check\ValueMode;
use WpTypeSafety\Core\Check\ValueReader;
use WpTypeSafety\Core\Check\ValueRules;

enum ValueReaderStatus: string
{
	case Draft = 'draft';
	case Published = 'published';
}

final class ValueReaderItem
{
}

final class DerivedValueReader extends ValueReader
{
}

$checks = 0;
$check = static function (bool $condition, string $message) use (&$checks): void {
	if (! $condition) {
		throw new RuntimeException($message);
	}

	$checks++;
};

$strictReader = new ValueReader();
$check($strictReader->String('value') === 'value', 'ValueReader accepts a string in the default Strict mode');
$check($strictReader->String(1) === null, 'ValueReader rejects a non-string in the default Strict mode');
$check($strictReader->Int(1) === 1, 'ValueReader accepts an int in the default Strict mode');
$check($strictReader->Int('1') === null, 'ValueReader does not normalize a numeric string by default');
$check($strictReader->Float(1) === null, 'ValueReader does not normalize an int to float by default');
$check($strictReader->Bool(false) === false, 'ValueReader preserves false in the default Strict mode');
$check($strictReader->IntList([1, '2', 3]) === [1, 3], 'ValueReader Strict lists filter values of the wrong type');
$check($strictReader->IntList('invalid') === null, 'ValueReader lists return null for a non-array input');

$numericReader = new ValueReader(ValueMode::NumericStr);
$check($numericReader->Int('12') === 12, 'ValueReader NumericStr normalizes an integer string');
$check($numericReader->Float('1.5') === 1.5, 'ValueReader NumericStr normalizes a float string');
$check($numericReader->IntList(['1', 'invalid', 2]) === [1, 2], 'ValueReader NumericStr normalizes list elements');
$check($strictReader->Int('12') === null, 'Creating a configured ValueReader does not affect another reader');

$booleanReader = new ValueReader(ValueRules::FromModes(
	ValueMode::BoolStr,
	ValueMode::BoolInt,
	ValueMode::EmptyStrAsFalse,
));
$check($booleanReader->Bool('true') === true, 'ValueReader composes BoolStr');
$check($booleanReader->Bool(0) === false, 'ValueReader composes BoolInt');
$check($booleanReader->Bool('') === false, 'ValueReader composes EmptyStrAsFalse');

$check(
	$strictReader->BackedEnum('published', ValueReaderStatus::class) === ValueReaderStatus::Published,
	'ValueReader reads a backed Enum',
);
$check(
	$strictReader->BackedEnumList(['draft', 'invalid', 'published'], ValueReaderStatus::class) === [ValueReaderStatus::Draft, ValueReaderStatus::Published],
	'ValueReader filters invalid backed Enum list elements',
);

$array = ['value' => 1];
$item = new ValueReaderItem();
$check($strictReader->Array($array) === $array, 'ValueReader returns an array without conversion');
$check($strictReader->Array('invalid') === null, 'ValueReader rejects a non-array');
$check($strictReader->Instance($item, ValueReaderItem::class) === $item, 'ValueReader reads an instance');
$check($strictReader->InstanceList([$item, 'invalid'], ValueReaderItem::class) === [$item], 'ValueReader filters invalid instance list elements');

$switchedReader = $strictReader->WithMode(ValueMode::NumericStr);
$check($switchedReader->Int('20') === 20, 'ValueReader WithMode returns a reader with new rules');
$check($strictReader->Int('20') === null, 'ValueReader WithMode does not change the original reader');

$rules = ValueRules::FromMode(ValueMode::Strict);
$rules->IntNormalizer = static fn(mixed $value): ?int => $value === null ? 100 : null;
$customReader = new ValueReader($rules);
$rules->IntNormalizer = static fn(mixed $value): ?int => 200;
$check($customReader->Int(null) === 100, 'ValueReader clones custom rules when constructed');
$check($customReader->IntList([null, 1]) === [100], 'ValueReader applies custom rules to list elements');

$predicateCalls = 0;
$check(
	$numericReader->Int(
		'12',
		predicate: static function (int $value) use (&$predicateCalls): bool {
			$predicateCalls++;
			return $value >= 10;
		},
	) === 12,
	'ValueReader applies a predicate after numeric normalization',
);
$check($predicateCalls === 1, 'ValueReader calls a predicate exactly once for a normalized scalar');
$check($numericReader->Int('12', predicate: static fn(int $value): bool => $value > 20) === null, 'ValueReader returns null when a scalar predicate rejects the value');

$skippedPredicateCalls = 0;
$check(
	$strictReader->Int(
		'invalid',
		predicate: static function (int $value) use (&$skippedPredicateCalls): bool {
			$skippedPredicateCalls++;
			return $value > 0;
		},
	) === null,
	'ValueReader returns null before a predicate when normalization fails',
);
$check($skippedPredicateCalls === 0, 'ValueReader does not call a predicate after normalization failure');
$check($strictReader->String('', predicate: static fn(string $value): bool => $value === '') === '', 'ValueReader passes a valid empty string to a predicate');
$check($strictReader->Bool(false, predicate: static fn(bool $value): bool => ! $value) === false, 'ValueReader passes a valid false to a predicate');
$check($strictReader->Int(0, predicate: static fn(int $value): bool => $value === 0) === 0, 'ValueReader passes a valid zero to a predicate');

$listPredicateCalls = 0;
$check(
	$numericReader->IntList(
		['1', 'invalid', 2, '3'],
		predicate: static function (int $value) use (&$listPredicateCalls): bool {
			$listPredicateCalls++;
			return $value % 2 === 1;
		},
	) === [1, 3],
	'ValueReader filters a normalized list with a predicate and reindexes it',
);
$check($listPredicateCalls === 3, 'ValueReader calls a list predicate once per normalized element');
$check($strictReader->IntList([], predicate: static fn(int $value): bool => true) === [], 'ValueReader accepts an empty list without invoking a predicate');

$check($strictReader->Array(['required' => true], predicate: static fn(array $value): bool => isset($value['required'])) !== null, 'ValueReader applies a predicate to an entire array');
$check($strictReader->Array([], predicate: static fn(array $value): bool => false) === null, 'ValueReader returns null when an array predicate rejects the value');
$check($strictReader->Instance($item, ValueReaderItem::class, predicate: static fn(ValueReaderItem $value): bool => $value === $item) === $item, 'ValueReader applies a predicate to an instance');
$check($strictReader->InstanceList([$item], ValueReaderItem::class, predicate: static fn(ValueReaderItem $value): bool => false) === [], 'ValueReader filters an instance list with a predicate');
$check($strictReader->BackedEnum('published', ValueReaderStatus::class, predicate: static fn(ValueReaderStatus $value): bool => $value === ValueReaderStatus::Published) === ValueReaderStatus::Published, 'ValueReader applies a predicate after backed Enum normalization');
$check($strictReader->BackedEnumList(['draft', 'published'], ValueReaderStatus::class, predicate: static fn(ValueReaderStatus $value): bool => $value === ValueReaderStatus::Published) === [ValueReaderStatus::Published], 'ValueReader filters a backed Enum list with a predicate');

$invalidPredicateWasRejected = false;
try {
	$strictReader->Int(1, predicate: static fn(int $value) => 1);
} catch (UnexpectedValueException) {
	$invalidPredicateWasRejected = true;
}
$check($invalidPredicateWasRejected, 'ValueReader rejects a predicate result that is not bool');

$predicateException = new RuntimeException('Predicate failure');
$caughtPredicateException = null;
try {
	$strictReader->Int(1, predicate: static fn(int $value): bool => throw $predicateException);
} catch (RuntimeException $exception) {
	$caughtPredicateException = $exception;
}
$check($caughtPredicateException === $predicateException, 'ValueReader propagates a predicate exception unchanged');

$derivedReader = new DerivedValueReader();
$check($derivedReader->WithMode(ValueMode::NumericStr) instanceof DerivedValueReader, 'WithMode preserves a ValueReader subclass');
$check($derivedReader->WithRules(ValueRules::FromMode(ValueMode::Strict)) instanceof DerivedValueReader, 'WithRules preserves a ValueReader subclass');

echo "ValueReader: {$checks} checks passed.\n";
