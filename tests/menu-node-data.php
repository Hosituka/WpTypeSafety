<?php

declare(strict_types=1);

// WordPress・DBに接続せず、MenuNodeDataのMembersとFactoryを検証する。
if (PHP_SAPI !== 'cli') {
	exit;
}

require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/fixtures/data-props.php';
function get_post_thumbnail_id(int $id): int { return 0; }
require_once __DIR__ . '/../Core/Data/ImgData.php';
require_once __DIR__ . '/../Core/Data/LinkData.php';
require_once __DIR__ . '/../Wp/Data/MenuItemType.php';
require_once __DIR__ . '/../Wp/Data/MenuNodeProps.php';
require_once __DIR__ . '/../Wp/Data/MenuNodeData.php';
require_once __DIR__ . '/../Wp/Internal/MenuAdapter.php';

use WpTypeSafety\Wp\Data\MenuItemType;
use WpTypeSafety\Wp\Data\MenuNodeData;
use WpTypeSafety\Wp\Data\MenuNodeProps;
use WpTypeSafety\Wp\Get\MenuGet;
use WpTypeSafety\Wp\Data\MenuData;
use WpTypeSafety\Wp\Data\MenuProps;

class WP_Post
{
	/** @param list<mixed> $classes */
	public function __construct(
		public int $ID,
		public int|string $menu_item_parent = 0,
		public mixed $title = '',
		public mixed $url = '',
		public mixed $target = '',
		public mixed $attr_title = '',
		public mixed $xfn = '',
		public mixed $description = '',
		public array $classes = [],
		public int|string $menu_order = 0,
		public mixed $type = '',
		public mixed $object = '',
		public int|string $object_id = 0,
		public string $post_type = 'nav_menu_item',
	) {}
}

class WP_Term
{
	public function __construct(
		public int $term_id = 10,
		public string $name = 'メニュー',
		public string $slug = 'primary-menu',
		public string $description = '',
		public string $taxonomy = 'nav_menu',
	) {}
}
function get_term(int $id, string $taxonomy): WP_Term { return new WP_Term($id); }
function get_registered_nav_menus(): array { return ['primary' => 'メイン']; }
function get_nav_menu_locations(): array { return ['primary' => 10]; }
function wp_get_nav_menu_items(int $menuId): mixed { return $GLOBALS['menuItems'] ?? []; }

/** @extends MenuNodeData<ProjectMenuNodeData> */
class ProjectMenuNodeData extends MenuNodeData
{
	public readonly string $Label;
	protected function initialize(WP_Post $item, array $children = []): void
	{
		parent::initialize($item, $children);
		$this->Label = $item->ID === 3 ? '案件固有' : '派生メニュー' . $item->ID;
	}
}
/** @extends MenuData<ProjectMenuNodeData> */
class ProjectMenu extends MenuData
{
	protected static function getNodeClass(): string { return ProjectMenuNodeData::class; }
}

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};

$child = TestMenuNodeData::FromProps(new MenuNodeProps(
	Id: 2,
	ParentId: 1,
	Title: '子項目',
	Url: '/child/',
	Description: '',
	Classes: [],
	Order: 2,
	Type: MenuItemType::Custom,
	Object: 'custom',
	ObjectId: 0,
));

$props = new MenuNodeProps(
	Id: 1,
	ParentId: 0,
	Title: '変更前',
	Url: '/parent/',
	Description: '説明',
	Classes: ['menu-item', 'primary'],
	Order: 1,
	Type: MenuItemType::PostType,
	Object: 'page',
	ObjectId: 10,
	Children: [$child],
);
$data = TestMenuNodeData::FromProps($props);
$props->Title = '変更後';
$props->Children = [];

$check($data->Title === '変更前', 'DataはMembersを構築時の値として保持する');
$check($data->Children === [$child] && $data->HasChildren(), '子ノードを保持する');
$check(
	$data->GetLink()->Url === '/parent/'
	&& $data->GetLink()->Title === '変更前'
	&& $data->GetLink()->Target === '',
	'リンクへ変換する',
);
$check(! $data->IsNewTab(), 'Target省略時は同じタブとして扱う');
$check($data->GetThumbnail() === null, 'Resolver省略時は画像なし');

/** @var list<ProjectMenuNodeData> $projectChildren */
$projectChildren = [];
$projectMembers = \WpTypeSafety\Wp\Internal\MenuAdapter::PropsFromWpPost(new WP_Post(3, title: '派生タイトル'), $projectChildren);
$derived = ProjectMenuNodeData::From(new WP_Post(3, title: '派生タイトル'));
$check($derived->Title === '派生タイトル' && $derived->Label === '案件固有', 'Membersを派生Dataへ渡せる');

$item = new WP_Post(
	ID: 20,
	menu_item_parent: '5',
	title: '会社案内',
	url: '/about/',
	target: '_blank',
	attr_title: '会社案内を開く',
	xfn: 'external',
	description: '会社案内です',
	classes: ['menu-item', false, 'about'],
	menu_order: '3',
	type: 'post_type',
	object: 'page',
	object_id: '30',
);
$factoryMembers = \WpTypeSafety\Wp\Internal\MenuAdapter::PropsFromWpPost($item, [$child]);
$check($factoryMembers->Id === 20 && $factoryMembers->ParentId === 5, 'FactoryがIDを正規化する');
$check($factoryMembers->Classes === ['menu-item', 'about'], 'Factoryが文字列クラスだけを保持する');
$check($factoryMembers->Type === MenuItemType::PostType, 'Factoryがメニュー種別をenumへ変換する');
$check($factoryMembers->Children === [$child], 'Factoryが構築済みの子ノードを受け取る');
$check(
	$factoryMembers->Target === '_blank'
	&& $factoryMembers->AttributeTitle === '会社案内を開く'
	&& $factoryMembers->Xfn === 'external',
	'Factoryがリンク属性を保持する',
);

$factoryData = \WpTypeSafety\Wp\Data\MenuNodeData::From($item, [$child]);
$check($factoryData->Title === '会社案内' && $factoryData->Children === [$child], '公開FactoryからMenuNodeDataを生成する');
$check($factoryData->IsNewTab() && $factoryData->GetLink()->Target === '_blank', '新規タブ判定とLinkDataへTargetを引き継ぐ');

$invalidLinkAttributes = \WpTypeSafety\Wp\Internal\MenuAdapter::PropsFromWpPost(new WP_Post(
	ID: 21,
	target: [],
	attr_title: false,
	xfn: 10,
));
$check(
	$invalidLinkAttributes->Target === ''
	&& $invalidLinkAttributes->AttributeTitle === ''
	&& $invalidLinkAttributes->Xfn === '',
	'Factoryが文字列以外のリンク属性を空文字へ正規化する',
);

$menu = TestMenuData::FromProps(new MenuProps(10, 'メニュー', 'primary-menu', '', [$data]));
$check(\WpTypeSafety\Wp\Data\MenuNodeData::From(new WP_Post(4, post_type: 'post')) === null, '対象外の投稿は変換しない');
$incomplete = new WP_Post(5);
unset($incomplete->title);
$check(\WpTypeSafety\Wp\Data\MenuNodeData::From($incomplete) === null && \WpTypeSafety\Wp\Internal\MenuAdapter::PropsFromWpPost($incomplete) === null, '未セットアップ項目はnull');
$check($menu->FindById(2) === $child, 'メニュー項目IDから深い子ノードを検索する');
$check($menu->FindById(0) === null, '0以下のメニュー項目IDは検索しない');
$check($menu->FindById(999) === null, '存在しないメニュー項目IDはnullを返す');

$check(
	$menu->FindByObject(MenuItemType::PostType, 'page', 10) === $data,
	'リンク先オブジェクトからノードを検索する',
);
$check(
	$menu->FindByObject(MenuItemType::Taxonomy, 'page', 10) === null,
	'ObjectIdが同じでも種別が異なるノードを返さない',
);
$check($menu->FindByPageId(10) === $data, '固定ページ検索を汎用検索へ委譲する');

$foundByUrl = $menu->FindByUrl('/child/');
$check($foundByUrl === $child, 'URLから深い子ノードを検索する');
$check($menu->FindByUrl('') === null, '空URLは検索しない');
$check($menu->FindByUrl('/missing/') === null, '存在しないURLはnullを返す');

$pathToUrl = $menu->PathToUrl('/child/');
$check($pathToUrl === [$data, $child], 'URLまでのパスをルートから対象自身の順で返す');
$check($menu->PathToUrl('/parent/') === [$data], 'ルート自身へのパスは1件を返す');
$check($menu->PathToUrl('/missing/') === [], '存在しないURLのパスは空配列を返す');

$check($menu->PathToId(2) === [$data, $child], 'メニュー項目IDまでのパスを返す');
$check($menu->PathToId(0) === [], '0以下のメニュー項目IDのパスは空配列を返す');
$check(
	$menu->PathToObject(MenuItemType::Custom, 'custom', 0) === [$data, $child],
	'リンク先オブジェクトまでのパスを返す',
);

$GLOBALS['menuItems'] = [
	new WP_Post(ID: 100, title: '親', url: '/parent/', object: 'custom'),
	new WP_Post(ID: 101, menu_item_parent: 100, title: '子', url: '/child/', object: 'custom'),
];
$nodeFactory = ProjectMenu::class;
$derivedTree = MenuGet::ByLocation('primary', $nodeFactory);
$check($derivedTree->Nodes[0] instanceof ProjectMenuNodeData, '指定Factoryでルートの派生型を生成する');
$check($derivedTree->Nodes[0]->Children[0]->Label === '派生メニュー101', '指定Factoryを子ノードへ引き継ぐ');
$check(
	$derivedTree->FindById(101) instanceof ProjectMenuNodeData,
	'派生ツリーの検索結果も派生型を維持する',
);
$derivedPath = $derivedTree->PathToId(101);
$check(
	count($derivedPath) === 2
	&& $derivedPath[0] instanceof ProjectMenuNodeData
	&& $derivedPath[1] instanceof ProjectMenuNodeData,
	'派生ツリーのパスも全階層で派生型を維持する',
);
$baseTree = MenuGet::ByLocation('primary');
$check(
	$baseTree->Nodes[0] instanceof MenuNodeData
	&& ! $baseTree->Nodes[0] instanceof ProjectMenuNodeData,
	'Factory省略時は純粋なMenuNodeDataを返す',
);

echo "Menu node data: {$checks} checks passed.\n";
