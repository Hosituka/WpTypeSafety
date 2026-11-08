<?php

declare(strict_types=1);

// WordPress・DBに接続せず、Resolver注入とFactoryの正規化を検証する。
if (PHP_SAPI !== 'cli') {
	exit;
}

require_once __DIR__ . '/../Wp/Data/PostData.php';
require_once __DIR__ . '/../Wp/Data/PostMembers.php';
require_once __DIR__ . '/../Wp/Internal/DtoFactory.php';
require_once __DIR__ . '/../Wp/Get/PostFactory.php';

use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\PostMembers;
use WpTypeSafety\Wp\Get\PostFactory;

class WP_Post
{
	public function __construct(
		public int $ID,
		public int $post_author = 1,
		public string $post_content = '',
		public string $post_status = 'publish',
		public string $post_type = 'post',
		public string $post_name = 'sample',
		public string $post_password = '',
	) {}
}

function get_permalink(WP_Post $post): string { return '/post/' . $post->ID; }
function get_post_datetime(WP_Post $post, string $field = 'date'): false { return false; }
function get_the_title(WP_Post $post): string { return 'title'; }
function is_post_publicly_viewable(WP_Post $post): bool
{
	return $post->post_status === 'publish' && $post->post_type !== 'internal';
}
function get_page_template_slug(int $id): mixed
{
	$GLOBALS['templateCalls'][] = $id;
	return $GLOBALS['templateValues'][$id] ?? false;
}

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};

$plain = new PostData(new PostMembers(1, '/', 'title', ''));
$check($plain->GetTemplateSlug() === '', 'Resolver省略時は空文字');
$check($plain->Status === '' && $plain->PostType === '' && $plain->Slug === '', '投稿情報省略時は未知を表す空文字');
$check(! $plain->IsPublished() && ! $plain->IsPasswordProtected && ! $plain->IsPubliclyViewable, '投稿状態省略時はfalse');

$calls = 0;
$direct = new PostData(
	new PostMembers(
		PostId: 1,
		Url: '/',
		Title: 'title',
		Content: '',
		TemplateSlugResolver: static function () use (&$calls): string {
			$calls++;
			return 'templates/custom-service-subpage.php';
		},
	),
);
$check($calls === 0, '構築時には取得しない');
$check($direct->GetTemplateSlug() === 'templates/custom-service-subpage.php', '直接注入とパスの維持');
$direct->GetTemplateSlug();
$check($calls === 2, '呼び出すたびにResolverを実行');

$mutableMembers = new PostMembers(2, '/original/', '変更前', '本文');
$snapshot = new PostData($mutableMembers);
$mutableMembers->Title = '変更後';
$check($snapshot->Title === '変更前', 'DataはMembersを構築時の値として保持する');

$GLOBALS['templateCalls'] = [];
$GLOBALS['templateValues'] = [
	1 => 'custom-service-subpage.php',
	2 => '',
	3 => false,
	4 => 'templates/example-page.php',
	5 => 123,
	6 => [],
];
$factoryMembers = PostFactory::Members(new WP_Post(20));
$check($factoryMembers instanceof PostMembers && $factoryMembers->PostId === 20, '公開FactoryからPostMembersを生成する');
foreach ($GLOBALS['templateValues'] as $id => $value) {
	$before = count($GLOBALS['templateCalls']);
	$data = PostFactory::From(new WP_Post($id));
	$check(count($GLOBALS['templateCalls']) === $before, 'Factory構築時は未取得');
	$check($data->GetTemplateSlug() === (is_string($value) ? $value : ''), 'Factoryの取得・正規化');
	$check(end($GLOBALS['templateCalls']) === $id, '投稿IDを渡す');
}
$GLOBALS['templateValues'][1] = 'changed-template.php';
$check(PostFactory::From(new WP_Post(1))->GetTemplateSlug() === 'changed-template.php', '新しい値を取得');
$check(PostFactory::From(new WP_Post(999))->GetTemplateSlug() === '', '存在しない投稿を空文字にする');

$published = PostFactory::From(new WP_Post(
	ID: 10,
	post_status: 'publish',
	post_type: 'page',
	post_name: 'published-page',
));
$check($published->Status === 'publish' && $published->IsPublished(), '公開状態を保持・判定する');
$check($published->PostType === 'page' && $published->Slug === 'published-page', '投稿タイプとスラッグを保持する');
$check(! $published->IsPasswordProtected && $published->IsPubliclyViewable, '公開閲覧可能な投稿を判定する');

$private = PostFactory::From(new WP_Post(
	ID: 11,
	post_status: 'private',
	post_type: 'page',
	post_name: 'private-page',
	post_password: 'secret',
));
$check(! $private->IsPublished() && $private->IsPasswordProtected, '非公開状態とパスワード保護を分けて保持する');
$check(! $private->IsPubliclyViewable, '公開閲覧できない投稿を判定する');

echo "Post template slug: {$checks} checks passed.\n";
