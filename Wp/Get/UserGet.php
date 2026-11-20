<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Data\WpUserData;
use WpTypeSafety\Wp\Internal\DtoFactory;
use WpTypeSafety\Wp\Internal\UserAdapter;

/** WP_UserからユーザーのPropsまたはDataを生成します。 */
final class UserGet
{
	private function __construct() {}

	/**
	 * @template TUser of WpUserData = WpUserData
	 * @param class-string<TUser> $dataClass
	 * @return TUser|null
	 */
	public static function ById(int $userId, int $avatarSize = 96, string $dataClass = WpUserData::class): ?WpUserData
	{
		return UserAdapter::ById($userId, $avatarSize, $dataClass);
	}

	/**
	 * user_nicenameによる検索。ログイン名とは区別する。
	 * @template TUser of WpUserData = WpUserData
	 * @param class-string<TUser> $dataClass
	 * @return TUser|null
	 */
	public static function BySlug(string $slug, int $avatarSize = 96, string $dataClass = WpUserData::class): ?WpUserData
	{
		return UserAdapter::ByField('slug', $slug, $avatarSize, $dataClass);
	}

	/**
	 * @template TUser of WpUserData = WpUserData
	 * @param class-string<TUser> $dataClass
	 * @return TUser|null
	 */
	public static function ByEmail(string $email, int $avatarSize = 96, string $dataClass = WpUserData::class): ?WpUserData
	{
		return UserAdapter::ByField('email', $email, $avatarSize, $dataClass);
	}

	/**
	 * @template TUser of WpUserData = WpUserData
	 * @param class-string<TUser> $dataClass
	 * @return TUser|null
	 */
	public static function ByLogin(string $login, int $avatarSize = 96, string $dataClass = WpUserData::class): ?WpUserData
	{
		return UserAdapter::ByField('login', $login, $avatarSize, $dataClass);
	}

	/**
	 * 元のメインクエリの表示対象ユーザーを取得する。著者以外はnull。
	 * @template TUser of WpUserData = WpUserData
	 * @param class-string<TUser> $dataClass
	 * @return TUser|null
	 */
	public static function Current(int $avatarSize = 96, string $dataClass = WpUserData::class): ?WpUserData
	{
		return DtoFactory::CurrentUser($avatarSize, $dataClass);
	}

	/**
	 * ログイン中のユーザーを取得する。未ログイン時はnull。
	 * @template TUser of WpUserData = WpUserData
	 * @param class-string<TUser> $dataClass
	 * @return TUser|null
	 */
	public static function LoggedIn(int $avatarSize = 96, string $dataClass = WpUserData::class): ?WpUserData
	{
		return DtoFactory::LoggedInUser($avatarSize, $dataClass);
	}
}
