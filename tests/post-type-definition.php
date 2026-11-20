<?php
declare(strict_types=1);

// WordPress APIスタブ。DB・実際の投稿タイプ登録は変更しない。
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/fixtures/post-props.php';

use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\PostProps;
use WpTypeSafety\Wp\Data\PostTypeData;
use WpTypeSafety\Wp\Data\PostTypeProps;
use WpTypeSafety\Wp\Get\PostGet;

class WP_Post
{
	public int $post_author = 1;
	public string $post_content = '';
	public string $post_status = 'publish';
	public string $post_name = 'sample';
	public string $post_password = '';
	public function __construct(public int $ID = 1, public string $post_type = 'cases') {}
}
class WP_Post_Type
{
	public string $label = '制作実績';
	public string $description = '';
	public bool $public = true;
	public bool $publicly_queryable = true;
	public bool $hierarchical = false;
	public bool $has_archive = true;
	public function __construct(public string $name) {}
}
class ProjectPost extends PostData { use TestPostProps; }
function get_post_type_object(string $name): mixed {
	$GLOBALS['definitionRequests'][] = $name;
	return $GLOBALS['definitions'][$name] ?? null;
}
function get_post_type_archive_link(string $name): string { return 'https://example.com/' . $name . '/'; }
function get_object_taxonomies(string $name, string $output): array { return ['category']; }
function get_permalink(WP_Post $post): string { return 'https://example.com/' . $post->ID; }
function get_post_datetime(WP_Post $post, string $field = 'date'): false { return false; }
function get_the_title(WP_Post $post): string { return '投稿'; }
function is_post_publicly_viewable(WP_Post $post): bool { return true; }

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok)
	{throw new RuntimeException($label);}
	$checks++;
};
$plain = TestPostData::FromProps(new PostProps(1, '/', '', ''));
$check($plain->PostTypeName === '' && $plain->GetPostType() === null, '直接構築の既定値');
$check(! property_exists($plain, 'PostType'), '旧プロパティを残さない');
$check((new ReflectionProperty(PostData::class, 'PostTypeName'))->isReadOnly(), '登録名はreadonly');
$GLOBALS['definitionRequests'] = [];
$GLOBALS['definitions'] = ['cases' => new WP_Post_Type('cases')];
$data = PostData::From(new WP_Post());
$check($GLOBALS['definitionRequests'] === [], 'WordPress投稿の変換でも登録定義は遅延取得');
$check($data->GetPostType()?->Name === 'cases' && $GLOBALS['definitionRequests'] === ['cases'], '登録定義をData化');
$check($data->GetPostType()?->GetTaxonomyNames() === ['category'], '取得した定義の関連APIを利用できる');
unset($GLOBALS['definitions']['cases']);
$check($data->GetPostType() === null && $data->PostTypeName === 'cases', '未登録でも保持済みの名前は残る');
$GLOBALS['definitions']['cases'] = new stdClass();
$check($data->GetPostType() === null, '不正なWordPress登録定義はnull');
$GLOBALS['definitions']['cases'] = new WP_Post_Type('cases');
$check($data->GetPostType() instanceof PostTypeData, '再登録後は取得できる');
$adjusted = \WpTypeSafety\Wp\Internal\DtoFactory::PostPropsFromWpPost(new WP_Post());
$adjusted->PostTypeName = 'other';
$GLOBALS['definitions']['other'] = new WP_Post_Type('other');
$check(ProjectPost::FromProps($adjusted)->GetPostType()?->Name === 'other', 'Propsで調整した名前を使用');
$copy = new PostProps(1, '/', '', '', PostTypeName: 'cases');
$snapshot = ProjectPost::FromProps($copy);
$copy->PostTypeName = 'other';
$check($snapshot->GetPostType()?->Name === 'cases', '構築後のProperty変更は影響しない');
class OverriddenPost extends PostData
{
	use TestPostProps;
	public function GetPostType(): ?PostTypeData { return null; }
}
$check(OverriddenPost::FromProps($copy)->GetPostType() === null, '案件側で取得動作を上書き');
echo "Post type definition: {$checks} checks passed.\n";
