<?php
declare(strict_types=1);

// WordPress APIスタブでメタ取得・木構造探索・WP参照解決を検証する。DBは変更しない。
if (PHP_SAPI !== 'cli') {
	exit;
}

class WP_Post
{
	public int $post_author = 0;
	public string $post_content = '';
	public string $post_status = 'publish';
	public string $post_type = 'post';
	public string $post_name = '';
	public string $post_password = '';
	public string $post_excerpt = '';

	public function __construct(public int $ID) {}
}

class WP_Term
{
	public int $parent = 0;
	public string $description = '';

	public function __construct(
		public int $term_id,
		public string $name = '',
		public string $slug = '',
		public string $taxonomy = 'category',
	) {}
}

class WP_User
{
	public string $display_name = '';
	public string $description = '';

	public function __construct(public int $ID) {}

	public function exists(): bool
	{
		return $this->ID > 0;
	}
}

class WP_Comment
{
	public int $comment_post_ID = 0;
	public int $comment_parent = 0;
	public int $user_id = 0;
	public string $comment_author = '';
	public string $comment_author_url = '';
	public string $comment_content = '';
	public string $comment_type = '';
	public string $comment_approved = '1';
	public string $comment_date = '';

	public function __construct(public int $comment_ID) {}
}

class WP_Error {}

/** @var array<string, array<int, array<string, list<mixed>>>> */
$GLOBALS['metaValues'] = [
	'post' => [
		7 => [
			'title' => ['見出し'],
			'count' => ['12'],
			'ratio' => ['1.5'],
			'enabled' => ['1'],
			'disabled' => [''],
			'settings' => [[
				'color' => 'blue',
				'columns' => '3',
				'relations' => [
					'post_id' => '20',
					'user_id' => '30',
					'term_id' => '40',
					'comment_id' => '50',
				],
			]],
			'nullable' => [null],
			'aliases' => ['first', 10, 'second'],
			'numbers' => ['1', 'invalid', 2],
			'flags' => ['1', 'false', 1, ''],
			'status' => ['publish'],
			'statuses' => ['draft', 'invalid', 'publish'],
			'related_post_id' => ['20'],
		],
		9 => ['title' => ['PostDataの投稿']],
		20 => ['subtitle' => ['参照先投稿']],
	],
	'term' => [40 => ['color' => ['green']]],
	'user' => [30 => ['nickname' => ['tester']]],
	'comment' => [50 => ['rating' => ['5']]],
];
$GLOBALS['metaCalls'] = [];

function metadata_exists(string $metaType, int $objectId, string $key): bool
{
	$GLOBALS['metaCalls'][] = ['exists', $metaType, $objectId, $key];
	return isset($GLOBALS['metaValues'][$metaType][$objectId])
		&& array_key_exists($key, $GLOBALS['metaValues'][$metaType][$objectId]);
}

function get_metadata(string $metaType, int $objectId, string $key, bool $single): mixed
{
	$GLOBALS['metaCalls'][] = ['get', $metaType, $objectId, $key, $single];
	$values = $GLOBALS['metaValues'][$metaType][$objectId][$key] ?? [];
	return $single ? ($values[0] ?? null) : $values;
}

$GLOBALS['posts'] = [20 => new WP_Post(20)];
$GLOBALS['users'] = [30 => new WP_User(30)];
$GLOBALS['terms'] = [40 => new WP_Term(40, '地域', 'area', 'category')];
$GLOBALS['comments'] = [50 => new WP_Comment(50)];

function get_post(int $postId): ?WP_Post { return $GLOBALS['posts'][$postId] ?? null; }
function get_permalink(WP_Post $post): string { return 'https://example.com/post/' . $post->ID . '/'; }
function get_post_datetime(WP_Post $post, string $field = 'date'): false { return false; }
function get_the_title(WP_Post $post): string { return '投稿' . $post->ID; }
function is_post_publicly_viewable(WP_Post $post): bool { return true; }
function get_user_by(string $field, int $userId): WP_User|false { return $GLOBALS['users'][$userId] ?? false; }
function get_author_posts_url(int $userId): string { return 'https://example.com/author/' . $userId . '/'; }
function get_term(int $termId, string $taxonomy = ''): WP_Term|WP_Error|null { return $GLOBALS['terms'][$termId] ?? null; }
function get_term_link(WP_Term $term): string { return 'https://example.com/term/' . $term->term_id . '/'; }
function is_wp_error(mixed $value): bool { return $value instanceof WP_Error; }
function get_comment(int $commentId): ?WP_Comment { return $GLOBALS['comments'][$commentId] ?? null; }
function get_comment_link(WP_Comment $comment): string { return 'https://example.com/#comment-' . $comment->comment_ID; }
function wp_timezone(): DateTimeZone { return new DateTimeZone('Asia/Tokyo'); }

require_once __DIR__ . '/../autoload.php';

use WpTypeSafety\Core\Check\ValueMode;
use WpTypeSafety\Core\Check\ValueRules;
use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\PostMembers;
use WpTypeSafety\Wp\Get\MetaGet;

enum MetaStatus: string
{
	case Draft = 'draft';
	case Publish = 'publish';
}

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};

$postMetaGet = new MetaGet(7);
$check($postMetaGet->Exists('title'), '保存済みメタが存在する');
$check(! $postMetaGet->Exists('missing'), '欠損メタが存在しない');
$check($postMetaGet->Exists('nullable'), 'nullの保存値と欠損を区別する');
$check(! $postMetaGet->Exists(''), '空のメタキーを拒否する');
$check($postMetaGet->Native('title') === '見出し', '単一のネイティブ値を取得する');
$check($postMetaGet->Native('missing') === null, '欠損したネイティブ値はnull');
$check($postMetaGet->NativeList('aliases') === ['first', 10, 'second'], '複数のネイティブ値を取得する');
$check($postMetaGet->String('title') === '見出し', '文字列を取得する');
$check($postMetaGet->Int('count') === 12, '既定でWordPressの数値文字列を変換する');
$check($postMetaGet->Float('ratio') === 1.5, '既定で小数文字列を変換する');
$check($postMetaGet->Bool('enabled') === true, '既定で真の文字列表現を変換する');
$check($postMetaGet->Bool('disabled') === false, '既定で空文字をfalseへ変換する');
$check($postMetaGet->Array('settings')['color'] === 'blue', '配列を取得する');
$check($postMetaGet->StringList('aliases') === ['first', 'second'], '複数値から文字列だけを取得する');
$check($postMetaGet->IntList('numbers') === [1, 2], '複数値の不正な整数を除外する');
$check($postMetaGet->BoolList('flags') === [true, false, true, false], '複数の真偽値表現を変換する');
$check($postMetaGet->BackedEnum('status', MetaStatus::class) === MetaStatus::Publish, 'BackedEnumを取得する');
$check(
	$postMetaGet->BackedEnumList('statuses', MetaStatus::class) === [MetaStatus::Draft, MetaStatus::Publish],
	'複数値からBackedEnumを取得する',
);

$predicateCalls = 0;
$check(
	$postMetaGet->Int(
		'count',
		predicate: static function (int $value) use (&$predicateCalls): bool {
			$predicateCalls++;
			return $value >= 10;
		},
	) === 12,
	'MetaGetはWordPress向けの正規化後にpredicateを適用する',
);
$check($predicateCalls === 1, 'MetaGetは正規化済みの値へpredicateを一度だけ適用する');
$check($postMetaGet->Int('count', predicate: static fn(int $value): bool => $value > 12) === null, '単一値のpredicate不一致では既定値を返す');
$check($postMetaGet->Bool('disabled', predicate: static fn(bool $value): bool => ! $value) === false, '有効なfalseをpredicateへ渡す');
$check($postMetaGet->IntList('numbers', predicate: static fn(int $value): bool => $value > 1) === [2], 'Listのpredicateで正規化済み要素を絞り込む');
$check($postMetaGet->Array('settings', predicate: static fn(array $value): bool => isset($value['color']))['color'] === 'blue', '配列全体へpredicateを適用する');
$check($postMetaGet->BackedEnumList('statuses', MetaStatus::class, predicate: static fn(MetaStatus $value): bool => $value === MetaStatus::Publish) === [MetaStatus::Publish], 'BackedEnumListへpredicateを適用する');

$skippedPredicateCalls = 0;
$postMetaGet->Int('missing', predicate: static function (int $value) use (&$skippedPredicateCalls): bool {
	$skippedPredicateCalls++;
	return true;
});
$check($skippedPredicateCalls === 0, '欠損したメタ値ではpredicateを実行しない');

$instance = new stdClass();
$instanceGet = MetaGet::FromTree(['item' => $instance, 'items' => [$instance, 'invalid']]);
$check($instanceGet->Instance('item', stdClass::class, predicate: static fn(stdClass $value): bool => $value === $instance) === $instance, 'Instanceへpredicateを適用する');
$check($instanceGet->InstanceList('items', stdClass::class, predicate: static fn(stdClass $value): bool => $value === $instance) === [$instance], 'InstanceListへpredicateを適用する');

$strictGet = $postMetaGet->WithMode(ValueMode::Strict);
$check($strictGet->Int('count') === null, 'Strictでは数値文字列を変換しない');
$check($postMetaGet->Int('count') === 12, 'WithModeは元の取得口を変更しない');

$snapshotRules = ValueRules::FromMode(ValueMode::Strict);
$snapshotGet = MetaGet::FromPost(7, $snapshotRules);
$snapshotRules->IntNormalizer = static fn(mixed $value): ?int => 100;
$check($snapshotGet->Int('count') === null, '生成時のValueRulesを保持する');

$nullRules = ValueRules::FromMode(ValueMode::Strict);
$nullRules->StringNormalizer = static fn(mixed $value): ?string => $value === null ? 'null-value' : null;
$nullGet = MetaGet::FromPost(7, $nullRules);
$check($nullGet->String('nullable') === 'null-value', '保存済みnullをNormalizerへ渡す');
$check($nullGet->String('missing') === '', '欠損時はNormalizerを呼ばない');

$settingsGet = $postMetaGet->Nested('settings');
$check($settingsGet instanceof MetaGet, 'NestedはMetaGetを返す');
$check($settingsGet->String('color') === 'blue', 'ネストした文字列を取得する');
$check($settingsGet->Int('columns') === 3, 'ネスト先へWordPress向け規則を引き継ぐ');
$relationsGet = $postMetaGet->NestedPath('settings', 'relations');
$check($relationsGet->ExistsPath('post_id'), 'NestedPathで配列をたどる');
$check($relationsGet->Post('post_id')?->PostId === 20, 'PHP値をPostDataへ変換する');
$check($relationsGet->User('user_id')?->Id === 30, 'PHP値をWpUserDataへ変換する');
$check($relationsGet->Term('term_id')?->Id === 40, 'PHP値をTermDataへ変換する');
$check($relationsGet->Comment('comment_id')?->Id === 50, 'PHP値をCommentDataへ変換する');
$check($relationsGet->WpPost('post_id')?->ID === 20, 'PHP値をWP_Postへ変換する');
$check($relationsGet->WpUser('user_id')?->ID === 30, 'PHP値をWP_Userへ変換する');
$check($relationsGet->WpTerm('term_id')?->term_id === 40, 'PHP値をWP_Termへ変換する');
$check($relationsGet->WpComment('comment_id')?->comment_ID === 50, 'PHP値をWP_Commentへ変換する');
$check($relationsGet->PostMeta('post_id')->String('subtitle') === '参照先投稿', '投稿IDから投稿メタへ再進入する');
$check($relationsGet->UserMeta('user_id')->String('nickname') === 'tester', 'ユーザーIDからユーザーメタへ再進入する');
$check($relationsGet->TermMeta('term_id')->String('color') === 'green', 'タームIDからタームメタへ再進入する');
$check($relationsGet->CommentMeta('comment_id')->Int('rating') === 5, 'コメントIDからコメントメタへ再進入する');
$check($postMetaGet->Post('related_post_id')?->PostId === 20, 'メタ直下の投稿IDをPostDataへ変換する');
$check($postMetaGet->PostMeta('related_post_id')->String('subtitle') === '参照先投稿', 'メタ直下から別投稿メタへ進む');

$wpPostGet = new MetaGet(new WP_Post(7));
$check($wpPostGet->String('title') === '見出し', 'WP_Postから投稿IDを取得する');

$postData = new PostData(new PostMembers(PostId: 9, Url: '', Title: '', Content: ''));
$check((new MetaGet($postData))->String('title') === 'PostDataの投稿', 'PostDataから投稿IDを取得する');
$check((new MetaGet(new WP_Term(40)))->String('color') === 'green', 'WP_Termからタームメタを取得する');
$check((new MetaGet(new WP_User(30)))->String('nickname') === 'tester', 'WP_Userからユーザーメタを取得する');
$check((new MetaGet(new WP_Comment(50)))->Int('rating') === 5, 'WP_Commentからコメントメタを取得する');
$check(MetaGet::FromTerm(40)->String('color') === 'green', '整数のタームIDを明示して取得する');
$check((new MetaGet(['post_id' => '20']))->Post('post_id')?->PostId === 20, '取得済み配列からWordPress Dataへ進む');

$GLOBALS['metaCalls'] = [];
$invalidGet = MetaGet::FromPost(0);
$check(! $invalidGet->Exists('title'), '0以下のIDを空の取得元として扱う');
$check($invalidGet->Native('title') === null, '無効なIDの値はnull');
$check($GLOBALS['metaCalls'] === [], '無効なIDではWordPress APIを呼ばない');

echo "MetaGet: {$checks} checks passed.\n";
