<?php
declare(strict_types=1);

// APIスタブによる検証。WordPress・DBには接続しない。
if (PHP_SAPI !== 'cli') {
	exit;
}

use WpTypeSafety\Acf\Get\AcfGet;
use WpTypeSafety\Acf\Get\AcfGetTerm;
use WpTypeSafety\Acf\Get\AcfGetUser;
use WpTypeSafety\Core\Data\ChoiceData;

class WP_Post { public function __construct(public int $ID) {} }
class WP_Term { public function __construct(public int $term_id) {} }
class WP_User { public function __construct(public int $ID) {} }

function get_field_object(string $name, mixed $source = null): mixed
{
	$GLOBALS['lastSelectSource'] = $source;
	return $GLOBALS['selectFields'][$name] ?? false;
}
function get_field(string $name, mixed $source = null): mixed
{
	return $GLOBALS['selectFields'][$name]['value'] ?? null;
}

require_once __DIR__ . '/../Core/Data/ChoiceData.php';
require_once __DIR__ . '/../Acf/Get/AcfGet.php';
require_once __DIR__ . '/../Acf/Get/AcfGetTerm.php';
require_once __DIR__ . '/../Acf/Get/AcfGetUser.php';
require_once __DIR__ . '/../Acf/Internal/AcfDtoFactory.php';
require_once __DIR__ . '/../Acf/Internal/AcfAdapter.php';
\WpTypeSafety\Acf\Internal\AcfAdapter::Register();
AcfGetTerm::RegisterIdResolver(static fn ($term): string => 'term_' . $term->term_id);
AcfGetUser::RegisterIdResolver(static fn ($user): string => 'user_' . $user->ID);

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};
$definitions = [
	['SelectValue', 'value', false, 'instagram'],
	['SelectLabel', 'label', false, 'Instagram'],
	['Select', 'array', false, ['value' => 'instagram', 'label' => 'Instagram']],
	['SelectValues', 'value', true, ['instagram', 'x']],
	['SelectLabels', 'label', true, ['Instagram', 'X']],
	['SelectChoices', 'array', true, [
		['value' => 'instagram', 'label' => 'Instagram'],
		['value' => 'x', 'label' => 'X'],
	]],
];
$normalize = static function (mixed $value): mixed {
	if ($value instanceof ChoiceData) {
		return [$value->Value, $value->Label];
	}
	if (is_array($value)) {
		return array_map(static fn ($item) => $item instanceof ChoiceData ? [$item->Value, $item->Label] : $item, $value);
	}
	return $value;
};
$expected = ['instagram', 'Instagram', ['instagram', 'Instagram'], ['instagram', 'x'], ['Instagram', 'X'], [['instagram', 'Instagram'], ['x', 'X']]];
foreach ($definitions as $index => [$method, $format, $multiple, $value]) {
	$field = ['type' => 'select', 'return_format' => $format, 'multiple' => $multiple, 'value' => $value];
	$GLOBALS['selectFields']['sns'] = $field;
	foreach ([new AcfGet(123), new AcfGet(new WP_Post(123)), new AcfGetTerm(new WP_Term(4)), new AcfGetUser(new WP_User(5))] as $get) {
		$check($normalize($get->$method('sns')) === $expected[$index], $method . ' direct/wrapper');
	}
	$check($normalize((new AcfGet(['sns' => $value]))->$method('sns')) === $expected[$index], $method . ' array');
	$check($normalize((new AcfGet(['basic_info' => ['sns' => $value]]))->Nested('basic_info')->$method('sns')) === $expected[$index], $method . ' nested');
	$empty = $multiple ? [] : null;
	foreach ([
		['type' => 'radio'],
		['type' => 'checkbox'],
		['type' => 'text'],
		['return_format' => $format === 'array' ? 'value' : 'array'],
		['multiple' => ! $multiple],
	] as $override) {
		$GLOBALS['selectFields']['sns'] = array_replace($field, $override);
		$check((new AcfGet(123))->$method('sns') === $empty, $method . ' rejects settings mismatch');
	}
	$GLOBALS['selectFields']['sns'] = $field;
	foreach ([null, false, '', 1.5, new stdClass()] as $bad) {
		$GLOBALS['selectFields']['sns']['value'] = $bad;
		$check((new AcfGet(123))->$method('sns') === $empty, $method . ' rejects invalid value');
		$check((new AcfGet(['sns' => $bad]))->$method('sns') === $empty, $method . ' array invalid value');
	}
	unset($GLOBALS['selectFields']['sns']);
	$check((new AcfGet(123))->$method('sns') === $empty, $method . ' missing field');
	$check((new AcfGet([]))->$method('sns') === $empty, $method . ' missing array key');
}
foreach ([0, '0'] as $zero) {
	$get = new AcfGet(['single' => $zero, 'many' => [$zero]]);
	$check($get->SelectValue('single') === '0', 'preserve zero');
	$check($get->SelectValues('many') === ['0'], 'preserve multiple zero');
}
$get = new AcfGet(['many' => ['x', false, null, '', 0, ['value' => 'bad'], 1.5]]);
$check($get->SelectValues('many') === ['x', '0'], 'filter invalid strings');
$check((new AcfGet(['many' => [4 => 'x']]))->SelectValues('many') === [], 'reject non-list');
$check((new AcfGet(['single' => ['value' => 'x']]))->Select('single') === null, 'reject incomplete choice');
$get = new AcfGet(['many' => [['value' => 'x', 'label' => 'X'], ['value' => 'bad'], false]]);
$check($normalize($get->SelectChoices('many')) === [['x', 'X']], 'filter malformed choices');
$check((new AcfGet(['single' => ['x']]))->SelectValue('single') === null, 'reject list as single');
$check((new AcfGet(['many' => 'x']))->SelectValues('many') === [], 'reject scalar as multiple');

// 既存Radio・Checkbox取得口の登録と設定検証を維持する。
$GLOBALS['selectFields']['radio'] = ['type' => 'radio', 'return_format' => 'value', 'value' => 'x'];
$GLOBALS['selectFields']['checkbox'] = ['type' => 'checkbox', 'return_format' => 'value', 'value' => ['x']];
$check((new AcfGet(123))->RadioValue('radio') === 'x', 'radio regression');
$check((new AcfGet(123))->CheckboxValues('checkbox') === ['x'], 'checkbox regression');
echo "Select accessors: {$checks} checks passed\n";
