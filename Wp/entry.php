<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/entry.php';

\WpTypeSafety\Wp\Internal\TermAdapter::Register();
\WpTypeSafety\Wp\Internal\PageAdapter::Register();
\WpTypeSafety\Wp\Internal\MenuAdapter::Register();
