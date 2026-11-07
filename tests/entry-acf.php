<?php
declare(strict_types=1);

use WpTypeSafety\Acf\Get\AcfGet;
use WpTypeSafety\Wp\Get\WpGet;

function get_query_var(string $name): int
{
	return $name === 'paged' ? 2 : 0;
}

function get_field(string $fieldName, mixed $source = null): mixed
{
	return $fieldName === 'title' ? 'ACF title' : null;
}

require_once __DIR__ . '/../Acf/entry.php';

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (!$ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};

$check(WpGet::CurrentPageNumber() === 2, 'Acf entry prepares Wp');
$check((new AcfGet())->Text('title') === 'ACF title', 'Acf API is registered');

echo "Acf entry: {$checks} checks passed.\n";
