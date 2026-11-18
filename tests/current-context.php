<?php
declare(strict_types=1);

// WordPress APIのスタブ。DB・ログイン状態・クエリを変更しない。
require_once __DIR__ . '/../autoload.php';

use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\WpUserData;
use WpTypeSafety\Wp\Get\PostGet;
use WpTypeSafety\Wp\Get\TermGet;
use WpTypeSafety\Wp\Get\UserGet;

class WP_Post
{
	public function __construct(
		public int $ID,
		public int $post_author = 1,
		public string $post_content = '本文',
		public string $post_status = 'publish',
		public string $post_type = 'page',
		public string $post_name = 'sample',
		public string $post_password = '',
	) {}
}
class WP_User
{
	public function __construct(public int $ID, public string $display_name = '名前') {}
	public function exists(): bool { return $this->ID > 0; }
}
class WP_Term {}
class WP_Query
{
	public function __construct(public ?object $object = null) {}
	public function get_queried_object(): ?object { return $this->object; }
}
class ProjectPost extends PostData {}
class ProjectUser extends WpUserData {}

function get_permalink(WP_Post $post): string { return 'https://example.com/' . $post->ID . '/'; }
function get_post_datetime(WP_Post $post, string $field = 'date'): false { return false; }
function get_the_title(WP_Post $post): string { return '投稿' . $post->ID; }
function is_post_publicly_viewable(WP_Post $post): bool { return true; }
function get_author_posts_url(int $id): string { return 'https://example.com/author/' . $id . '/'; }
function get_avatar_url(int $id, array $args): string {
	$GLOBALS['avatarSizes'][] = $args['size'];
	return 'https://example.com/avatar.png';
}
function wp_get_current_user(): WP_User {
	$GLOBALS['currentUserCalls']++;
	return $GLOBALS['loggedInUser'];
}
function get_post(int $id): never { throw new RuntimeException('投稿の再取得は不要'); }

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok)
	{throw new RuntimeException($label);}
	$checks++;
};
$postCalls = 0;
$postFactory = static function (WP_Post $post) use (&$postCalls): ProjectPost {
	$postCalls++;
	return new ProjectPost(PostGet::Props($post));
};
unset($GLOBALS['wp_the_query'], $GLOBALS['post']);
$check(PostGet::Current($postFactory) === null && PostGet::Loop($postFactory) === null && $postCalls === 0, '取得元なしではFactory未実行');
$main = new WP_Query(new WP_Post(10));
$GLOBALS['wp_the_query'] = $main;
$GLOBALS['wp_query'] = new WP_Query(new WP_Post(999));
$GLOBALS['post'] = new WP_Post(20);
$check(PostGet::Current()?->Id === 10, '表示対象は元のメインクエリ');
$check(PostGet::Loop()?->Id === 20, 'ループはグローバルpost');
$check(PostGet::Current($postFactory) instanceof ProjectPost && $postCalls === 1, 'Currentで派生Data生成を一度実行');
$check(PostGet::Loop($postFactory) instanceof ProjectPost && $postCalls === 2, 'Loopで派生Data生成を一度実行');
$GLOBALS['post'] = new WP_Post(30);
$check(PostGet::Current()?->Id === 10 && PostGet::Loop()?->Id === 30, '独自ループで表示対象は変化しない');
foreach ([null, new WP_Term(), new WP_User(1), new stdClass()] as $object) {
	$main->object = $object;
	$check(PostGet::Current($postFactory) === null && $postCalls === 2, '投稿以外の表示対象はnull');
}
foreach ([null, 123, 'post', [], new stdClass()] as $object) {
	$GLOBALS['post'] = $object;
	$check(PostGet::Loop($postFactory) === null && $postCalls === 2, 'postの型不一致はnull');
}
$main->object = new WP_Post(10);
$GLOBALS['post'] = new WP_Post(20);
foreach (['Current', 'Loop'] as $method) {
	foreach ([null, new stdClass()] as $bad) {
		try {
			PostGet::$method(static fn () => $bad);
			throw new RuntimeException('不正なpostFactoryを受け入れた');
		} catch (UnexpectedValueException) {
			$checks++;
		}
	}
}
unset($GLOBALS['wp_the_query']);
$check(TermGet::Current() === null, 'メインクエリなしのTerm Current');
$GLOBALS['wp_the_query'] = $main;

$GLOBALS['currentUserCalls'] = 0;
$GLOBALS['loggedInUser'] = new WP_User(0);
$userCalls = 0;
$userFactory = static function (WP_User $user, int $avatarSize) use (&$userCalls): ProjectUser {
	$userCalls++;
	return new ProjectUser(UserGet::Props($user, $avatarSize));
};
$check(UserGet::LoggedIn(userFactory: $userFactory) === null && $userCalls === 0, '未ログインではFactory未実行');
$GLOBALS['loggedInUser'] = new WP_User(7);
$user = UserGet::LoggedIn();
$check($user instanceof WpUserData && $user->Id === 7, 'ログインユーザーをData化');
$GLOBALS['avatarSizes'] = [];
$user->GetAvatar();
$check($GLOBALS['avatarSizes'] === [96], '既定のアバターサイズ');
$user = UserGet::LoggedIn(64, $userFactory);
$check($user instanceof ProjectUser && $userCalls === 1, '派生ユーザーを一度生成');
$user->GetAvatar();
$check($GLOBALS['avatarSizes'] === [96, 64], '指定サイズをFactoryへ渡す');
$check($GLOBALS['currentUserCalls'] === 3, '各呼び出しでユーザーを一度取得');
foreach ([null, new stdClass()] as $bad) {
	try {
		UserGet::LoggedIn(userFactory: static fn () => $bad);
		throw new RuntimeException('不正なuserFactoryを受け入れた');
	} catch (UnexpectedValueException) {
		$checks++;
	}
}
$error = new RuntimeException('factory failed');
foreach ([
	static fn () => PostGet::Current(static fn () => throw $error),
	static fn () => PostGet::Loop(static fn () => throw $error),
	static fn () => UserGet::LoggedIn(userFactory: static fn () => throw $error),
] as $call) {
	try {
		$call();
		throw new LogicException('例外を握りつぶした');
	} catch (RuntimeException $caught) {
		$check($caught === $error, 'Factory例外をそのまま伝播');
	}
}
$check($GLOBALS['wp_the_query'] === $main && $GLOBALS['post']->ID === 20 && $GLOBALS['wp_query']->object->ID === 999, 'グローバル状態を変更しない');
$loginCalls = $GLOBALS['currentUserCalls'];
$callsBeforeCurrent = $userCalls;
unset($GLOBALS['wp_the_query']);
$check(UserGet::Current(userFactory: $userFactory) === null, 'メインクエリ未設定ではCurrent Userはnull');
$GLOBALS['wp_the_query'] = $main;
foreach ([null, new WP_Post(10), new WP_Term(), new stdClass(), new WP_User(0)] as $object) {
	$main->object = $object;
	$check(UserGet::Current(userFactory: $userFactory) === null, '著者以外・無効ユーザーではnull');
}
$check($userCalls === $callsBeforeCurrent, '著者以外ではFactory未実行');
$main->object = new WP_User(42);
$GLOBALS['wp_query']->object = new WP_User(999);
$user = UserGet::Current();
$check($user instanceof WpUserData && $user->Id === 42, 'Currentは元のメインクエリの著者を取得');
$GLOBALS['avatarSizes'] = [];
$user->GetAvatar();
$check($GLOBALS['avatarSizes'] === [96], 'Currentの既定アバターサイズ');
$user = UserGet::Current(48, $userFactory);
$check($user instanceof ProjectUser && $user->Id === 42 && $userCalls === $callsBeforeCurrent + 1, 'Currentの派生Factoryを一度実行');
$user->GetAvatar();
$check($GLOBALS['avatarSizes'] === [96, 48], 'Currentも指定アバターサイズを渡す');
UserGet::Current(48, $userFactory);
$check($userCalls === $callsBeforeCurrent + 2, 'CurrentのDataをキャッシュしない');
$check($GLOBALS['currentUserCalls'] === $loginCalls, 'Currentはログインユーザーを参照しない');
foreach ([null, new stdClass()] as $bad) {
	try {
		UserGet::Current(userFactory: static fn () => $bad);
		throw new RuntimeException('不正なCurrent User Factoryを受け入れた');
	} catch (UnexpectedValueException) {
		$checks++;
	}
}
try {
	UserGet::Current(userFactory: static fn () => throw $error);
	throw new LogicException('Current Userの例外を握りつぶした');
} catch (RuntimeException $caught) {
	$check($caught === $error, 'Current UserのFactory例外を伝播');
}
$check($GLOBALS['wp_the_query'] === $main && $main->object->ID === 42 && $GLOBALS['wp_query']->object->ID === 999, 'Current Userはグローバルを変更しない');

echo "Current context: {$checks} checks passed.\n";
