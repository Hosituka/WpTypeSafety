<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/Wp/entry.php';

\WpTypeSafety\Acf\Internal\AcfAdapter::Register();
\WpTypeSafety\Acf\Internal\SubjectAdapter::Register();
