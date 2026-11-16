<?php
declare(strict_types=1);

use WpTypeSafety\Acf\Get\AcfGet;
use WpTypeSafety\Autoload\Psr4Autoload;
use WpTypeSafety\Core\Check\TypeCheck;
use WpTypeSafety\Wp\Get\MainQueryGet;

class WP_Query
{
	public function get(string $name): int { return $name === 'paged' ? 4 : 0; }
	public function is_front_page(): bool { return false; }
	public function is_page(): bool { return false; }
}
$GLOBALS['wp_the_query'] = new WP_Query();

function get_field(string $fieldName, mixed $source = null): mixed
{
	return $fieldName === 'title' ? 'Autoload title' : null;
}

require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../autoload.php';

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};

$check(class_exists(Psr4Autoload::class), 'Autoloader API is available');
$check(TypeCheck::String('ready') === 'ready', 'Core API is autoloaded');
$check(MainQueryGet::CurrentPageNumber() === 4, 'Wp API is autoloaded');
$check((new AcfGet())->Text('title') === 'Autoload title', 'Acf API is autoloaded');

echo "Autoload bootstrap: {$checks} checks passed.\n";
