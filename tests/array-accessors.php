<?php
declare(strict_types=1);

// APIスタブでArray()の契約とNested()・Repeater()の互換性を検証する。
if (PHP_SAPI !== 'cli') {
	exit;
}

use WpTypeSafety\Acf\Get\AcfGet;

class WP_Post { public function __construct(public int $ID) {} }
class WP_Term { public function __construct(public int $term_id) {} }
class WP_User { public function __construct(public int $ID) {} }

function get_field(string $name, mixed $source = null): mixed
{
	$GLOBALS['arrayCalls'][] = [$name, $source];
	return $GLOBALS['arrayFields'][$name] ?? null;
}

require_once __DIR__ . '/../Acf/Get/AcfGet.php';
require_once __DIR__ . '/../Acf/Internal/AcfAdapter.php';
$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};
$cases = [
	[null, []], [false, []], ['', []], [123, []], [new stdClass(), []],
	[[], []], [['title' => 'Title'], ['title' => 'Title']],
	[[['text' => 'A'], ['text' => 'B']], [['text' => 'A'], ['text' => 'B']]],
	[[2 => 'x', 8 => null], [2 => 'x', 8 => null]],
];
foreach ($cases as [$value, $expected]) {
	$GLOBALS['arrayFields']['field'] = $value;
	foreach ([new AcfGet(123), new AcfGet(new WP_Post(123)), new AcfGet('option'), new AcfGet(new WP_Term(4)), new AcfGet(new WP_User(5))] as $get) {
		$check($get->Array('field') === $expected, 'direct/wrapper raw array');
	}
	$check((new AcfGet(['field' => $value]))->Array('field') === $expected, 'array source');
	$check((new AcfGet(['group' => ['field' => $value]]))->Nested('group')->Array('field') === $expected, 'nested source');
}
$check((new AcfGet([]))->Array('missing') === [], 'missing array field');
$check((new AcfGet([]))->Array('missing') === [], 'empty source');
$GLOBALS['arrayFields']['group'] = ['title' => 'Before'];
$nested = (new AcfGet(123))->Nested('group');
$GLOBALS['arrayFields']['group'] = ['title' => 'After'];
$check($nested->Text('title') === 'Before', 'Nested keeps snapshot');
$check((new AcfGet(['bad' => false]))->Nested('bad')->Text('title') === '', 'Nested missing stays empty');
$rows = [['text' => 'A'], ['text' => 'B']];
$GLOBALS['arrayFields']['rows'] = $rows;
$wrappedRows = (new AcfGet(123))->Repeater('rows');
$check(count($wrappedRows) === 2 && $wrappedRows[0] instanceof AcfGet && $wrappedRows[0]->Text('text') === 'A', 'Repeater wraps rows');
$GLOBALS['arrayCalls'] = [];
(new AcfGet(new WP_Post(7)))->Array('field');
(new AcfGet(new WP_Term(8)))->Array('field');
(new AcfGet(new WP_User(9)))->Array('field');
$check($GLOBALS['arrayCalls'] === [['field', 7], ['field', 'term_8'], ['field', 'user_9']], 'source forwarding');
$check(method_exists(AcfGet::class, 'Array') && ! method_exists(AcfGet::class, 'Group'), 'renamed public API');
echo "Array accessors: {$checks} checks passed\n";
