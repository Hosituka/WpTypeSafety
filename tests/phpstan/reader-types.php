<?php
declare(strict_types=1);

namespace WpTypeSafetyTests\ReaderTypes;

use WpTypeSafety\Core\Check\ArrayGet;
use WpTypeSafety\Core\Check\ObjectGet;
use WpTypeSafety\Core\Check\TreeGet;
use WpTypeSafety\Core\Check\ValueRules;
use WpTypeSafety\Core\Check\Data\NumericMode;
use function PHPStan\Testing\assertType;

// 実行時テストとは分離する。PHPStanはこの関数を実行せず解析する。
function VerifyProjectReaders(\ProjectArrayReader $array, \ProjectObjectReader $object, \ProjectTreeReader $tree): void
{
	assertType('ProjectObjectReader', $array->NestedObject('object'));
	assertType('list<ProjectObjectReader>', $array->NestedObjectList('items'));
	assertType('ProjectArrayReader', $object->NestedArray('array'));
	assertType('list<ProjectArrayReader>', $object->NestedArrayList('items'));
	assertType('ProjectArrayReader', $tree->NestedArray('array'));
	assertType('ProjectObjectReader', $tree->NestedObject('object'));
	assertType('list<ProjectArrayReader>', $tree->NestedArrayList('items'));
	assertType('list<ProjectObjectReader>', $tree->NestedObjectList('items'));
	assertType('ProjectArrayReader|null', $tree->AsArrayGet());
	assertType('ProjectObjectReader|null', $tree->AsObjectGet());
	assertType('ProjectObjectReader', $array->Nested('group')->NestedObject('object'));
	assertType('ProjectObjectReader', $array->NestedPath('a', 'b')->NestedObject('object'));
	assertType('ProjectArrayReader', $object->NestedList('items')[0]->NestedArray('array'));
	assertType('ProjectArrayReader', $tree->WithModes(NumericMode::Str)->NestedArray('array'));
	assertType('ProjectObjectReader', $tree->WithRules(ValueRules::Strict())->NestedObject('object'));
	assertType('ProjectObjectReader', $tree->Transform('object', static fn (mixed $value): mixed => $value)->NestedObject('object'));
	assertType('ProjectArrayReader', $tree->NestedObject('object')->NestedArray('array'));
	assertType('ProjectObjectReader', $tree->NestedArray('array')->NestedObject('object'));
	assertType('ProjectArrayReader', $tree->NestedArray('array')->WithModes()->NestedObject('o')->NestedArray('a'));
}

function VerifyInternalReaders(\InternalProjectArray $array, \InternalProjectObject $object, \InternalProjectTree $tree): void
{
	assertType('InternalProjectObject', $array->NestedObject('object'));
	assertType('list<InternalProjectObject>', $array->NestedObjectList('items'));
	assertType('InternalProjectArray', $object->NestedArray('array'));
	assertType('list<InternalProjectArray>', $object->NestedArrayList('items'));
	assertType('InternalProjectArray', $tree->NestedArray('array'));
	assertType('InternalProjectObject', $tree->NestedObject('object'));
	assertType('list<InternalProjectArray>', $tree->NestedArrayList('items'));
	assertType('list<InternalProjectObject>', $tree->NestedObjectList('items'));
	assertType('InternalProjectArray|null', $tree->AsArrayGet());
	assertType('InternalProjectObject|null', $tree->AsObjectGet());
	assertType('InternalProjectArray', $tree->NestedObject('object')->NestedArray('array'));
}

function VerifyDefaults(): void
{
	$array = new ArrayGet([]);
	$object = new ObjectGet(new \stdClass());
	$tree = new TreeGet([]);
	assertType('WpTypeSafety\\Core\\Check\\ObjectGet', $array->NestedObject('object'));
	assertType('WpTypeSafety\\Core\\Check\\ArrayGet', $object->NestedArray('array'));
	assertType('WpTypeSafety\\Core\\Check\\ArrayGet', $tree->NestedArray('array'));
	assertType('WpTypeSafety\\Core\\Check\\ObjectGet', $tree->NestedObject('object'));
}
