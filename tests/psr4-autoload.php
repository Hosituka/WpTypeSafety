<?php
declare(strict_types=1);

use WpTypeSafety\Autoload\Psr4Autoload;

require_once __DIR__ . '/../Autoload/Internal/ComposerJsonReader.php';
require_once __DIR__ . '/../Autoload/Psr4Autoload.php';

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (!$ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};
$root = sys_get_temp_dir() . '/wpts-autoload-' . bin2hex(random_bytes(6));
mkdir($root . '/parts/Card', 0777, true);
mkdir($root . '/other', 0777, true);
file_put_contents($root . '/parts/Card/Data.php', '<?php namespace Theme\\Parts\\Card; class Data {}');
file_put_contents($root . '/other/Extra.php', '<?php namespace Theme\\Parts; class Extra {}');
$config = $root . '/composer.json';
file_put_contents($config, json_encode([
	'autoload' => ['psr-4' => ['Theme\\Parts\\' => ['parts/', 'other/']]],
], JSON_THROW_ON_ERROR));

$check(!class_exists('Theme\\Parts\\Card\\Data', false), 'opt-in: no class loaded before registration');
$check(!class_exists(Composer\Autoload\ClassLoader::class, false), 'Composer loader is lazy');
Psr4Autoload::RegisterFromComposerJson($config);
$check(class_exists('Theme\\Parts\\Card\\Data'), 'nested PSR-4 class');
$check(class_exists('Theme\\Parts\\Extra'), 'second PSR-4 directory');
$before = count(spl_autoload_functions());
Psr4Autoload::RegisterFromComposerJson($config);
$check(count(spl_autoload_functions()) === $before, 'same configuration is registered once');

file_put_contents($root . '/other/Second.php', '<?php namespace Other; class Second {}');
$secondConfig = $root . '/other-composer.json';
file_put_contents($secondConfig, '{"autoload":{"psr-4":{"Other\\\\":"other/"}}}');
Psr4Autoload::RegisterFromComposerJson($secondConfig);
$check(class_exists('Other\\Second'), 'second configuration uses already loaded Composer class');

foreach (['missing.json', 'invalid.json', 'bad-prefix.json', 'missing-directory.json'] as $name) {
	if ($name === 'invalid.json') {
		file_put_contents($root . '/' . $name, '{');
	} elseif ($name === 'bad-prefix.json') {
		file_put_contents($root . '/' . $name, '{"autoload":{"psr-4":{"Bad":"parts/"}}}');
	} elseif ($name === 'missing-directory.json') {
		file_put_contents($root . '/' . $name, '{"autoload":{"psr-4":{"Bad\\\\":"absent/"}}}');
	}
	try {
		Psr4Autoload::RegisterFromComposerJson($root . '/' . $name);
		throw new RuntimeException('Expected invalid configuration: ' . $name);
	} catch (InvalidArgumentException|JsonException $exception) {
		$checks++;
	}
}

unlink($root . '/parts/Card/Data.php');
unlink($root . '/other/Extra.php');
unlink($config);
unlink($secondConfig);
unlink($root . '/other/Second.php');
unlink($root . '/invalid.json');
unlink($root . '/bad-prefix.json');
unlink($root . '/missing-directory.json');
rmdir($root . '/parts/Card');
rmdir($root . '/parts');
rmdir($root . '/other');
rmdir($root);
echo "PSR-4 autoload: {$checks} checks passed.\n";
