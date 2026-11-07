<?php

declare(strict_types=1);

// WordPress・DBには接続せず、親取得の注入と公開Factoryを検証する。
if (PHP_SAPI !== 'cli') {
	exit;
}

require_once __DIR__ . '/../Wp/Data/TermData.php';
require_once __DIR__ . '/../Wp/Internal/TermAdapter.php';
require_once __DIR__ . '/../Wp/Get/WpDataFactory.php';

use WpTypeSafety\Wp\Data\TermData;
use WpTypeSafety\Wp\Get\WpDataFactory;

class WP_Term
{
	public function __construct(
		public int $term_id,
		public string $name,
		public string $slug,
		public string $taxonomy = 'area',
		public int $parent = 0,
		public string $description = '',
	) {}
}

class WP_Error {}

function get_term(int $id, string $taxonomy): mixed
{
	$GLOBALS['termCalls'][] = [$id, $taxonomy];
	return $GLOBALS['termValues'][$id] ?? null;
}
function get_term_link(WP_Term $term): mixed
{
	return $GLOBALS['termLinks'][$term->term_id] ?? '/area/' . $term->slug . '/';
}
function is_wp_error(mixed $value): bool { return $value instanceof WP_Error; }

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};

$parent = new TermData(1, '神奈川県', 'kanagawa', 'area', '/area/kanagawa/');
$plain = new TermData(2, '海老名市', 'ebina', 'area', '/area/ebina/', 1);
$check($plain->HasParent(), '親IDがある');
$check($plain->GetParent() === null, 'Resolver省略時はnull');

$calls = 0;
$resolver = static function () use (&$calls, $parent): ?TermData {
	$calls++;
	return $parent;
};
$direct = new TermData(2, '海老名市', 'ebina', 'area', '/', 1, parentResolver: $resolver);
$check($calls === 0, '構築時には取得しない');
$check($direct->GetParent() === $parent, '直接注入した親を返す');
$direct->GetParent();
$check($calls === 2, '呼び出すたびに取得する');
$root = new TermData(1, '神奈川県', 'kanagawa', 'area', '/', parentResolver: $resolver);
$check(! $root->HasParent() && $root->GetParent() === null && $calls === 2, 'ルートはResolverを呼ばない');
$missing = new TermData(2, '海老名市', 'ebina', 'area', '/', 1, parentResolver: static fn (): ?TermData => null);
$check($missing->GetParent() === null, '注入先のnullを維持する');

$invalid = new TermData(2, '海老名市', 'ebina', 'area', '/', 1, parentResolver: static fn () => 'invalid');
try {
	$invalid->GetParent();
	throw new RuntimeException('不正なResolverの戻り値を受け入れた');
} catch (TypeError) {
	$check(true, 'Resolverの型違いを検出する');
}

$GLOBALS['termCalls'] = [];
$GLOBALS['termLinks'] = [];
$GLOBALS['termValues'] = [
	1 => new WP_Term(1, '神奈川県', 'kanagawa'),
];
$wpChild = new WP_Term(2, '海老名市', 'ebina', parent: 1);
$data = WpDataFactory::TermFromWpTerm($wpChild);
$check($data instanceof TermData && $GLOBALS['termCalls'] === [], '公開Factoryも親の取得は遅延する');
$result = $data->GetParent();
$check($result?->Id === 1 && $result->Slug === 'kanagawa', '公開Factory経由で親を取得する');
$check($GLOBALS['termCalls'] === [[1, 'area']], '親IDと同じタクソノミーを渡す');
$check($result->GetParent() === null, '親の親がなければnull');
$legacy = new TermData(2, '海老名市', 'ebina', 'area', '/', 1, '', static fn (): array => [$parent]);
$check($legacy->GetChildren() === [$parent], '既存の位置引数による子取得を維持する');

foreach ([null, false, new WP_Error(), 'invalid', []] as $value) {
	$GLOBALS['termValues'][1] = $value;
	$check($data->GetParent() === null, '欠損・WP_Error・型違いはnull');
}
$GLOBALS['termValues'][1] = new WP_Term(1, '神奈川県', 'kanagawa');
foreach ([new WP_Error(), false, []] as $url) {
	$GLOBALS['termLinks'][1] = $url;
	$check($data->GetParent() === null, '親URLの取得失敗はnull');
}
unset($GLOBALS['termLinks'][1]);
$check($data->GetParent()?->Slug === 'kanagawa', '再取得で親を取得できる');
$rootData = WpDataFactory::TermFromWpTerm(new WP_Term(1, '神奈川県', 'kanagawa'));
$before = count($GLOBALS['termCalls']);
$check($rootData->GetParent() === null && count($GLOBALS['termCalls']) === $before, 'Factoryで作ったルートも親を問い合わせない');
$GLOBALS['termLinks'][2] = new WP_Error();
$check(WpDataFactory::TermFromWpTerm($wpChild) === null, '元のターム変換失敗の契約を維持する');

echo "term-parent: {$checks} checks passed\n";
