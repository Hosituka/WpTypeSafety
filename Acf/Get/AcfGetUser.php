<?php
declare(strict_types=1);

namespace WpTypeSafety\Acf\Get;

use WpTypeSafety\Wp\Data\WpUserData;

/** ユーザーを対象にしたACFフィールド取得。 */
final class AcfGetUser extends AcfGet
{
	/** @var null|\Closure(\WP_User|WpUserData): string */
	private static ?\Closure $idResolver = null;

	/** @internal @param \Closure(\WP_User|WpUserData): string $idResolver */
	public static function RegisterIdResolver(\Closure $idResolver): void
	{
		if (self::$idResolver !== null) {
			throw new \LogicException('AcfGetUser implementation is already registered.');
		}
		self::$idResolver = $idResolver;
	}

	/** 対象なしは空の取得元として扱い、現在の投稿へフォールバックしない。 */
	public function __construct(\WP_User|WpUserData|null $user)
	{
		if ($user === null) {
			parent::__construct([]);
			return;
		}

		$resolver = self::$idResolver ?? throw new \LogicException('AcfGetUser implementation is not registered.');
		parent::__construct($resolver($user));
	}
}
