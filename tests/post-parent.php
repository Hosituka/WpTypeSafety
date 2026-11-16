<?php
declare(strict_types=1);

// WordPress APIスタブ。DBに接続せず、親取得の遅延実行と派生型維持を検証する。
require_once __DIR__ . '/../autoload.php';

use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\PostMembers;
use WpTypeSafety\Wp\Data\UserMembers;
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
readonly class ProjectPost extends PostData {}
readonly class ProjectUser extends WpUserData {}

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
$plain = new PostData(new PostMembers(1, '/', 'title', ''));
$check($plain->ParentId === 0 && ! $plain->HasParent() && $plain->GetParent() === null, '直接構築の既定値');
$calls = 0;
$members = new PostMembers(2, '/', 'title', '', ParentId: 1, ParentResolver: static function () use (&$calls, $plain): PostData {
	$calls++;
	return $plain;
});
$data = new PostData($members);
$members->ParentId = 99;
$check($data->ParentId === 1 && $data->HasParent() && $calls === 0, '親IDはコピーされ、HasParentは取得しない');
$check($data->GetParent() === $plain && $data->GetParent() === $plain && $calls === 2, 'Resolverは呼び出しごとに実行');
$noResolver = new PostData(new PostMembers(2, '/', '', '', ParentId: 1));
$check($noResolver->HasParent() && $noResolver->GetParent() === null, '親IDありでもResolver省略時はnull');
$noParent = new PostData(new PostMembers(2, '/', '', '', ParentResolver: static function () use (&$calls): never {
	$calls++;
	throw new RuntimeException('親なしで呼ばれた');
}));
$check($noParent->GetParent() === null && $calls === 2, '親なしではResolverを呼ばない');
$check((new ReflectionProperty(PostData::class, 'ParentId'))->isReadOnly(), '親IDはreadonly');
$GLOBALS['posts'] = [1 => new WP_Post(1), 2 => new WP_Post(2, 1)];
$GLOBALS['postRequests'] = [];
$child = PostGet::From($GLOBALS['posts'][2]);
$check($child->ParentId === 1 && $child->HasParent() && $GLOBALS['postRequests'] === [], '生成時に親を取得しない');
$check($child->GetParent()?->Id === 1 && $GLOBALS['postRequests'] === [1], '親IDから直接の親を取得');
$check($child->GetParent() instanceof PostData && $GLOBALS['postRequests'] === [1, 1], '暗黙キャッシュなし');
$factoryCalls = 0;
$relatedFactory = static function (WP_Post $post) use (&$relatedFactory, &$factoryCalls): ProjectPost {
	$factoryCalls++;
	return new ProjectPost(PostGet::Members($post, $relatedFactory));
};
$GLOBALS['posts'][1]->post_parent = 3;
$GLOBALS['posts'][3] = new WP_Post(3);
$derived = PostGet::From($GLOBALS['posts'][2], relatedPostFactory: $relatedFactory);
$parent = $derived->GetParent();
$check($parent instanceof ProjectPost && $parent->Id === 1 && $factoryCalls === 1, '既存relatedPostFactoryを親にも適用');
$check($parent->GetParent() instanceof ProjectPost && $factoryCalls === 2, '生成側でも指定すれば祖先も派生型を維持');
$GLOBALS['posts'][1] = new WP_Post(1);
$userFactory = static fn (WP_User $user): ProjectUser => new ProjectUser(new UserMembers(7, '作者'));
$withAuthor = PostGet::From($GLOBALS['posts'][2], authorFactory: $userFactory);
$check($withAuthor->GetParent()?->GetAuthor() instanceof ProjectUser, '標準の親生成でもauthorFactoryを維持');
foreach ([null, false, new stdClass()] as $missing) {
	$GLOBALS['posts'][1] = $missing;
	$check($derived->HasParent() && $derived->GetParent() === null, '親の削除・型不一致はnull');
}
$GLOBALS['posts'][1] = new WP_Post(1);
$nullFactory = PostGet::From($GLOBALS['posts'][2], relatedPostFactory: static fn () => null);
$check($nullFactory->GetParent() === null, '既存の関連Factory契約どおりnullを許可');
$badFactory = PostGet::From($GLOBALS['posts'][2], relatedPostFactory: static fn () => new stdClass());
try {
	$badFactory->GetParent();
	throw new RuntimeException('不正な関連Factoryを受け入れた');
} catch (UnexpectedValueException) {
	$checks++;
}
$error = new RuntimeException('resolver failed');
$throws = new PostData(new PostMembers(2, '/', '', '', ParentId: 1, ParentResolver: static fn () => throw $error));
try {
	$throws->GetParent();
	throw new LogicException('例外を握りつぶした');
} catch (RuntimeException $caught) {
	$check($caught === $error, 'Resolver例外は伝播');
}
$attachment = PostGet::From(new WP_Post(4, 1, 'attachment'));
$check($attachment->GetParent()?->Id === 1, '添付ファイルのpost_parentも取得可能');
echo "Post parent: {$checks} checks passed.\n";
