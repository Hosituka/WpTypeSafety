<?php
declare(strict_types=1);

namespace WpTypeSafety\Core\Internal;

/** @internal */
final class UrlCheck
{
	private function __construct() {}

	public static function IsHttpUrl(mixed $value): bool
	{
		if (! is_string($value) || preg_match('/[\x00-\x20\x7F]/', $value) === 1) {
			return false;
		}
		$matched = preg_match(
			'~^(?<scheme>https?)://(?:(?<userinfo>[^/?#@]*)@)?(?<host>\[[^\]]+\]|[^:/?#]+)(?<port>:\d+)?(?<rest>[/?#].*)?$~iD',
			$value,
			$parts,
			PREG_UNMATCHED_AS_NULL,
		);
		if ($matched !== 1) {
			return false;
		}
		$host = $parts['host'];
		if (preg_match('/[^\x00-\x7F]/', $host) === 1) {
			$host = self::asciiHost($host);
			if ($host === null) {
				return false;
			}
		}
		$userInfo = self::encodeNonAscii($parts['userinfo'] ?? '');
		$rest = self::encodeNonAscii($parts['rest'] ?? '');
		if ($userInfo === null || $rest === null) {
			return false;
		}
		$encoded = $parts['scheme'] . '://'
			. ($parts['userinfo'] !== null ? $userInfo . '@' : '')
			. $host
			. ($parts['port'] ?? '')
			. $rest;
		return filter_var($encoded, FILTER_VALIDATE_URL) !== false;
	}

	private static function asciiHost(string $host): ?string
	{
		if (! function_exists('idn_to_ascii')
			|| ! defined('IDNA_DEFAULT')
			|| ! defined('INTL_IDNA_VARIANT_UTS46')
		) {
			return null;
		}
		$info = [];
		try {
			$ascii = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46, $info);
		} catch (\ValueError) {
			return null;
		}
		return is_string($ascii) && ($info['errors'] ?? 0) === 0
			? $ascii
			: null;
	}

	private static function encodeNonAscii(string $value): ?string
	{
		$encoded = preg_replace_callback(
			'/[^\x00-\x7F]+/',
			static fn (array $match): string => rawurlencode($match[0]),
			$value,
		);
		return is_string($encoded) ? $encoded : null;
	}
}
