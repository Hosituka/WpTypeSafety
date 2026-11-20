<?php
declare(strict_types=1);

// No WordPress database: verify root resolution, regeneration and failure semantics.
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/fixtures/data-props.php';

use WpTypeSafety\Wp\Data\TermData;
use WpTypeSafety\Wp\Data\TermProps;
use WpTypeSafety\Wp\Get\TermGet;

class WP_Term
{
	public string $description = '';
	public function __construct(
		public int $term_id,
		public string $name,
		public string $slug,
		public string $taxonomy = 'area',
		public int $parent = 0,
	) {}
}
class WP_Error {}
function get_ancestors(int $id, string $taxonomy, string $resourceType): array
{
	$GLOBALS['rootCalls'][] = ['ancestors', $id, $taxonomy, $resourceType];
	return $GLOBALS['rootAncestors'][$taxonomy][$id] ?? [];
}
function get_term(int $id, string $taxonomy): mixed
{
	$GLOBALS['rootCalls'][] = ['term', $id, $taxonomy];
	return $GLOBALS['rootNative'][$taxonomy][$id] ?? null;
}
function get_term_link(WP_Term $term): mixed
{
	return $GLOBALS['rootLinks'][$term->taxonomy][$term->term_id] ?? '/term/' . $term->slug . '/';
}
function is_wp_error(mixed $value): bool { return $value instanceof WP_Error; }

class InputRootTerm extends TermData
{
	use TestTermProps;
	public readonly string $LocalOnly;
	protected function initialize(WP_Term $term): void
	{
		parent::initialize($term);
		$this->LocalOnly = 'local';
	}
}
class OutputRootTerm extends TermData
{
	public readonly string $Marker;
	protected static function getProps(WP_Term $term): ?TermProps
	{
		$props = parent::getProps($term);
		if ($props === null)
		{return null;}
		$props->Name = 'fresh:' . $props->Name;
		return $props;
	}
	protected function initialize(WP_Term $term): void
	{
		parent::initialize($term);
		$this->Marker = 'output:' . $this->Id;
	}
}
class ExcludedRootTerm extends OutputRootTerm
{
	protected static function getProps(WP_Term $term): ?TermProps { return null; }
	protected function initialize(WP_Term $term): void { throw new LogicException('must not initialize'); }
}
class ThrowingRootTerm extends OutputRootTerm
{
	protected function initialize(WP_Term $term): void { throw $GLOBALS['rootError']; }
}
abstract class AbstractRootTerm extends TermData {}

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (!$ok)
	{throw new RuntimeException($label);}
	$checks++;
};
$GLOBALS['rootCalls'] = [];
$GLOBALS['rootNative'] = [
	'area' => [
		1 => new WP_Term(1, 'root', 'root'),
		2 => new WP_Term(2, 'child', 'child', parent: 1),
		3 => new WP_Term(3, 'grandchild', 'grandchild', parent: 2),
	],
	'category' => [1 => new WP_Term(1, 'category root', 'category-root', 'category')],
];
$GLOBALS['rootAncestors'] = ['area' => [2 => [1], 3 => [2, 1]]];
$input = InputRootTerm::FromProps(new TermProps(1, 'edited locally', 'edited', 'area', '/local/'));
$child = InputRootTerm::FromProps(new TermProps(2, 'old child', 'old-child', 'area', '/old/', 0));
$grandchild = InputRootTerm::FromProps(new TermProps(3, 'old grandchild', 'old-grandchild', 'area', '/old/', 2));

$output = TermGet::Roots([$input], OutputRootTerm::class);
$check(count($output) === 1 && $output[0] instanceof OutputRootTerm, '親なしも指定クラスへ変換');
$check($output[0] !== $input && $output[0]->Name === 'fresh:root' && $output[0]->Url === '/term/root/', '入力のインスタンス・編集済みPropsを引き継がない');
$check($output[0]->Marker === 'output:1' && !property_exists($output[0], 'LocalOnly'), '指定クラスのinitializeを実行');
$check(TermGet::Roots([$input], OutputRootTerm::class)[0] !== $output[0], '呼び出し間のキャッシュなし');
$check(TermGet::Roots([$child], OutputRootTerm::class)[0]->Id === 1, '入力ParentIdが古くても現在の階層を解決');
$check(count(TermGet::Roots([$input, $child, $grandchild], OutputRootTerm::class)) === 1, '同じルートを重複排除');
$base = TermGet::Roots([$input], TermData::class)[0];
$check(get_class($base) === TermData::class && !property_exists($base, 'LocalOnly'), '基底クラスを明示した場合も新規生成');
$category = InputRootTerm::FromProps(new TermProps(1, 'local category', 'local', 'category', '/'));
$cross = TermGet::Roots([$category, $child, $input], OutputRootTerm::class);
$check(array_map(static fn (TermData $term): string => $term->TaxonomyName, $cross) === ['category', 'area'], 'タクソノミーとIDで重複を判定・最初の解決順を維持');
$check(array_is_list($cross), '結果は連番のlist');
$check(TermGet::Roots([$input, $child], ExcludedRootTerm::class) === [], '変換nullでも入力へフォールバックしない');
$GLOBALS['rootLinks']['area'][1] = new WP_Error();
$check(TermGet::Roots([$input, $child], OutputRootTerm::class) === [], 'URL取得失敗も除外');
unset($GLOBALS['rootLinks']['area'][1]);

foreach ([null, false, new WP_Error(), new stdClass(), new WP_Term(1, 'wrong taxonomy', 'wrong', 'category'), new WP_Term(1, 'not root', 'not-root', parent: 2)] as $missing) {
	$GLOBALS['rootNative']['area'][1] = $missing;
	$check(TermGet::Roots([$input, $child], OutputRootTerm::class) === [], '取得失敗・異なる分類・ルート未解決は除外');
}
$GLOBALS['rootNative']['area'][1] = new WP_Term(1, 'root', 'root');
$deleted = InputRootTerm::FromProps(new TermProps(99, 'deleted', 'deleted', 'area', '/'));
$check(TermGet::Roots([$deleted], OutputRootTerm::class) === [], '削除された親なし入力も返さない');
$GLOBALS['rootAncestors']['area'][2] = [999];
$check(TermGet::Roots([$child], OutputRootTerm::class) === [], '祖先の参照切れを入力で補わない');
$GLOBALS['rootAncestors']['area'][2] = [];
$check(TermGet::Roots([$child], OutputRootTerm::class) === [], '祖先取得が空でも子自身をルートにしない');
$GLOBALS['rootAncestors']['area'][2] = [1];

$invalidId = InputRootTerm::FromProps(new TermProps(0, '', '', 'area', '/'));
$invalidTaxonomy = InputRootTerm::FromProps(new TermProps(1, '', '', '', '/'));
$GLOBALS['rootCalls'] = [];
$check(TermGet::Roots([$invalidId, $invalidTaxonomy], OutputRootTerm::class) === [] && $GLOBALS['rootCalls'] === [], '無効な識別子は問い合わせない');
$check(TermGet::Roots([], OutputRootTerm::class) === [], '空入力は空list');
foreach (['', 'MissingRootDataClass', stdClass::class, AbstractRootTerm::class] as $dataClass) {
	foreach ([[], [$input]] as $terms) {
		$GLOBALS['rootCalls'] = [];
		try {
			TermGet::Roots($terms, $dataClass);
			$check(false, 'invalid class accepted');
		} catch (InvalidArgumentException) {
			$check($GLOBALS['rootCalls'] === [], '空入力でもクラス検証・問い合わせ前に失敗');
		}
	}
}
try {
	TermGet::Roots([$input]);
	$check(false, 'dataClass must be required');
} catch (ArgumentCountError) {
	$checks++;
}
$GLOBALS['rootError'] = new LogicException('root initialization');
try {
	TermGet::Roots([$input], ThrowingRootTerm::class);
	$check(false, 'exception swallowed');
} catch (LogicException $error) {
	$check($error === $GLOBALS['rootError'], '生成例外をそのまま伝播');
}
$check(TermGet::Deepest([$input]) === [$input] && TermGet::Shallowest([$input]) === [$input], '選別APIの入力インスタンス維持は変更しない');

echo "Term roots: {$checks} checks passed.\n";
