<?php
declare(strict_types=1);

// APIスタブで未選択時の空値と、取得関数を呼ばないことを検証する。
if (PHP_SAPI !== 'cli') {
	exit;
}

use WpTypeSafety\Acf\Get\AcfGet;
use WpTypeSafety\Acf\Get\AcfGetTerm;
use WpTypeSafety\Acf\Get\AcfGetUser;
use WpTypeSafety\Wp\Data\TermData;
use WpTypeSafety\Wp\Data\WpUserData;

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
require_once __DIR__ . '/../Acf/Get/AcfGetTerm.php';
require_once __DIR__ . '/../Acf/Get/AcfGetUser.php';
require_once __DIR__ . '/../Acf/Internal/AcfDtoFactory.php';
require_once __DIR__ . '/../Acf/Internal/AcfAdapter.php';
require_once __DIR__ . '/../Acf/Internal/SubjectAdapter.php';

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};

// nullの構築は対象IDのResolverが未登録でも可能。
$termEmpty = new AcfGetTerm(null);
$userEmpty = new AcfGetUser(null);
foreach ([static fn () => new AcfGetTerm(new WP_Term(7)), static fn () => new AcfGetUser(new WP_User(8))] as $create) {
	try {
		$create();
		throw new RuntimeException('Non-null subject accepted without resolver');
	} catch (LogicException) {
		$check(true, 'non-null still requires registration');
	}
}
\WpTypeSafety\Acf\Internal\AcfAdapter::Register();
$termResolverCalls = 0;
$userResolverCalls = 0;
AcfGetTerm::RegisterIdResolver(static function ($term) use (&$termResolverCalls): string {
	$termResolverCalls++;
	return $term instanceof TermData ? $term->Taxonomy . '_' . $term->Id : $term->taxonomy . '_' . $term->term_id;
});
AcfGetUser::RegisterIdResolver(static function ($user) use (&$userResolverCalls): string {
	$userResolverCalls++;
	return 'user_' . ($user instanceof WpUserData ? $user->Id : $user->ID);
});
$expected = [
	'Text' => '', 'Array' => [], 'Repeater' => [], 'Gallery' => [],
	'Checkbox' => [], 'CheckboxValues' => [], 'CheckboxLabels' => [],
	'SelectValues' => [], 'SelectLabels' => [], 'SelectChoices' => [],
	'Post' => null, 'Term' => null, 'User' => null, 'Link' => null,
	'Number' => null, 'Img' => null, 'ImgUrl' => null, 'File' => null,
	'FileUrl' => null, 'Url' => null, 'Bool' => null,
	'Radio' => null, 'RadioValue' => null, 'RadioLabel' => null,
	'Select' => null, 'SelectValue' => null, 'SelectLabel' => null,
];
$GLOBALS['fieldCalls'] = [];
$GLOBALS['fieldObjectCalls'] = [];
foreach ([$termEmpty, $userEmpty, new AcfGetTerm(null), new AcfGetUser(null)] as $get) {
	foreach ($expected as $method => $value) {
		$check($get->$method('missing') === $value, $method . ' empty value');
		$check($get->Nested('missing')->Nested('nested')->$method('missing') === $value, $method . ' nested empty value');
	}
}
$check($termResolverCalls === 0 && $userResolverCalls === 0, 'null skips ID resolvers');
$check($GLOBALS['fieldCalls'] === [] && $GLOBALS['fieldObjectCalls'] === [], 'null never reads ACF/current post');
$term = new AcfGetTerm(new WP_Term(7));
$user = new AcfGetUser(new WP_User(8));
$check($term->Text('text') === 'target text', 'normal term unchanged');
$check($user->Text('text') === 'target text', 'normal user unchanged');
$check($GLOBALS['fieldCalls'] === [['text', 'area_7'], ['text', 'user_8']], 'subject source unchanged');
$check($termResolverCalls === 1 && $userResolverCalls === 1, 'normal subjects resolve once');
$check((new AcfGet(null))->Text('text') === 'current post sentinel', 'AcfGet(null) keeps current-post semantics');
echo "Nullable subjects: {$checks} checks passed\n";
