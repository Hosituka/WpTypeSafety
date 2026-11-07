<?php
declare(strict_types=1);

namespace WpTypeSafety\Autoload\Internal;

/** @internal composer.json の autoload.psr-4 だけを読む。 */
final class ComposerJsonReader
{
	private function __construct() {}

	/** @return array<string, list<string>> */
	public static function ReadPsr4(string $configPath): array
	{
		$json = file_get_contents($configPath);
		if ($json === false) {
			throw new \RuntimeException('Cannot read Composer JSON file: ' . $configPath);
		}
		$config = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
		$psr4 = is_array($config) ? ($config['autoload']['psr-4'] ?? null) : null;
		if (!is_array($psr4) || $psr4 === []) {
			throw new \InvalidArgumentException('composer.json must contain a non-empty autoload.psr-4 object.');
		}

		$mapping = [];
		foreach ($psr4 as $prefix => $paths) {
			if (!is_string($prefix) || $prefix === '' || !str_ends_with($prefix, '\\')) {
				throw new \InvalidArgumentException('PSR-4 namespace prefix must end with a backslash.');
			}
			$paths = is_string($paths) ? [$paths] : $paths;
			if (!is_array($paths) || !array_is_list($paths) || $paths === []) {
				throw new \InvalidArgumentException('PSR-4 paths must be a string or non-empty list of strings: ' . $prefix);
			}
			$directories = [];
			foreach ($paths as $relativePath) {
				if (!is_string($relativePath)) {
					throw new \InvalidArgumentException('PSR-4 path must be a string: ' . $prefix);
				}
				$directory = realpath(dirname($configPath) . DIRECTORY_SEPARATOR . $relativePath);
				if ($directory === false || !is_dir($directory)) {
					throw new \InvalidArgumentException('PSR-4 directory does not exist: ' . $relativePath);
				}
				$directories[] = $directory;
			}
			$mapping[$prefix] = $directories;
		}
		return $mapping;
	}
}
