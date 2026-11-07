<?php
declare(strict_types=1);

namespace WpTypeSafety\Core\Internal;

/** @internal ローカルSVGからHTMLの寸法属性に使える寸法または比率を取得する。 */
final class SvgDimensions
{
	private const NUMBER_PATTERN = '[+-]?(?:\d+(?:\.\d*)?|\.\d+)(?:[eE][+-]?\d+)?';

	private function __construct() {}

	/** @return null|array{0: int, 1: int} */
	public static function FromFile(string $path): ?array
	{
		if (! is_file($path) || ! is_readable($path)) {
			return null;
		}
		$source = file_get_contents($path, false, null, 0, 65536);
		if (! is_string($source) || preg_match('/<svg\b([^>]*)>/is', $source, $rootMatch) !== 1) {
			return null;
		}
		$attributes = $rootMatch[1];
		$width = self::cssPixelLength(self::attribute($attributes, 'width'));
		$height = self::cssPixelLength(self::attribute($attributes, 'height'));
		if ($width !== null && $height !== null) {
			return self::pixelDimensions($width, $height);
		}

		$viewBox = self::attribute($attributes, 'viewBox');
		return $viewBox !== null
			? self::dimensionsFromViewBox($viewBox)
			: null;
	}

	private static function attribute(string $attributes, string $name): ?string
	{
		$pattern = '/(?:^|\s)' . preg_quote($name, '/') . '\s*=\s*([\'\"])(.*?)\1/is';
		return preg_match($pattern, $attributes, $match) === 1
			? trim($match[2])
			: null;
	}

	/** @return null|array{0: int, 1: int} */
	private static function dimensionsFromViewBox(string $viewBox): ?array
	{
		$number = self::NUMBER_PATTERN;
		$pattern = "/^\s*{$number}[\s,]+{$number}[\s,]+({$number})[\s,]+({$number})\s*$/";
		if (preg_match($pattern, $viewBox, $match) !== 1) {
			return null;
		}
		return self::integerPair((float) $match[1], (float) $match[2]);
	}

	private static function cssPixelLength(?string $value): ?float
	{
		if ($value === null) {
			return null;
		}
		$number = self::NUMBER_PATTERN;
		if (preg_match("/^\s*({$number})(?:px)?\s*$/i", $value, $match) !== 1) {
			return null;
		}
		$length = (float) $match[1];
		return is_finite($length) && $length > 0 ? $length : null;
	}

	/** @return array{0: int, 1: int} */
	private static function pixelDimensions(float $width, float $height): array
	{
		return [
			max(1, (int) round($width)),
			max(1, (int) round($height)),
		];
	}

	/** @return null|array{0: int, 1: int} */
	private static function integerPair(float $width, float $height): ?array
	{
		if (! is_finite($width) || ! is_finite($height) || $width <= 0 || $height <= 0) {
			return null;
		}
		$scale = 1;
		while ($scale < 1000000) {
			$scaledWidth = $width * $scale;
			$scaledHeight = $height * $scale;
			if (abs($scaledWidth - round($scaledWidth)) < 0.0000001
				&& abs($scaledHeight - round($scaledHeight)) < 0.0000001
			) {
				break;
			}
			$scale *= 10;
		}
		$scaledWidth = round($width * $scale);
		$scaledHeight = round($height * $scale);
		if ($scaledWidth <= 0 || $scaledHeight <= 0
			|| $scaledWidth > PHP_INT_MAX || $scaledHeight > PHP_INT_MAX
		) {
			return null;
		}
		return [(int) $scaledWidth, (int) $scaledHeight];
	}
}
