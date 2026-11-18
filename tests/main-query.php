<?php
declare(strict_types=1);

// WP_Queryはスタブ。クエリの実行やDB更新は行わない。
require_once __DIR__ . '/../autoload.php';

use WpTypeSafety\Wp\Get\MainQueryGet;
use WpTypeSafety\Wp\Data\PostData;

class WP_Post {}
class FixturePostData extends PostData {}

class WP_Query
{
	public array $posts = [];
	public function __construct(
		public array $queryVars = [],
		public bool $front = false,
		public bool $page = false,
		public int|float $max_num_pages = 0,
		public int $found_posts = 0,
	) {}

	public function get(string $key): mixed { return $this->queryVars[$key] ?? ''; }
	public function is_front_page(): bool { return $this->front; }
	public function is_page(): bool { return $this->page; }
}

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok)
	{throw new RuntimeException($label);}
	$checks++;
};

unset($GLOBALS['wp_the_query']);
foreach (['Current', 'CurrentPageNumber', 'MaxPageCount', 'FoundPostCount'] as $method) {
	try {
		MainQueryGet::$method();
		throw new RuntimeException('クエリ未設定を受け入れた');
	} catch (LogicException) {
		$checks++;
	}
}

$main = new WP_Query(['paged' => '3'], max_num_pages: 8.0, found_posts: 77);
$GLOBALS['wp_the_query'] = $main;
$GLOBALS['wp_query'] = new WP_Query(['paged' => 99], max_num_pages: 99, found_posts: 999);
$check(MainQueryGet::CurrentPageNumber() === 3, '置き換えられたwp_queryではなくwp_the_queryを参照');
$check(MainQueryGet::MaxPageCount() === 8, '総ページ数をintで返す');
$check(MainQueryGet::FoundPostCount() === 77, '現在ページ件数ではなく検索条件に一致した総件数');

foreach ([null, '', 0, -2, 'invalid', [], '2.5'] as $value) {
	$main->queryVars = ['paged' => $value];
	$check(MainQueryGet::CurrentPageNumber() === 1, '不正・未指定のページ番号は1');
}
$main->queryVars = ['page' => 4];
$main->front = true;
$main->page = true;
$check(MainQueryGet::CurrentPageNumber() === 4, '固定フロントページはpageを参照');
$main->queryVars = [];
$check(MainQueryGet::CurrentPageNumber() === 1, '固定フロントの初期ページは1');
$main->queryVars = ['paged' => 3, 'page' => 8];
$main->page = false;
$check(MainQueryGet::CurrentPageNumber() === 3, '最新投稿を表示するフロントページはpaged');
$main->front = false;
$main->page = true;
$check(MainQueryGet::CurrentPageNumber() === 3, '通常の固定ページでは本文分割番号pageを使わない');
$main->queryVars = ['page' => 8];
$check(MainQueryGet::CurrentPageNumber() === 1, '本文分割番号だけでは一覧番号を増やさない');
$main->max_num_pages = 0;
$main->found_posts = 0;
$check(MainQueryGet::MaxPageCount() === 0 && MainQueryGet::FoundPostCount() === 0, '空結果・集計省略時の0を維持');
$main->max_num_pages = -1;
$main->found_posts = -1;
$check(MainQueryGet::MaxPageCount() === 0 && MainQueryGet::FoundPostCount() === 0, '件数を負数にしない');
$check($GLOBALS['wp_the_query'] === $main && $GLOBALS['wp_query']->found_posts === 999, 'グローバルを置き換えない');
$main->max_num_pages = 8;
$main->found_posts = 77;
$main->front = true;
$main->page = true;
$main->queryVars = ['page' => 4];
$result = MainQueryGet::Current();
$check($result->Posts === [] && ! $result->HasPosts(), '空のメインクエリもDataを返す');
$check($result->CurrentPageNumber === 4, 'Currentも固定フロントのpageを参照');
$check($result->TotalCount === 77 && $result->MaxPageCount === 8, '元のメインクエリの集計を維持');
$fixture = (new ReflectionClass(FixturePostData::class))->newInstanceWithoutConstructor();
$post = new WP_Post();
$main->posts = [$post, $post];
$calls = 0;
$factory = static function (WP_Post $value) use ($fixture, $post, &$calls): FixturePostData {
	if ($value !== $post)
	{throw new RuntimeException('元の投稿を渡していない');}
	$calls++;
	return $fixture;
};
$result = MainQueryGet::Current($factory);
$check($result->Posts === [$fixture, $fixture], 'Factoryの派生Dataと投稿順を維持');
$check($calls === 2, '各投稿を一度だけ変換');
MainQueryGet::Current($factory);
$check($calls === 4, '変換結果をキャッシュしない');
$main->queryVars['no_found_rows'] = true;
$result = MainQueryGet::Current($factory);
$check($result->TotalCount === null && $result->MaxPageCount === null, '集計省略時はnull');
$check($GLOBALS['wp_the_query'] === $main && $GLOBALS['wp_query']->found_posts === 999, 'Currentはグローバルを変更しない');
try {
	MainQueryGet::Current(static fn(WP_Post $value) => null);
	throw new RuntimeException('不正Factoryを受け入れた');
} catch (UnexpectedValueException) {
	$checks++;
}
$main->posts = [];
$main->queryVars['fields'] = 'ids';
try {
	MainQueryGet::Current();
	throw new RuntimeException('ID専用クエリを受け入れた');
} catch (InvalidArgumentException) {
	$checks++;
}

echo "Main query: {$checks} checks passed.\n";
