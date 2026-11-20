<?php
declare(strict_types=1);

// 単体取得の契約をWordPress APIスタブで検証。DBには接続しない。
if (PHP_SAPI !== 'cli')
{exit;}
require_once __DIR__ . '/../autoload.php';

use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\TermData;
use WpTypeSafety\Wp\Data\WpUserData;
use WpTypeSafety\Wp\Data\CommentData;
use WpTypeSafety\Wp\Get\PostGet;
use WpTypeSafety\Wp\Get\TermGet;
use WpTypeSafety\Wp\Get\UserGet;
use WpTypeSafety\Wp\Get\CommentGet;

define('OBJECT', 'OBJECT');
class WP_Error {}
class WP_Post
{
	public function __construct(
		public int $ID = 1,
		public string $post_type = 'page',
		public string $post_status = 'draft',
		public string $post_content = '本文',
		public string $post_name = 'about',
		public string $post_password = '',
		public int $post_author = 2,
	) {}
}
class WP_Term
{
	public function __construct(
		public int $term_id = 3,
		public string $taxonomy = 'category',
		public string $name = '分類',
		public string $slug = 'sample',
		public string $description = '',
		public int $parent = 0,
	) {}
}
class WP_User
{
	public function __construct(
		public int $ID = 2,
		public string $display_name = '利用者',
		public string $description = '',
		public string $user_url = '',
	) {}
	public function exists(): bool { return $this->ID > 0; }
}
class WP_Comment
{
	public function __construct(
		public int $comment_ID = 4,
		public int $comment_post_ID = 1,
		public int $comment_parent = 0,
		public int $user_id = 2,
		public string $comment_author = '投稿者',
		public string $comment_author_url = '',
		public string $comment_content = 'コメント',
		public string $comment_type = 'comment',
		public string $comment_approved = '0',
		public string $comment_date = '',
	) {}
}
class ProjectPost extends PostData
{
	protected function initialize(WP_Post $post): void { $GLOBALS['factoryCalls'][] = ['post', $post]; }
}
class ProjectTerm extends TermData
{
	protected function initialize(WP_Term $term): void { $GLOBALS['factoryCalls'][] = ['term', $term]; }
}
class ThrowingTerm extends TermData
{
	protected function initialize(WP_Term $term): void { throw $GLOBALS['error']; }
}
class ProjectUser extends WpUserData
{
	protected function initialize(WP_User $user, int $size = 96): void { $GLOBALS['factoryCalls'][] = ['user', $user, $size]; }
}
class ThrowingUser extends WpUserData
{
	protected function initialize(WP_User $user, int $size = 96): void { throw $GLOBALS['error']; }
}
class ProjectComment extends CommentData
{
	protected function initialize(WP_Comment $comment, int $size = 96): void { $GLOBALS['factoryCalls'][] = ['comment', $comment, $size]; }
}
class ThrowingComment extends CommentData
{
	protected function initialize(WP_Comment $comment, int $size = 96): void { throw $GLOBALS['error']; }
}

function get_post(int $id): mixed
{
	$GLOBALS['calls'][] = ['post', $id];
	return $GLOBALS['nativePost'];
}
function get_page_by_path(string $path, string $output, array $types): mixed
{
	$GLOBALS['calls'][] = ['path', $path, $output, $types];
	return $GLOBALS['nativePost'];
}
function get_term(int $id, string $taxonomy = ''): mixed
{
	$GLOBALS['calls'][] = ['term', $id, $taxonomy];
	return $GLOBALS['nativeTerm'];
}
function get_userdata(int $id): mixed
{
	$GLOBALS['calls'][] = ['user', $id];
	return $GLOBALS['nativeUser'];
}
function get_user_by(string $field, string $value): mixed
{
	$GLOBALS['calls'][] = ['user-field', $field, $value];
	return $GLOBALS['nativeUser'];
}
function get_comment(int $id): mixed
{
	$GLOBALS['calls'][] = ['comment', $id];
	return $GLOBALS['nativeComment'];
}
function get_permalink(WP_Post $post): string { return 'https://example.com/about/'; }
function get_post_datetime(WP_Post $post, string $field = 'date'): false { return false; }
function get_the_title(WP_Post $post): string { return '紹介'; }
function is_post_publicly_viewable(WP_Post $post): bool { return false; }
function get_term_link(WP_Term $term): mixed { return $GLOBALS['termUrl']; }
function is_wp_error(mixed $value): bool { return $value instanceof WP_Error; }
function get_author_posts_url(int $id): string { return 'https://example.com/author/user/'; }
function get_comment_link(WP_Comment $comment): string { return 'https://example.com/about/#comment-4'; }

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (!$ok)
	{throw new RuntimeException($label);}
	$checks++;
};
$GLOBALS['calls'] = [];
$GLOBALS['nativePost'] = new WP_Post();
$GLOBALS['nativeTerm'] = new WP_Term();
$GLOBALS['nativeUser'] = new WP_User();
$GLOBALS['nativeComment'] = new WP_Comment();
$GLOBALS['termUrl'] = 'https://example.com/category/sample/';

$check(PostGet::ById(1)?->Id === 1, '投稿IDから標準Data');
$check(PostGet::ById(1)->Status === 'draft', '取得時に公開状態・権限で除外しない');
$check(PostGet::ByPath('/company/about/')?->Id === 1, '親子slug列から取得');
$check(end($GLOBALS['calls']) === ['path', 'company/about', OBJECT, ['page']], '投稿タイプを配列で渡し添付ファイルを混ぜない');
$GLOBALS['nativePost']->post_type = 'staff';
$check(PostGet::ByPath('member', 'staff')?->PostTypeName === 'staff', '指定した投稿タイプを取得');
$check(PostGet::ByPath('member', 'page') === null, 'ネイティブ結果の投稿タイプも確認');
$GLOBALS['nativePost']->post_type = 'page';
$check(TermGet::ById(3)?->Id === 3, 'タームIDから標準Data');
$check(end($GLOBALS['calls']) === ['term', 3, ''], 'taxonomy省略時はIDだけで検索');
$check(TermGet::ById(3, 'category')?->TaxonomyName === 'category', '指定taxonomyを検証');
$check(TermGet::ById(3, 'area') === null, '異なるtaxonomyはnull');
$check(UserGet::ById(2)?->Id === 2, 'ユーザーIDから標準Data');
foreach ([['BySlug', 'public-name', 'slug'], ['ByEmail', 'user@example.com', 'email'], ['ByLogin', 'account-name', 'login']] as [$method, $value, $field]) {
	$check(UserGet::$method($value)?->Id === 2, $method . 'で標準Data');
	$check(end($GLOBALS['calls']) === ['user-field', $field, $value], 'slug・email・loginを区別');
}
$check(CommentGet::ById(4)?->Id === 4, 'コメントIDから標準Data');
$check(CommentGet::ById(4)->Status === 'unapproved', '未承認コメントも権限判定とは別に取得');

$factoryCalls = [];
$postClass = ProjectPost::class;
$termFactory = ProjectTerm::class;
$userFactory = ProjectUser::class;
$commentFactory = ProjectComment::class;
$check(PostGet::ById(1, $postClass) instanceof ProjectPost, '投稿IDのdataClass');
$check(PostGet::ByPath('company/about', dataClass: $postClass) instanceof ProjectPost, '投稿PathのdataClass');
$check(TermGet::ById(3, dataClass: $termFactory) instanceof ProjectTerm, 'タームIDのFactory');
foreach ([['ById', 2], ['BySlug', 'public-name'], ['ByEmail', 'user@example.com'], ['ByLogin', 'account-name']] as [$method, $value]) {
	$check(UserGet::$method($value, 48, $userFactory) instanceof ProjectUser, $method . 'のFactory');
	$check(end($factoryCalls) === ['user', $GLOBALS['nativeUser'], 48], 'userFactoryにアバターサイズを渡す');
}
$check(CommentGet::ById(4, 64, $commentFactory) instanceof ProjectComment, 'コメントIDのFactory');
$check(end($factoryCalls) === ['comment', $GLOBALS['nativeComment'], 64], 'commentFactoryにアバターサイズを渡す');
$before = count($factoryCalls);
$check(PostGet::ById(1) instanceof PostData && count($factoryCalls) === $before, 'Factoryを登録・キャッシュしない');

$nativeCalls = count($GLOBALS['calls']);
foreach ([0, -1] as $id) {
	$check(PostGet::ById($id, $postClass) === null, '不正な投稿ID');
	$check(TermGet::ById($id, dataClass: $termFactory) === null, '不正なタームID');
	$check(UserGet::ById($id, dataClass: $userFactory) === null, '不正なユーザーID');
	$check(CommentGet::ById($id, dataClass: $commentFactory) === null, '不正なコメントID');
}
foreach (['', '/', 'https://example.com/about', '//example.com/about', 'about?preview=1', 'about#section', 'company\\about'] as $path) {
	$check(PostGet::ByPath($path, dataClass: $postClass) === null, '不正なPath');
}
$check(PostGet::ByPath('about', '') === null, '投稿タイプの空文字');
$check(TermGet::ById(3, '') === null, 'taxonomyの明示的な空文字');
foreach (['BySlug', 'ByEmail', 'ByLogin'] as $method) {
	$check(UserGet::$method('', dataClass: $userFactory) === null, '識別子の空文字');
}
$check(count($GLOBALS['calls']) === $nativeCalls && count($factoryCalls) === $before, '無効引数はWP・Factoryを呼ばない');

foreach ([null, false, new WP_Error(), new stdClass()] as $invalid) {
	$GLOBALS['nativePost'] = $GLOBALS['nativeTerm'] = $GLOBALS['nativeUser'] = $GLOBALS['nativeComment'] = $invalid;
	$check(PostGet::ById(1, $postClass) === null && PostGet::ByPath('about', dataClass: $postClass) === null, '投稿の未発見・エラー');
	$check(TermGet::ById(3, dataClass: $termFactory) === null, 'タームの未発見・エラー');
	$check(UserGet::ById(2, dataClass: $userFactory) === null && UserGet::BySlug('name', dataClass: $userFactory) === null, 'ユーザーの未発見・エラー');
	$check(CommentGet::ById(4, dataClass: $commentFactory) === null, 'コメントの未発見・エラー');
}
$check(count($factoryCalls) === $before, '未発見時はFactoryを呼ばない');
$GLOBALS['nativePost'] = new WP_Post();
$GLOBALS['nativeTerm'] = new WP_Term();
$GLOBALS['nativeUser'] = new WP_User(0);
$GLOBALS['nativeComment'] = new WP_Comment();
$check(UserGet::ById(2, dataClass: $userFactory) === null && count($factoryCalls) === $before, '存在しないWP_UserはFactoryへ渡さない');
$GLOBALS['nativeUser'] = new WP_User();
$GLOBALS['termUrl'] = new WP_Error();
$check(TermGet::ById(3) === null, '標準TermDataの変換失敗もnull');
$GLOBALS['termUrl'] = 'https://example.com/category/sample/';

$lookups = [
	static fn (string $factory) => TermGet::ById(3, dataClass: $factory),
	static fn (string $factory) => UserGet::ById(2, dataClass: $factory),
	static fn (string $factory) => UserGet::BySlug('name', dataClass: $factory),
	static fn (string $factory) => UserGet::ByEmail('user@example.com', dataClass: $factory),
	static fn (string $factory) => UserGet::ByLogin('account', dataClass: $factory),
	static fn (string $factory) => CommentGet::ById(4, dataClass: $factory),
];
foreach ($lookups as $lookup) {
	foreach ([null, new stdClass()] as $invalid) {
		try {
			$lookup(stdClass::class);
			$check(false, '不正なFactory戻り値');
		} catch (InvalidArgumentException) {
			$check(true, 'Factoryのnull・型違反を握りつぶさない');
		}
	}
	$error = new LogicException('factory');
	try {
		$lookup($lookup === $lookups[0] ? ThrowingTerm::class : ($lookup === $lookups[5] ? ThrowingComment::class : ThrowingUser::class));
		$check(false, 'Factoryの例外');
	} catch (LogicException $caught) {
		$check($caught === $error, 'Factoryの例外をそのまま伝播');
	}
}
echo "Single get: {$checks} checks passed.\n";
