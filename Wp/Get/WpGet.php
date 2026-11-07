<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

/** WordPressのページ番号を取得します。 */
final class WpGet
{
	/** @var null|\Closure(): int */
	private static ?\Closure $currentPageNumber = null;

	private function __construct() {}

	/** @internal */
	public static function Register(\Closure $currentPageNumber): void
	{
		if (self::$currentPageNumber !== null) {
			throw new \LogicException('WpGet implementation is already registered.');
		}
		self::$currentPageNumber = $currentPageNumber;
	}

	public static function CurrentPageNumber(): int
	{
		$resolver = self::$currentPageNumber ?? throw new \LogicException('WpGet implementation is not registered.');
		return $resolver();
	}
}
