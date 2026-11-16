<?php
declare(strict_types=1);

// WordPress・DBを起動せず、定義DataとAPI接続をスタブで検証する。
if (PHP_SAPI !== 'cli')
{exit;}

require_once __DIR__ . '/../autoload.php';

use WpTypeSafety\Wp\Data\PostTypeData;
use WpTypeSafety\Wp\Data\PostTypeMembers;
use WpTypeSafety\Wp\Data\TaxonomyData;
use WpTypeSafety\Wp\Data\TaxonomyMembers;
use WpTypeSafety\Wp\Get\PostTypeGet;
use WpTypeSafety\Wp\Get\TaxonomyGet;

class WP_Post_Type
{
	public mixed $label = '制作実績';
	public mixed $labels;
	public mixed $description = '投稿タイプの説明';
	public mixed $public = true;
	public mixed $publicly_queryable = false;
	public mixed $hierarchical = false;
	public mixed $has_archive = 'works';
	public function __construct(public string $name)
	{
		$this->labels = (object) ['singular_name' => '制作実績1件'];
	}
}

class WP_Taxonomy
{
	public mixed $label = '地域';
	public mixed $labels;
	public mixed $description = '分類の説明';
	public mixed $public = false;
	public mixed $publicly_queryable = true;
	public mixed $hierarchical = true;
	public mixed $object_type = [5 => 'cases', 9 => 'post'];
	public function __construct(public string $name)
	{
		$this->labels = (object) ['singular_name' => '地域1件'];
	}
}

function get_post_type_object(string $name): mixed
{
	$GLOBALS['postTypeRequests'][] = $name;
	return $GLOBALS['postTypes'][$name] ?? null;
}
function get_taxonomy(string $name): mixed
{
	$GLOBALS['taxonomyRequests'][] = $name;
	return $GLOBALS['taxonomies'][$name] ?? false;
}
function get_post_type_archive_link(string $name): mixed
{
	$GLOBALS['archiveRequests'][] = $name;
	return $GLOBALS['archives'][$name] ?? false;
}
function get_object_taxonomies(string $name, string $output): mixed
{
	$GLOBALS['taxonomyNameRequests'][] = [$name, $output];
	return $GLOBALS['taxonomyNames'][$name] ?? [];
}

readonly class ProjectPostTypeData extends PostTypeData
{
	public function __construct(PostTypeMembers $members, public string $Extra)
	{
		parent::__construct($members);
	}
}
readonly class ProjectTaxonomyData extends TaxonomyData
{
	public function __construct(TaxonomyMembers $members, public string $Extra)
	{
		parent::__construct($members);
	}
}

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok)
	{throw new RuntimeException($label);}
	$checks++;
};

// Data単独で構築でき、WordPress関数を呼ばない。
$postMembers = new PostTypeMembers('cases', '実績', '実績1件');
$postData = new ProjectPostTypeData($postMembers, '追加値');
$postMembers->Label = '変更後';
$check($postData->Label === '実績' && $postData->Extra === '追加値', '派生Dataと値のコピー');
$check($postData->GetTaxonomyNames() === [], 'Resolver省略時は空配列');
$check($postData->ArchiveUrl === '' && ! $postData->HasArchive && ! $postData->IsPublic, '既定値');
$taxMembers = new TaxonomyMembers('area', '地域', '地域', ObjectTypes: ['cases']);
$taxonomyData = new ProjectTaxonomyData($taxMembers, '追加分類');
$taxMembers->ObjectTypes[] = 'post';
$check($taxonomyData->ObjectTypes === ['cases'] && $taxonomyData->Extra === '追加分類', '配列のコピーと派生Data');
$check($taxonomyData->HasObjectType('cases') && ! $taxonomyData->HasObjectType('post'), '対象投稿タイプ判定');
$check(! $taxonomyData->HasObjectType('CASES'), '投稿タイプ名は完全一致');
$check(! isset($GLOBALS['archiveRequests'], $GLOBALS['taxonomyNameRequests']), 'Data単独ではWPを呼ばない');

$calls = 0;
$postMembers->TaxonomyNamesResolver = static function () use (&$calls): array {
	$calls++;
	return $calls === 1 ? ['area'] : ['area', 'genre'];
};
$resolvedData = new PostTypeData($postMembers);
$check($calls === 0, '構築時はResolver未実行');
$check($resolvedData->GetTaxonomyNames() === ['area'], '注入Resolver');
$check($resolvedData->GetTaxonomyNames() === ['area', 'genre'] && $calls === 2, '非キャッシュ');
foreach ([null, 'area', [3 => 'area'], [12]] as $badValue) {
	$badMembers = new PostTypeMembers('cases', '', '', TaxonomyNamesResolver: static fn () => $badValue);
	$badData = new PostTypeData($badMembers);
	try {
		$badData->GetTaxonomyNames();
		throw new RuntimeException('不正なResolverを受け入れた');
	} catch (UnexpectedValueException) {
		$checks++;
	}
}
$throwingData = new PostTypeData(new PostTypeMembers('cases', '', '', TaxonomyNamesResolver: static function (): array {
	throw new LogicException('resolver failure');
}));
try {
	$throwingData->GetTaxonomyNames();
	throw new RuntimeException('例外を握り潰した');
} catch (LogicException $error) {
	$check($error->getMessage() === 'resolver failure', 'Resolver例外の伝播');
}

$nativePost = new WP_Post_Type('cases');
$nativeTax = new WP_Taxonomy('area');
$GLOBALS['postTypes'] = ['cases' => $nativePost];
$GLOBALS['taxonomies'] = ['area' => $nativeTax];
$GLOBALS['archives'] = ['cases' => 'https://example.com/works/'];
$GLOBALS['taxonomyNames'] = ['cases' => ['area']];
$GLOBALS['taxonomyNameRequests'] = [];
$fromPost = PostTypeGet::FromName('cases');
$check($fromPost?->Name === 'cases' && $fromPost->SingularLabel === '制作実績1件', '投稿タイプの名前検索');
$check($fromPost->Description === '投稿タイプの説明' && $fromPost->Label === '制作実績', '投稿タイプの表示値');
$check($fromPost->IsPublic && ! $fromPost->IsPubliclyQueryable && ! $fromPost->IsHierarchical, '投稿タイプ設定');
$check($fromPost->HasArchive && $fromPost->ArchiveUrl === 'https://example.com/works/', '文字列has_archiveとURL');
$check($GLOBALS['taxonomyNameRequests'] === [], 'Factory生成時も関連取得は遅延');
$check($fromPost->GetTaxonomyNames() === ['area'], '関連タクソノミー名');
$GLOBALS['taxonomyNames']['cases'] = [2 => 'area', 4 => false, 8 => 'genre', 9 => '', 10 => 123];
$check($fromPost->GetTaxonomyNames() === ['area', 'genre'], '最新の関連と要素の正規化・再採番');
$check($GLOBALS['taxonomyNameRequests'] === [['cases', 'names'], ['cases', 'names']], 'WPへの引数');
$GLOBALS['taxonomyNames']['cases'] = false;
$check($fromPost->GetTaxonomyNames() === [], '関連取得の型違いは空配列');
$nativePost->label = '変更後';
$check($fromPost->Label === '制作実績', '元WPオブジェクトの変更から独立');
$factoryPostMembers = PostTypeGet::Members($nativePost);
$factoryPostMembers->Label = '案件表示名';
$derivedPost = new ProjectPostTypeData($factoryPostMembers, '派生');
$check($derivedPost->Label === '案件表示名', 'Factory Membersの調整');

foreach ([false, true, 'works'] as $archiveFlag) {
	$nativePost->has_archive = $archiveFlag;
	$data = PostTypeGet::From($nativePost);
	$check($data->HasArchive === (bool) $archiveFlag, 'has_archiveのbool化');
}
foreach ([false, '', 'javascript:alert(1)', '/works/', 123] as $url) {
	$GLOBALS['archives']['cases'] = $url;
	$data = PostTypeGet::From($nativePost);
	$check($data->ArchiveUrl === '', '不正・取得失敗のURLは空文字');
}
$standardPost = new WP_Post_Type('post');
$standardPost->has_archive = false;
$GLOBALS['archives']['post'] = 'https://example.com/news/';
$data = PostTypeGet::From($standardPost);
$check(! $data->HasArchive && $data->ArchiveUrl === 'https://example.com/news/', '標準投稿の一覧URLはhas_archiveと独立');
$nativePost->labels = null;
$nativePost->description = false;
$data = PostTypeGet::From($nativePost);
$check($data->SingularLabel === '変更後' && $data->Description === '', 'ラベル欠損と説明型違い');

$fromTax = TaxonomyGet::FromName('area');
$check($fromTax?->Name === 'area' && $fromTax->Label === '地域' && $fromTax->SingularLabel === '地域1件', 'タクソノミー名前検索');
$check($fromTax->Description === '分類の説明' && $fromTax->ObjectTypes === ['cases', 'post'], 'タクソノミー値の正規化');
$check(! $fromTax->IsPublic && $fromTax->IsPubliclyQueryable && $fromTax->IsHierarchical, 'タクソノミー設定');
$nativeTax->object_type = [4 => 'cases', 9 => false, 11 => '', 12 => 'post'];
$nativeTax->labels = (object) [];
$nativeTax->description = [];
$members = TaxonomyGet::Members($nativeTax);
$check($members->ObjectTypes === ['cases', 'post'] && $members->Description === '' && $members->SingularLabel === '地域', '分類欠損値・要素型違い');
$members->Label = '案件分類';
$derivedTax = new ProjectTaxonomyData($members, '派生');
$check($derivedTax->Label === '案件分類' && $derivedTax->HasObjectType('cases'), '分類Factoryから派生Data');
$nativeTax->object_type = null;
$data = TaxonomyGet::From($nativeTax);
$check($data->ObjectTypes === [] && ! $data->HasObjectType('cases'), '対象投稿タイプの型違い');

foreach ([null, false, new stdClass()] as $missing) {
	$GLOBALS['postTypes']['missing'] = $missing;
	$GLOBALS['taxonomies']['missing'] = $missing;
	$check(PostTypeGet::FromName('missing') === null, '投稿タイプ未登録・型違い');
	$check(TaxonomyGet::FromName('missing') === null, 'タクソノミー未登録・型違い');
}
$before = count($GLOBALS['postTypeRequests']) + count($GLOBALS['taxonomyRequests']);
$check(PostTypeGet::FromName('') === null && TaxonomyGet::FromName('') === null, '空名はnull');
$check(count($GLOBALS['postTypeRequests']) + count($GLOBALS['taxonomyRequests']) === $before, '空名は検索しない');

echo "Definitions: {$checks} checks passed.\n";
