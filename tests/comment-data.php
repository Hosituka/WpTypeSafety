<?php
declare(strict_types=1);

// WordPress APIスタブによるテスト。DBは変更しない。
if (PHP_SAPI !== 'cli') {
	exit;
}

class WP_Comment
{
	public function __construct(
		public int|string $comment_ID,
		public int|string $comment_post_ID = 0,
		public string $comment_author = '',
		public mixed $comment_author_url = '',
		public string $comment_content = '',
		public string $comment_type = 'comment',
		public string $comment_approved = '1',
		public string $comment_date = '2026-11-08 12:34:56',
		public int|string $comment_parent = 0,
		public int|string $user_id = 0,
	) {}
}

class WP_Post
{
	public function __construct(
		public int $ID,
		public int $post_author = 0,
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
		public string $display_name = '登録ユーザー',
		public string $description = '',
	) {}

	public function exists(): bool { return $this->ID > 0; }
}

function get_comment_link(WP_Comment $comment): mixed
{
	return $GLOBALS['commentLinks'][(int) $comment->comment_ID] ?? 'https://example.com/post/#comment-' . $comment->comment_ID;
}

function wp_timezone(): DateTimeZone { return new DateTimeZone('Asia/Tokyo'); }
function get_userdata(int $id): mixed { return $GLOBALS['users'][$id] ?? null; }
function get_author_posts_url(int $id): mixed { return 'https://example.com/author/' . $id . '/'; }
function get_avatar_url(mixed $subject, array $args = []): mixed
{
	$GLOBALS['avatarCalls'][] = [$subject, $args];
	return $GLOBALS['avatarUrl'] ?? 'https://example.com/avatar.png';
}
function get_post(int $id): mixed { return $GLOBALS['posts'][$id] ?? null; }
function get_permalink(WP_Post $post): string { return 'https://example.com/post/' . $post->ID . '/'; }
function get_post_datetime(WP_Post $post, string $field = 'date'): false { return false; }
function get_the_title(WP_Post $post): string { return '投稿' . $post->ID; }
function is_post_publicly_viewable(WP_Post $post): bool { return $post->post_status === 'publish'; }
function get_comment(int $id): mixed
{
	$GLOBALS['commentCalls'][] = $id;
	return $GLOBALS['comments'][$id] ?? null;
}
function get_comments(array $args): mixed
{
	$GLOBALS['commentsArgs'][] = $args;
	return $GLOBALS['children'] ?? [];
}

require_once __DIR__ . '/../Core/Data/ImgData.php';
require_once __DIR__ . '/../Core/Data/LinkData.php';
require_once __DIR__ . '/../Core/Internal/UrlCheck.php';
require_once __DIR__ . '/../Wp/Data/WpUserData.php';
require_once __DIR__ . '/../Wp/Data/UserMembers.php';
require_once __DIR__ . '/../Wp/Data/PostData.php';
require_once __DIR__ . '/../Wp/Data/PostMembers.php';
require_once __DIR__ . '/../Wp/Data/CommentData.php';
require_once __DIR__ . '/../Wp/Data/CommentMembers.php';
require_once __DIR__ . '/../Wp/Internal/DtoFactory.php';
require_once __DIR__ . '/../Wp/Get/CommentFactory.php';

use WpTypeSafety\Wp\Data\CommentData;
use WpTypeSafety\Wp\Data\CommentMembers;
use WpTypeSafety\Wp\Data\UserMembers;
use WpTypeSafety\Wp\Get\CommentFactory;

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};

$plain = new CommentData(
	new CommentMembers(
		Id: 1,
		PostId: 2,
		ParentId: 0,
		AuthorUserId: 0,
		AuthorName: '匿名ユーザー',
		AuthorUrl: '',
		Url: '/#comment-1',
		Content: 'コメント',
		Type: 'comment',
		Status: 'approved',
	),
);
$check(! $plain->HasUser() && $plain->GetUser() === null, '匿名コメントはユーザーを持たない');
$check($plain->HasContent() && $plain->IsApproved(), '保持済みの値を判定する');
$check(! $plain->HasParent() && $plain->GetParent() === null, '親なしはResolverを呼ばない');
$check($plain->GetAvatar() === null && $plain->GetPost() === null && $plain->GetChildren() === [], 'Resolver省略時の空値');

$userCalls = 0;
$direct = new CommentData(
	new CommentMembers(
		Id: 2,
		PostId: 3,
		ParentId: 1,
		AuthorUserId: 7,
		AuthorName: '登録ユーザー',
		AuthorUrl: 'https://example.com/',
		Url: '/#comment-2',
		Content: '',
		Type: 'comment',
		Status: 'unapproved',
		UserResolver: static function () use (&$userCalls): ?\WpTypeSafety\Wp\Data\WpUserData {
			$userCalls++;
			return new \WpTypeSafety\Wp\Data\WpUserData(new UserMembers(7, '登録ユーザー'));
		},
		ParentResolver: static fn (): CommentData => $plain,
		ChildrenResolver: static fn (string $status, string $order): array => [$plain],
	),
);
$check($direct->HasUser() && $userCalls === 0, '登録ユーザーの取得は遅延する');
$check($direct->GetUser()?->Id === 7 && $userCalls === 1, '登録ユーザーを取得する');
$check($direct->GetParent() === $plain && $direct->GetChildren() === [$plain], '親子Resolverを利用する');
$check(! $direct->HasContent() && ! $direct->IsApproved(), '空本文と未承認を判定する');

$GLOBALS['users'] = [7 => new WP_User(7)];
$GLOBALS['posts'] = [12 => new WP_Post(12)];
$GLOBALS['comments'] = [4 => new WP_Comment(4, 12, '親投稿者')];
$GLOBALS['children'] = [
	new WP_Comment(9, 12, '返信者', comment_parent: 8),
	'invalid',
];
$GLOBALS['commentCalls'] = [];
$GLOBALS['commentsArgs'] = [];
$GLOBALS['avatarCalls'] = [];

$comment = new WP_Comment(
	comment_ID: '8',
	comment_post_ID: '12',
	comment_author: '登録ユーザー',
	comment_author_url: 'https://example.com/profile/',
	comment_content: "本文\n2行目",
	comment_type: '',
	comment_approved: '1',
	comment_date: '2026-11-08 12:34:56',
	comment_parent: '4',
	user_id: '7',
);
$commentMembers = CommentFactory::Members($comment, 80);
$check($commentMembers instanceof CommentMembers && $commentMembers->Id === 8, '公開FactoryからCommentMembersを生成する');
$data = CommentFactory::From($comment, 80);
$check($data->Id === 8 && $data->PostId === 12 && $data->ParentId === 4, 'IDを整数へ正規化する');
$check($data->AuthorUserId === 7 && $data->AuthorName === '登録ユーザー', '投稿者情報を保持する');
$check($data->AuthorUrl === 'https://example.com/profile/', 'HTTPの投稿者URLを保持する');
$check($data->Type === 'comment' && $data->Status === 'approved', '旧コメント種別と承認状態を正規化する');
$check($data->CreatedAt?->format('Y-m-d H:i:s P') === '2026-11-08 12:34:56 +09:00', 'サイトのタイムゾーンで日時を生成する');
$check($data->GetUser()?->Id === 7, '登録ユーザーを遅延取得する');
$check($data->GetPost()?->PostId === 12, '関連投稿を遅延取得する');
$check($GLOBALS['commentCalls'] === [], '親コメントは構築時に取得しない');
$check($data->GetParent()?->Id === 4 && $GLOBALS['commentCalls'] === [4], '親コメントを遅延取得する');

$avatar = $data->GetAvatar();
$check($avatar?->Width === 80 && $avatar->Height === 80, '既定のアバター寸法を使用する');
$avatar = $data->GetAvatar(40);
$check($avatar?->Width === 40 && ($GLOBALS['avatarCalls'][1][1]['size'] ?? null) === 40, '呼び出し時のアバター寸法を使用する');

$children = $data->GetChildren('hold', 'DESC');
$check(count($children) === 1 && $children[0]->Id === 9, 'WP_Commentだけを子へ変換する');
$check($GLOBALS['commentsArgs'][0] === [
	'parent' => 8,
	'status' => 'hold',
	'orderby' => 'comment_date_gmt comment_ID',
	'order' => 'DESC',
	'hierarchical' => false,
], '直下の子コメントの取得条件を渡す');

foreach ([
	['0', 'unapproved', false],
	['spam', 'spam', false],
	['trash', 'trash', false],
] as [$rawStatus, $expectedStatus, $approved]) {
	$value = CommentFactory::From(new WP_Comment(20, comment_approved: $rawStatus));
	$check($value->Status === $expectedStatus && $value->IsApproved() === $approved, 'コメント状態を正規化する');
}

$GLOBALS['commentLinks'][30] = false;
$invalid = CommentFactory::From(new WP_Comment(
	comment_ID: 30,
	comment_author_url: 'javascript:alert(1)',
	comment_date: 'invalid',
));
$check($invalid->Url === '' && $invalid->AuthorUrl === '', '不正なURLを空文字へ正規化する');
$check($invalid->CreatedAt === null, '不正な日時をnullへ正規化する');
$check($invalid->GetUser() === null && $invalid->GetParent() === null, '匿名・親なしは問い合わせない');

$invalidChildren = new CommentData(
	new CommentMembers(
		Id: 1,
		PostId: 1,
		ParentId: 0,
		AuthorUserId: 0,
		AuthorName: '',
		AuthorUrl: '',
		Url: '',
		Content: '',
		Type: 'comment',
		Status: 'approved',
		ChildrenResolver: static fn (string $status, string $order): array => ['invalid'],
	),
);
try {
	$invalidChildren->GetChildren();
	throw new RuntimeException('不正な子コメントを受け入れた');
} catch (UnexpectedValueException) {
	$check(true, '子コメントの要素型を検証する');
}

echo "Comment data: {$checks} checks passed.\n";
