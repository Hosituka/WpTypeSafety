<?php
declare(strict_types=1);

// WordPress APIスタブによるクエリ取得口のテスト。DBは変更しない。
if (PHP_SAPI !== 'cli') {
	exit;
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

class WP_Term
{
	public function __construct(
		public int $term_id,
		public string $name,
		public string $slug,
		public string $taxonomy = 'category',
		public int $parent = 0,
		public string $description = '',
	) {}
}

class WP_User
{
	public function __construct(
		public int $ID,
		public string $display_name = 'ユーザー',
		public string $description = '',
	) {}

	public function exists(): bool
	{
		return $this->ID > 0;
	}
}

class WP_Comment
{
	public function __construct(
		public int $comment_ID,
		public int $comment_post_ID = 0,
		public string $comment_author = '投稿者',
		public string $comment_author_url = '',
		public string $comment_content = '',
		public string $comment_type = 'comment',
		public string $comment_approved = '1',
		public string $comment_date = '2026-11-10 12:00:00',
		public int $comment_parent = 0,
		public int $user_id = 0,
	) {}
}

class WP_Error {}

class WP_Query
{
	/** @var list<WP_Post|int>|null */
	public ?array $posts = null;
	public int $found_posts = 0;
	public int $max_num_pages = 0;
	/** @var array<string, mixed> */
	private array $queryVars = [];

	/** @param array<string, mixed> $args */
	public function query(array $args): array
	{
		$GLOBALS['postQueryArgs'][] = $args;
		$this->queryVars = $args + ['fields' => '', 'no_found_rows' => false, 'paged' => 1];
		$this->posts = $args['_results'] ?? [];
		$this->found_posts = (int) ($args['_total'] ?? count($this->posts));
		$this->max_num_pages = (int) ($args['_max_pages'] ?? ($this->posts === [] ? 0 : 1));

		return $this->posts;
	}

	public function get(string $name): mixed
	{
		return $this->queryVars[$name] ?? null;
	}
}

class WP_Term_Query
{
	/** @var array<string, mixed> */
	public array $query_vars = [];
	/** @var list<WP_Term|int>|null */
	public ?array $terms = null;

	/** @param array<string, mixed> $args */
	public function query(array $args): array
	{
		$GLOBALS['termQueryArgs'][] = $args;
		$this->query_vars = $args + ['fields' => 'all'];
		$this->terms = $args['_results'] ?? [];

		return $this->terms;
	}
}

class WP_User_Query
{
	/** @var array<string, mixed> */
	public array $query_vars = [];
	/** @var list<WP_User|int> */
	private array $results = [];
	private int $total = 0;

	/** @param array<string, mixed> $args */
	public function prepare_query(array $args): void
	{
		$GLOBALS['userQueryArgs'][] = $args;
		$this->query_vars = $args + [
			'fields' => 'all',
			'count_total' => true,
			'number' => '',
			'paged' => 1,
		];
	}

	public function query(): void
	{
		$this->results = $this->query_vars['_results'] ?? [];
		$this->total = (int) ($this->query_vars['_total'] ?? count($this->results));
	}

	public function get(string $name): mixed
	{
		return $this->query_vars[$name] ?? null;
	}

	public function get_results(): array
	{
		return $this->results;
	}

	public function get_total(): int
	{
		return $this->total;
	}
}

class WP_Comment_Query
{
	/** @var array<string, mixed> */
	public array $query_vars = [];
	/** @var list<WP_Comment|int>|null */
	public ?array $comments = null;
	public int $found_comments = 0;
	public int $max_num_pages = 0;

	/** @param array<string, mixed> $args */
	public function query(array $args): array
	{
		$GLOBALS['commentQueryArgs'][] = $args;
		$this->query_vars = $args + [
			'fields' => '',
			'count' => false,
			'no_found_rows' => false,
			'paged' => 1,
		];
		$this->comments = $args['_results'] ?? [];
		$this->found_comments = (int) ($args['_total'] ?? count($this->comments));
		$this->max_num_pages = (int) ($args['_max_pages'] ?? ($this->comments === [] ? 0 : 1));

		return $this->comments;
	}
}

function get_permalink(WP_Post $post): string { return 'https://example.com/post/' . $post->ID . '/'; }
function get_post_datetime(WP_Post $post, string $field = 'date'): false { return false; }
function get_the_title(WP_Post $post): string { return '投稿' . $post->ID; }
function is_post_publicly_viewable(WP_Post $post): bool { return $post->post_status === 'publish'; }
function get_term_link(WP_Term $term): string { return 'https://example.com/' . $term->taxonomy . '/' . $term->slug . '/'; }
function is_wp_error(mixed $value): bool { return $value instanceof WP_Error; }
function get_author_posts_url(int $id): string { return 'https://example.com/author/' . $id . '/'; }
function get_comment_link(WP_Comment $comment): string { return 'https://example.com/#comment-' . $comment->comment_ID; }
function wp_timezone(): DateTimeZone { return new DateTimeZone('Asia/Tokyo'); }

require_once __DIR__ . '/../autoload.php';

use WpTypeSafety\Wp\Get\CommentQuery;
use WpTypeSafety\Wp\Get\PostQuery;
use WpTypeSafety\Wp\Get\TermQuery;
use WpTypeSafety\Wp\Get\UserQuery;

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};

$postResult = PostQuery::Get([
	'post_type' => 'staff',
	'paged' => 2,
	'_results' => [new WP_Post(10), new WP_Post(11)],
	'_total' => 7,
	'_max_pages' => 4,
]);
$check(count($postResult->Posts) === 2 && $postResult->Posts[0]->PostId === 10, '投稿をPostDataへ変換する');
$check($postResult->TotalCount === 7 && $postResult->MaxPageCount === 4, '投稿の総数と最大ページ数を保持する');
$check($postResult->CurrentPageNumber === 2 && $postResult->HasPosts(), '投稿の現在ページと存在を判定する');
$check(($GLOBALS['postQueryArgs'][0]['post_type'] ?? null) === 'staff', '投稿クエリ引数をそのまま渡す');

$postNoTotal = PostQuery::Get(['no_found_rows' => true]);
$check($postNoTotal->TotalCount === null && $postNoTotal->MaxPageCount === null, '投稿の件数計算無効をnullで表す');

$termResult = TermQuery::Get([
	'taxonomy' => 'area',
	'_results' => [new WP_Term(3, '厚木市', 'atsugi', 'area')],
]);
$check(count($termResult->Terms) === 1 && $termResult->Terms[0]->Taxonomy === 'area', 'タームをTermDataへ変換する');
$check($termResult->HasTerms(), 'タームの存在を判定する');
$check(($GLOBALS['termQueryArgs'][0]['taxonomy'] ?? null) === 'area', 'タームクエリ引数をそのまま渡す');

$userResult = UserQuery::Get([
	'role' => 'author',
	'number' => 2,
	'paged' => 3,
	'_results' => [new WP_User(5, '利用者')],
	'_total' => 5,
], 64);
$check(count($userResult->Users) === 1 && $userResult->Users[0]->Id === 5, 'ユーザーをWpUserDataへ変換する');
$check($userResult->TotalCount === 5 && $userResult->MaxPageCount === 3, 'ユーザーの最大ページ数を計算する');
$check($userResult->CurrentPageNumber === 3 && $userResult->HasUsers(), 'ユーザーの現在ページと存在を判定する');
$check(($GLOBALS['userQueryArgs'][0]['role'] ?? null) === 'author', 'ユーザークエリ引数をそのまま渡す');

$commentResult = CommentQuery::Get([
	'post_id' => 10,
	'paged' => 2,
	'_results' => [new WP_Comment(8, 10)],
	'_total' => 3,
	'_max_pages' => 2,
], 80);
$check(count($commentResult->Comments) === 1 && $commentResult->Comments[0]->Id === 8, 'コメントをCommentDataへ変換する');
$check($commentResult->TotalCount === 3 && $commentResult->MaxPageCount === 2, 'コメントの総数と最大ページ数を保持する');
$check($commentResult->CurrentPageNumber === 2 && $commentResult->HasComments(), 'コメントの現在ページと存在を判定する');
$check(($GLOBALS['commentQueryArgs'][0]['post_id'] ?? null) === 10, 'コメントクエリ引数をそのまま渡す');

foreach ([
	static fn () => PostQuery::Get(['fields' => 'ids']),
	static fn () => TermQuery::Get(['fields' => 'ids']),
	static fn () => UserQuery::Get(['fields' => 'ids']),
	static fn () => CommentQuery::Get(['fields' => 'ids']),
] as $invalidQuery) {
	try {
		$invalidQuery();
		throw new RuntimeException('IDだけを返すクエリを受け入れた');
	} catch (InvalidArgumentException) {
		$check(true, 'Dataへ変換できないfieldsを明示的に拒否する');
	}
}

$nativePostQuery = new WP_Query();
$nativePostQuery->query(['_results' => [new WP_Post(20)]]);
$check(PostQuery::From($nativePostQuery)->Posts[0]->PostId === 20, '既存のWP_Queryも変換できる');

$nativeTermQuery = new WP_Term_Query();
$nativeTermQuery->query(['_results' => [new WP_Term(21, '横浜市', 'yokohama', 'area')]]);
$check(TermQuery::From($nativeTermQuery)->Terms[0]->Id === 21, '既存のWP_Term_Queryも変換できる');

$nativeUserQuery = new WP_User_Query();
$nativeUserQuery->prepare_query(['count_total' => false, '_results' => [new WP_User(22)]]);
$nativeUserQuery->query();
$nativeUserResult = UserQuery::From($nativeUserQuery);
$check($nativeUserResult->Users[0]->Id === 22, '既存のWP_User_Queryも変換できる');
$check($nativeUserResult->TotalCount === null && $nativeUserResult->MaxPageCount === null, 'ユーザーの件数計算無効をnullで表す');

$nativeCommentQuery = new WP_Comment_Query();
$nativeCommentQuery->query(['no_found_rows' => true, '_results' => [new WP_Comment(23)]]);
$nativeCommentResult = CommentQuery::From($nativeCommentQuery);
$check($nativeCommentResult->Comments[0]->Id === 23, '既存のWP_Comment_Queryも変換できる');
$check($nativeCommentResult->TotalCount === null && $nativeCommentResult->MaxPageCount === null, 'コメントの件数計算無効をnullで表す');

echo "WordPress query: {$checks} checks passed.\n";
