<?php
declare(strict_types=1);

// WordPress・添付ファイル・DBを読み込まず、IDの抽出だけを検証する。
if (PHP_SAPI !== 'cli')
{exit;}

require_once __DIR__ . '/../autoload.php';

use WpTypeSafety\Acf\Get\AcfGet;

function get_field(string $name, mixed $source = null): mixed
{
	$GLOBALS['attachmentSource'] = $source;
	return $GLOBALS['attachmentFields'][$name] ?? null;
}

class ProjectAttachmentGet extends AcfGet {}

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok)
	{throw new RuntimeException($label);}
	$checks++;
};

$cases = [
	[123, 123], ['123', 123], ['00123', 123], [PHP_INT_MAX, PHP_INT_MAX],
	[(string) PHP_INT_MAX, PHP_INT_MAX],
	[['ID' => '12'], 12], [['id' => 13], 13],
	[['ID' => 14, 'id' => 15], 14], [['ID' => null, 'id' => 15], 15],
	[['ID' => 0, 'id' => 15], null],
	[['ID' => 16, 'url' => 'invalid', 'width' => 0], 16],
	[null, null], [false, null], [true, null], [0, null], [-1, null],
	[12.0, null], [12.5, null], ['', null], ['0', null], ['000', null],
	['-1', null], ['+1', null], ['1.0', null], ['1e2', null],
	[' 12', null], ["12\n", null], [(string) PHP_INT_MAX . '0', null],
	['https://example.com/a.jpg', null], [['url' => 'https://example.com/a.jpg'], null],
	[[], null], [new stdClass(), null], [['ID' => ['ID' => 12]], null],
];
foreach ($cases as [$value, $expected]) {
	$get = new AcfGet(['attachment' => $value]);
	$check($get->ImgId('attachment') === $expected, 'ImgId normalization');
	$check($get->FileId('attachment') === $expected, 'FileId normalization');
	$GLOBALS['attachmentFields']['attachment'] = $value;
	$liveGet = new AcfGet(42);
	$check($liveGet->ImgId('attachment') === $expected, 'ACF ImgId');
	$check($liveGet->FileId('attachment') === $expected, 'ACF FileId');
	$check($GLOBALS['attachmentSource'] === 42, 'Source forwarded');
}

$items = [8 => 12, 9 => '13', 20 => ['ID' => '12'], 21 => false, 25 => ['id' => 14], 30 => 'https://example.com/a.jpg'];
$get = new ProjectAttachmentGet([
	'gallery' => $items,
	'group' => ['gallery' => $items, 'photo' => ['ID' => 12]],
	'rows' => [['photo' => 13, 'file' => ['id' => 14], 'gallery' => $items]],
]);
$check($get->GalleryIds('gallery') === [12, 13, 12, 14], 'Order, duplicates, reindexing');
$nested = $get->Nested('group');
$check($nested instanceof ProjectAttachmentGet, 'Derived type preserved');
$check($nested->ImgId('photo') === 12 && $nested->GalleryIds('gallery') === [12, 13, 12, 14], 'Nested IDs');
$row = $get->Repeater('rows')[0];
$check($row->ImgId('photo') === 13 && $row->FileId('file') === 14, 'Repeater IDs');
$check($row->GalleryIds('gallery') === [12, 13, 12, 14], 'Repeater gallery');
$check($get->ImgId('missing') === null && $get->FileId('missing') === null && $get->GalleryIds('missing') === [], 'Missing values');
foreach ([null, false, '', 12, new stdClass(), [], [false, 0, ['url' => 'https://example.com/a.jpg']]] as $value) {
	$invalidGet = new AcfGet(['gallery' => $value]);
	$check($invalidGet->GalleryIds('gallery') === [], 'Invalid/empty gallery');
}
$GLOBALS['attachmentFields']['gallery'] = $items;
$liveGet = new AcfGet('option');
$check($liveGet->GalleryIds('gallery') === [12, 13, 12, 14], 'ACF gallery IDs');
$check($GLOBALS['attachmentSource'] === 'option', 'Gallery source forwarded');

echo "Attachment IDs: {$checks} checks passed.\n";
