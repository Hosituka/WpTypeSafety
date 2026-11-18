<?php

declare(strict_types=1);

// WordPress・DBには接続せず、親取得の注入と公開Factoryを検証する。
if (PHP_SAPI !== 'cli') {
	exit;
}

require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../Wp/Data/TermData.php';
require_once __DIR__ . '/../Wp/Data/TermProps.php';
require_once __DIR__ . '/../Wp/Internal/TermAdapter.php';
require_once __DIR__ . '/../Wp/Get/TermGet.php';

use WpTypeSafety\Wp\Data\TermData;
use WpTypeSafety\Wp\Data\TermProps;
use WpTypeSafety\Wp\Get\TermGet;

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
function is_taxonomy_hierarchical(string $taxonomy): bool { return $taxonomy === 'area'; }
function get_terms(array $args): mixed { $GLOBALS['termChildArgs'] = $args; return $GLOBALS['termChildren'] ?? []; }

/**
 * @template TRelatedTerm of TermData = TermData
 * @extends TermData<TRelatedTerm>
 */
class ProjectTermData extends TermData
{
	/** @param TermProps<TRelatedTerm> $props */
	public function __construct(TermProps $props, public readonly string $Label)
	{
		parent::__construct($props);
	}

	/** @return ProjectTermData<ProjectTermData>|null */
	public static function FromWpTerm(WP_Term $term): ?self
	{
		/** @var TermProps<ProjectTermData>|null $props */
		$props = TermGet::Props($term);
		return $props !== null ? new self($props, '派生ターム' . $term->term_id) : null;
	}
}

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};

class RelatedTermData extends ProjectTermData
{
	protected function createRelatedTerm(WP_Term $term): ?ProjectTermData { return ProjectTermData::FromWpTerm($term); }
}
$plain = new TermData(new TermProps(2, '海老名市', 'ebina', 'area', '/area/ebina/', 1));
$check($plain->HasParent(), '親IDがある');
$check($plain->GetParent() === null, '取得先が欠損ならnull');
$root = new TermData(new TermProps(1, '神奈川県', 'kanagawa', 'area', '/'));
$check(! $root->HasParent() && $root->GetParent() === null, 'ルートは取得しない');

$GLOBALS['termCalls'] = [];
$GLOBALS['termLinks'] = [];
$GLOBALS['termValues'] = [
	1 => new WP_Term(1, '神奈川県', 'kanagawa'),
];
$wpChild = new WP_Term(2, '海老名市', 'ebina', parent: 1);
$termMembers = TermGet::Props($wpChild);
$check($termMembers instanceof TermProps && $termMembers->Id === 2, '公開FactoryからTermPropsを生成する');
$data = TermGet::From($wpChild);
$check($data instanceof TermData && $GLOBALS['termCalls'] === [], '公開Factoryも親の取得は遅延する');
$result = $data->GetParent();
$check($result?->Id === 1 && $result->Slug === 'kanagawa', '公開Factory経由で親を取得する');
$check($GLOBALS['termCalls'] === [[1, 'area']], '親IDと同じタクソノミーを渡す');
$check($result->GetParent() === null, '親の親がなければnull');


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
$rootData = TermGet::From(new WP_Term(1, '神奈川県', 'kanagawa'));
$before = count($GLOBALS['termCalls']);
$check($rootData->GetParent() === null && count($GLOBALS['termCalls']) === $before, 'Factoryで作ったルートも親を問い合わせない');
$GLOBALS['termLinks'][2] = new WP_Error();
$check(TermGet::From($wpChild) === null, '元のターム変換失敗の契約を維持する');
unset($GLOBALS['termLinks'][2]);
$GLOBALS['termValues'][1] = new WP_Term(1, '神奈川県', 'kanagawa');
$GLOBALS['termValues'][2] = $wpChild;
$GLOBALS['termChildren'] = [new WP_Term(3, '綾瀬市', 'ayase', parent: 2)];
$derivedTerm = new RelatedTermData(TermGet::Props($wpChild), '生成フック');
$check($derivedTerm?->GetParent()?->Label === '派生ターム1', '関連Factoryで親タームの派生型を維持する');
$check($derivedTerm?->GetChildren()[0]->Label === '派生ターム3', '関連Factoryで子タームの派生型を維持する');
$baseRelations = new ProjectTermData(TermGet::Props($wpChild), '基底関連');
$check(
	$baseRelations->GetParent() instanceof TermData
	&& ! $baseRelations->GetParent() instanceof ProjectTermData,
	'関連Factory省略時は純粋なTermDataを返す',
);

$adjusted = TermGet::Props($wpChild);
$adjusted->Id = 4;
$adjusted->ParentId = 3;
$GLOBALS['termValues'][3] = new WP_Term(3, '変更後の親', 'changed-parent');
$GLOBALS['termValues'][4] = new WP_Term(4, '変更後の自身', 'changed-self', parent: 3);
$GLOBALS['termChildren'] = [8 => new WP_Term(5, '子', 'child'), 9 => 'invalid'];
$snapshot = new TermData($adjusted);
$adjusted->Id = 99;
$check($snapshot->GetParent()?->Id === 3, '変更した親IDで取得');
$check($snapshot->GetChildren()[0]->Id === 5 && $GLOBALS['termChildArgs']['parent'] === 4, '変更した自身のIDで取得');
class ExcludedTerm extends TermData
{
	protected function createRelatedTerm(WP_Term $term): ?TermData { return null; }
}
class InvalidTerm extends TermData
{
	protected function createRelatedTerm(WP_Term $term): ?TermData { return new stdClass(); }
}
$adjusted->Id = 4;
$excluded = new ExcludedTerm($adjusted);
$check($excluded->GetParent() === null && $excluded->GetChildren() === [], '生成フックのnullを親子双方へ適用');
$invalid = new InvalidTerm($adjusted);
foreach (['GetParent', 'GetChildren'] as $method) {
	try {
		$invalid->$method();
		throw new RuntimeException('不正な生成フックを受け入れた');
	} catch (TypeError) {
		$checks++;
	}
}
echo "term-parent: {$checks} checks passed\n";
