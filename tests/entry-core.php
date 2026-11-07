<?php
declare(strict_types=1);

use WpTypeSafety\Acf\Get\AcfGet;
use WpTypeSafety\Core\Check\TypeCheck;
use WpTypeSafety\Wp\Get\WpGet;

function get_query_var(string $name): int
{
	return $name === 'paged' ? 2 : 0;
}

require_once __DIR__ . '/../Core/entry.php';

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (!$ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};

$check(TypeCheck::Int(12) === 12, 'Core API is available');
$check(WpGet::CurrentPageNumber() === 2, 'Wp API works without registration');
$check((new AcfGet(['title' => 'Example']))->Text('title') === 'Example', 'Acf API works without registration');

echo "Core entry: {$checks} checks passed.\n";
