<?php
declare(strict_types=1);

// WordPressクエリの参照はスタブ。DBを変更しない。
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
		public string $slug = 'sample',
		public string $taxonomy = 'category',
		public int $parent = 0,
		public string $description = '',
	) {}
}
class WP_Post {}
class WP_Error {}
readonly class ProjectTerm extends TermData {}
class WP_Query
{
	public function get_queried_object(): ?object { return $GLOBALS['queriedObject'] ?? null; }
}
$GLOBALS['wp_the_query'] = new WP_Query();
$GLOBALS['wp_query'] = new stdClass();
function get_term_link(WP_Term $term): mixed { return $GLOBALS['termLink'] ?? '/category/' . $term->slug . '/'; }
function is_wp_error(mixed $value): bool { return $value instanceof WP_Error; }

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};
foreach (['category', 'post_tag', 'custom_taxonomy'] as $taxonomy) {
	$GLOBALS['queriedObject'] = new WP_Term(taxonomy: $taxonomy);
	$current = TermGet::Current();
	$check($current instanceof TermData && $current->Taxonomy === $taxonomy, 'タームアーカイブをData化');
}
$calls = 0;
$factory = static function (WP_Term $term) use (&$calls): ProjectTerm {
	$calls++;
	return new ProjectTerm(TermGet::Members($term));
};
$check(TermGet::Current($factory) instanceof ProjectTerm && $calls === 1, '案件固有Factory');
foreach ([null, new WP_Post(), new stdClass()] as $value) {
	$GLOBALS['queriedObject'] = $value;
	$check(TermGet::Current($factory) === null && $calls === 1, 'ターム以外はnull・Factory未実行');
}
$GLOBALS['queriedObject'] = new WP_Term();
$GLOBALS['termLink'] = new WP_Error();
$check(TermGet::Current() === null, 'ターム変換失敗もnull');
unset($GLOBALS['termLink']);
try {
	TermGet::Current(static fn () => null);
	throw new RuntimeException('不正なFactoryを受け入れた');
} catch (UnexpectedValueException) {
	$checks++;
}
echo "Current term: {$checks} checks passed.\n";
