<?php
declare(strict_types=1);

// WordPress・DBなしで、共通値の不変性と派生クラスの可変状態を検証する。
require_once __DIR__ . '/../autoload.php';

use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\PostProps;
use WpTypeSafety\Wp\Data\TermData;
use WpTypeSafety\Wp\Data\TermProps;
use WpTypeSafety\Wp\Data\CommentData;
use WpTypeSafety\Wp\Data\CommentProps;
use WpTypeSafety\Wp\Data\WpUserData;
use WpTypeSafety\Wp\Data\UserProps;
use WpTypeSafety\Wp\Data\MenuNodeData;
use WpTypeSafety\Wp\Data\MenuNodeProps;
use WpTypeSafety\Wp\Data\MenuItemType;
use WpTypeSafety\Wp\Data\PostTypeData;
use WpTypeSafety\Wp\Data\PostTypeProps;
use WpTypeSafety\Wp\Data\TaxonomyData;
use WpTypeSafety\Wp\Data\TaxonomyProps;

class EditablePost extends PostData
{
	public ?string $PendingTitle = null;
	public readonly string $Label;
	public int $Revision = 0;
	private array $cache = [];

	public function __construct(PostProps $props)
	{
		parent::__construct($props);
		$this->Label = 'fixed label';
	}

	public function Remember(string $value): void { $this->cache['value'] = $value; }
	public function Recall(): ?string { return $this->cache['value'] ?? null; }
}
class EditableTerm extends TermData { public int $Revision = 0; }
class EditableComment extends CommentData { public int $Revision = 0; }
class EditableUser extends WpUserData { public int $Revision = 0; }
class EditableMenuNode extends MenuNodeData { public int $Revision = 0; }
class EditablePostType extends PostTypeData { public int $Revision = 0; }
class EditableTaxonomy extends TaxonomyData { public int $Revision = 0; }

$cases = [
	[new EditablePost(new PostProps(1, '/', 'title', 'raw')), 'Id', 2],
	[new EditableTerm(new TermProps(1, 'name', 'slug', 'area', '/')), 'Id', 2],
	[new EditableComment(new CommentProps(1, 2, 0, 0, 'guest', '', '/', 'body', 'comment', 'approved')), 'Id', 2],
	[new EditableUser(new UserProps(1, 'name')), 'Id', 2],
	[new EditableMenuNode(new MenuNodeProps(1, 0, 'title', '/', '', [], 0, MenuItemType::Custom, 'custom', 0)), 'Id', 2],
	[new EditablePostType(new PostTypeProps('post', 'Posts', 'Post')), 'Name', 'other'],
	[new EditableTaxonomy(new TaxonomyProps('category', 'Categories', 'Category')), 'Name', 'other'],
];
$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok)
	{throw new RuntimeException($label);}
	$checks++;
};
foreach ($cases as [$data, $field, $replacement]) {
	$original = $data->$field;
	$data->Revision = 1;
	$data->Revision++;
	$check($data->Revision === 2, '派生クラスの追加状態は変更可能');
	$check(! (new ReflectionClass($data))->isReadOnly(), '派生クラスにreadonlyを強制しない');
	try {
		$data->$field = $replacement;
		throw new RuntimeException('共通値を書き換えた');
	} catch (Error) {
		$check($data->$field === $original, '共通値はreadonlyを維持');
	}
	try {
		unset($data->$field);
		throw new RuntimeException('共通値をunsetした');
	} catch (Error) {
		$check($data->$field === $original, '共通値はunsetも不可');
	}
}
$post = $cases[0][0];
$post->PendingTitle = 'draft';
$post->PendingTitle = 'edited';
$post->Remember('first');
$post->Remember('second');
$check($post->PendingTitle === 'edited' && $post->Title === 'title', '編集状態と共通スナップショットは独立');
$check($post->Recall() === 'second', '派生クラスはprivateキャッシュも持てる');
try {
	$post->Label = 'changed';
	throw new RuntimeException('派生readonly値を書き換えた');
} catch (Error) {
	$check($post->Label === 'fixed label', '派生プロパティはreadonlyも選べる');
}
$menu = $cases[4][0];
try {
	$menu->Children[] = $menu;
	throw new RuntimeException('共通配列を変更した');
} catch (Error) {
	$check($menu->Children === [], '共通配列の追加も不可');
}
echo "Data extensibility: {$checks} checks passed.\n";
