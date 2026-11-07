<?php
declare(strict_types=1);

use WpTypeSafety\Acf\Get\AcfGet;
use WpTypeSafety\Core\Check\TypeCheck;
use WpTypeSafety\Wp\Get\WpGet;

require_once __DIR__ . '/../Core/entry.php';

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (!$ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};

$check(TypeCheck::Int(12) === 12, 'Core API is available');

try {
	WpGet::CurrentPageNumber();
	throw new RuntimeException('Wp must not be registered by Core entry.');
} catch (LogicException $exception) {
	$checks++;
}

try {
	(new AcfGet())->Text('title');
	throw new RuntimeException('Acf must not be registered by Core entry.');
} catch (LogicException $exception) {
	$checks++;
}

echo "Core entry: {$checks} checks passed.\n";
