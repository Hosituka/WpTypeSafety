<?php
declare(strict_types=1);

// WordPress APIスタブによる契約テスト。実際のブロック・プラグインの描画は別途確認する。
if (PHP_SAPI !== 'cli') {
	exit;
}
require_once __DIR__ . '/../autoload.php';

use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\PostMembers;
use WpTypeSafety\Wp\Data\TermData;
use WpTypeSafety\Wp\Get\PostGet;
use WpTypeSafety\Wp\Get\TermGet;

class WP_Post
{
	public function __construct(
		public int $ID,
		public string $post_content = '',
		public int $post_author = 1,
		public string $post_status = 'publish',
		public string $post_type = 'post',
		public string $post_name = 'sample',
		public string $post_password = '',
	) {}
}
class WP_Term
{
	public function __construct(
		public int $term_id,
		public string $name = '分類',
		public string $slug = 'sample',
		public string $taxonomy = 'category',
		public int $parent = 0,
		public string $description = '',
	) {}
}
class WP_Error {}
readonly class ProjectTerm extends TermData {}

function get_permalink(WP_Post $post): string { return '/posts/' . $post->ID; }
function get_post_datetime(WP_Post $post, string $field = 'date'): false { return false; }
function get_the_title(WP_Post $post): string { return '投稿'; }
function is_post_publicly_viewable(WP_Post $post): bool { return true; }
function setup_postdata(WP_Post $post): bool
{
	$GLOBALS['setupCalls']++;
	foreach (['id', 'authordata', 'currentday', 'currentmonth', 'page', 'pages', 'multipage', 'more', 'numpages', 'preview'] as $key) {
		$GLOBALS[$key] = 'temporary';
	}
	$GLOBALS['id'] = $post->ID;
	return $GLOBALS['setupResult'] ?? true;
}
function get_the_content(mixed $more, bool $strip, WP_Post $post): string
{
	if ($GLOBALS['post'] !== $post || $GLOBALS['id'] !== $post->ID) {
		throw new RuntimeException('Wrong post context');
	}
	return $post->post_content;
}
function apply_filters(string $name, mixed $value): mixed
{
	if ($name !== 'the_content') {
		throw new RuntimeException('Wrong filter');
	}
	$GLOBALS['filterCalls']++;
	return ($GLOBALS['contentFilter'])($value);
}
function get_the_terms(int $id, string $taxonomy): mixed
{
	$GLOBALS['termCalls'][] = [$id, $taxonomy];
	return $GLOBALS['termsResult'];
}
function get_term_link(WP_Term $term): string { return '/terms/' . $term->term_id; }
function is_wp_error(mixed $value): bool { return $value instanceof WP_Error; }

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};
$plain = new PostData(new PostMembers(1, '', '', '未加工'));
$check($plain->RawContent === '未加工' && $plain->HasContent(), '直接構築とRawContent');
$check($plain->GetRenderedContent() === '' && $plain->GetTerms('category') === [], 'Resolver未指定');
$check(! property_exists($plain, 'Content'), '旧Contentは廃止');
$check((new ReflectionProperty(PostData::class, 'RawContent'))->isReadOnly(), 'RawContentはreadonly');
$empty = new PostData(new PostMembers(1, '', '', " \n\t"));
$check(! $empty->HasContent(), '空白のみの本文');
$seen = [];
$injected = new PostData(new PostMembers(1, '', '', 'raw', RenderedContentResolver: static function (string $raw) use (&$seen): string {
	$seen[] = $raw;
	return '<p>' . $raw . '</p>';
}));
$check($injected->GetRenderedContent() === '<p>raw</p>' && $seen === ['raw'], '生本文をResolverへ渡す');

$GLOBALS['setupCalls'] = 0;
$GLOBALS['filterCalls'] = 0;
$GLOBALS['termCalls'] = [];
$GLOBALS['contentFilter'] = static fn (string $raw): string => '<p>' . $GLOBALS['post']->ID . ':' . $raw . ']]></p>';
$sourcePost = new WP_Post(7, 'original');
$members = PostGet::Members($sourcePost);
$members->RawContent = 'adjusted';
$data = new PostData($members);
$check($GLOBALS['setupCalls'] === 0 && $GLOBALS['termCalls'] === [], '構築時は遅延処理しない');
$keys = ['post', 'id', 'authordata', 'currentday', 'currentmonth', 'page', 'pages', 'multipage', 'more', 'numpages', 'preview'];
foreach ($keys as $key) {
	unset($GLOBALS[$key]);
}
$before = ['post' => new WP_Post(99), 'id' => null, 'page' => 3, 'more' => 1, 'pages' => ['before']];
foreach ($before as $key => $value) {
	$GLOBALS[$key] = $value;
}
$checkRestored = static function () use ($keys, $before, $check): void {
	foreach ($keys as $key) {
		$check(array_key_exists($key, $GLOBALS) === array_key_exists($key, $before), 'グローバルの存在状態を復元: ' . $key);
		if (array_key_exists($key, $before)) {
			$check($GLOBALS[$key] === $before[$key], 'グローバルの値を復元: ' . $key);
		}
	}
};
ob_start();
$rendered = $data->GetRenderedContent();
$printed = ob_get_clean();
$check($printed === '' && $rendered === '<p>7:adjusted]]&gt;</p>', '対象投稿のコンテキスト・フィルター・文字列返却');
$check($sourcePost->post_content === 'original' && $data->RawContent === 'adjusted', '元投稿とRawContentを変更しない');
$checkRestored();
$data->GetRenderedContent();
$check($GLOBALS['filterCalls'] === 2, '描画結果はキャッシュしない');
$GLOBALS['contentFilter'] = static function (): never { throw new LogicException('filter failure'); };
try {
	$data->GetRenderedContent();
	throw new RuntimeException('例外が伝播しない');
} catch (LogicException $error) {
	$check($error->getMessage() === 'filter failure', 'フィルター例外の伝播');
}
$checkRestored();
$GLOBALS['contentFilter'] = static fn (): array => [];
try {
	$data->GetRenderedContent();
	throw new RuntimeException('不正なフィルター結果');
} catch (UnexpectedValueException) {
	$checks++;
}
$checkRestored();
$GLOBALS['setupResult'] = false;
try {
	$data->GetRenderedContent();
	throw new RuntimeException('セットアップ失敗');
} catch (LogicException) {
	$checks++;
}
$checkRestored();

$GLOBALS['termsResult'] = [5 => new WP_Term(2), 8 => 'invalid', 9 => new WP_Term(3)];
$terms = $data->GetTerms('category');
$check(array_is_list($terms) && count($terms) === 2 && $terms[0]->Id === 2, '型フィルタリングと連番化');
$check($GLOBALS['termCalls'] === [[7, 'category']], '対象IDとタクソノミー');
$factory = static fn (WP_Term $term): ProjectTerm => new ProjectTerm(TermGet::Members($term));
$derived = $data->GetTerms('category', $factory);
$check($derived[0] instanceof ProjectTerm && $derived[1] instanceof ProjectTerm, '案件固有Term Factory');
$beforeCalls = count($GLOBALS['termCalls']);
$check($data->GetTerms('') === [] && count($GLOBALS['termCalls']) === $beforeCalls, '空のタクソノミーでは取得しない');
foreach ([false, null, new WP_Error(), []] as $bad) {
	$GLOBALS['termsResult'] = $bad;
	$check($data->GetTerms('category') === [], '欠損・エラー・空リスト');
}
foreach ([null, 'invalid', [3 => $terms[0]], [new stdClass()]] as $bad) {
	$badData = new PostData(new PostMembers(1, '', '', '', TermsResolver: static fn () => $bad));
	try {
		$badData->GetTerms('category');
		throw new RuntimeException('不正なResolver結果');
	} catch (UnexpectedValueException) {
		$checks++;
	}
}
echo "Post content and terms: {$checks} checks passed.\n";
