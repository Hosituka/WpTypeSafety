<?php
declare(strict_types=1);

// APIスタブでFloat()・Int()の正規化契約を検証する。DBは変更しない。
if (PHP_SAPI !== 'cli') {
	exit;
}

require_once __DIR__ . '/../autoload.php';

use WpTypeSafety\Acf\Get\AcfGet;

class WP_Post { public function __construct(public int $ID) {} }

function get_field(string $name, mixed $source = null): mixed
{
	return $GLOBALS['numericFields'][$name] ?? null;
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

$floatCases = [
	[5, 5.0],
	[5.5, 5.5],
	['5', 5.0],
	[' 12', 12.0],
	['1e3', 1000.0],
	['', null],
	['abc', null],
	[null, null],
];
foreach ($floatCases as [$value, $expected]) {
	$GLOBALS['numericFields']['value'] = $value;
	$check((new AcfGet(1))->Float('value') === $expected, 'Float direct value');
	$check((new AcfGet(['value' => $value]))->Float('value') === $expected, 'Float array value');
}

$intCases = [
	[5, 5],
	[5.0, 5],
	['5', 5],
	['0005', 5],
	[-5, -5],
	['-5', -5],
	['0', 0],
	['+5', 5],
	[5.5, null],
	[PHP_INT_MAX * 2.0, null],
	['5.5', null],
	[' 12', 12],
	["\t-12\n", -12],
	['1 2', null],
	['1e3', null],
	['', null],
	['abc', null],
	[null, null],
	[(string) PHP_INT_MAX, PHP_INT_MAX],
	[(string) PHP_INT_MIN, PHP_INT_MIN],
	[(string) PHP_INT_MAX . '0', null],
];
foreach ($intCases as [$value, $expected]) {
	$GLOBALS['numericFields']['value'] = $value;
	$check((new AcfGet(1))->Int('value') === $expected, 'Int direct value');
	$check((new AcfGet(['value' => $value]))->Int('value') === $expected, 'Int array value');
}

echo "Numeric accessors: {$checks} checks passed.\n";
