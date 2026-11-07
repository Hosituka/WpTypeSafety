<?php
declare(strict_types=1);

namespace WpTypeSafety\Autoload;

use WpTypeSafety\Autoload\Internal\ComposerJsonReader;

/** 任意で有効にする、composer.json の PSR-4 設定用 autoloader。 */
final class Psr4Autoload
{
	/** @var array<string, true> */
	private static array $registeredConfigs = [];

	private function __construct() {}

	public static function RegisterFromComposerJson(string $configPath): void
	{
		$path = realpath($configPath);
		if ($path === false || !is_file($path)) {
			throw new \InvalidArgumentException('Composer JSON file does not exist: ' . $configPath);
		}
		if (isset(self::$registeredConfigs[$path])) {
			return;
		}

		$mapping = ComposerJsonReader::ReadPsr4($path);
		if (!class_exists(\Composer\Autoload\ClassLoader::class, false)) {
			require_once __DIR__ . '/Internal/Composer/ClassLoader.php';
		}
		$loader = new \Composer\Autoload\ClassLoader();
		foreach ($mapping as $prefix => $directories) {
			$loader->addPsr4($prefix, $directories);
		}
		$loader->register();
		self::$registeredConfigs[$path] = true;
	}
}
