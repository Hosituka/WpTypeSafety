<?php
declare(strict_types=1);

// APIスタブによる検証。WordPress・DBには接続しない。
if (PHP_SAPI !== 'cli') {
	exit;
}

require_once __DIR__ . '/../autoload.php';

use WpTypeSafety\Acf\Get\AcfGet;
use WpTypeSafety\Core\Data\ChoiceData;

class WP_Post { public function __construct(public int $ID) {} }
class WP_Term { public function __construct(public int $term_id) {} }
class WP_User { public function __construct(public int $ID) {} }

function get_field_object(string $name, mixed $source = null): mixed
{
	return $GLOBALS['buttonGroupFields'][$name] ?? false;
}

function get_field(string $name, mixed $source = null): mixed
{
	return $GLOBALS['buttonGroupFields'][$name]['value'] ?? null;
}

require_once __DIR__ . '/../Core/Data/ChoiceData.php';
require_once __DIR__ . '/../Acf/Get/AcfGet.php';
require_once __DIR__ . '/../Acf/Internal/AcfDtoFactory.php';
require_once __DIR__ . '/../Acf/Internal/AcfAdapter.php';

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};
$normalize = static fn (mixed $value): mixed => $value instanceof ChoiceData
	? [$value->Value, $value->Label]
	: $value;
$definitions = [
	['ButtonGroupValue', 'value', 'center', 'center'],
	['ButtonGroupLabel', 'label', '中央寄せ', '中央寄せ'],
	['ButtonGroup', 'array', ['value' => 'center', 'label' => '中央寄せ'], ['center', '中央寄せ']],
];

foreach ($definitions as [$method, $format, $value, $expected]) {
	$field = ['type' => 'button_group', 'return_format' => $format, 'value' => $value];
	$GLOBALS['buttonGroupFields']['alignment'] = $field;
	foreach ([new AcfGet(123), new AcfGet(new WP_Post(123)), new AcfGet(new WP_Term(4)), new AcfGet(new WP_User(5))] as $get) {
		$check($normalize($get->$method('alignment')) === $expected, $method . ' direct/wrapper');
	}
	$check($normalize((new AcfGet(['alignment' => $value]))->$method('alignment')) === $expected, $method . ' array');
	$check($normalize((new AcfGet(['settings' => ['alignment' => $value]]))->Nested('settings')->$method('alignment')) === $expected, $method . ' nested');
	foreach ([
		['type' => 'radio'],
		['type' => 'select'],
		['return_format' => $format === 'array' ? 'value' : 'array'],
	] as $override) {
		$GLOBALS['buttonGroupFields']['alignment'] = array_replace($field, $override);
		$check((new AcfGet(123))->$method('alignment') === null, $method . ' rejects settings mismatch');
	}
}

foreach ([null, false, '', [], ['value' => 'center'], new stdClass()] as $invalid) {
	$check((new AcfGet(['alignment' => $invalid]))->ButtonGroup('alignment') === null, 'reject invalid choice shape');
}
$check((new AcfGet(['alignment' => 0]))->ButtonGroupValue('alignment') === '0', 'preserve integer zero');
$check((new AcfGet(['alignment' => '0']))->ButtonGroupLabel('alignment') === '0', 'preserve string zero');

echo "Button Group accessors: {$checks} checks passed\n";
