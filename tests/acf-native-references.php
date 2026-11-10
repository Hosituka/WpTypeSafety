<?php
declare(strict_types=1);

// ACFのID・WPオブジェクト返却形式をネイティブWP型へ正規化できることを検証する。
if (PHP_SAPI !== 'cli') {
	exit;
}

class WP_Post
{
	public function __construct(public int $ID) {}
}

class WP_Term
{
	public function __construct(
		public int $term_id,
		public string $taxonomy = 'category',
	) {}
}

class WP_User
{
	public function __construct(public int $ID) {}
}

class WP_Error {}

$postObject = new WP_Post(1);
$termObject = new WP_Term(3);
$userObject = new WP_User(5);

$GLOBALS['acfNativeFields'] = [
	'post_object' => $postObject,
	'post_id' => '2',
	'posts' => [$postObject, '2', 'invalid', 0],
	'term_object' => $termObject,
	'term_id' => '4',
	'terms' => [$termObject, '4', 'invalid', 0],
	'user_object' => $userObject,
	'user_id' => ['ID' => '6'],
	'users' => [$userObject, ['ID' => '6'], 'invalid', 0],
	'group' => ['post_id' => '2'],
];
$GLOBALS['wpPosts'] = [2 => new WP_Post(2)];
$GLOBALS['wpTerms'] = [4 => new WP_Term(4)];
$GLOBALS['wpUsers'] = [6 => new WP_User(6)];

function get_field(string $name, mixed $source = null): mixed
{
	return $GLOBALS['acfNativeFields'][$name] ?? null;
}

function get_post(int $postId): ?WP_Post
{
	return $GLOBALS['wpPosts'][$postId] ?? null;
}

function get_term(int $termId, string $taxonomy = ''): WP_Term|WP_Error|null
{
	return $GLOBALS['wpTerms'][$termId] ?? null;
}

function get_user_by(string $field, int $userId): WP_User|false
{
	return $GLOBALS['wpUsers'][$userId] ?? false;
}

require_once __DIR__ . '/../autoload.php';

use WpTypeSafety\Acf\Get\AcfGet;

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};

$postGet = new AcfGet(10);
$check($postGet->WpPost('post_object') === $postObject, 'WP_Postをそのまま返す');
$check($postGet->WpPost('post_id')?->ID === 2, '投稿IDをWP_Postへ変換する');
$check(
	array_map(static fn (WP_Post $post): int => $post->ID, $postGet->WpPosts('posts')) === [1, 2],
	'複数投稿をWP_Postリストへ変換する',
);
$check($postGet->WpTerm('term_object') === $termObject, 'WP_Termをそのまま返す');
$check($postGet->WpTerm('term_id')?->term_id === 4, 'タームIDをWP_Termへ変換する');
$check(
	array_map(static fn (WP_Term $term): int => $term->term_id, $postGet->WpTerms('terms')) === [3, 4],
	'複数タームをWP_Termリストへ変換する',
);
$check($postGet->WpUser('user_object') === $userObject, 'WP_Userをそのまま返す');
$check($postGet->WpUser('user_id')?->ID === 6, 'ユーザー配列をWP_Userへ変換する');
$check(
	array_map(static fn (WP_User $user): int => $user->ID, $postGet->WpUsers('users')) === [5, 6],
	'複数ユーザーをWP_Userリストへ変換する',
);
$check($postGet->Nested('group')->WpPost('post_id')?->ID === 2, 'Nested内でもWP_Postへ変換する');
$check($postGet->WpPost('missing') === null, '欠損した投稿参照はnull');
$check($postGet->WpPosts('missing') === [], '欠損した複数投稿参照は空配列');

echo "ACF native references: {$checks} checks passed.\n";
