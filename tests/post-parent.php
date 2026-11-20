<?php
declare(strict_types=1);

// WordPress APIスタブ。DBに接続せず、親取得の遅延実行と派生型維持を検証する。
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/fixtures/post-props.php';
require_once __DIR__ . '/fixtures/data-props.php';

use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\PostProps;
use WpTypeSafety\Wp\Data\UserProps;
use WpTypeSafety\Wp\Data\WpUserData;
use WpTypeSafety\Wp\Get\PostGet;

class WP_Post
{
	public function __construct(
		public int $ID,
		public int $post_parent = 0,
		public string $post_type = 'page',
		public int $post_author = 1,
		public string $post_content = '',
		public string $post_status = 'publish',
		public string $post_name = 'sample',
		public string $post_password = '',
	) {}
}
class WP_User {}
class ProjectUser extends WpUserData { use TestUserProps; }
/** @extends PostData<ProjectPost, ProjectUser> */
class ProjectPost extends PostData
{
	protected function createRelatedPost(WP_Post $post): ?self
	{
		$GLOBALS['factoryCalls']++;
		return self::From($post);
	}
	public function GetAuthor(): ?ProjectUser { return ProjectUser::FromProps(new UserProps(7, '作者')); }
}
class FilteredPost extends PostData
{
	protected function createRelatedPost(WP_Post $post): ?PostData { return null; }
}
class InvalidPost extends PostData
{
	protected function createRelatedPost(WP_Post $post): ?PostData { return new stdClass(); }
}
class ThrowingPost extends PostData
{
	use TestPostProps;
	protected function createRelatedPost(WP_Post $post): ?PostData { throw $GLOBALS['hookError']; }
}

function get_permalink(WP_Post $post): string { return 'https://example.com/' . $post->ID; }
function get_post_datetime(WP_Post $post, string $field = 'date'): false { return false; }
function get_the_title(WP_Post $post): string { return '投稿' . $post->ID; }
function is_post_publicly_viewable(WP_Post $post): bool { return true; }
function get_post(int $id): mixed {
	$GLOBALS['postRequests'][] = $id;
	return $GLOBALS['posts'][$id] ?? null;
}
function get_userdata(int $id): WP_User { return new WP_User(); }

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok)
	{throw new RuntimeException($label);}
	$checks++;
};
$plain = TestPostData::FromProps(new PostProps(1, '/', 'title', ''));
$check($plain->ParentId === 0 && ! $plain->HasParent() && $plain->GetParent() === null, '直接構築の既定値');
$props = new PostProps(2, '/', 'title', '', ParentId: 1);
$data = TestPostData::FromProps($props);
$props->ParentId = 99;
$check($data->ParentId === 1 && $data->HasParent(), '親IDは構築時にコピー');
$noParent = TestPostData::FromProps(new PostProps(2, '/', '', ''));
$check($noParent->GetParent() === null, '親なしでは問い合わせない');
$check((new ReflectionProperty(PostData::class, 'ParentId'))->isReadOnly(), '親IDはreadonly');
$GLOBALS['posts'] = [1 => new WP_Post(1), 2 => new WP_Post(2, 1)];
$GLOBALS['postRequests'] = [];
$child = PostData::From($GLOBALS['posts'][2]);
$check($child->ParentId === 1 && $child->HasParent() && $GLOBALS['postRequests'] === [], '生成時に親を取得しない');
$check($child->GetParent()?->Id === 1 && $GLOBALS['postRequests'] === [1], '親IDから直接の親を取得');
$check($child->GetParent() instanceof PostData && $GLOBALS['postRequests'] === [1, 1], '暗黙キャッシュなし');
$factoryCalls = 0;
$GLOBALS['posts'][1]->post_parent = 3;
$GLOBALS['posts'][3] = new WP_Post(3);
$derived = ProjectPost::From($GLOBALS['posts'][2]);
$parent = $derived->GetParent();
$check($parent instanceof ProjectPost && $parent->Id === 1 && $factoryCalls === 1, '共通生成フックを親にも適用');
$check($parent->GetParent() instanceof ProjectPost && $factoryCalls === 2, '祖先でも生成フックを維持');
$GLOBALS['posts'][1] = new WP_Post(1);
$check($derived->GetParent()?->GetAuthor() instanceof ProjectUser, '関連投稿でも著者のオーバーライドを使用');
foreach ([null, false, new stdClass()] as $missing) {
	$GLOBALS['posts'][1] = $missing;
	$check($derived->HasParent() && $derived->GetParent() === null, '親の削除・型不一致はnull');
}
$GLOBALS['posts'][1] = new WP_Post(1);
$nullFactory = FilteredPost::From($GLOBALS['posts'][2]);
$check($nullFactory->GetParent() === null, '生成フックのnullを許可');
$badFactory = InvalidPost::From($GLOBALS['posts'][2]);
try {
	$badFactory->GetParent();
	throw new RuntimeException('不正な関連Factoryを受け入れた');
} catch (TypeError) {
	$checks++;
}
$error = new RuntimeException('hook failed');
$GLOBALS['hookError'] = $error;
$throws = ThrowingPost::FromProps(new PostProps(2, '/', '', '', ParentId: 1));
try {
	$throws->GetParent();
	throw new LogicException('例外を握りつぶした');
} catch (RuntimeException $caught) {
	$check($caught === $error, '生成フック例外は伝播');
}
$attachment = PostData::From(new WP_Post(4, 1, 'attachment'));
$check($attachment->GetParent()?->Id === 1, '添付ファイルのpost_parentも取得可能');
echo "Post parent: {$checks} checks passed.\n";
