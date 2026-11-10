<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/autoload.php';

use WpTypeSafety\Core\Check\ArrayGet;
use WpTypeSafety\Core\Check\ObjectGet;
use WpTypeSafety\Core\Check\TypeCheck;
use WpTypeSafety\Core\Check\ValueMode;
use WpTypeSafety\Core\Check\ValueRules;
use WpTypeSafety\Core\Data\TimeData;

enum ObjectGetPublishStatus: string
{
	case Draft = 'draft';
	case Published = 'published';
}

enum ObjectGetPriority: int
{
	case Low = 0;
	case High = 1;
}

final class ObjectGetTestSource
{
	public string $Title = 'Title';
	public int|string|null $Count = 12;
	public array $Labels = ['first', 2, 'second'];
	public object $Child;
	public array $Group;
	public array $Children;
	public array $Groups;
	public TimeData $OpeningTime;
	public array $Times;
	public readonly string $ReadOnly;
	public ?string $Nullable = null;
	public string $Uninitialized;
	public static string $StaticValue = 'static';
	protected string $ProtectedValue = 'protected';
	private string $PrivateValue = 'private';

	public function __construct()
	{
		$this->Child = (object) ['Name' => 'Child'];
		$this->Group = ['Name' => 'Group'];
		$this->Children = [
			(object) ['Name' => 'First'],
			'invalid',
			(object) ['Name' => 'Second'],
		];
		$this->Groups = [
			['Name' => 'First group'],
			'invalid',
			['Name' => 'Second group'],
		];
		$this->OpeningTime = new TimeData(Hour: 9, Minute: 30, Second: 0);
		$this->Times = [
			$this->OpeningTime,
			'invalid',
			new TimeData(Hour: 18, Minute: 0, Second: 0),
		];
		$this->ReadOnly = 'readonly';
	}
}

final class ObjectGetMagicSource
{
	public int $MagicReadCount = 0;

	public function __get(string $name): string
	{
		$this->MagicReadCount++;
		return "magic-{$name}";
	}

	public function __isset(string $name): bool
	{
		$this->MagicReadCount++;
		return true;
	}
}

$checks = 0;
$check = static function (bool $condition, string $message) use (&$checks): void {
	if (! $condition) {
		throw new RuntimeException($message);
	}
	$checks++;
};

$source = new ObjectGetTestSource();
$get = new ObjectGet($source);

$check($get->String('Title') === 'Title', 'String reads a public property');
$check($get->Int('Count') === 12, 'Int reads the current union property value');
$source->Count = '12';
$check($get->Int('Count') === null, 'Int does not convert a numeric string');
$check($get->String('Count') === '12', 'String reflects a changed property value');
$check($get->StringList('Labels') === ['first', 'second'], 'StringList filters values and reindexes them');
$check($get->String('ReadOnly') === 'readonly', 'String reads a readonly property');
$check($get->Exists('Title'), 'Exists returns true for a public initialized property');
$check($get->Exists('Nullable'), 'Exists returns true for a public property containing null');
$check(! $get->Exists('Missing'), 'Exists returns false for a missing property');
$check(! $get->Exists('Uninitialized'), 'Exists returns false for an uninitialized property');
$check(! $get->Exists('ProtectedValue'), 'Exists returns false for a protected property');
$check(! $get->Exists('PrivateValue'), 'Exists returns false for a private property');
$check(! $get->Exists('StaticValue'), 'Exists returns false for a static property');
$check($get->String('Nullable') === '', 'String returns an empty string for null');
$check($get->String('Uninitialized') === '', 'String ignores an uninitialized property');
$check($get->String('ProtectedValue') === '', 'String ignores a protected property');
$check($get->String('PrivateValue') === '', 'String ignores a private property');
$check($get->String('StaticValue') === '', 'String ignores a static property');
$check($get->String('Missing') === '', 'String returns an empty string for a missing property');
$check($get->Nested('Child')->String('Name') === 'Child', 'Nested reads a child object');
$check($get->NestedArray('Group')->String('Name') === 'Group', 'NestedArray reads an array property');
$check($get->NestedArray('Child')->String('Name') === '', 'NestedArray returns an empty ArrayGet for a non-array value');

$pathGet = new ObjectGet((object) [
	'Middle' => (object) [
		'Inner' => (object) [
			'Name' => 'Deep object',
			'Nullable' => null,
		],
		'Array' => ['Name' => 'Array in object path'],
	],
]);
$check($pathGet->ExistsPath('Middle', 'Inner', 'Name'), 'ObjectGet ExistsPath returns true for an existing object path');
$check($pathGet->ExistsPath('Middle', 'Inner', 'Nullable'), 'ObjectGet ExistsPath returns true for an explicit null at the path endpoint');
$check(! $pathGet->ExistsPath('Middle', 'Missing', 'Name'), 'ObjectGet ExistsPath returns false for a missing intermediate property');
$check(! $pathGet->ExistsPath('Middle', 'Array', 'Name'), 'ObjectGet ExistsPath does not cross from an object to an array');
$check($pathGet->NestedPath('Middle', 'Inner')->String('Name') === 'Deep object', 'ObjectGet NestedPath reads a path containing only objects');
$check($pathGet->NestedPath('Middle', 'Array')->String('Name') === '', 'ObjectGet NestedPath does not cross from an object to an array');
$check($pathGet->NestedPath('Middle', 'Missing')->String('Name') === '', 'ObjectGet NestedPath returns an empty getter after a missing segment');

$children = $get->NestedList('Children');
$check(count($children) === 2, 'NestedList filters non-object items');
$check($children[0] instanceof ObjectGet, 'NestedList returns ObjectGet instances');
$check($children[0]->String('Name') === 'First', 'NestedList reads the first object');
$check($children[1]->String('Name') === 'Second', 'NestedList preserves object order');

$groups = $get->NestedArrayList('Groups');
$check(count($groups) === 2, 'NestedArrayList filters non-array items');
$check($groups[0] instanceof ArrayGet, 'NestedArrayList returns ArrayGet instances');
$check($groups[0]->String('Name') === 'First group', 'NestedArrayList reads the first array');
$check($groups[1]->String('Name') === 'Second group', 'NestedArrayList preserves array order');
$check($get->NestedArrayList('Missing') === [], 'NestedArrayList returns an empty list for a missing property');
$check($get->TimeData('OpeningTime') === $source->OpeningTime, 'TimeData returns a TimeData instance');
$check(count($get->TimeDataList('Times')) === 2, 'TimeDataList filters non-TimeData items');

$dynamicSource = (object) ['Count' => 10];
$dynamicGet = new ObjectGet($dynamicSource);
$check($dynamicGet->Int('Count') === 10, 'Int reads a dynamic stdClass property');
$dynamicSource->Count = 20;
$check($dynamicGet->Int('Count') === 20, 'Int reflects a changed dynamic property');
$dynamicSource->Added = 'added';
$check($dynamicGet->String('Added') === 'added', 'String reflects an added dynamic property');
unset($dynamicSource->Added);
$check($dynamicGet->String('Added') === '', 'String reflects a removed dynamic property');

$originalSource = (object) ['Count' => 30];
$originalGet = new ObjectGet($originalSource);
$originalSource = (object) ['Count' => 40];
$check($originalGet->Int('Count') === 30, 'ObjectGet does not follow replacement of the source variable');

$magicSource = new ObjectGetMagicSource();
$magicGet = new ObjectGet($magicSource);
$check($magicGet->String('Virtual') === '', 'String ignores a magic-only property');
$check(! $magicGet->Exists('Virtual'), 'Exists returns false for a magic-only property');
$check($magicSource->MagicReadCount === 0, 'ObjectGet does not invoke __get or __isset');
$check((new ObjectGet(false))->String('Title') === '', 'A non-object source behaves as an empty source');

$numericPropertyGet = new ObjectGet((object) [0 => 'Numeric property']);
$check($numericPropertyGet->String('0') === 'Numeric property', 'ObjectGet reads a numeric property name as a string');

$numericSource = (object) [
	'Int' => '12',
	'Float' => '12.5',
	'Ints' => [1, '2', 'invalid'],
	'Child' => (object) ['Count' => '3'],
	'Path' => (object) ['Child' => (object) ['Count' => '7']],
	'Group' => ['Count' => '4'],
	'Children' => [(object) ['Count' => '5']],
	'Groups' => [['Count' => '6']],
];
$numericObjectGet = new ObjectGet($numericSource, ValueMode::NumericStr);
$check($numericObjectGet->Int('Int') === 12, 'ObjectGet NumericStr normalizes an integer string');
$check($numericObjectGet->Float('Float') === 12.5, 'ObjectGet NumericStr normalizes a float string');
$check($numericObjectGet->IntList('Ints') === [1, 2], 'ObjectGet NumericStr normalizes list elements');
$numericSource->Int = '13';
$check($numericObjectGet->Int('Int') === 13, 'ObjectGet NumericStr reads and normalizes the current property value');
$check($numericObjectGet->Nested('Child')->Int('Count') === 3, 'ObjectGet Nested inherits NumericStr rules');
$check($numericObjectGet->NestedPath('Path', 'Child')->Int('Count') === 7, 'ObjectGet NestedPath inherits NumericStr rules');
$check($numericObjectGet->NestedArray('Group')->Int('Count') === 4, 'ObjectGet NestedArray inherits NumericStr rules');
$check($numericObjectGet->NestedList('Children')[0]->Int('Count') === 5, 'ObjectGet NestedList inherits NumericStr rules');
$check($numericObjectGet->NestedArrayList('Groups')[0]->Int('Count') === 6, 'ObjectGet NestedArrayList inherits NumericStr rules');

$objectRules = ValueRules::FromMode(ValueMode::Strict);
$objectRules->StringNormalizer = static fn(mixed $value): ?string => is_int($value)
	? "number-{$value}"
	: null;
$customObjectGet = new ObjectGet((object) [
	'Value' => 8,
	'Child' => (object) ['Value' => 9],
], $objectRules);
$objectRules->StringNormalizer = static fn(mixed $value): ?string => 'changed';

$check($customObjectGet->String('Value') === 'number-8', 'ObjectGet accepts a partial ValueRules override');
$check($customObjectGet->Nested('Child')->String('Value') === 'number-9', 'ObjectGet Nested inherits a custom rule snapshot');

$nullObjectRules = ValueRules::FromMode(ValueMode::Strict);
$nullObjectRules->IntNormalizer = static fn(mixed $value): ?int => $value === null
	? 100
	: TypeCheck::Int($value);
$nullObjectGet = new ObjectGet((object) [
	'NullValue' => null,
	'NullItems' => [1, null, 2],
], $nullObjectRules);
$check($nullObjectGet->Int('Missing') === null, 'A missing property bypasses the custom normalizer');
$check($nullObjectGet->Int('NullValue') === 100, 'An explicitly stored null property is passed to the custom normalizer');
$check($nullObjectGet->IntList('NullItems') === [1, 100, 2], 'An explicit null list element is passed to the custom normalizer');

$switchObjectSource = (object) [
	'Count' => '20',
	'Child' => (object) ['Count' => '21'],
];
$switchObjectGet = new ObjectGet($switchObjectSource);
$numericSwitchObjectGet = $switchObjectGet->WithMode(ValueMode::NumericStr);
$check($switchObjectGet->Int('Count') === null, 'ObjectGet WithMode does not mutate the original getter');
$check($numericSwitchObjectGet->Int('Count') === 20, 'ObjectGet WithMode changes the mode without ending the chain');
$check($numericSwitchObjectGet->Nested('Child')->Int('Count') === 21, 'ObjectGet WithMode is inherited by Nested');
$check($numericSwitchObjectGet->WithMode(ValueMode::Strict)->Int('Count') === null, 'ObjectGet WithMode can return a chain to Strict');
$check($switchObjectGet->WithRules($objectRules)->String('Count') === 'changed', 'ObjectGet WithRules replaces its current rules');

$combinedObjectGet = new ObjectGet((object) [
	'Count' => '40',
	'Enabled' => 'false',
], ValueRules::FromModes(
	ValueMode::BoolStr,
	ValueMode::BoolInt,
	ValueMode::NumericStr,
));
$check($combinedObjectGet->Int('Count') === 40, 'ObjectGet accepts composed numeric rules');
$check($combinedObjectGet->Bool('Enabled') === false, 'ObjectGet accepts composed boolean rules');

$booleanIntObjectGet = new ObjectGet((object) [
	'Enabled' => 1,
	'Disabled' => 0,
	'Invalid' => 2,
], ValueMode::BoolInt);
$check($booleanIntObjectGet->Bool('Enabled') === true, 'ObjectGet BoolInt normalizes integer one');
$check($booleanIntObjectGet->Bool('Disabled') === false, 'ObjectGet BoolInt normalizes integer zero');
$check($booleanIntObjectGet->Bool('Invalid') === null, 'ObjectGet BoolInt rejects other integers');

$emptyObjectGet = new ObjectGet((object) [
	'Enabled' => '',
], ValueMode::EmptyStrAsFalse);
$check($emptyObjectGet->Bool('Enabled') === false, 'ObjectGet accepts EmptyStrAsFalse');
$check($emptyObjectGet->Bool('Missing') === null, 'ObjectGet EmptyStrAsFalse keeps a missing property as null');

$enumObjectGet = new ObjectGet((object) [
	'Status' => 'published',
	'Priority' => 1,
	'Statuses' => ['draft', 'invalid', ObjectGetPublishStatus::Published],
]);
$check($enumObjectGet->BackedEnum('Status', ObjectGetPublishStatus::class) === ObjectGetPublishStatus::Published, 'ObjectGet BackedEnum normalizes a string backing value');
$check($enumObjectGet->BackedEnum('Priority', ObjectGetPriority::class) === ObjectGetPriority::High, 'ObjectGet BackedEnum normalizes an integer backing value');
$check($enumObjectGet->BackedEnumList('Statuses', ObjectGetPublishStatus::class) === [ObjectGetPublishStatus::Draft, ObjectGetPublishStatus::Published], 'ObjectGet BackedEnumList filters unknown values and preserves instances');
$check($enumObjectGet->BackedEnum('Missing', ObjectGetPublishStatus::class) === null, 'ObjectGet BackedEnum returns null for a missing property');

$numericEnumObjectGet = new ObjectGet((object) [
	'Priority' => '1',
], ValueMode::NumericStr);
$check($numericEnumObjectGet->BackedEnum('Priority', ObjectGetPriority::class) === ObjectGetPriority::High, 'ObjectGet NumericStr applies to an int-backed enum');

if (PHP_VERSION_ID >= 80400) {
	eval(<<<'PHP'
		final class ObjectGetHookSource
		{
			public int $ValueReads = 0;
			public int $OtherReads = 0;

			public int $Value {
				get {
					$this->ValueReads++;
					return 42;
				}
			}

			public int $Other {
				get {
					$this->OtherReads++;
					return 99;
				}
			}

			public int $SetOnly {
				set {
				}
			}

			public int $Broken {
				get {
					throw new RuntimeException('hook failure');
				}
			}
		}
		PHP);

	$hookSource = new ObjectGetHookSource();
	$hookGet = new ObjectGet($hookSource);
	$check($hookGet->Int('Value') === 42, 'Int reads a public get hook');
	$check($hookSource->ValueReads === 1, 'ObjectGet invokes the requested get hook once');
	$check($hookSource->OtherReads === 0, 'ObjectGet does not invoke unrelated get hooks');
	$check($hookGet->Int('SetOnly') === null, 'ObjectGet ignores a virtual set-only property');

	$hookExceptionWasThrown = false;
	try {
		$hookGet->Int('Broken');
	} catch (RuntimeException $exception) {
		$hookExceptionWasThrown = $exception->getMessage() === 'hook failure';
	}
	$check($hookExceptionWasThrown, 'ObjectGet propagates exceptions from a get hook');
}

echo "ObjectGet: {$checks} checks passed\n";
