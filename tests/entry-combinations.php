<?php
declare(strict_types=1);

use WpTypeSafety\Acf\Get\AcfGet;
use WpTypeSafety\Core\Check\TypeCheck;
use WpTypeSafety\Wp\Get\WpGet;

function get_query_var(string $name): int
{
	return $name === 'paged' ? 4 : 0;
}

function get_field(string $fieldName, mixed $source = null): mixed
{
	return $fieldName === 'title' ? 'Combined title' : null;
}

require_once __DIR__ . '/../Core/entry.php';
require_once __DIR__ . '/../Wp/entry.php';
require_once __DIR__ . '/../Acf/entry.php';
require_once __DIR__ . '/../entry.php';

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (!$ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};

$check(TypeCheck::String('ready') === 'ready', 'Core remains available');
$check(WpGet::CurrentPageNumber() === 4, 'Wp remains registered once');
$check((new AcfGet())->Text('title') === 'Combined title', 'Acf remains registered once');

echo "Entry combinations: {$checks} checks passed.\n";
