<?php
declare(strict_types=1);

// APIスタブで未選択時の空値と、取得関数を呼ばないことを検証する。
if (PHP_SAPI !== 'cli') {
	exit;
}

use WpTypeSafety\Acf\Get\AcfGet;

class WP_Term {
	public function __construct(public int $term_id, public string $taxonomy = 'area') {}
}
class WP_User {
	public function __construct(public int $ID) {}
}

function get_field(string $name, mixed $source = null): mixed
{
	$GLOBALS['fieldCalls'][] = [$name, $source];
	return $source === null ? 'current post sentinel' : 'target text';
}
function get_field_object(string $name, mixed $source = null): mixed
{
	$GLOBALS['fieldObjectCalls'][] = [$name, $source];
	throw new RuntimeException('Empty subjects must not read field definitions');
}

require_once __DIR__ . '/../Wp/Data/TermData.php';
require_once __DIR__ . '/../Wp/Data/WpUserData.php';
require_once __DIR__ . '/../Core/Data/ChoiceData.php';
require_once __DIR__ . '/../Core/Internal/UrlCheck.php';
require_once __DIR__ . '/../Acf/Get/AcfGet.php';
require_once __DIR__ . '/../Acf/Internal/AcfDtoFactory.php';
require_once __DIR__ . '/../Acf/Internal/AcfAdapter.php';

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};

// 空配列は空の取得元、対象ありはAdapterから参照先IDを生成する。
$emptyGet = new AcfGet([]);
$expected = [
	'Text' => '', 'Array' => [], 'Repeater' => [], 'Gallery' => [],
	'Checkbox' => [], 'CheckboxValues' => [], 'CheckboxLabels' => [],
	'SelectValues' => [], 'SelectLabels' => [], 'SelectChoices' => [],
	'Post' => null, 'Term' => null, 'User' => null, 'Link' => null,
	'Float' => null, 'Img' => null, 'ImgUrl' => null, 'File' => null,
	'FileUrl' => null, 'Url' => null, 'Bool' => null,
	'Radio' => null, 'RadioValue' => null, 'RadioLabel' => null,
	'Select' => null, 'SelectValue' => null, 'SelectLabel' => null,
];
$GLOBALS['fieldCalls'] = [];
$GLOBALS['fieldObjectCalls'] = [];
foreach ([$emptyGet, new AcfGet([])] as $get) {
	foreach ($expected as $method => $value) {
		$check($get->$method('missing') === $value, $method . ' empty value');
		$check($get->Nested('missing')->Nested('nested')->$method('missing') === $value, $method . ' nested empty value');
	}
}
$check($GLOBALS['fieldCalls'] === [] && $GLOBALS['fieldObjectCalls'] === [], 'empty source never reads ACF/current post');
$term = new AcfGet(new WP_Term(7));
$user = new AcfGet(new WP_User(8));
$check($term->Text('text') === 'target text', 'normal term unchanged');
$check($user->Text('text') === 'target text', 'normal user unchanged');
$check($GLOBALS['fieldCalls'] === [['text', 'area_7'], ['text', 'user_8']], 'subject source unchanged');
$check((new AcfGet(null))->Text('text') === 'current post sentinel', 'AcfGet(null) keeps current-post semantics');
echo "Nullable subjects: {$checks} checks passed\n";
