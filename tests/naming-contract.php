<?php
declare(strict_types=1);

// 命名と既存のブロック判定を検証する。WordPress・DBは使用しない。
if (PHP_SAPI !== 'cli') {
	exit;
}

require_once __DIR__ . '/../autoload.php';

require_once __DIR__ . '/../Acf/Data/AcfBlockData.php';
require_once __DIR__ . '/../Acf/Get/AcfGet.php';
require_once __DIR__ . '/../Acf/Internal/AcfAdapter.php';
require_once __DIR__ . '/../Acf/Internal/AcfDtoFactory.php';
require_once __DIR__ . '/../Acf/Internal/BlockAdapter.php';
require_once __DIR__ . '/../Wp/Data/WpUserData.php';

use WpTypeSafety\Acf\Data\AcfBlockData;
use WpTypeSafety\Acf\Get\AcfGet;
use WpTypeSafety\Acf\Internal\AcfAdapter;
use WpTypeSafety\Acf\Internal\AcfDtoFactory;
use WpTypeSafety\Acf\Internal\BlockAdapter;
use WpTypeSafety\Core\Check\ValueReader;
use WpTypeSafety\Internal\TreeTypeReader\Internal\ValuePredicate;
use WpTypeSafety\Wp\Data\WpUserData;
use WpTypeSafety\Wp\Get\MetaGet;
use WpTypeSafety\Wp\Internal\MetaAdapter;
use WpTypeSafety\Wp\Internal\ReferenceAdapter;
use WpTypeSafety\Wp\Internal\WpValueRules;

$checks = 0;
$Check = static function (bool $ok, string $label) use (&$checks): void {
	if (!$ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};

foreach ([
	AcfBlockData::class,
	AcfGet::class,
	AcfAdapter::class,
	AcfDtoFactory::class,
	BlockAdapter::class,
	ValueReader::class,
	ValuePredicate::class,
	WpUserData::class,
	MetaGet::class,
	MetaAdapter::class,
	ReferenceAdapter::class,
	WpValueRules::class,
] as $class) {
	$reflection = new ReflectionClass($class);
	foreach ($reflection->getMethods() as $method) {
		if (str_starts_with($method->name, '__')) {
			continue;
		}
		$pattern = $method->isPublic() ? '/^[A-Z][A-Za-z0-9]*$/' : '/^[a-z][A-Za-z0-9]*$/';
		$Check(preg_match($pattern, $method->name) === 1, $class . '::' . $method->name);
	}
	foreach ($reflection->getProperties() as $property) {
		$pattern = $property->isPublic() ? '/^[A-Z][A-Za-z0-9]*$/' : '/^[a-z][A-Za-z0-9]*$/';
		$Check(preg_match($pattern, $property->name) === 1, $class . '::$' . $property->name);
	}
}

$Check(!method_exists(AcfGet::class, 'Register'), 'AcfGet does not require static registration');
$Check(!method_exists(AcfAdapter::class, 'Register'), 'AcfAdapter does not require static registration');
$constructor = new ReflectionMethod(AcfGet::class, '__construct');
$adapterParameter = $constructor->getParameters()[1] ?? null;
$Check($adapterParameter?->getName() === 'adapter', 'AcfGet accepts an adapter');
$Check($adapterParameter?->isOptional() === true, 'AcfGet adapter is optional');

$block = new AcfBlockData(Name: 'test', Id: 'test-id', Classes: ['is-style-without-img']);
$Check($block->HasClass('is-style-without-img'), 'HasClass preserves exact matching');
$Check(!$block->HasClass('without-img'), 'HasClass rejects partial matching');
$Check($block->HasStyle('without-img'), 'HasStyle preserves style matching');
$Check(!$block->HasStyle(''), 'HasStyle rejects empty style');
$Check(!$block->HasStyle('default'), 'HasStyle does not infer default');

echo "Naming contract: {$checks} checks passed.\n";
