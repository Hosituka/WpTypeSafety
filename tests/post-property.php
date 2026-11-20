<?php
declare(strict_types=1);

// WordPress APIスタブ。DB更新なしで保持値・Factory・上書きを検証する。
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
		public int $ID = 1,
		public int $post_author = 7,
		public int $post_parent = 0,
		public string $post_title = 'native title',
		public string $post_excerpt = '<b>native excerpt</b>',
		public string $post_content = 'native content',
		public string $post_status = 'publish',
		public string $post_type = 'page',
		public string $post_name = 'native',
		public string $post_password = '',
	) {}
}
class WP_User { public function __construct(public int $ID) {} }
class ProjectUser extends WpUserData { use TestUserProps; }
/** @extends PostData<ProjectPost, ProjectUser> */
class ProjectPost extends PostData
{
	use TestPostProps;
	protected function createRelatedPost(WP_Post $post): ?self
	{
		return self::From($post);
	}
	public function GetAuthor(): ?ProjectUser
	{
		$user = get_userdata($this->AuthorId);
		return $user instanceof WP_User ? self::UserFromWpUser($user) : null;
	}
	public static function UserFromWpUser(WP_User $user): ProjectUser
	{
		return ProjectUser::FromProps(new UserProps($user->ID, 'author'));
	}
}
class CustomExcerptPost extends PostData
{
	use TestPostProps;
	public function GetExcerpt(): string { return 'custom:' . $this->RawContent; }
}
function get_permalink(WP_Post $post): string { return '/post/' . $post->ID; }
function get_the_title(WP_Post $post): string { return $post->post_title; }
function get_post_datetime(WP_Post $post, string $field = 'date'): false { return false; }
function is_post_publicly_viewable(WP_Post $post): bool { return true; }
function wp_strip_all_tags(string $text): string { return trim(strip_tags($text)); }
function get_post(int $id): mixed { $GLOBALS['requests'][] = $id; return $GLOBALS['posts'][$id] ?? null; }
function get_userdata(int $id): mixed { return $GLOBALS['users'][$id] ?? null; }
function is_post_type_hierarchical(string $type): bool { return $type === 'page'; }
function get_posts(array $args): array { $GLOBALS['childrenArgs'] = $args; return $GLOBALS['children']; }
function get_adjacent_post(bool $same, string $excluded, bool $previous): mixed
{
	return $GLOBALS[$previous ? 'previousNative' : 'nextNative'] ?? null;
}

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok)
	{throw new RuntimeException($label);}
	$checks++;
};
$source = new WP_Post();
$props = \WpTypeSafety\Wp\Internal\DtoFactory::PostPropsFromWpPost($source);
$check($props->AuthorId === 7 && $props->RawExcerpt === '<b>native excerpt</b>', '値の取得');
$check(! method_exists(PostGet::class, 'Members'), '旧Members入口を廃止');
$check(! class_exists('WpTypeSafety\\Wp\\Data\\PostMembers'), '旧PostMembersを廃止');
foreach (get_object_vars($props) as $value) {
	$check(! $value instanceof Closure, 'PropertyはResolver/Factoryを持たない');
}
$props->Id = 9;
$props->AuthorId = 8;
$props->ParentId = 3;
$props->Title = 'adjusted';
$props->Url = '/adjusted/';
$props->RawExcerpt = '<i>adjusted excerpt</i>';
$props->RawContent = 'adjusted content';
$data = ProjectPost::FromProps($props);
$props->Id = 99;
$props->Title = 'later';
$props->RawExcerpt = '';
$check($data->Id === 9 && $data->Title === 'adjusted', '構築後の変更を遮断');
$check($data->GetLink()->Title === 'adjusted' && $data->GetLink()->Url === '/adjusted/', 'リンクは保持値');
$check($data->GetExcerpt() === 'adjusted excerpt' && $data->HasExcerpt(), '抜粋は変更済み保持値');
$GLOBALS['posts'] = [3 => new WP_Post(3), 9 => new WP_Post(9, post_excerpt: 'DB changed')];
$GLOBALS['users'] = [8 => new WP_User(8)];
$GLOBALS['requests'] = [];
$GLOBALS['children'] = [new WP_Post(10), 'invalid', new WP_Post(11)];
$check($data->GetAuthor() instanceof ProjectUser && $data->GetAuthor()->Id === 8, '変更した著者IDとFactory');
$check($data->GetParent() instanceof ProjectPost && $data->GetParent()->Id === 3, '変更した親IDとFactory');
$childData = $data->GetChildren();
$check(count($childData) === 2 && array_is_list($childData) && $childData[0] instanceof ProjectPost, '子取得の型と連番');
$check($GLOBALS['childrenArgs']['post_parent'] === 9, '変更した自身のIDで関連取得');
$check($data->GetExcerpt() === 'adjusted excerpt', '後のDB抜粋変更を参照しない');
$empty = TestPostData::FromProps(new PostProps(0, '', '', 'content'));
$check($empty->GetExcerpt() === '' && ! $empty->HasExcerpt(), '自動抜粋を追加しない');
$check($empty->GetChildren() === [] && $empty->GetParent() === null && $empty->GetAuthor() === null, '無効なIDでは取得しない');
$custom = CustomExcerptPost::FromProps(new PostProps(1, '', '', 'body'));
$check($custom->GetExcerpt() === 'custom:body', 'Resolverではなくメソッドで変更');
class FilteredPost extends PostData
{
	use TestPostProps;
	protected function createRelatedPost(WP_Post $post): ?PostData
	{
		return $post->ID === 11 ? null : parent::createRelatedPost($post);
	}
	public function GetAuthor(): ?WpUserData { return null; }
}
$filtered = FilteredPost::FromProps(new PostProps(9, '', '', '', ParentId: 3, AuthorId: 8));
$check($filtered->GetAuthor() === null, 'GetAuthorを直接オーバーライド');
$filteredChildren = $filtered->GetChildren();
$check(count($filteredChildren) === 1 && array_is_list($filteredChildren) && $filteredChildren[0]->Id === 10, '生成フックで除外して再採番');
$check($filteredChildren[0]::class === PostData::class, 'parentフックなら純粋なPostData');
$check(count((new ReflectionMethod(PostData::class, '__construct'))->getParameters()) === 1, '構築引数は値のみ');
$check(! method_exists(PostGet::class, 'From') && ! method_exists(PostGet::class, 'Props'), 'PostGetの変換・Props入口を廃止');
$check(count((new ReflectionMethod(PostData::class, 'From'))->getParameters()) === 1, 'Fromにも関連Factory引数なし');
$check(count((new ReflectionMethod(PostGet::class, 'ById'))->getParameters()) === 2, 'ByIdは取得入口のFactoryを受け取りDataに保持しない');
$check((new ReflectionMethod(PostData::class, 'createRelatedPost'))->isProtected(), '生成フックはサブクラス向け');
$check(! property_exists($data, 'relatedPostFactory') && ! property_exists($data, 'authorFactory'), 'Factoryを保持しない');
$GLOBALS['previousNative'] = new WP_Post(12);
$GLOBALS['nextNative'] = new WP_Post(13);
$check($data->GetPreviousPost() instanceof ProjectPost && $data->GetPreviousPost()->Id === 12, '前投稿も生成フック');
$check($data->GetNextPost() instanceof ProjectPost && $data->GetNextPost()->Id === 13, '次投稿も生成フック');
class ExcludedPost extends PostData
{
	use TestPostProps;
	protected function createRelatedPost(WP_Post $post): ?PostData { return null; }
}
class InvalidHookPost extends PostData
{
	use TestPostProps;
	protected function createRelatedPost(WP_Post $post): ?PostData { return new stdClass(); }
}
class ThrowingHookPost extends PostData
{
	use TestPostProps;
	protected function createRelatedPost(WP_Post $post): ?PostData { throw $GLOBALS['hookError']; }
}
$hookProperty = new PostProps(9, '', '', '', ParentId: 3);
$excluded = ExcludedPost::FromProps($hookProperty);
$check($excluded->GetChildren() === [] && $excluded->GetParent() === null && $excluded->GetPreviousPost() === null && $excluded->GetNextPost() === null, '全関連取得でフックのnullを許可');
$bad = InvalidHookPost::FromProps($hookProperty);
$throwing = ThrowingHookPost::FromProps($hookProperty);
$GLOBALS['hookError'] = new RuntimeException('hook error');
foreach (['GetParent', 'GetChildren', 'GetPreviousPost', 'GetNextPost'] as $method) {
	try {
		$bad->$method();
		throw new RuntimeException('不正なフック結果を受け入れた');
	} catch (TypeError) {
		$checks++;
	}
	foreach ([false, true] as $hasGlobal) {
		unset($GLOBALS['post']);
		if ($hasGlobal) {
			$GLOBALS['post'] = null;
		}
		try {
			$throwing->$method();
			throw new LogicException('生成フック例外を握りつぶした');
		} catch (RuntimeException $error) {
			$check($error === $GLOBALS['hookError'], '生成フック例外はそのまま伝播');
		}
		$check(array_key_exists('post', $GLOBALS) === $hasGlobal && ($GLOBALS['post'] ?? null) === null, '例外時もグローバルの値と存在状態を復元');
	}
}
echo "Post props: {$checks} checks passed.\n";
