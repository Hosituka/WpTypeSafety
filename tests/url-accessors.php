<?php
declare(strict_types=1);

// APIスタブのみで検証し、DBは変更しない。
if (PHP_SAPI !== 'cli') {
	exit;
}

require_once __DIR__ . '/../autoload.php';

use WpTypeSafety\Acf\Get\AcfGet;

class WP_Post { public function __construct(public int $ID) {} }
class WP_Term { public function __construct(public int $term_id) {} }
class WP_User { public function __construct(public int $ID) {} }

function get_field(string $name, mixed $source = null): mixed
{
	$GLOBALS['urlSource'] = $source;
	return $GLOBALS['urlFields'][$name] ?? null;
}

require_once __DIR__ . '/../Core/Internal/UrlCheck.php';
require_once __DIR__ . '/../Acf/Get/AcfGet.php';
require_once __DIR__ . '/../Acf/Internal/AcfAdapter.php';

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};
$cases = [
	['https://example.com/', 'https://example.com/'],
	['http://localhost:8080/path?x=1&y=2#anchor', 'http://localhost:8080/path?x=1&y=2#anchor'],
	['https://user:pass@example.com:8443/path', 'https://user:pass@example.com:8443/path'],
	['http://[::1]:8080/path', 'http://[::1]:8080/path'],
	['https://example.com/日本語/', 'https://example.com/日本語/'],
	['https://xn--wgv71a119e.jp/a.jpg', 'https://xn--wgv71a119e.jp/a.jpg'],
	['https://日本語.jp/a.jpg', function_exists('idn_to_ascii') ? 'https://日本語.jp/a.jpg' : null],
	['https://日本語.jp/画像.jpg', function_exists('idn_to_ascii') ? 'https://日本語.jp/画像.jpg' : null],
	['HTTPS://example.com/', 'HTTPS://example.com/'],
	['', null],
	[' ', null],
	[null, null],
	[false, null],
	[true, null],
	[123, null],
	[1.5, null],
	[[], null],
	[['url' => 'https://example.com/'], null],
	[new stdClass(), null],
	['javascript:alert(1)', null],
	['data:text/html,test', null],
	['mailto:test@example.com', null],
	['tel:0123456789', null],
	['ftp://example.com/', null],
	['/relative/', null],
	['//example.com/', null],
	['example.com', null],
	['https://', null],
	['https://exa mple.com/', null],
	['https://my_host.test/a.jpg', null],
	[' https://example.com/', null],
	["https://example.com/\n", null],
];
foreach ($cases as [$value, $expected]) {
	$GLOBALS['urlFields']['url'] = $value;
	$sources = [
		[new AcfGet(123), 123],
		[new AcfGet(new WP_Post(123)), 123],
		[new AcfGet('option'), 'option'],
		[new AcfGet(new WP_Term(4)), 'term_4'],
		[new AcfGet(new WP_User(5)), 'user_5'],
	];
	foreach ($sources as [$get, $source]) {
		$check($get->Url('url') === $expected, 'direct/wrapper URL validation');
		$check($GLOBALS['urlSource'] === $source, 'source forwarding');
	}
	$check((new AcfGet(['url' => $value]))->Url('url') === $expected, 'array URL validation');
	$check((new AcfGet(['basic_info' => ['url' => $value]]))->Nested('basic_info')->Url('url') === $expected, 'nested URL validation');
}
$check((new AcfGet([]))->Url('missing') === null, 'missing array key');
$check((new AcfGet(123))->Url('missing') === null, 'missing field');
$GLOBALS['urlFields']['text'] = 'ordinary text';
$check((new AcfGet(123))->Text('text') === 'ordinary text', 'text regression');
echo "URL accessors: {$checks} checks passed\n";
