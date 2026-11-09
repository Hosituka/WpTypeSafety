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
require_once __DIR__ . '/../Wp/Get/WpGetMenuNode.php';

use WpTypeSafety\Wp\Data\MenuItemType;
use WpTypeSafety\Wp\Data\MenuNodeData;
use WpTypeSafety\Wp\Data\MenuNodeMembers;
use WpTypeSafety\Wp\Get\MenuNodeFactory;
use WpTypeSafety\Wp\Get\WpGetMenuNode;

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
$check(
	$data->GetLink()->Url === '/parent/'
	&& $data->GetLink()->Title === '変更前'
	&& $data->GetLink()->Target === '',
	'リンクへ変換する',
);
$check(! $data->IsNewTab(), 'Target省略時は同じタブとして扱う');
$check($data->GetThumbnail() === null, 'Resolver省略時は画像なし');

$derived = new ProjectMenuNodeData($members, '案件固有');
$check($derived->Title === '変更後' && $derived->Label === '案件固有', 'Membersを派生Dataへ渡せる');

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
$factoryMembers = MenuNodeFactory::Members($item, [$child]);
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

$factoryData = MenuNodeFactory::From($item, [$child]);
$check($factoryData->Title === '会社案内' && $factoryData->Children === [$child], '公開FactoryからMenuNodeDataを生成する');
$check($factoryData->IsNewTab() && $factoryData->GetLink()->Target === '_blank', '新規タブ判定とLinkDataへTargetを引き継ぐ');

$invalidLinkAttributes = MenuNodeFactory::Members(new WP_Post(
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

$check(WpGetMenuNode::FindById([$data], 2) === $child, 'メニュー項目IDから深い子ノードを検索する');
$check(WpGetMenuNode::FindById([$data], 0) === null, '0以下のメニュー項目IDは検索しない');
$check(WpGetMenuNode::FindById([$data], 999) === null, '存在しないメニュー項目IDはnullを返す');

$check(
	WpGetMenuNode::FindByObject([$data], MenuItemType::PostType, 'page', 10) === $data,
	'リンク先オブジェクトからノードを検索する',
);
$check(
	WpGetMenuNode::FindByObject([$data], MenuItemType::Taxonomy, 'page', 10) === null,
	'ObjectIdが同じでも種別が異なるノードを返さない',
);
$check(WpGetMenuNode::FindByPageId([$data], 10) === $data, '固定ページ検索を汎用検索へ委譲する');

$foundByUrl = WpGetMenuNode::FindByUrl([$data], '/child/');
$check($foundByUrl === $child, 'URLから深い子ノードを検索する');
$check(WpGetMenuNode::FindByUrl([$data], '') === null, '空URLは検索しない');
$check(WpGetMenuNode::FindByUrl([$data], '/missing/') === null, '存在しないURLはnullを返す');

$pathToUrl = WpGetMenuNode::PathToUrl([$data], '/child/');
$check($pathToUrl === [$data, $child], 'URLまでのパスをルートから対象自身の順で返す');
$check(WpGetMenuNode::PathToUrl([$data], '/parent/') === [$data], 'ルート自身へのパスは1件を返す');
$check(WpGetMenuNode::PathToUrl([$data], '/missing/') === [], '存在しないURLのパスは空配列を返す');

$check(WpGetMenuNode::PathToId([$data], 2) === [$data, $child], 'メニュー項目IDまでのパスを返す');
$check(WpGetMenuNode::PathToId([$data], 0) === [], '0以下のメニュー項目IDのパスは空配列を返す');
$check(
	WpGetMenuNode::PathToObject([$data], MenuItemType::Custom, 'custom', 0) === [$data, $child],
	'リンク先オブジェクトまでのパスを返す',
);

echo "Menu node data: {$checks} checks passed.\n";
