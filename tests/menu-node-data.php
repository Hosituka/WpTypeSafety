<?php

declare(strict_types=1);

// WordPress・DBに接続せず、MenuNodeDataのMembersとFactoryを検証する。
if (PHP_SAPI !== 'cli') {
	exit;
}

require_once __DIR__ . '/../Core/Data/ImgData.php';
require_once __DIR__ . '/../Core/Data/LinkData.php';
require_once __DIR__ . '/../Wp/Data/MenuItemType.php';
require_once __DIR__ . '/../Wp/Data/MenuNodeMembers.php';
require_once __DIR__ . '/../Wp/Data/MenuNodeData.php';
require_once __DIR__ . '/../Wp/Internal/MenuAdapter.php';
require_once __DIR__ . '/../Wp/Get/MenuNodeFactory.php';

use WpTypeSafety\Wp\Data\MenuItemType;
use WpTypeSafety\Wp\Data\MenuNodeData;
use WpTypeSafety\Wp\Data\MenuNodeMembers;
use WpTypeSafety\Wp\Get\MenuNodeFactory;

class WP_Post
{
	/** @param list<mixed> $classes */
	public function __construct(
		public int $ID,
		public int|string $menu_item_parent = 0,
		public mixed $title = '',
		public mixed $url = '',
		public mixed $description = '',
		public array $classes = [],
		public int|string $menu_order = 0,
		public mixed $type = '',
		public mixed $object = '',
		public int|string $object_id = 0,
	) {}
}

readonly class ProjectMenuNodeData extends MenuNodeData
{
	public function __construct(
		MenuNodeMembers $members,
		public string $Label,
	) {
		parent::__construct($members);
	}
}

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};

$child = new MenuNodeData(new MenuNodeMembers(
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

$members = new MenuNodeMembers(
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
$data = new MenuNodeData($members);
$members->Title = '変更後';
$members->Children = [];

$check($data->Title === '変更前', 'DataはMembersを構築時の値として保持する');
$check($data->Children === [$child] && $data->HasChildren(), '子ノードを保持する');
$check($data->GetLink()->Url === '/parent/' && $data->GetLink()->Title === '変更前', 'リンクへ変換する');
$check($data->GetThumbnail() === null, 'Resolver省略時は画像なし');

$derived = new ProjectMenuNodeData($members, '案件固有');
$check($derived->Title === '変更後' && $derived->Label === '案件固有', 'Membersを派生Dataへ渡せる');

$item = new WP_Post(
	ID: 20,
	menu_item_parent: '5',
	title: '会社案内',
	url: '/about/',
	description: '会社案内です',
	classes: ['menu-item', false, 'about'],
	menu_order: '3',
	type: 'post_type',
	object: 'page',
	object_id: '30',
);
$factoryMembers = MenuNodeFactory::Members($item, [$child]);
$check($factoryMembers->Id === 20 && $factoryMembers->ParentId === 5, 'FactoryがIDを正規化する');
$check($factoryMembers->Classes === ['menu-item', 'about'], 'Factoryが文字列クラスだけを保持する');
$check($factoryMembers->Type === MenuItemType::PostType, 'Factoryがメニュー種別をenumへ変換する');
$check($factoryMembers->Children === [$child], 'Factoryが構築済みの子ノードを受け取る');

$factoryData = MenuNodeFactory::From($item, [$child]);
$check($factoryData->Title === '会社案内' && $factoryData->Children === [$child], '公開FactoryからMenuNodeDataを生成する');

echo "Menu node data: {$checks} checks passed.\n";
