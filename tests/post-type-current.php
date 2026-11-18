<?php
declare(strict_types=1);

require_once __DIR__ . '/../autoload.php';

use WpTypeSafety\Wp\Get\PostTypeGet;

class WP_Post
{
	public function __construct(public string $post_type) {}
}
class WP_Post_Type
{
	public string $label = '投稿タイプ';
	public string $description = '';
	public bool $public = true;
	public bool $publicly_queryable = true;
	public bool $hierarchical = false;
	public bool $has_archive = true;
	public function __construct(public string $name) {}
}
class WP_Term {}
class WP_User {}
class WP_Query
{
	public function __construct(
		public ?object $object = null,
		public bool $singular = false,
		public bool $archive = false,
	) {}
	public function is_singular(): bool { return $this->singular; }
	public function is_post_type_archive(): bool { return $this->archive; }
	public function get_queried_object(): ?object { return $this->object; }
}
function get_post_type_object(string $name): mixed {
	$GLOBALS['definitionCalls'][] = $name;
	return $GLOBALS['definitions'][$name] ?? null;
}
function get_post_type_archive_link(string $name): string { return 'https://example.com/' . $name . '/'; }
function get_object_taxonomies(string $name, string $output): array { return []; }

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok)
	{throw new RuntimeException($label);}
	$checks++;
};
$GLOBALS['definitions'] = [];
$GLOBALS['definitionCalls'] = [];
unset($GLOBALS['wp_the_query']);
$check(PostTypeGet::Current() === null, 'メインクエリ未設定はnull');
$main = new WP_Query();
$GLOBALS['wp_the_query'] = $main;
$GLOBALS['wp_query'] = new WP_Query(new WP_Post('other'), true);
$GLOBALS['post'] = new WP_Post('loop');
foreach (['post', 'page', 'cases'] as $name) {
	$GLOBALS['definitions'][$name] = new WP_Post_Type($name);
	$main->object = new WP_Post($name);
	$main->singular = true;
	$check(PostTypeGet::Current()?->Name === $name, '詳細ページの型を取得');
}
$main->singular = false;
$main->archive = true;
$main->object = $GLOBALS['definitions']['cases'];
$check(PostTypeGet::Current()?->Name === 'cases', '投稿タイプアーカイブの登録定義を取得');
$first = PostTypeGet::Current();
$check(PostTypeGet::Current() !== $first, 'Dataをキャッシュしない');
unset($GLOBALS['definitions']['cases']);
$check(PostTypeGet::Current() === null, '登録解除済みならnull');
$GLOBALS['definitions']['cases'] = new stdClass();
$check(PostTypeGet::Current() === null, '不正な登録定義はnull');
$main->archive = false;
$before = count($GLOBALS['definitionCalls']);
foreach ([null, new WP_Term(), new WP_User(), new WP_Post('page'), new WP_Post_Type('cases')] as $object) {
	$main->object = $object;
	$check(PostTypeGet::Current() === null, '検索・ターム・著者・投稿一覧ページ等から推測しない');
}
$check(count($GLOBALS['definitionCalls']) === $before, '対象外では登録定義を取得しない');
$main->singular = true;
$main->object = new WP_Term();
$check(PostTypeGet::Current() === null, '詳細の取得対象が投稿でなければnull');
$main->singular = false;
$main->archive = true;
$main->object = new WP_Post('post');
$check(PostTypeGet::Current() === null, 'アーカイブの取得対象が登録定義でなければnull');
$check($GLOBALS['wp_the_query'] === $main && $GLOBALS['wp_query']->object->post_type === 'other' && $GLOBALS['post']->post_type === 'loop', 'グローバルを変更しない');
echo "Current post type: {$checks} checks passed.\n";
