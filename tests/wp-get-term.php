<?php
declare(strict_types=1);

// 投稿ターム取得と階層選別で派生TermDataを維持できることを検証する。DBは変更しない。
if (PHP_SAPI !== 'cli') {
	exit;
}

class WP_Post
{
	public function __construct(public int $ID) {}
}

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

function get_the_terms(int $postId, string $taxonomy): mixed
{
	return $GLOBALS['postTerms'][$postId][$taxonomy] ?? false;
}

function get_term_link(WP_Term $term): string
{
	return '/term/' . $term->slug . '/';
}

function is_wp_error(mixed $value): bool
{
	return $value instanceof WP_Error;
}

/** @return list<int> */
function get_ancestors(int $termId, string $taxonomy, string $resourceType): array
{
	return $GLOBALS['termAncestors'][$termId] ?? [];
}

function get_term(int $termId, string $taxonomy): mixed
{
	return $GLOBALS['wpTermValues'][$termId] ?? null;
}

require_once __DIR__ . '/../autoload.php';

use WpTypeSafety\Wp\Data\TermData;
use WpTypeSafety\Wp\Data\TermProps;
use WpTypeSafety\Wp\Get\TermGet;

class ProjectPostTermData extends TermData {}

$root = new WP_Term(1, '神奈川県', 'kanagawa');
$child = new WP_Term(2, '横浜市', 'yokohama', parent: 1);
$grandchild = new WP_Term(3, '中区', 'naka', parent: 2);
$GLOBALS['postTerms'] = [
	10 => ['area' => [$child, 'invalid', $grandchild]],
];
$GLOBALS['wpTermValues'] = [1 => $root, 2 => $child, 3 => $grandchild];
$GLOBALS['termAncestors'] = [1 => [], 2 => [1], 3 => [2, 1]];

$termFactory = static fn (WP_Term $term): ProjectPostTermData => new ProjectPostTermData(
	new TermProps(
		Id: $term->term_id,
		Name: $term->name,
		Slug: $term->slug,
		TaxonomyName: $term->taxonomy,
		Url: '/term/' . $term->slug . '/',
		ParentId: $term->parent,
	),
);

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};

$baseTerms = TermGet::FromPost(10, 'area');
$check(count($baseTerms) === 2 && $baseTerms[0] instanceof TermData, 'Factory省略時は共通TermDataを返す');

$terms = TermGet::FromPost(10, 'area', $termFactory);
$check(count($terms) === 2 && $terms[0] instanceof ProjectPostTermData, '投稿タームを派生Dataへ変換する');
$check(TermGet::Deepest($terms) === [$terms[1]], '最深タームで派生Dataと同じインスタンスを維持する');
$check(TermGet::Shallowest($terms) === [$terms[0]], '最浅タームで派生Dataと同じインスタンスを維持する');

$roots = TermGet::Roots($terms, $termFactory);
$check(count($roots) === 1 && $roots[0] instanceof ProjectPostTermData, 'ルート解決後も派生Dataを維持する');
$check($roots[0]->Id === 1, '祖先の最上位タームを返す');

foreach ([
	static fn () => TermGet::FromPost(
		10,
		'area',
		static fn (WP_Term $term): mixed => null,
	),
	static fn () => TermGet::Roots(
		$terms,
		static fn (WP_Term $term): mixed => null,
	),
] as $invalidFactory) {
	try {
		$invalidFactory();
		throw new RuntimeException('ターム生成Factoryがnullを返せた');
	} catch (UnexpectedValueException) {
		$check(true, 'ターム生成FactoryはTermDataを必ず返す');
	}
}

echo "TermGet: {$checks} checks passed.\n";
