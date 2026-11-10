<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
	exit;
}

require_once __DIR__ . '/../autoload.php';

use WpTypeSafety\Acf\Data\FlexibleNode;
use WpTypeSafety\Acf\Get\AcfGet;

class WP_Post { public function __construct(public int $ID) {} }
class WP_Term { public function __construct(public int $term_id) {} }
class WP_User { public function __construct(public int $ID) {} }

function get_field(string $name, mixed $source = null): mixed
{
	return $GLOBALS['flexibleFields'][$name] ?? null;
}

require_once __DIR__ . '/../Acf/Data/FlexibleNode.php';
require_once __DIR__ . '/../Acf/Get/AcfGet.php';
require_once __DIR__ . '/../Acf/Internal/AcfAdapter.php';
require_once __DIR__ . '/../Acf/Internal/AcfDtoFactory.php';
require_once __DIR__ . '/../Core/Internal/UrlCheck.php';


$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (!$ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};

$GLOBALS['flexibleFields']['contents'] = [
	[
		'acf_fc_layout' => 'image_section',
		'title' => 'Image title',
	],
	[
		'acf_fc_layout' => 'text_section',
		'body' => 'Text body',
	],
	[
		'acf_fc_layout' => '',
		'body' => 'Missing layout',
	],
	[
		'acf_fc_layout' => 123,
	],
];

$nodes = (new AcfGet(123))->Flexible('contents');
$check(count($nodes) === 2, 'invalid layout rows are removed');
$check($nodes[0] instanceof FlexibleNode, 'rows are FlexibleNode');
$check($nodes[0]->Layout === 'image_section', 'layout name');
$check($nodes[0]->Get instanceof AcfGet, 'Get is AcfGet');
$check($nodes[0]->Get->Text('title') === 'Image title', 'row fields');
$check($nodes[1]->Layout === 'text_section', 'preserve order');
$check($nodes[1]->Get->Text('body') === 'Text body', 'second row fields');
$check((new AcfGet(['contents' => $GLOBALS['flexibleFields']['contents']]))->Flexible('contents')[0]->Layout === 'image_section', 'array source');
$check((new AcfGet(123))->Flexible('missing') === [], 'missing field');

echo "Flexible content: {$checks} checks passed\n";
