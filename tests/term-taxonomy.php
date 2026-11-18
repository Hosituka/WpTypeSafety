<?php
declare(strict_types=1);

// WordPress APIスタブ。DBや実際のタクソノミー登録は変更しない。
require_once __DIR__ . '/../autoload.php';

use WpTypeSafety\Wp\Data\TermData;
use WpTypeSafety\Wp\Data\TermProps;
use WpTypeSafety\Wp\Data\TaxonomyData;
use WpTypeSafety\Wp\Data\TaxonomyProps;
use WpTypeSafety\Wp\Get\TermGet;

class WP_Term
{
	public function __construct(
		public int $term_id = 1,
		public string $name = '分類',
		public string $slug = 'sample',
		public string $taxonomy = 'area',
		public int $parent = 0,
		public string $description = '',
	) {}
}
class WP_Taxonomy
{
	public string $label = 'エリア';
	public string $description = '';
	public array $object_type = ['cases'];
	public bool $public = true;
	public bool $publicly_queryable = true;
	public bool $hierarchical = true;
	public function __construct(public string $name) {}
}
class ProjectTerm extends TermData {}
function get_term_link(WP_Term $term): string { return '/area/' . $term->slug . '/'; }
function is_wp_error(mixed $value): bool { return false; }
function get_taxonomy(string $name): mixed {
	$GLOBALS['taxonomyRequests'][] = $name;
	return $GLOBALS['taxonomies'][$name] ?? false;
}

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok)
	{throw new RuntimeException($label);}
	$checks++;
};
$plain = new TermData(new TermProps(1, '分類', 'sample', TaxonomyName: 'area', Url: '/'));
$check($plain->TaxonomyName === 'area' && $plain->GetTaxonomy() === null, '直接構築は登録名だけでも利用可能');
$check(! property_exists($plain, 'Taxonomy'), '旧Taxonomyプロパティを残さない');
$check((new ReflectionProperty(TermData::class, 'TaxonomyName'))->isReadOnly(), '登録名はreadonly');
$props = new TermProps(1, '分類', 'sample', 'area', '/');
$direct = new ProjectTerm($props);
$props->TaxonomyName = 'changed';
$check($direct->TaxonomyName === 'area', '構築時に登録名をコピー');
$empty = new TermData(new TermProps(1, '', '', '', '/'));
$check($empty->GetTaxonomy() === null, '空の登録名では取得しない');
$GLOBALS['taxonomyRequests'] = [];
$GLOBALS['taxonomies'] = ['area' => new WP_Taxonomy('area')];
$native = new WP_Term();
$data = TermGet::From($native);
$check($data instanceof TermData && $GLOBALS['taxonomyRequests'] === [], 'WordPressタームのData化でも定義取得は遅延');
$definition = $data->GetTaxonomy();
$check($definition instanceof TaxonomyData && $definition->Name === 'area', '登録定義をTaxonomyDataへ変換');
$check($definition->HasObjectType('cases') && $GLOBALS['taxonomyRequests'] === ['area'], '対応投稿タイプを取得');
$data->GetTaxonomy();
$check($GLOBALS['taxonomyRequests'] === ['area', 'area'], 'WordPressからも毎回取得');
unset($GLOBALS['taxonomies']['area']);
$check($data->GetTaxonomy() === null && $data->TaxonomyName === 'area', '登録定義が消えても保持済み登録名は残る');
$GLOBALS['taxonomies']['area'] = new stdClass();
$check($data->GetTaxonomy() === null, 'WordPress定義の型不一致はnull');
$GLOBALS['taxonomies']['area'] = new WP_Taxonomy('area');
$check($data->GetTaxonomy() instanceof TaxonomyData, '再登録後は新しい定義を取得できる');
$changed = TermGet::Props($native);
$changed->TaxonomyName = 'other';
$GLOBALS['taxonomies']['other'] = new WP_Taxonomy('other');
$derived = new ProjectTerm($changed);
$check($derived->GetTaxonomy()?->Name === 'other', 'Membersの調整後の登録名で遅延取得');
class CustomTerm extends TermData
{
	public function GetTaxonomy(): ?TaxonomyData { return null; }
}
$check((new CustomTerm($changed))->GetTaxonomy() === null, 'Getterを直接上書きできる');
echo "Term taxonomy: {$checks} checks passed.\n";
