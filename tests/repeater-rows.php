<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
	exit;
}

require_once __DIR__ . '/../autoload.php';

use WpTypeSafety\Acf\Get\AcfGet;

class WP_Post { public function __construct(public int $ID) {} }
class WP_Term { public function __construct(public int $term_id) {} }
class WP_User { public function __construct(public int $ID) {} }
function get_field(string $name, mixed $source = null): mixed
{
	$GLOBALS['rowCalls'][] = [$name, $source];
	return $GLOBALS['rowFields'][$name] ?? null;
}

require_once __DIR__ . '/../Acf/Get/AcfGet.php';
require_once __DIR__ . '/../Acf/Internal/AcfAdapter.php';
require_once __DIR__ . '/../Acf/Internal/AcfDtoFactory.php';
require_once __DIR__ . '/../Core/Internal/UrlCheck.php';
$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};
$raw = [4 => ['text' => 'A', 'nested' => ['description' => 'Nested']], 8 => false, 10 => ['text' => 'B', 'children' => [['text' => 'Child']]], 11 => 'bad', 15 => []];
$GLOBALS['rowFields']['rows'] = $raw;
foreach ([new AcfGet(123), new AcfGet(new WP_Post(123)), new AcfGet('option'), new AcfGet(new WP_Term(4)), new AcfGet(new WP_User(5)), new AcfGet(['rows' => $raw])] as $get) {
	$rows = $get->Repeater('rows');
	$check(array_is_list($rows) && count($rows) === 3, 'remove invalid rows and reindex');
	$check(array_reduce($rows, static fn (bool $ok, $row): bool => $ok && $row instanceof AcfGet, true), 'all rows are AcfGet');
	$check($rows[0]->Text('text') === 'A' && $rows[1]->Text('text') === 'B', 'preserve order');
	$check($rows[2]->Text('missing') === '', 'empty array remains an empty row');
	$check($rows[0]->Nested('nested')->Text('description') === 'Nested', 'nested group');
	$check($rows[1]->Repeater('children')[0]->Text('text') === 'Child', 'nested repeater');
	$check($get->Array('rows') === $raw, 'Array keeps raw values and keys');
	$check($get->Repeater('missing') === [], 'missing field');
}
foreach ([null, false, '', 0, 'text', new stdClass(), new AcfGet([])] as $bad) {
	$GLOBALS['rowFields']['rows'] = $bad;
	$check((new AcfGet(123))->Repeater('rows') === [], 'invalid root');
	$check((new AcfGet(['rows' => $bad]))->Repeater('rows') === [], 'invalid array source root');
}
$GLOBALS['rowCalls'] = [];
$check((new AcfGet([]))->Repeater('rows') === [] && $GLOBALS['rowCalls'] === [], 'empty source no lookup');
$get = new AcfGet(['rows' => [['type' => 'x', 'url' => 'https://example.com/']]]);
$row = $get->Repeater('rows')[0];
$check($row->SelectValue('type') === 'x', 'select on row');
$check($row->Url('url') === 'https://example.com/', 'URL on row');
$check(count(array_slice($get->Repeater('rows'), 0, 1)) === 1, 'array_slice still works');
$check(count(array_filter($get->Repeater('rows'), static fn (AcfGet $r): bool => $r->Text('type') !== '')) === 1, 'typed filter');
$GLOBALS['rowFields']['rows'] = [['text' => 'Before']];
$rows = (new AcfGet(123))->Repeater('rows');
$GLOBALS['rowFields']['rows'] = [['text' => 'After']];
$check($rows[0]->Text('text') === 'Before', 'rows keep snapshot');
echo "Repeater rows: {$checks} checks passed\n";
