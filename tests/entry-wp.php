<?php
declare(strict_types=1);

use WpTypeSafety\Acf\Get\AcfGet;
use WpTypeSafety\Wp\Get\WpGet;

function get_query_var(string $name): int
{
	return $name === 'paged' ? 3 : 0;
}

require_once __DIR__ . '/../Wp/entry.php';

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (!$ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};

$check(WpGet::CurrentPageNumber() === 3, 'Wp API is registered');

try {
	(new AcfGet())->Text('title');
	throw new RuntimeException('Acf must not be registered by Wp entry.');
} catch (LogicException $exception) {
	$checks++;
}

echo "Wp entry: {$checks} checks passed.\n";
