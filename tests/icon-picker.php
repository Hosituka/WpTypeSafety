<?php
declare(strict_types=1);

// ACF Icon Pickerの返却形式をAPIスタブで検証する。DBには接続しない。
if (PHP_SAPI !== 'cli') {
	exit;
}

require_once __DIR__ . '/../autoload.php';

use WpTypeSafety\Acf\Get\AcfGet;

class WP_Post
{
	public function __construct(public int $ID) {}
}

function get_field_object(
	string $name,
	mixed $source = null,
	bool $formatValue = true,
	bool $loadValue = true,
): mixed {
	$GLOBALS['iconFieldRequests'][] = [$name, $source, $formatValue, $loadValue];
	$field = $GLOBALS['iconFields'][$name] ?? null;
	if (! is_array($field)) {
		return false;
	}

	$field['value'] = $formatValue
		? ($field['formatted_value'] ?? $field['value'] ?? null)
		: ($field['value'] ?? null);
	return $field;
}

function get_field(string $name, mixed $source = null): mixed
{
	$field = $GLOBALS['iconFields'][$name] ?? null;
	return is_array($field) ? ($field['formatted_value'] ?? null) : null;
}

function get_post_mime_type(int $attachmentId): string
{
	return 'image/png';
}

/** @return array{0: string, 1: int, 2: int} */
function wp_get_attachment_image_src(int $attachmentId, string|array $size): array
{
	$sizeName = is_string($size) ? $size : implode('x', $size);
	return [
		"https://example.com/icon-{$attachmentId}-{$sizeName}.png",
		$size === 'medium' ? 300 : 1200,
		$size === 'medium' ? 200 : 800,
	];
}

function get_post_meta(int $attachmentId, string $key, bool $single): string
{
	return "Icon {$attachmentId}";
}

require_once __DIR__ . '/../Core/Data/ImgData.php';
require_once __DIR__ . '/../Core/Internal/UrlCheck.php';
require_once __DIR__ . '/../Wp/Internal/DtoFactory.php';
require_once __DIR__ . '/../Acf/Internal/AcfDtoFactory.php';
require_once __DIR__ . '/../Acf/Internal/AcfAdapter.php';
require_once __DIR__ . '/../Acf/Get/AcfGet.php';

$GLOBALS['iconFields'] = [
	'dashicon' => [
		'type' => 'icon_picker',
		'value' => ['type' => 'dashicons', 'value' => 'dashicons-admin-home'],
		'formatted_value' => 'dashicons-admin-home',
	],
	'media_icon' => [
		'type' => 'icon_picker',
		'value' => ['type' => 'media_library', 'value' => 42],
		'formatted_value' => [
			'type' => 'media_library',
			'value' => [
				'ID' => 42,
				'url' => 'https://example.com/icon-42.png',
				'width' => 1200,
				'height' => 800,
				'alt' => 'Icon 42',
			],
		],
	],
	'url_icon' => [
		'type' => 'icon_picker',
		'value' => ['type' => 'url', 'value' => 'https://example.com/icon.svg'],
		'formatted_value' => 'https://example.com/icon.svg',
	],
	'invalid_url_icon' => [
		'type' => 'icon_picker',
		'value' => ['type' => 'url', 'value' => 'javascript:alert(1)'],
	],
	'invalid_class_icon' => [
		'type' => 'icon_picker',
		'value' => ['type' => 'dashicons', 'value' => 'dashicons admin-home'],
	],
	'custom_icon' => [
		'type' => 'icon_picker',
		'value' => ['type' => 'project_icons', 'value' => 'project-home'],
	],
	'wrong_field_type' => [
		'type' => 'text',
		'value' => ['type' => 'dashicons', 'value' => 'dashicons-admin-home'],
	],
	'icon_group' => [
		'type' => 'group',
		'value' => [
			'field_group_class' => ['type' => 'dashicons', 'value' => 'dashicons-heart'],
			'field_group_img' => ['type' => 'media_library', 'value' => 55],
			'field_group_url' => ['type' => 'url', 'value' => 'https://example.com/group-icon.svg'],
		],
		'formatted_value' => [
			'icon_class' => 'dashicons-heart',
			'icon_img' => 'https://example.com/icon-55.png',
			'icon_url' => 'https://example.com/group-icon.svg',
		],
		'sub_fields' => [
			[
				'key' => 'field_group_class',
				'name' => 'group_icon_class',
				'_name' => 'icon_class',
				'type' => 'icon_picker',
			],
			[
				'key' => 'field_group_img',
				'name' => 'group_icon_img',
				'_name' => 'icon_img',
				'type' => 'icon_picker',
			],
			[
				'key' => 'field_group_url',
				'name' => 'group_icon_url',
				'_name' => 'icon_url',
				'type' => 'icon_picker',
			],
		],
	],
];

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};

$postGet = new AcfGet(new WP_Post(10));
$check($postGet->IconClass('dashicon') === 'dashicons-admin-home', 'Dashiconクラスを取得する');
$check($postGet->IconImg('dashicon') === null, 'Dashiconを画像として扱わない');
$check($postGet->IconUrl('dashicon') === null, 'DashiconをURLとして扱わない');

$mediaIcon = $postGet->IconImg('media_icon', 'medium');
$check($mediaIcon?->Url === 'https://example.com/icon-42-medium.png', 'メディア画像のサイズを指定する');
$check($mediaIcon?->Width === 300 && $mediaIcon?->Height === 200, 'メディア画像の寸法を保持する');
$check($mediaIcon?->Alt === 'Icon 42', 'メディア画像のaltを保持する');
$check($postGet->IconClass('media_icon') === null, 'メディア画像をDashiconとして扱わない');
$check($postGet->IconUrl('media_icon') === null, 'メディア画像をURLタブとして扱わない');

$check($postGet->IconUrl('url_icon') === 'https://example.com/icon.svg', 'URLアイコンを取得する');
$check($postGet->IconImg('url_icon') === null, 'URLアイコンをImgDataへ変換しない');
$check($postGet->IconUrl('invalid_url_icon') === null, '不正URLを除外する');
$check($postGet->IconClass('invalid_class_icon') === null, '不正なDashiconクラスを除外する');
$check($postGet->IconClass('custom_icon') === null, '独自タブを標準Dashiconとして扱わない');
$check($postGet->IconClass('wrong_field_type') === null, '別フィールドタイプを除外する');
$check($postGet->IconClass('missing') === null, '未設定はnull');

$arrayClassGet = new AcfGet(['icon' => ['type' => 'dashicons', 'value' => 'dashicons-star-filled']]);
$arrayImgGet = new AcfGet([
	'icon' => [
		'type' => 'media_library',
		'value' => [
			'ID' => 70,
			'url' => 'https://example.com/icon-70.png',
			'width' => 640,
			'height' => 480,
		],
	],
]);
$arrayUrlGet = new AcfGet(['icon' => ['type' => 'url', 'value' => 'https://example.com/array-icon.svg']]);
$check($arrayClassGet->IconClass('icon') === 'dashicons-star-filled', '取得済み配列のDashicon');
$check($arrayImgGet->IconImg('icon')?->Width === 640, '取得済み配列のメディア画像');
$check($arrayUrlGet->IconUrl('icon') === 'https://example.com/array-icon.svg', '取得済み配列のURL');
$check((new AcfGet(['icon' => 'dashicons-camera']))->IconClass('icon') === 'dashicons-camera', '文字列形式のDashicon');
$check(
	(new AcfGet(['icon' => 'https://example.com/string-icon.svg']))->IconUrl('icon')
		=== 'https://example.com/string-icon.svg',
	'文字列形式のURL',
);

$groupGet = $postGet->Nested('icon_group');
$check($groupGet->IconClass('icon_class') === 'dashicons-heart', 'Nested内のDashicon');
$check($groupGet->IconImg('icon_img', 'medium')?->Url === 'https://example.com/icon-55-medium.png', 'Nested内の画像');
$check($groupGet->IconUrl('icon_url') === 'https://example.com/group-icon.svg', 'Nested内のURL');

$check($postGet->IconId('media_icon') === 42, '未整形のメディアIDを取得する');
$lastRequest = $GLOBALS['iconFieldRequests'][array_key_last($GLOBALS['iconFieldRequests'])];
$check($lastRequest === ['media_icon', 10, false, true], 'IconIdもReturn Formatに依存せず取得する');
$check($arrayImgGet->IconId('icon') === 70, '整形済み画像配列からIDを抽出する');
$check($groupGet->IconId('icon_img') === 55, 'Nested内のURL整形前のIDを保持する');
$check($groupGet->IconId('icon_class') === null && $groupGet->IconId('icon_url') === null, 'Nested内の別タブはnull');
$check($groupGet->IconId('missing') === null, 'Nested内の欠損はnull');
foreach (['dashicon', 'url_icon', 'custom_icon', 'wrong_field_type', 'missing'] as $fieldName) {
	$check($postGet->IconId($fieldName) === null, '別タブ・別フィールド・欠損はnull');
}

$GLOBALS['iconFields']['wrong_media_field'] = [
	'type' => 'image',
	'value' => ['type' => 'media_library', 'value' => 42],
];
$check($postGet->IconId('wrong_media_field') === null, 'メディア形式でもIcon Picker以外は除外する');

foreach ([
	[42, 42], ['42', 42], ['0042', 42], [['id' => '43'], 43],
	[['ID' => 44, 'id' => 45], 44], [PHP_INT_MAX, PHP_INT_MAX],
	[0, null], [-1, null], [42.0, null], [false, null], [null, null],
	['', null], ['42.0', null], [' 42', null], [(string) PHP_INT_MAX . '0', null],
	['https://example.com/icon.png', null], [['url' => 'https://example.com/icon.png'], null],
	[[], null], [new stdClass(), null],
] as [$value, $expected]) {
	$idGet = new AcfGet(['icon' => ['type' => 'media_library', 'value' => $value]]);
	$check($idGet->IconId('icon') === $expected, 'メディアIDの正規化');
}
foreach ([42, '42', 'dashicons-camera', 'https://example.com/icon.png', [], ['value' => 42], ['type' => 'url', 'value' => 42]] as $value) {
	$idGet = new AcfGet(['icon' => $value]);
	$check($idGet->IconId('icon') === null, 'メディアタブを識別できない値はnull');
}

$rowsGet = new AcfGet([
	'rows' => [['icon' => ['type' => 'media_library', 'value' => 46]]],
	'layouts' => [['acf_fc_layout' => 'sample', 'icon' => ['type' => 'media_library', 'value' => 47]]],
]);
$rowGet = $rowsGet->Repeater('rows')[0];
$check($rowGet->IconId('icon') === 46, 'Repeater行でIDを取得する');
$layoutGet = $rowsGet->Flexible('layouts')[0]->Get;
$check($layoutGet->IconId('icon') === 47, 'Flexible行でIDを取得する');

echo "Icon Picker: {$checks} checks passed.\n";
