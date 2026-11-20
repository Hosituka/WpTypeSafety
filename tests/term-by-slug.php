<?php
declare(strict_types=1);

// WordPress APIはスタブ。DBを変更せず、単一ターム検索の契約を検証する。
if (PHP_SAPI !== 'cli') {
	exit;
}
require_once __DIR__ . '/../autoload.php';

use WpTypeSafety\Wp\Data\TermData;
use WpTypeSafety\Wp\Get\TermGet;

class WP_Term
{
	public function __construct(
		public int $term_id = 1,
		public string $name = '分類',
		public string $slug = 'knowledge',
		public string $taxonomy = 'category',
		public int $parent = 0,
		public string $description = '',
		public int $count = 0,
	) {}
}
class WP_Error {}
class ProjectTerm extends TermData
{
	protected function initialize(WP_Term $term): void { $GLOBALS['calls']++; }
}
class ThrowingTerm extends TermData
{
	protected function initialize(WP_Term $term): void { throw $GLOBALS['expected']; }
}

function get_term_by(string $field, string $value, string $taxonomy): mixed
{
	$GLOBALS['lookups'][] = [$field, $value, $taxonomy];
	return $GLOBALS['terms'][$taxonomy][$value] ?? false;
}
function get_term_link(WP_Term $term): mixed
{
	return $GLOBALS['termLink'] ?? '/category/' . $term->slug . '/';
}
function is_wp_error(mixed $value): bool { return $value instanceof WP_Error; }

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (!$ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};
$GLOBALS['lookups'] = [];
$GLOBALS['terms']['category']['knowledge'] = new WP_Term();
$GLOBALS['terms']['area']['knowledge'] = new WP_Term(term_id: 2, taxonomy: 'area');
$term = TermGet::BySlug(taxonomy: 'category', slug: 'knowledge');
$check($term instanceof TermData && $term->Id === 1, '単一TermDataを返し投稿0件でも取得');
$check($term->Name === '分類' && $term->Url === '/category/knowledge/', '名前とURLを保持');
$check($GLOBALS['lookups'][0] === ['slug', 'knowledge', 'category'], 'ネイティブAPIへスラッグとタクソノミーを渡す');
$check(TermGet::BySlug('area', 'knowledge')->Id === 2, '同じスラッグでもタクソノミーで区別');

$calls = 0;
$factory = ProjectTerm::class;
$check(TermGet::BySlug('category', 'knowledge', $factory) instanceof ProjectTerm && $calls === 1, 'Factoryで派生型を一度だけ生成');
foreach ([['category', 'missing'], ['missing', 'knowledge']] as [$taxonomy, $slug]) {
	$check(TermGet::BySlug($taxonomy, $slug, $factory) === null && $calls === 1, '該当なしはnull・Factoryを呼ばない');
}
$lookupCount = count($GLOBALS['lookups']);
foreach ([['', 'knowledge'], ['category', ''], ['', '']] as [$taxonomy, $slug]) {
	$check(TermGet::BySlug($taxonomy, $slug, $factory) === null, '空の指定はnull');
}
$check(count($GLOBALS['lookups']) === $lookupCount && $calls === 1, '空の指定では検索とFactoryを呼ばない');
foreach ([new WP_Error(), null, new stdClass()] as $invalid) {
	$GLOBALS['terms']['category']['invalid'] = $invalid;
	$check(TermGet::BySlug('category', 'invalid', $factory) === null && $calls === 1, '不正な取得結果はnull');
}
$GLOBALS['termLink'] = new WP_Error();
$check(TermGet::BySlug('category', 'knowledge') === null, '標準Dataへの変換失敗はnull');
unset($GLOBALS['termLink']);
foreach ([null, new stdClass()] as $invalid) {
	try {
		TermGet::BySlug('category', 'knowledge', stdClass::class);
		throw new RuntimeException('不正なFactoryを受け入れた');
	} catch (InvalidArgumentException) {
		$checks++;
	}
}
$expected = new RuntimeException('Factory exception');
try {
	TermGet::BySlug('category', 'knowledge', ThrowingTerm::class);
	throw new RuntimeException('例外が伝播しなかった');
} catch (RuntimeException $actual) {
	$check($actual === $expected, 'Factory内部の例外をそのまま伝播');
}
echo "Term BySlug: {$checks} checks passed.\n";
