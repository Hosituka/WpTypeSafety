<?php
declare(strict_types=1);

// 別種類のReader生成フックと公開Facade・MetaGetの継承を検証する。
if (PHP_SAPI !== 'cli')
{exit;}

require_once __DIR__ . '/../autoload.php';

use WpTypeSafety\Core\Check\ArrayGet;
use WpTypeSafety\Core\Check\ObjectGet;
use WpTypeSafety\Core\Check\TreeGet;
use WpTypeSafety\Core\Check\ValueRules;
use WpTypeSafety\Core\Check\Data\NumericMode;
use WpTypeSafety\Core\Data\ImgData;
use WpTypeSafety\Internal\TreeTypeReader\ArrayGet as InternalArrayGet;
use WpTypeSafety\Internal\TreeTypeReader\ObjectGet as InternalObjectGet;
use WpTypeSafety\Internal\TreeTypeReader\TreeGet as InternalTreeGet;
use WpTypeSafety\Wp\Get\MetaGet;

/** @extends ArrayGet<ProjectObjectReader> */
class ProjectArrayReader extends ArrayGet
{
	public int $ObjectCalls = 0;
	/** @return ProjectObjectReader */
	protected function createObjectReader(mixed $source): ObjectGet
	{
		$this->ObjectCalls++;
		return new ProjectObjectReader($source, $this->publicRulesSnapshot());
	}
}
/** @extends ObjectGet<ProjectArrayReader> */
class ProjectObjectReader extends ObjectGet
{
	public int $ArrayCalls = 0;
	/** @return ProjectArrayReader */
	protected function createArrayReader(mixed $source): ArrayGet
	{
		$this->ArrayCalls++;
		return new ProjectArrayReader($source, $this->publicRulesSnapshot());
	}
}
/** @extends TreeGet<ProjectArrayReader, ProjectObjectReader> */
class ProjectTreeReader extends TreeGet
{
	public int $ArrayCalls = 0;
	public int $ObjectCalls = 0;
	protected function createArrayReader(mixed $source): ProjectArrayReader
	{
		$this->ArrayCalls++;
		return new ProjectArrayReader($source, $this->publicRulesSnapshot());
	}
	protected function createObjectReader(mixed $source): ProjectObjectReader
	{
		$this->ObjectCalls++;
		return new ProjectObjectReader($source, $this->publicRulesSnapshot());
	}
}

// 汎用側単独でも同じ生成フックを利用できる。
/** @extends InternalArrayGet<InternalProjectObject> */
class InternalProjectArray extends InternalArrayGet
{
	/** @return InternalProjectObject */
	protected function createObjectReader(mixed $source): InternalObjectGet
	{
		return new InternalProjectObject($source, $this->rulesSnapshot());
	}
}
/** @extends InternalObjectGet<InternalProjectArray> */
class InternalProjectObject extends InternalObjectGet
{
	/** @return InternalProjectArray */
	protected function createArrayReader(mixed $source): InternalArrayGet
	{
		return new InternalProjectArray($source, $this->rulesSnapshot());
	}
}
/** @extends InternalTreeGet<InternalProjectArray, InternalProjectObject> */
class InternalProjectTree extends InternalTreeGet
{
	protected function createArrayReader(mixed $source): InternalProjectArray
	{
		return new InternalProjectArray($source, $this->rulesSnapshot());
	}
	protected function createObjectReader(mixed $source): InternalProjectObject
	{
		return new InternalProjectObject($source, $this->rulesSnapshot());
	}
}

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok)
	{throw new RuntimeException($label);}
	$checks++;
};
$img = new ImgData('https://example.com/a.png', 10, 20);
$array = ['count' => '7', 'img' => $img];
$object = (object) $array;
$source = [
	'array' => $array,
	'object' => $object,
	'items' => [8 => $object, 10 => null, 12 => $array, 14 => 3, 16 => $object, 20 => $array],
];
$arrayReader = new ProjectArrayReader($source, NumericMode::Str);
$child = $arrayReader->NestedObject('object');
$check($child instanceof ProjectObjectReader && $arrayReader->ObjectCalls === 1, '配列→オブジェクト生成フック');
$check($child->Int('count') === 7 && $child->ImgData('img') === $img, '規則とCore Data Getterを維持');
$objects = $arrayReader->NestedObjectList('items');
$check(count($objects) === 2 && array_is_list($objects) && $arrayReader->ObjectCalls === 3, '適合要素ごとに1回だけ生成');
$check($objects[0] instanceof ProjectObjectReader && $objects[1] instanceof ProjectObjectReader, 'リストも案件版Reader');
$check($arrayReader->NestedObject('missing')->Int('count') === null, '欠損時は空Reader');
$before = $arrayReader->ObjectCalls;
$check($arrayReader->NestedObjectList('missing') === [] && $arrayReader->ObjectCalls === $before, '空リストは生成しない');
$check($arrayReader->NestedObject('array')->ImgData('img') === null, '単数の型違いは空Reader');

$objectReader = new ProjectObjectReader((object) $source, NumericMode::Str);
$child = $objectReader->NestedArray('array');
$check($child instanceof ProjectArrayReader && $objectReader->ArrayCalls === 1, 'オブジェクト→配列生成フック');
$check($child->Int('count') === 7 && $child->ImgData('img') === $img, '配列Readerの規則とData Getter');
$arrays = $objectReader->NestedArrayList('items');
$check(count($arrays) === 2 && array_is_list($arrays) && $objectReader->ArrayCalls === 3, '配列リストのフィルタ・再採番');
$check($arrays[0] instanceof ProjectArrayReader && $arrays[1] instanceof ProjectArrayReader, '配列リストの案件版Reader');
$check($objectReader->NestedArray('object')->Int('count') === null, '配列への不正型切替');

$tree = new ProjectTreeReader($source, NumericMode::Str);
$check($tree->AsArrayGet() instanceof ProjectArrayReader && $tree->ArrayCalls === 1, 'AsArrayGetの生成フック');
$check($tree->AsObjectGet() === null && $tree->ObjectCalls === 0, 'AsObjectGet不適合時は生成しない');
$check($tree->NestedArray('array') instanceof ProjectArrayReader, 'Treeの配列切替');
$check($tree->NestedObject('object') instanceof ProjectObjectReader, 'Treeのオブジェクト切替');
$check($tree->NestedArrayList('items')[1]->Int('count') === 7, 'Treeの配列リスト規則');
$check($tree->NestedObjectList('items')[1]->ImgData('img') === $img, 'TreeのオブジェクトリストData Getter');
$check($tree->ArrayCalls === 4 && $tree->ObjectCalls === 3, '全切替が生成フック経由');
$nested = $tree->Nested('object');
$check($nested instanceof ProjectTreeReader && $nested->AsObjectGet() instanceof ProjectObjectReader, 'Nestedの派生型維持');
$check($nested->AsArrayGet() === null, 'AsArrayGetの不適合');
$back = $arrayReader->NestedObject('object');
$check($back->NestedArray('missing') instanceof ProjectArrayReader, '案件版Readerを相互に生成できる');

$rules = ValueRules::Strict();
$rules->IntNormalizer = static fn (mixed $value): ?int => $value === '7' ? 70 : null;
$custom = $tree->WithRules($rules);
$check($custom->NestedObject('object')->Int('count') === 70, '独自Normalizerを保持');
$check($tree->NestedObject('object')->Int('count') === 7, '元Readerの規則を変更しない');

foreach ([new ArrayGet($source), new TreeGet($source)] as $reader) {
	$check($reader->NestedObject('object') instanceof ObjectGet, '既定フックは公開ObjectGet');
	$check($reader->NestedObjectList('items')[0]->ImgData('img') === $img, '既定リストも公開版');
}
$plainObject = new ObjectGet((object) $source);
$check($plainObject->NestedArray('array') instanceof ArrayGet, '既定フックは公開ArrayGet');
$check($plainObject->NestedArrayList('items')[0]->ImgData('img') === $img, '公開ArrayGetリスト');

$internalArray = new InternalProjectArray($source);
$check($internalArray->NestedObject('object') instanceof InternalProjectObject, '汎用配列単数フック');
$check($internalArray->NestedObjectList('items')[0] instanceof InternalProjectObject, '汎用配列リストフック');
$internalObject = new InternalProjectObject((object) $source);
$check($internalObject->NestedArray('array') instanceof InternalProjectArray, '汎用オブジェクト単数フック');
$check($internalObject->NestedArrayList('items')[0] instanceof InternalProjectArray, '汎用オブジェクトリストフック');
$internalTree = new InternalProjectTree($source);
$check($internalTree->AsArrayGet() instanceof InternalProjectArray, '汎用Tree AsArrayGet');
$check($internalTree->NestedObject('object') instanceof InternalProjectObject, '汎用Tree NestedObject');
$check($internalTree->NestedArrayList('items')[0] instanceof InternalProjectArray, '汎用Tree配列リスト');
$check($internalTree->NestedObjectList('items')[0] instanceof InternalProjectObject, '汎用Treeオブジェクトリスト');
$internalObjectTree = new InternalProjectTree($object);
$check($internalObjectTree->AsObjectGet() instanceof InternalProjectObject, '汎用Tree AsObjectGet');

$meta = MetaGet::FromTree($source, NumericMode::Str);
$check($meta->NestedArray('array') instanceof ArrayGet && $meta->NestedArray('array')->Int('count') === 7, 'MetaGetの継承先配列生成');
$check($meta->NestedObjectList('items')[0]->ImgData('img') === $img, 'MetaGetの継承先リスト生成');
$check($meta->Nested('object') instanceof MetaGet, 'MetaGet固有の子生成を維持');

echo "Reader factories: {$checks} checks passed.\n";
