<?php
declare(strict_types=1);

// WordPress APIスタブによるテスト。DBは変更しない。
if (PHP_SAPI !== 'cli') {
	exit;
}

class WP_Comment
{
	public string $comment_author_email = '';
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

require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../Core/Data/ImgData.php';
require_once __DIR__ . '/../Core/Data/LinkData.php';
require_once __DIR__ . '/../Core/Internal/UrlCheck.php';
require_once __DIR__ . '/../Wp/Data/WpUserData.php';
require_once __DIR__ . '/../Wp/Data/UserProps.php';
require_once __DIR__ . '/../Wp/Data/PostData.php';
require_once __DIR__ . '/../Wp/Data/PostProps.php';
require_once __DIR__ . '/../Wp/Data/CommentData.php';
require_once __DIR__ . '/../Wp/Data/CommentProps.php';
require_once __DIR__ . '/../Wp/Internal/DtoFactory.php';
require_once __DIR__ . '/../Wp/Get/CommentGet.php';

use WpTypeSafety\Wp\Data\CommentData;
use WpTypeSafety\Wp\Data\CommentProps;
use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\PostProps;
use WpTypeSafety\Wp\Data\UserProps;
use WpTypeSafety\Wp\Data\WpUserData;
use WpTypeSafety\Wp\Get\CommentGet;

/**
 * @template TRelatedComment of CommentData = CommentData
 * @template TUser of WpUserData = WpUserData
 * @template TPost of PostData = PostData
 * @extends CommentData<TRelatedComment, TUser, TPost>
 */
class ProjectCommentData extends CommentData
{
	/** @param CommentProps<TRelatedComment, TUser, TPost> $props */
	public function __construct(CommentProps $props, public readonly string $Label)
	{
		parent::__construct($props);
	}

	/** @return ProjectCommentData<ProjectCommentData, WpUserData, PostData> */
	public static function FromWpComment(WP_Comment $comment): self
	{
		return new self(
			CommentGet::Props($comment),
			'派生コメント' . $comment->comment_ID,
		);
	}
}

class ProjectCommentUserData extends WpUserData
{
	public function __construct(UserProps $props, public readonly string $Label)
	{
		parent::__construct($props);
	}
}

class ProjectCommentPostData extends PostData
{
	public function __construct(PostProps $props, public readonly string $Label)
	{
		parent::__construct($props);
	}
}

class RelatedProjectComment extends ProjectCommentData
{
	protected function createRelatedComment(WP_Comment $comment): ?ProjectCommentData
	{
		return ProjectCommentData::FromWpComment($comment);
	}
}
class DirectComment extends CommentData
{
	public function GetUser(): ?WpUserData
	{
		$GLOBALS['userCalls']++;
		return new WpUserData(new UserProps(7, '登録ユーザー'));
	}
	public function GetParent(): ?CommentData { return $GLOBALS['plain']; }
	public function GetChildren(string $status = 'approve', string $order = 'ASC'): array { return [$GLOBALS['plain']]; }
}
class CrossComment extends ProjectCommentData
{
	protected function createRelatedComment(WP_Comment $comment): ?self
	{
		return new self(CommentGet::Props($comment), '派生関連');
	}
	public function GetUser(): ?ProjectCommentUserData
	{
		$user = get_userdata($this->AuthorUserId);
		return $user instanceof WP_User ? new ProjectCommentUserData(new UserProps($user->ID, $user->display_name), '派生ユーザー') : null;
	}
	public function GetPost(): ?ProjectCommentPostData
	{
		$post = get_post($this->PostId);
		return $post instanceof WP_Post ? new ProjectCommentPostData(new PostProps($post->ID, '/', '投稿', ''), '派生投稿') : null;
	}
}
class InvalidHookComment extends CommentData
{
	protected function createRelatedComment(WP_Comment $comment): ?CommentData { return new stdClass(); }
}

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};

$plain = new CommentData(
	new CommentProps(
		Id: 1,
		PostId: 2,
		ParentId: 0,
		AuthorUserId: 0,
		AuthorName: '匿名ユーザー',
		WebsiteUrl: '',
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
$direct = new DirectComment(
	new CommentProps(
		Id: 2,
		PostId: 3,
		ParentId: 1,
		AuthorUserId: 7,
		AuthorName: '登録ユーザー',
		WebsiteUrl: 'https://example.com/',
		Url: '/#comment-2',
		Content: '',
		Type: 'comment',
		Status: 'unapproved',
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
$GLOBALS['comments'][8] = $comment;
$commentMembers = CommentGet::Props($comment, 80);
$check($commentMembers instanceof CommentProps && $commentMembers->Id === 8, '公開FactoryからCommentPropsを生成する');
$data = CommentGet::From($comment, 80);
$check($data->Id === 8 && $data->PostId === 12 && $data->ParentId === 4, 'IDを整数へ正規化する');
$check($data->AuthorUserId === 7 && $data->AuthorName === '登録ユーザー', '投稿者情報を保持する');
$check($data->WebsiteUrl === 'https://example.com/profile/', 'HTTPの投稿者URLを保持する');
$check($data->Type === 'comment' && $data->Status === 'approved', '旧コメント種別と承認状態を正規化する');
$check($data->CreatedAt?->format('Y-m-d H:i:s P') === '2026-11-08 12:34:56 +09:00', 'サイトのタイムゾーンで日時を生成する');
$check($data->GetUser()?->Id === 7, '登録ユーザーを遅延取得する');
$check($data->GetPost()?->Id === 12, '関連投稿を遅延取得する');
$check($GLOBALS['commentCalls'] === [], '親コメントは構築時に取得しない');
$check($data->GetParent()?->Id === 4 && $GLOBALS['commentCalls'] === [4], '親コメントを遅延取得する');

$avatar = $data->GetAvatar();
$check($avatar?->Width === 80 && $avatar->Height === 80, '既定のアバター寸法を使用する');
$avatar = $data->GetAvatar(40);
$check($avatar?->Width === 40 && ($GLOBALS['avatarCalls'][1][1]['size'] ?? null) === 40, '呼び出し時のアバター寸法を使用する');

$childResults = $data->GetChildren('hold', 'DESC');
$check(count($childResults) === 1 && $childResults[0]->Id === 9, 'WP_Commentだけを子へ変換する');
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
	$value = CommentGet::From(new WP_Comment(20, comment_approved: $rawStatus));
	$check($value->Status === $expectedStatus && $value->IsApproved() === $approved, 'コメント状態を正規化する');
}

$GLOBALS['commentLinks'][30] = false;
$invalid = CommentGet::From(new WP_Comment(
	comment_ID: 30,
	comment_author_url: 'javascript:alert(1)',
	comment_date: 'invalid',
));
$check($invalid->Url === '' && $invalid->WebsiteUrl === '', '不正なURLを空文字へ正規化する');
$check($invalid->CreatedAt === null, '不正な日時をnullへ正規化する');
$check($invalid->GetUser() === null && $invalid->GetParent() === null, '匿名・親なしは問い合わせない');

$invalidChildren = new InvalidHookComment(
	new CommentProps(
		Id: 1,
		PostId: 1,
		ParentId: 0,
		AuthorUserId: 0,
		AuthorName: '',
		WebsiteUrl: '',
		Url: '',
		Content: '',
		Type: 'comment',
		Status: 'approved',
	),
);
try {
	$invalidChildren->GetChildren();
	throw new RuntimeException('不正な子コメントを受け入れた');
} catch (TypeError) {
	$check(true, '子コメントの要素型を検証する');
}

$GLOBALS['children'] = [new WP_Comment(9, 12, '返信者', comment_parent: 8)];
$derivedComment = new RelatedProjectComment(CommentGet::Props($comment), '共通フック');
$check($derivedComment->GetParent()?->Label === '派生コメント4', '生成フックで親コメントの派生型を維持する');
$check($derivedComment->GetChildren()[0]->Label === '派生コメント9', '生成フックで子コメントの派生型を維持する');
$baseRelations = new ProjectCommentData(CommentGet::Props($comment), '基底関連');
$check(
	$baseRelations->GetParent() instanceof CommentData
	&& ! $baseRelations->GetParent() instanceof ProjectCommentData,
	'生成フック省略時は純粋なCommentDataを返す',
);

$GLOBALS['comments'][4] = new WP_Comment(4, 12, '親投稿者', user_id: 7);
$crossData = new CrossComment(CommentGet::Props($comment), '異種Data');
$check($crossData->GetUser()?->Label === '派生ユーザー', 'GetUser上書きでコメント投稿者の派生型を返す');
$check($crossData->GetPost()?->Label === '派生投稿', 'GetPost上書きで関連投稿の派生型を返す');
$crossParent = $crossData->GetParent();
$check(
	$crossParent?->GetUser() instanceof ProjectCommentUserData
	&& $crossParent->GetPost() instanceof ProjectCommentPostData,
	'関連Getterの上書きを基底型の親コメントへ引き継ぐ',
);
$check(
	$data->GetUser() instanceof WpUserData
	&& ! $data->GetUser() instanceof ProjectCommentUserData
	&& $data->GetPost() instanceof PostData
	&& ! $data->GetPost() instanceof ProjectCommentPostData,
	'関連Getterの上書き省略時は純粋な基底Dataを返す',
);

$guest = new WP_Comment(41, 12, 'guest');
$guest->comment_author_email = 'guest@example.invalid';
$GLOBALS['comments'][41] = $guest;
$guestProps = CommentGet::Props($guest, 0);
$guestProps->AuthorUserId = 7;
$guestProps->AuthorName = 'adjusted guest';
$guestProps->WebsiteUrl = 'https://example.com/changed/';
$guestData = new CommentData($guestProps);
$check($guestData->GetAvatar()?->Width === 1, 'アバターは最小1');
$avatarSubject = $GLOBALS['avatarCalls'][array_key_last($GLOBALS['avatarCalls'])][0];
$check($avatarSubject !== $guest && $avatarSubject->comment_author_email === 'guest@example.invalid', '現在のゲスト情報を複製して使う');
$check($avatarSubject->user_id === 7 && $avatarSubject->comment_author === 'adjusted guest' && $avatarSubject->comment_author_url === 'https://example.com/changed/', '保持済み著者情報を複製へ反映');
$check((int) $guest->user_id === 0 && $guest->comment_author === 'guest', '元のコメントは変更しない');
unset($GLOBALS['comments'][41]);
$check($guestData->GetAvatar() === null, 'コメント削除後はアバターもnull');
$check(! property_exists($guestData, 'AuthorEmail'), '公開メールプロパティは追加しない');

echo "Comment data: {$checks} checks passed.\n";
