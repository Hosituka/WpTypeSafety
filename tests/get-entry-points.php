<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
	exit;
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
foreach (['Post', 'Term', 'User', 'Comment', 'MenuNode', 'Attachment', 'PostType', 'Taxonomy'] as $name) {
	$class = 'WpTypeSafety\\Wp\\Get\\' . $name . 'Get';
	$check(class_exists($class), 'Get入口のautoload: ' . $class);
	$check(! class_exists('WpTypeSafety\\Wp\\Get\\' . $name . 'Factory'), '旧Factoryを廃止');
}
foreach (['WpGetTerm', 'WpGetMenuNode', 'WpGet'] as $name) {
	$check(! class_exists('WpTypeSafety\\Wp\\Get\\' . $name), '旧WpGet入口を廃止');
}
foreach (['Current', 'CurrentPageNumber', 'MaxPageCount', 'FoundPostCount'] as $name) {
	$check(method_exists('WpTypeSafety\\Wp\\Get\\MainQueryGet', $name), 'メインクエリ取得API');
}
foreach (['PostQuery', 'TermQuery', 'UserQuery', 'CommentQuery', 'MetaGet'] as $name) {
	$check(class_exists('WpTypeSafety\\Wp\\Get\\' . $name), '既存のQuery・MetaGetは維持');
}
foreach (['PostFields', 'PostsFields', 'TermFields', 'TermsFields', 'UserFields', 'UsersFields'] as $name) {
	$check(method_exists(AcfGet::class, $name), '参照先Fields API');
}
foreach (['PostGet', 'PostGets', 'TermGet', 'TermGets', 'UserGet', 'UserGets'] as $name) {
	$check(! method_exists(AcfGet::class, $name), '旧参照先Get APIは廃止');
}
foreach (['Props', 'From', 'Current', 'FromPost', 'Roots', 'Deepest', 'Shallowest'] as $name) {
	$check(method_exists('WpTypeSafety\\Wp\\Get\\TermGet', $name), 'TermGetへの統合');
}
foreach (['Current', 'Loop'] as $name) {
	$check(method_exists('WpTypeSafety\\Wp\\Get\\PostGet', $name), '投稿コンテキスト取得API');
}
$check(method_exists('WpTypeSafety\\Wp\\Get\\UserGet', 'LoggedIn'), 'ログインユーザー取得API');
$check(method_exists('WpTypeSafety\\Wp\\Get\\UserGet', 'Current'), '著者アーカイブの表示対象取得API');
foreach (['Props', 'From', 'TreeFromLocation', 'FindByPageId', 'FindById', 'FindByObject', 'FindByUrl', 'PathToId', 'PathToObject', 'PathToUrl'] as $name) {
	$check(method_exists('WpTypeSafety\\Wp\\Get\\MenuNodeGet', $name), 'MenuNodeGetへの統合');
}
echo "Get entry points: {$checks} checks passed.\n";
