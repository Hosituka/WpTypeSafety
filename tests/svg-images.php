<?php
declare(strict_types=1);

// APIスタブと一時SVGによる検証。WordPress・DBには接続しない。
if (PHP_SAPI !== 'cli') {
	exit;
}

use WpTypeSafety\Acf\Internal\AcfDtoFactory;
use WpTypeSafety\Core\Data\ImgData;
use WpTypeSafety\Wp\Internal\DtoFactory;

function get_post_mime_type(int $attachmentId): string
{
	return $GLOBALS['svgAttachments'][$attachmentId]['mime'] ?? '';
}

function wp_get_attachment_url(int $attachmentId): string|false
{
	return $GLOBALS['svgAttachments'][$attachmentId]['url'] ?? false;
}

function get_attached_file(int $attachmentId): string|false
{
	return $GLOBALS['svgAttachments'][$attachmentId]['path'] ?? false;
}

function get_post_meta(int $attachmentId, string $key, bool $single): string
{
	return $GLOBALS['svgAttachments'][$attachmentId]['alt'] ?? '';
}

function wp_get_attachment_image_src(int $attachmentId, string|array $size): array|false
{
	$GLOBALS['rasterImageSourceCalls']++;
	return false;
}

function wp_get_original_image_url(int $attachmentId): string|false
{
	return false;
}

function wp_get_original_image_path(int $attachmentId): string|false
{
	return false;
}

function wp_getimagesize(string $path): array|false
{
	return false;
}

require_once __DIR__ . '/../Core/Data/ImgData.php';
require_once __DIR__ . '/../Core/Internal/UrlCheck.php';
require_once __DIR__ . '/../Core/Internal/SvgDimensions.php';
require_once __DIR__ . '/../Wp/Internal/DtoFactory.php';
require_once __DIR__ . '/../Acf/Internal/AcfDtoFactory.php';

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};
$temporaryPaths = [];
$addSvg = static function (int $id, string $source, string $alt = '') use (&$temporaryPaths): void {
	$path = tempnam(sys_get_temp_dir(), 'wpts-svg-');
	if (! is_string($path) || file_put_contents($path, $source) === false) {
		throw new RuntimeException('Could not create temporary SVG.');
	}
	$temporaryPaths[] = $path;
	$GLOBALS['svgAttachments'][$id] = [
		'mime' => 'image/svg+xml',
		'url' => "https://example.com/{$id}.svg",
		'path' => $path,
		'alt' => $alt,
	];
};

$GLOBALS['svgAttachments'] = [];
$GLOBALS['rasterImageSourceCalls'] = 0;

try {
	$addSvg(1, '<svg viewBox="0 0 300 120"></svg>', 'viewBox image');
	$addSvg(2, '<svg viewBox="0 0 24.5 10.5"></svg>');
	$addSvg(3, '<svg width="48px" height="24px"></svg>');
	$addSvg(4, '<svg width="100%" height="auto"></svg>');
	$addSvg(5, '<svg width="24" height="12" viewBox="0 0 512 512"></svg>');
	$addSvg(6, '<svg width="24.5px" height="10.5px" viewBox="0 0 100 100"></svg>');

	$image = DtoFactory::ImgFromAttachmentId(1, 'thumbnail');
	$check($image instanceof ImgData, 'SVG attachment returns ImgData');
	$check([$image?->Width, $image?->Height] === [300, 120], 'viewBox dimensions');
	$check($image?->Alt === 'viewBox image', 'attachment alt text');
	$check($image?->Url === 'https://example.com/1.svg', 'original SVG URL for size request');

	$decimal = DtoFactory::ImgFromAttachmentId(2);
	$check([$decimal?->Width, $decimal?->Height] === [245, 105], 'decimal viewBox ratio');

	$attributes = DtoFactory::ImgFromAttachmentId(3);
	$check([$attributes?->Width, $attributes?->Height] === [48, 24], 'width and height dimensions');
	$check(DtoFactory::ImgFromAttachmentId(4) === null, 'SVG without usable ratio returns null');

	$attributesWithViewBox = DtoFactory::ImgFromAttachmentId(5);
	$check([$attributesWithViewBox?->Width, $attributesWithViewBox?->Height] === [24, 12], 'width and height take precedence over viewBox');

	$decimalAttributes = DtoFactory::ImgFromAttachmentId(6);
	$check([$decimalAttributes?->Width, $decimalAttributes?->Height] === [25, 11], 'decimal width and height are rounded as dimensions');

	$acfImage = AcfDtoFactory::ImgFromAcf([
		'ID' => 1,
		'url' => 'https://example.com/1.svg',
		'alt' => 'ACF alt',
	]);
	$check([$acfImage?->Width, $acfImage?->Height] === [300, 120], 'ACF array resolves SVG ratio');
	$check($acfImage?->Alt === 'ACF alt', 'ACF array alt takes precedence');
	$check($GLOBALS['rasterImageSourceCalls'] === 0, 'SVG does not use raster image source');
} finally {
	foreach ($temporaryPaths as $path) {
		@unlink($path);
	}
}

echo "SVG images: {$checks} checks passed.\n";
