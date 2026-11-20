<?php
declare(strict_types=1);

// WordPress APIスタブ。実際のDBへの接続・更新は行わない。
if (PHP_SAPI !== 'cli')
{exit;}
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/fixtures/data-props.php';

use WpTypeSafety\Wp\Data\MenuData;
use WpTypeSafety\Wp\Data\MenuNodeData;
use WpTypeSafety\Wp\Data\MenuNodeProps;
use WpTypeSafety\Wp\Get\MenuGet;

class WP_Term
{
	public function __construct(
		public int $term_id = 10,
		public string $name = 'メイン',
		public string $slug = 'main-menu',
		public string $description = '説明',
		public string $taxonomy = 'nav_menu',
	) {}
}
#[AllowDynamicProperties]
class WP_Post
{
	public function __construct(
		public int $ID,
		public int $menu_item_parent = 0,
		public string $title = '項目',
		public string $url = '/sample/',
		public string $description = '',
		public array $classes = [],
		public int $menu_order = 0,
		public string $type = 'custom',
		public string $object = 'custom',
		public int $object_id = 0,
		public string $post_type = 'nav_menu_item',
		public bool $current = false,
	) {}
	public function __isset(string $name): bool
	{
		throw new LogicException('変換時にWPの暗黙メタ取得を使わない: ' . $name);
	}
}
class WP_Error {}
class WP_Query {}
/** @extends MenuNodeData<ProjectNode> */
class ProjectNode extends MenuNodeData
{
	protected function initialize(WP_Post $item, array $children = []): void
	{
		parent::initialize($item, $children);
		$GLOBALS['order'][] = $item->ID;
	}
}
/** @extends MenuData<ProjectNode> */
class ProjectMenu extends MenuData
{
	protected static function getNodeClass(): string { return ProjectNode::class; }
}
class ExcludedNode extends ProjectNode
{
	protected static function getProps(WP_Post $item, array $children = []): ?MenuNodeProps
	{
		return $item->ID === 1 ? null : parent::getProps($item, $children);
	}
}
class ExcludedMenu extends MenuData
{
	protected static function getNodeClass(): string { return ExcludedNode::class; }
}
class ThrowingMenu extends MenuData
{
	protected function initialize(WP_Term $term): void { throw $GLOBALS['exception']; }
}

class InvalidNodeMenu extends MenuData
{
	protected static function getNodeClass(): string { return stdClass::class; }
}
abstract class AbstractNode extends MenuNodeData {}
class AbstractNodeMenu extends MenuData
{
	protected static function getNodeClass(): string { return AbstractNode::class; }
}

function get_term(int $id, string $taxonomy): mixed
{
	$GLOBALS['calls'][] = ['id', $id, $taxonomy];
	return $GLOBALS['terms'][$id] ?? null;
}
function get_term_by(string $field, string $value, string $taxonomy): mixed
{
	$GLOBALS['calls'][] = [$field, $value, $taxonomy];
	foreach ($GLOBALS['terms'] as $term) {
		if ($term instanceof WP_Term && $term->slug === $value)
		{return $term;}
	}
	return false;
}
function get_registered_nav_menus(): array { return ['primary' => 'メイン', 'empty' => '空', 'unassigned' => '未割当']; }
function get_nav_menu_locations(): array { return ['primary' => 10, 'empty' => 20, 'unregistered' => 10]; }
function wp_get_nav_menu_items(int $id): mixed
{
	$GLOBALS['calls'][] = ['items', $id];
	return $GLOBALS['items'][$id] ?? false;
}
function _wp_menu_item_classes_by_context(array &$items): void
{
	$GLOBALS['calls'][] = ['context'];
	foreach ($items as $item) {
		$item->current = in_array($item->ID, $GLOBALS['currentIds'], true);
		$item->classes[] = $item->current ? 'current-menu-item' : 'menu-item';
	}
}

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (!$ok)
	{throw new RuntimeException($label);}
	$checks++;
};
$GLOBALS['calls'] = [];
$GLOBALS['currentIds'] = [2];
$GLOBALS['wp_query'] = new WP_Query();
$GLOBALS['terms'] = [10 => new WP_Term(), 20 => new WP_Term(20, '空', 'empty')];
$GLOBALS['items'] = [10 => [new WP_Post(1), new WP_Post(2, 1)], 20 => []];
$check(\WpTypeSafety\Wp\Data\MenuNodeData::From(new WP_Post(9))?->IsCurrent === false, '任意プロパティの欠損をメタ取得で補わない');

$byId = MenuGet::ById(10);
$bySlug = MenuGet::BySlug('main-menu');
$byLocation = MenuGet::ByLocation('primary');
$check($byId instanceof MenuData && $byId->Id === 10 && $bySlug->Id === 10 && $byLocation->Id === 10, '3つの取得口で同じメニュー');
$check($byId->Name === 'メイン' && $byId->Slug === 'main-menu' && $byId->Description === '説明', 'メニュー定義を保持');
$check($byId !== $bySlug && $byId->Nodes[0] !== $bySlug->Nodes[0], '取得ごとに新しいスナップショット');
$check($byId->FindCurrent() === $byId->Nodes[0]->Children[0], 'メニュー内の現在項目を子階層まで検索');
$check($byId->FindCurrent()->IsCurrent, '現在位置判定をノードに保持');
$check(!$GLOBALS['items'][10][1]->current && $GLOBALS['items'][10][1]->classes === [], 'WPの元オブジェクトを変更しない');
$callCount = count($GLOBALS['calls']);
$byId->FindCurrent();
$byId->FindById(2);
$byId->PathToUrl('/sample/');
$check(count($GLOBALS['calls']) === $callCount, '取得済みツリーの探索はWP APIを呼ばない');
$GLOBALS['currentIds'] = [1];
$check($byId->FindCurrent()->Id === 2 && MenuGet::ById(10)->FindCurrent()->Id === 1, '旧スナップショットの現在位置は変えない');
$GLOBALS['currentIds'] = [];
$check(MenuGet::ById(10)->FindCurrent() === null, 'メニュー内に現在項目がなければnull');
$empty = MenuGet::ByLocation('empty');
$check($empty instanceof MenuData && $empty->Nodes === [] && $empty->FindCurrent() === null, '存在する空メニューはData');
foreach ([0, -1, 999] as $id) {
	$check(MenuGet::ById($id) === null, '不正・未発見IDはnull');
}
foreach (['', 'missing', 'メイン', '10'] as $slug) {
	$check(MenuGet::BySlug($slug) === null, 'slug以外の名前やIDへフォールバックしない');
}
foreach (['', 'missing', 'unregistered', 'unassigned'] as $location) {
	$check(MenuGet::ByLocation($location) === null, '未登録・未割当の位置はnull');
}
$wrong = new WP_Term(taxonomy: 'category');
$check(\WpTypeSafety\Wp\Data\MenuData::From($wrong) === null && \WpTypeSafety\Wp\Internal\MenuAdapter::PropsFromWpTerm($wrong) === null, '別タクソノミーは変換しない');
$check(\WpTypeSafety\Wp\Data\MenuData::From(new WP_Term(0)) === null, '不正なタームIDを変換しない');
$GLOBALS['terms'][30] = new WP_Error();
$check(MenuGet::ById(30) === null, 'WP_Errorはnull');
$GLOBALS['items'][20] = false;
$check(MenuGet::ById(20) === null && \WpTypeSafety\Wp\Internal\MenuAdapter::PropsFromWpTerm($GLOBALS['terms'][20]) === null, '項目取得失敗を空メニューと区別');
$GLOBALS['items'][20] = [];

$props = \WpTypeSafety\Wp\Internal\MenuAdapter::PropsFromWpTerm($GLOBALS['terms'][10]);
$copy = TestMenuData::FromProps($props);
$props->Name = '変更';
$props->Nodes = [];
$check($copy->Name === 'メイン' && count($copy->Nodes) === 1, 'PropsをData構築時にコピー');
try {
	$copy->Name = '書き換え';
	$check(false, '公開プロパティはreadonly');
} catch (Error) {
	$check(true, '公開プロパティはreadonly');
}

$order = [];
$GLOBALS['currentIds'] = [2];
$factory = ProjectMenu::class;
$project = MenuGet::ByLocation('primary', $factory);
$check($order === [2, 1], '子を先に生成してFactoryへ渡す');
$check($project->Nodes[0] instanceof ProjectNode && $project->FindById(2) instanceof ProjectNode, '全階層の派生ノードを維持');
$check($project->FindCurrent() instanceof ProjectNode && $project->PathToId(2)[1] instanceof ProjectNode, '現在位置・パスも派生ノードを維持');
$check(ProjectMenu::From($GLOBALS['terms'][10])->Nodes[0] instanceof ProjectNode, 'FromもFactory対応');
$check(\WpTypeSafety\Wp\Internal\MenuAdapter::PropsFromWpTerm($GLOBALS['terms'][10], ProjectNode::class)->Nodes[0] instanceof ProjectNode, 'PropsもFactory対応');
$excluded = MenuGet::ById(10, ExcludedMenu::class);
$check($excluded->Nodes === [], '親を除外した場合は子をルートへ昇格しない');
try {
	MenuGet::ById(10, stdClass::class);
	$check(false, 'Factory型違反');
} catch (InvalidArgumentException) {
	$check(true, 'Factory型違反は伝播');
}
$exception = new LogicException('factory');
try {
	MenuGet::ById(10, ThrowingMenu::class);
	$check(false, 'Factory例外');
} catch (LogicException $caught) {
	$check($caught === $exception, 'Factory例外を握りつぶさない');
}

foreach ([InvalidNodeMenu::class, AbstractNodeMenu::class] as $class) {
	$before = count(array_filter($GLOBALS['calls'], static fn (array $call): bool => $call[0] === 'items'));
	try {
		MenuGet::ById(10, $class);
		$check(false, 'invalid node class');
	} catch (InvalidArgumentException) {
		$after = count(array_filter($GLOBALS['calls'], static fn (array $call): bool => $call[0] === 'items'));
		$check($before === $after, 'ノードクラスは項目取得前に検証');
	}
}

$GLOBALS['items'][10] = [new WP_Post(3, 999), new WP_Post(4, 5), new WP_Post(5, 4), new WP_Post(6, 6), new WP_Post(0), new WP_Post(7, post_type: 'post')];
$incomplete = new WP_Post(8);
unset($incomplete->url);
$GLOBALS['items'][10][] = $incomplete;
$GLOBALS['items'][10][] = 'invalid';
$check(array_map(static fn ($node) => $node->Id, MenuGet::ById(10)->Nodes) === [3], '親欠損はルート・循環と不完全項目は除外');
$GLOBALS['items'][10] = [new WP_Post(1), new WP_Post(2, 1), new WP_Post(3)];
$GLOBALS['currentIds'] = [2, 3];
$check(MenuGet::ById(10)->FindCurrent()->Id === 2, '複数一致では深さ優先の最初の現在項目');
unset($GLOBALS['wp_query']);
$check(MenuGet::ById(10)->FindCurrent() === null, 'クエリ確定前は現在項目なし');

echo "Menu get: {$checks} checks passed.\n";
