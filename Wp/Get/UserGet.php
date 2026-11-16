<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Data\UserMembers;
use WpTypeSafety\Wp\Data\WpUserData;
use WpTypeSafety\Wp\Internal\DtoFactory;

/** WP_UserからユーザーのMembersまたはDataを生成します。 */
final class UserGet
{
	private function __construct() {}

	/**
	 * ログイン中のユーザーを取得する。未ログイン時はnull。
	 * @template TUser of WpUserData
	 * @param null|\Closure(\WP_User, int): TUser $userFactory
	 * @return ($userFactory is null ? WpUserData|null : TUser|null)
	 */
	public static function LoggedIn(int $avatarSize = 96, ?\Closure $userFactory = null): ?WpUserData
	{
		return DtoFactory::LoggedInUser($avatarSize, $userFactory);
	}

	public static function Members(\WP_User $user, int $avatarSize = 96): ?UserMembers
	{
		return DtoFactory::UserMembersFromWpUser($user, $avatarSize);
	}

	public static function From(\WP_User $user, int $avatarSize = 96): ?WpUserData
	{
		return DtoFactory::UserFromWpUser($user, $avatarSize);
	}
}
