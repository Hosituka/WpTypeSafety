<?php
declare(strict_types=1);

// APIスタブで検証する。DBの読み書きは行わない。
if (PHP_SAPI !== 'cli') {
	exit;
}

use WpTypeSafety\Acf\Get\AcfGet;
use WpTypeSafety\Acf\Get\AcfGetTerm;
use WpTypeSafety\Acf\Get\AcfGetUser;

class WP_Post { public function __construct(public int $ID) {} }
class WP_Term { public function __construct(public int $term_id) {} }
class WP_User { public function __construct(public int $ID) {} }

function get_field(string $name, mixed $source = null): mixed
{
	$GLOBALS['gallerySource'] = $source;
	return $GLOBALS['galleryFields'][$name] ?? null;
}

require_once __DIR__ . '/../Core/Internal/UrlCheck.php';
require_once __DIR__ . '/../Acf/Get/AcfGet.php';
require_once __DIR__ . '/../Acf/Get/AcfGetTerm.php';
require_once __DIR__ . '/../Acf/Get/AcfGetUser.php';
require_once __DIR__ . '/../Acf/Internal/AcfAdapter.php';


$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (!$ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};
$url = 'https://example.com/a.jpg?x=1&y=2';
$cases = [
	[null, []],
	[false, []],
	['https://example.com/a.jpg', []],
	[123, []],
	[new stdClass(), []],
	[[], []],
	[[$url, 'http://localhost:8080/b.jpg', $url], [$url, 'http://localhost:8080/b.jpg', $url]],
	[[8 => '', 10 => $url, 20 => 'javascript:alert(1)', 30 => 'https://example.com/c.jpg'], [$url, 'https://example.com/c.jpg']],
	[[false, null, 12, [], ['url' => $url], new stdClass(), '/relative', '//example.com/a.jpg', 'data:image/png,x'], []],
	[[' https://example.com/a.jpg', "https://example.com/a.jpg\n", 'https://', 'ftp://example.com/a.jpg'], []],
	[['https://example.com/日本語.jpg'], ['https://example.com/日本語.jpg']],
];
foreach ($cases as [$value, $expected]) {
	$GLOBALS['galleryFields']['gallery'] = $value;
	$sources = [
		[new AcfGet(12), 12],
		[new AcfGet(new WP_Post(12)), 12],
		[new AcfGet('option'), 'option'],
		[new AcfGetTerm(new WP_Term(3)), 'term_3'],
		[new AcfGetUser(new WP_User(4)), 'user_4'],
	];
	foreach ($sources as [$get, $source]) {
		$check($get->GalleryUrl('gallery') === $expected, 'direct/wrapper gallery URLs');
		$check($GLOBALS['gallerySource'] === $source, 'source forwarded');
	}
	$check((new AcfGet(['gallery' => $value]))->GalleryUrl('gallery') === $expected, 'array source');
	$check((new AcfGet(['group' => ['gallery' => $value]]))->Nested('group')->GalleryUrl('gallery') === $expected, 'Nested source');
}
$check((new AcfGet([]))->GalleryUrl('missing') === [], 'missing field');
$check((new AcfGetTerm(null))->GalleryUrl('gallery') === [], 'null term does not use current post');
$check((new AcfGetUser(null))->GalleryUrl('gallery') === [], 'null user does not use current post');
echo "gallery-url: {$checks} checks passed.\n";
