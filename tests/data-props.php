<?php
declare(strict_types=1);

// WordPressなしで構築、コピー、公開API・拡張契約を検証する。
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/fixtures/post-props.php';
require_once __DIR__ . '/fixtures/data-props.php';

use WpTypeSafety\Wp\Data\PostProps;
use WpTypeSafety\Wp\Data\TermProps;
use WpTypeSafety\Wp\Data\CommentProps;
use WpTypeSafety\Wp\Data\UserProps;
use WpTypeSafety\Wp\Data\MenuNodeProps;
use WpTypeSafety\Wp\Data\PostTypeProps;
use WpTypeSafety\Wp\Data\TaxonomyProps;
use WpTypeSafety\Wp\Data\MenuItemType;

$cases = [
	['Post', 'PostData', new PostProps(1, '/', 'title', 'raw'), 'Id'],
	['Term', 'TermData', new TermProps(1, 'name', 'slug', 'area', '/'), 'Id'],
	['Comment', 'CommentData', new CommentProps(1, 2, 0, 0, 'guest', '', '/', 'content', 'comment', 'approved'), 'Id'],
	['User', 'WpUserData', new UserProps(1, 'name'), 'Id'],
	['MenuNode', 'MenuNodeData', new MenuNodeProps(1, 0, 'title', '/', '', [], 0, MenuItemType::Custom, 'custom', 0), 'Id'],
	['PostType', 'PostTypeData', new PostTypeProps('post', 'Posts', 'Post'), 'Name'],
	['Taxonomy', 'TaxonomyData', new TaxonomyProps('category', 'Categories', 'Category'), 'Name'],
];
$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok)
	{throw new RuntimeException($label);}
	$checks++;
};
foreach ($cases as [$prefix, $dataName, $props, $field]) {
	$dataClass = 'WpTypeSafety\\Wp\\Data\\' . $dataName;
	$getClass = 'WpTypeSafety\\Wp\\Get\\' . $prefix . 'Get';
	$data = ('Test' . $prefix . 'Data')::FromProps($props);
	$original = $data->$field;
	$props->$field = is_int($original) ? 99 : 'changed';
	$check($data->$field === $original, $prefix . ': 構築時の値をコピー');
	$check(! (new ReflectionClass($props))->isReadOnly(), $prefix . ': Propsは変更可能');
	$check(! (new ReflectionClass($data))->isReadOnly(), $prefix . ': Dataは継承先の状態を制限しない');
	foreach (get_object_vars($props) as $value) {
		$check(! $value instanceof Closure, $prefix . ': Propsは値のみ');
	}
	foreach ((new ReflectionClass($data))->getProperties() as $property) {
		$check($property->isReadOnly(), $prefix . ': 共通プロパティはreadonly');
		$check(! str_contains((string) $property->getType(), 'Closure'), $prefix . ': DataにResolverなし');
	}
	$check(!method_exists($getClass, 'Props') && ! method_exists($getClass, 'Members') && ! method_exists($getClass, 'Property'), $prefix . ': 投稿だけProps公開入口を廃止');
	$parameters = (new ReflectionClass($data))->getConstructor()->getParameters();
	$check(count($parameters) === 1 && $parameters[0]->getName() === 'props', $prefix . ': コンストラクターはPropsのみ');
}
foreach (['PostProperty', 'PostMembers', 'TermMembers', 'CommentMembers', 'UserMembers', 'MenuNodeMembers', 'PostTypeMembers', 'TaxonomyMembers'] as $old) {
	$check(! class_exists('WpTypeSafety\\Wp\\Data\\' . $old), '旧構築用クラスを廃止: ' . $old);
}
foreach (['PostData' => 'createRelatedPost', 'TermData' => 'createRelatedTerm', 'CommentData' => 'createRelatedComment'] as $name => $hook) {
	$check((new ReflectionMethod('WpTypeSafety\\Wp\\Data\\' . $name, $hook))->isProtected(), '生成フックはprotected');
}
$check(! method_exists('WpTypeSafety\\Wp\\Data\\MenuNodeData', 'createRelatedNode'), 'メニューの子は取得済みの値');
$check(count((new ReflectionMethod('WpTypeSafety\\Wp\\Data\\CommentData', 'From'))->getParameters()) === 2, 'CommentGetの関連Factory廃止');
$check(count((new ReflectionMethod('WpTypeSafety\\Wp\\Data\\TermData', 'From'))->getParameters()) === 1, 'TermGetの関連Factory廃止');
echo "Data Props: {$checks} checks passed.\n";
