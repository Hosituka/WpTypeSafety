<?php

declare(strict_types=1);

// WordPress・DBに接続せず、Resolver注入とFactoryの正規化を検証する。
if (PHP_SAPI !== 'cli') {
	exit;
}

require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../Wp/Data/PostData.php';
require_once __DIR__ . '/../Wp/Data/PostProps.php';
require_once __DIR__ . '/../Wp/Data/WpUserData.php';
require_once __DIR__ . '/../Wp/Data/UserProps.php';
require_once __DIR__ . '/../Core/Internal/UrlCheck.php';
require_once __DIR__ . '/../Wp/Internal/DtoFactory.php';
require_once __DIR__ . '/../Wp/Get/PostGet.php';

use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\PostProps;
use WpTypeSafety\Wp\Data\UserProps;
use WpTypeSafety\Wp\Data\WpUserData;
use WpTypeSafety\Wp\Get\PostGet;

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

class WP_User
{
	public function __construct(
		public int $ID,
		public string $display_name = '投稿者',
		public string $description = '',
	) {}

	public function exists(): bool { return $this->ID > 0; }
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
function get_post(int $id): mixed
{
	$GLOBALS['postCalls'][] = $id;
	return $GLOBALS['postValues'][$id] ?? null;
}
function is_post_type_hierarchical(string $postType): bool { return $postType === 'page'; }
function get_posts(array $args): array { return $GLOBALS['postChildren'] ?? []; }
function get_adjacent_post(bool $inSameTerm, string $excludedTerms, bool $previous): mixed
{
	return $previous ? ($GLOBALS['previousPost'] ?? null) : ($GLOBALS['nextPost'] ?? null);
}
function get_userdata(int $id): mixed { return $GLOBALS['users'][$id] ?? null; }
function get_author_posts_url(int $id): string { return 'https://example.com/author/' . $id . '/'; }

/**
 * @template TRelatedPost of PostData = PostData
 * @template TAuthor of WpUserData = WpUserData
 * @extends PostData<TRelatedPost, TAuthor>
 */
class ProjectPostData extends PostData
{
	/** @param PostProps $props */
	public function __construct(PostProps $props, public readonly string $Label)
	{
		parent::__construct($props);
	}

	/** @return ProjectPostData<ProjectPostData, WpUserData> */
	public static function FromWpPost(WP_Post $post): self
	{
		return new self(PostGet::Props($post), '派生投稿' . $post->ID);
	}
}

class SameTypePostData extends ProjectPostData
{
	protected function createRelatedPost(WP_Post $post): ?ProjectPostData
	{
		return ProjectPostData::FromWpPost($post);
	}
}
class ProjectUserData extends WpUserData
{
	public function __construct(UserProps $props, public readonly string $Label)
	{
		parent::__construct($props);
	}
}

class AuthorPostData extends PostData
{
	public function GetAuthor(): ?ProjectUserData
	{
		$user = get_userdata($this->AuthorId);
		return $user instanceof WP_User
			? new ProjectUserData(new UserProps($user->ID, $user->display_name), '派生投稿者')
			: null;
	}
}

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};

$GLOBALS['templateCalls'] = [];
$GLOBALS['templateValues'] = [1 => 'templates/custom-service-subpage.php'];
$plain = new PostData(new PostProps(1, '/', 'title', ''));
$check($GLOBALS['templateCalls'] === [], '構築時には取得しない');
$check($plain->GetTemplateSlug() === 'templates/custom-service-subpage.php', '直接構築でも標準取得');
$plain->GetTemplateSlug();
$check($GLOBALS['templateCalls'] === [1, 1], 'キャッシュせず取得');
$check($plain->Status === '' && ! $plain->IsPublished(), '省略時の値');
$mutableMembers = new PostProps(2, '/original/', '変更前', '本文');
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
$factoryMembers = PostGet::Props(new WP_Post(20));
$check($factoryMembers instanceof PostProps && $factoryMembers->Id === 20, '公開FactoryからPostPropsを生成する');
$GLOBALS['postCalls'] = [];
$GLOBALS['postValues'] = [21 => new WP_Post(21)];
$check(PostGet::FromId(21)?->Id === 21, '投稿IDからPostDataを生成する');
$check($GLOBALS['postCalls'] === [21], '投稿IDをget_postへ渡す');
$check(PostGet::FromId(22) === null, '存在しない投稿IDはnull');
$check($GLOBALS['postCalls'] === [21, 22], '存在しない投稿も1回だけ取得する');
$check(PostGet::FromId(0) === null && PostGet::FromId(-1) === null, '0以下の投稿IDはnull');
$check($GLOBALS['postCalls'] === [21, 22], '0以下の投稿IDは問い合わせない');
foreach ($GLOBALS['templateValues'] as $id => $value) {
	$before = count($GLOBALS['templateCalls']);
	$data = PostGet::From(new WP_Post($id));
	$check(count($GLOBALS['templateCalls']) === $before, 'Factory構築時は未取得');
	$check($data->GetTemplateSlug() === (is_string($value) ? $value : ''), 'Factoryの取得・正規化');
	$check(end($GLOBALS['templateCalls']) === $id, '投稿IDを渡す');
}
$GLOBALS['templateValues'][1] = 'changed-template.php';
$check(PostGet::From(new WP_Post(1))->GetTemplateSlug() === 'changed-template.php', '新しい値を取得');
$check(PostGet::From(new WP_Post(999))->GetTemplateSlug() === '', '存在しない投稿を空文字にする');

$published = PostGet::From(new WP_Post(
	ID: 10,
	post_status: 'publish',
	post_type: 'page',
	post_name: 'published-page',
));
$check($published->Status === 'publish' && $published->IsPublished(), '公開状態を保持・判定する');
$check($published->PostTypeName === 'page' && $published->Slug === 'published-page', '投稿タイプとスラッグを保持する');
$check(! $published->IsPasswordProtected && $published->IsPubliclyViewable, '公開閲覧可能な投稿を判定する');

$private = PostGet::From(new WP_Post(
	ID: 11,
	post_status: 'private',
	post_type: 'page',
	post_name: 'private-page',
	post_password: 'secret',
));
$check(! $private->IsPublished() && $private->IsPasswordProtected, '非公開状態とパスワード保護を分けて保持する');
$check(! $private->IsPubliclyViewable, '公開閲覧できない投稿を判定する');

$rootPost = new WP_Post(30, post_type: 'page');
$GLOBALS['postValues'][30] = $rootPost;
$GLOBALS['postChildren'] = [new WP_Post(31, post_type: 'page')];
$GLOBALS['nextPost'] = new WP_Post(32, post_type: 'page');
$derivedPost = new SameTypePostData(PostGet::Props($rootPost), '生成フック');
$check($derivedPost->GetChildren()[0]->Label === '派生投稿31', '生成フックで子投稿の派生型を維持する');
$check($derivedPost->GetNextPost()?->Label === '派生投稿32', '生成フックで隣接投稿の派生型を維持する');
$baseRelations = new ProjectPostData(PostGet::Props($rootPost), '基底関連');
$check(
	$baseRelations->GetChildren()[0] instanceof PostData
	&& ! $baseRelations->GetChildren()[0] instanceof ProjectPostData,
	'生成フック未変更時は純粋なPostDataを返す',
);
$GLOBALS['users'][7] = new WP_User(7);
$postWithAuthor = new WP_Post(40, post_author: 7);
$customAuthorPost = new AuthorPostData(PostGet::Props($postWithAuthor));
$check($customAuthorPost->GetAuthor() instanceof ProjectUserData, 'GetAuthorのオーバーライドで投稿者の派生型を返す');
$baseAuthorPost = PostGet::From($postWithAuthor);
$check(
	$baseAuthorPost->GetAuthor() instanceof WpUserData
	&& ! $baseAuthorPost->GetAuthor() instanceof ProjectUserData,
	'GetAuthor未変更時は純粋なWpUserDataを返す',
);

echo "Post template slug: {$checks} checks passed.\n";
