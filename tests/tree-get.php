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
	'company' => (object) [
		'users' => [
			'john' => (object) [
				'Name' => 'John',
				'Age' => '30',
				'Status' => 'published',
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
$johnGet = $treeGet->NestedPath('company', 'users', 'john');
$check($johnGet->String('Name') === 'John', 'TreeGet crosses array, object, array, and object nodes');
$check($johnGet->Int('Age') === 30, 'TreeGet inherits ValueMode through a mixed path');
$check($johnGet->BackedEnum('Status', TreeGetPublishStatus::class) === TreeGetPublishStatus::Published, 'TreeGet delegates BackedEnum normalization');
$check($treeGet->IntList('counts') === [1, 2], 'TreeGet delegates primitive list normalization');
$check($treeGet->NestedPath('company', 'missing', 'john')->String('Name') === '', 'TreeGet returns an empty getter after a missing segment');
$check($treeGet->NestedPath('company', 'users', 'john', 'Name')->String('value') === '', 'TreeGet returns an empty getter after reaching a scalar');

$items = $treeGet->NestedList('items');
$check(count($items) === 2, 'TreeGet NestedList accepts array and object elements');
$check($items[0]->String('Name') === 'Array item', 'TreeGet NestedList wraps an array element');
$check($items[1]->String('Name') === 'Object item', 'TreeGet NestedList wraps an object element');
$check($treeGet->NestedList('missing') === [], 'TreeGet NestedList returns an empty list for a missing segment');

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
$check($nullGet->Int('Missing') === null, 'TreeGet bypasses normalizers for a missing segment');
$check($nullGet->Nested('Child')->Int('NullValue') === 100, 'TreeGet Nested inherits the rule snapshot');

$liveSource = (object) ['Count' => '1'];
$liveGet = new TreeGet($liveSource, ValueMode::NumericStr);
$check($liveGet->Int('Count') === 1, 'TreeGet reads an object property');
$liveSource->Count = '2';
$check($liveGet->Int('Count') === 2, 'TreeGet reflects changes to its object source');

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

echo "TreeGet: {$checks} checks passed\n";
