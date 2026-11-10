<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/autoload.php';

use WpTypeSafety\Core\Check\TreeGet;
use WpTypeSafety\Core\Check\TypeCheck;
use WpTypeSafety\Core\Check\ValueMode;
use WpTypeSafety\Core\Check\ValueRules;

enum TreeGetPublishStatus: string
{
	case Draft = 'draft';
	case Published = 'published';
}

final class TreeGetRestrictedSource
{
	public object $PublicNode;
	public object $UninitializedNode;
	public static object $StaticNode;
	protected object $ProtectedNode;
	private object $PrivateNode;
	public int $MagicReadCount = 0;

	public function __construct()
	{
		$this->PublicNode = (object) ['Name' => 'Public'];
		$this->ProtectedNode = (object) ['Name' => 'Protected'];
		$this->PrivateNode = (object) ['Name' => 'Private'];
	}

	public function __get(string $name): object
	{
		$this->MagicReadCount++;
		return (object) ['Name' => $name];
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

$source = [
	0 => 'Root index',
	'company' => (object) [
		'users' => [
			'john' => (object) [
				'Name' => 'John',
				'Age' => '30',
				'Status' => 'published',
			],
		],
	],
	'users' => [
		(object) [
			'Profiles' => [
				(object) ['Name' => 'Indexed user'],
			],
		],
	],
	'items' => [
		['Name' => 'Array item'],
		(object) ['Name' => 'Object item'],
		'invalid',
	],
	'counts' => ['1', 2, 'invalid'],
];

$treeGet = new TreeGet($source, ValueMode::NumericStr);
$check($treeGet->String(0) === 'Root index', 'TreeGet reads an integer key from an array node');
$check($treeGet->Exists(0), 'TreeGet Exists accepts an integer key on an array node');
$check($treeGet->Exists('company'), 'TreeGet Exists returns true for an existing array key');
$check(! $treeGet->Exists('missing'), 'TreeGet Exists returns false for a missing array key');
$check($treeGet->ExistsPath('company', 'users', 'john', 'Name'), 'TreeGet ExistsPath crosses mixed array and object nodes');
$check($treeGet->ExistsPath('users', 0, 'Profiles', 0, 'Name'), 'TreeGet ExistsPath accepts integer segments in array nodes');
$check(! $treeGet->ExistsPath('company', 'missing', 'Name'), 'TreeGet ExistsPath returns false for a missing intermediate segment');
$check(! $treeGet->ExistsPath('company', 'users', 'john', 'Name', 'value'), 'TreeGet ExistsPath returns false after reaching a scalar');
$arrayGet = $treeGet->AsArrayGet();
$check($arrayGet !== null, 'AsArrayGet returns ArrayGet for an array node');
$check($arrayGet?->IntList('counts') === [1, 2], 'AsArrayGet inherits TreeGet rules');
$check($treeGet->AsObjectGet() === null, 'AsObjectGet returns null for an array node');
$johnGet = $treeGet->NestedPath('company', 'users', 'john');
$check($johnGet->String('Name') === 'John', 'TreeGet crosses array, object, array, and object nodes');
$johnObjectGet = $johnGet->AsObjectGet();
$check($johnObjectGet !== null, 'AsObjectGet returns ObjectGet for an object node');
$check($johnObjectGet?->Int('Age') === 30, 'AsObjectGet inherits TreeGet rules');
$check($johnGet->AsArrayGet() === null, 'AsArrayGet returns null for an object node');
$check($johnGet->Int('Age') === 30, 'TreeGet inherits ValueMode through a mixed path');
$check($johnGet->BackedEnum('Status', TreeGetPublishStatus::class) === TreeGetPublishStatus::Published, 'TreeGet delegates BackedEnum normalization');
$check($treeGet->IntList('counts') === [1, 2], 'TreeGet delegates primitive list normalization');
$check($treeGet->NestedPath('company', 'missing', 'john')->String('Name') === '', 'TreeGet returns an empty getter after a missing segment');
$check($treeGet->NestedPath('company', 'users', 'john', 'Name')->String('value') === '', 'TreeGet returns an empty getter after reaching a scalar');
$check($treeGet->NestedPath('users', 0, 'Profiles', 0)->String('Name') === 'Indexed user', 'TreeGet crosses array indexes and object properties in one path');

$items = $treeGet->NestedList('items');
$check(count($items) === 2, 'TreeGet NestedList accepts array and object elements');
$check($items[0]->String('Name') === 'Array item', 'TreeGet NestedList wraps an array element');
$check($items[1]->String('Name') === 'Object item', 'TreeGet NestedList wraps an object element');
$check($treeGet->NestedList('missing') === [], 'TreeGet NestedList returns an empty list for a missing segment');

$arrayItems = $treeGet->NestedArrayList('items');
$check(count($arrayItems) === 1, 'TreeGet NestedArrayList filters non-array elements');
$check($arrayItems[0]->String('Name') === 'Array item', 'TreeGet NestedArrayList returns ArrayGet instances');
$objectItems = $treeGet->NestedObjectList('items');
$check(count($objectItems) === 1, 'TreeGet NestedObjectList filters non-object elements');
$check($objectItems[0]->String('Name') === 'Object item', 'TreeGet NestedObjectList returns ObjectGet instances');
$check($treeGet->NestedArrayList('missing') === [], 'TreeGet NestedArrayList returns an empty list for a missing segment');
$check($treeGet->NestedObjectList('missing') === [], 'TreeGet NestedObjectList returns an empty list for a missing segment');

$companyGet = $treeGet->NestedObject('company');
$check($companyGet->NestedArray('users')->NestedObject('john')->Int('Age') === 30, 'TreeGet NestedObject inherits rules and can switch to ArrayGet');
$usersGet = $treeGet->NestedArray('users');
$check($usersGet->NestedObject(0)->NestedArray('Profiles')->NestedObject(0)->String('Name') === 'Indexed user', 'TreeGet NestedArray returns ArrayGet for an array child');
$check($treeGet->NestedArray('company')->String('missing') === '', 'TreeGet NestedArray returns an empty ArrayGet for an object child');
$check($treeGet->NestedObject('users')->String('missing') === '', 'TreeGet NestedObject returns an empty ObjectGet for an array child');

$strictGet = new TreeGet(['Count' => '10']);
$check($strictGet->Int('Count') === null, 'TreeGet is strict by default');
$check($strictGet->WithMode(ValueMode::NumericStr)->Int('Count') === 10, 'TreeGet WithMode returns a getter with new rules');
$check($strictGet->Int('Count') === null, 'TreeGet WithMode does not mutate the original getter');

$rules = ValueRules::FromMode(ValueMode::Strict);
$rules->IntNormalizer = static fn(mixed $value): ?int => $value === null
	? 100
	: TypeCheck::Int($value);
$nullGet = new TreeGet([
	'NullValue' => null,
	'Child' => (object) ['NullValue' => null],
], $rules);
$rules->IntNormalizer = static fn(mixed $value): ?int => 200;
$check($nullGet->Int('NullValue') === 100, 'TreeGet passes an explicit null to its rule snapshot');
$check($nullGet->Exists('NullValue'), 'TreeGet Exists returns true for an explicitly stored null');
$check($nullGet->ExistsPath('Child', 'NullValue'), 'TreeGet ExistsPath returns true for an explicit null at the path endpoint');
$check(! $nullGet->ExistsPath('NullValue', 'Child'), 'TreeGet ExistsPath cannot continue through an explicit null');
$check($nullGet->Int('Missing') === null, 'TreeGet bypasses normalizers for a missing segment');
$check($nullGet->Nested('Child')->Int('NullValue') === 100, 'TreeGet Nested inherits the rule snapshot');

$liveSource = (object) ['Count' => '1'];
$liveGet = new TreeGet($liveSource, ValueMode::NumericStr);
$check($liveGet->Int('Count') === 1, 'TreeGet reads an object property');
$liveSource->Count = '2';
$check($liveGet->Int('Count') === 2, 'TreeGet reflects changes to its object source');

$numericPropertySource = (object) [0 => 'Numeric property'];
$numericPropertyGet = new TreeGet($numericPropertySource);
$check(! $numericPropertyGet->Exists(0), 'TreeGet Exists rejects an integer segment on an object node');
$check($numericPropertyGet->Exists('0'), 'TreeGet Exists accepts a numeric string property name');
$check($numericPropertyGet->String(0) === '', 'TreeGet does not treat an integer segment as an object property name');
$check($numericPropertyGet->String('0') === 'Numeric property', 'TreeGet reads a numeric object property through its string name');

$restrictedSource = new TreeGetRestrictedSource();
$restrictedGet = new TreeGet($restrictedSource);
$check($restrictedGet->Nested('PublicNode')->String('Name') === 'Public', 'TreeGet reads a public initialized property');
$check($restrictedGet->Nested('ProtectedNode')->String('Name') === '', 'TreeGet ignores a protected property');
$check($restrictedGet->Nested('PrivateNode')->String('Name') === '', 'TreeGet ignores a private property');
$check($restrictedGet->Nested('StaticNode')->String('Name') === '', 'TreeGet ignores a static property');
$check($restrictedGet->Nested('UninitializedNode')->String('Name') === '', 'TreeGet ignores an uninitialized property');
$check($restrictedGet->Nested('VirtualNode')->String('Name') === '', 'TreeGet ignores a magic-only property');
$check($restrictedSource->MagicReadCount === 0, 'TreeGet does not invoke __get or __isset');

$scalarGet = new TreeGet('invalid');
$check($scalarGet->String('Name') === '', 'TreeGet treats a scalar source as empty');
$check($scalarGet->Nested('Child')->Int('Count') === null, 'TreeGet cannot traverse a scalar source');
$check($scalarGet->AsArrayGet() === null, 'AsArrayGet returns null for a scalar node');
$check($scalarGet->AsObjectGet() === null, 'AsObjectGet returns null for a scalar node');

echo "TreeGet: {$checks} checks passed\n";
