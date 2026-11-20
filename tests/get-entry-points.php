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
foreach (['Post', 'Term', 'User', 'Comment', 'Menu', 'Attachment', 'PostType', 'Taxonomy'] as $name) {
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
foreach (['Current', 'FromPost', 'Roots', 'Deepest', 'Shallowest'] as $name) {
	$check(method_exists('WpTypeSafety\\Wp\\Get\\TermGet', $name), 'TermGetへの統合');
}
foreach (['Current', 'Loop'] as $name) {
	$check(method_exists('WpTypeSafety\\Wp\\Get\\PostGet', $name), '投稿コンテキスト取得API');
}
$check(method_exists('WpTypeSafety\\Wp\\Get\\UserGet', 'LoggedIn'), 'ログインユーザー取得API');
$check(method_exists('WpTypeSafety\\Wp\\Get\\UserGet', 'Current'), '著者アーカイブの表示対象取得API');
$check(!class_exists('WpTypeSafety\\Wp\\Get\\MenuNodeGet'), 'ノード変換はData::Fromへ集約');
foreach (['ById', 'BySlug', 'ByLocation'] as $name) {
	$check(method_exists('WpTypeSafety\\Wp\\Get\\MenuGet', $name), 'メニュー全体の取得入口');
}
foreach (['FindCurrent', 'FindByPageId', 'FindById', 'FindByObject', 'FindByUrl', 'PathToId', 'PathToObject', 'PathToUrl'] as $name) {
	$check(method_exists('WpTypeSafety\\Wp\\Data\\MenuData', $name), 'メニュー単位の探索');
	$check(!method_exists('WpTypeSafety\\Wp\\Get\\MenuNodeGet', $name), 'ノード変換から探索を分離');
}
$check(!method_exists('WpTypeSafety\\Wp\\Get\\MenuNodeGet', 'TreeFromLocation'), '旧ツリー取得APIを廃止');
$check(!method_exists('WpTypeSafety\\Wp\\Get\\MenuNodeGet', 'Current'), 'Currentという曖昧な取得入口は設けない');
foreach (['Post', 'Term', 'User', 'Comment'] as $name) {
	$check(method_exists('WpTypeSafety\\Wp\\Get\\' . $name . 'Get', 'ById'), '対象別のID取得');
}
$check(!method_exists('WpTypeSafety\\Wp\\Get\\PostGet', 'FromId'), 'PostGet::FromIdはByIdへ改名');
$check(method_exists('WpTypeSafety\\Wp\\Get\\PostGet', 'ByPath'), '投稿のパス取得');
foreach (['BySlug', 'ByEmail', 'ByLogin'] as $name) {
	$check(method_exists('WpTypeSafety\\Wp\\Get\\UserGet', $name), 'ユーザーの識別子取得');
}
echo "Get entry points: {$checks} checks passed.\n";
