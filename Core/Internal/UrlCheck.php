<?php
declare(strict_types=1);

namespace WpTypeSafety\Core\Internal;

/** @internal */
final class UrlCheck
{
	private function __construct() {}

	public static function IsHttpUrl(mixed $value): bool
	{
		if (! is_string($value)) {
			return false;
		}
		$encoded = preg_replace_callback(
			'/[^\x00-\x7F]+/',
			static fn (array $match): string => rawurlencode($match[0]),
			$value,
		);
		$scheme = parse_url($value, PHP_URL_SCHEME);
		return is_string($encoded)
			&& filter_var($encoded, FILTER_VALIDATE_URL) !== false
			&& is_string($scheme)
			&& in_array(strtolower($scheme), ['http', 'https'], true);
	}
}
