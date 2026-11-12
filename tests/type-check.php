<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/autoload.php';

use WpTypeSafety\Core\Check\TypeCheck;
use WpTypeSafety\Core\Data\ChoiceData;
use WpTypeSafety\Core\Data\FileData;
use WpTypeSafety\Core\Data\ImgData;
use WpTypeSafety\Core\Data\LinkData;
use WpTypeSafety\Core\Data\TimeData;

$checks = 0;
$check = static function (bool $condition, string $message) use (&$checks): void {
	if (!$condition) {
		throw new RuntimeException($message);
	}
	$checks++;
};

$cases = [
	['ImgData', new ImgData(Url: '/image.jpg', Width: 100, Height: 100)],
	['LinkData', new LinkData(Url: '/page')],
	['ChoiceData', new ChoiceData(Value: 'value', Label: 'Label')],
	['FileData', new FileData(Id: 1, Url: '/file.pdf', FileName: 'file.pdf', MimeType: 'application/pdf', FileSize: 100)],
	['TimeData', new TimeData(Hour: 9, Minute: 30, Second: 0)],
];

foreach ($cases as [$method, $value]) {
	$listMethod = $method . 'List';
	$check(TypeCheck::{$method}($value) === $value, $method . ' accepts its Data instance');
	$check(TypeCheck::{$method}(new stdClass()) === null, $method . ' rejects another object type');
	$check(TypeCheck::{$listMethod}([$value, new stdClass(), $value]) === [$value, $value], $listMethod . ' filters invalid elements');
	$check(TypeCheck::{$listMethod}('invalid') === null, $listMethod . ' returns null for a non-array value');
}

echo "TypeCheck: {$checks} checks passed\n";
