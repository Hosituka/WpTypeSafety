<?php
declare(strict_types=1);

require_once __DIR__ . '/Autoload/Internal/ComposerJsonReader.php';
require_once __DIR__ . '/Autoload/Psr4Autoload.php';

\WpTypeSafety\Autoload\Psr4Autoload::RegisterFromComposerJson(
	__DIR__ . '/composer.json',
);
