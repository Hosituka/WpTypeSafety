<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/autoload.php';

use WpTypeSafety\Core\Check\ArrayGet;
use WpTypeSafety\Core\Check\ObjectGet;
use WpTypeSafety\Core\Data\TimeData;

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
$check($get->String('Nullable') === '', 'String returns an empty string for null');
$check($get->String('Uninitialized') === '', 'String ignores an uninitialized property');
$check($get->String('ProtectedValue') === '', 'String ignores a protected property');
$check($get->String('PrivateValue') === '', 'String ignores a private property');
$check($get->String('StaticValue') === '', 'String ignores a static property');
$check($get->String('Missing') === '', 'String returns an empty string for a missing property');
$check($get->Nested('Child')->String('Name') === 'Child', 'Nested reads a child object');
$check($get->NestedArray('Group')->String('Name') === 'Group', 'NestedArray reads an array property');
$check($get->NestedArray('Child')->String('Name') === '', 'NestedArray returns an empty ArrayGet for a non-array value');

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
$check($magicSource->MagicReadCount === 0, 'ObjectGet does not invoke __get or __isset');
$check((new ObjectGet(false))->String('Title') === '', 'A non-object source behaves as an empty source');

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
