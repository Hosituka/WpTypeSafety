<?php
declare(strict_types=1);

// WordPress APIスタブによる契約テスト。実際のブロック・プラグインの描画は別途確認する。
if (PHP_SAPI !== 'cli') {
	exit;
}
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/fixtures/post-props.php';

use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\PostProps;
use WpTypeSafety\Wp\Data\TermData;
use WpTypeSafety\Wp\Get\PostGet;
use WpTypeSafety\Wp\Get\TermGet;

#[AllowDynamicProperties]
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
class ProjectTerm extends TermData {}

function get_post(int $id): mixed { return $GLOBALS['nativePosts'][$id] ?? null; }
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
$plain = TestPostData::FromProps(new PostProps(1, '', '', '未加工'));
$check($plain->RawContent === '未加工' && $plain->HasContent(), '直接構築とRawContent');
$check($plain->GetRenderedContent() === '', '投稿が存在しなければ描画しない');
$check(! property_exists($plain, 'Content'), '旧Contentは廃止');
$check((new ReflectionProperty(PostData::class, 'RawContent'))->isReadOnly(), 'RawContentはreadonly');
$empty = TestPostData::FromProps(new PostProps(1, '', '', " \n\t"));
$check(! $empty->HasContent(), '空白のみの本文');
class OverriddenContentPost extends PostData
{
	use TestPostProps;
	public function GetRenderedContent(): string { return '<p>' . $this->RawContent . '</p>'; }
}
$check(OverriddenContentPost::FromProps(new PostProps(1, '', '', 'raw'))->GetRenderedContent() === '<p>raw</p>', 'メソッドの上書き');
$GLOBALS['setupCalls'] = 0;
$GLOBALS['filterCalls'] = 0;
$GLOBALS['termCalls'] = [];
$GLOBALS['contentFilter'] = static fn (string $raw): string => '<p>' . $GLOBALS['post']->ID . ':' . $raw . ']]></p>';
$sourcePost = new WP_Post(7, 'original');
$GLOBALS['nativePosts'][7] = $sourcePost;
$props = \WpTypeSafety\Wp\Internal\DtoFactory::PostPropsFromWpPost($sourcePost);
$props->RawContent = 'adjusted';
$props->Title = 'adjusted title';
$props->RawExcerpt = 'adjusted excerpt';
$props->AuthorId = 8;
$props->ParentId = 4;
$props->PublishedAt = new DateTimeImmutable('2026-11-18T12:00:00+09:00');
$data = TestPostData::FromProps($props);
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
$GLOBALS['contentFilter'] = static function (string $raw) use ($check): string {
	$renderPost = $GLOBALS['post'];
	$check($renderPost->post_title === 'adjusted title' && $renderPost->post_excerpt === 'adjusted excerpt', '描画用投稿も変更済みタイトル・抜粋');
	$check($renderPost->post_author === 8 && $renderPost->post_parent === 4, '描画用投稿も変更済み関連ID');
	$check($renderPost->post_date === '2026-11-18 12:00:00' && $renderPost->post_date_gmt === '2026-11-18 03:00:00', '描画用日時の保持値とUTC');
	return $raw;
};
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
$factory = ProjectTerm::class;
$derived = $data->GetTerms('category', $factory);
$check($derived[0] instanceof ProjectTerm && $derived[1] instanceof ProjectTerm, '案件固有Term Factory');
$beforeCalls = count($GLOBALS['termCalls']);
$check($data->GetTerms('') === [] && count($GLOBALS['termCalls']) === $beforeCalls, '空のタクソノミーでは取得しない');
foreach ([false, null, new WP_Error(), []] as $bad) {
	$GLOBALS['termsResult'] = $bad;
	$check($data->GetTerms('category') === [], '欠損・エラー・空リスト');
}
$GLOBALS['termsResult'] = [new WP_Term(2)];
try {
	$data->GetTerms('category', stdClass::class);
	throw new RuntimeException('不正なTerm Factoryを受け入れた');
} catch (InvalidArgumentException) {
	$checks++;
}
echo "Post content and terms: {$checks} checks passed.\n";
