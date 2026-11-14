<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Data\UserQueryData;
use WpTypeSafety\Wp\Data\WpUserData;
use WpTypeSafety\Wp\Internal\UserQueryAdapter;

/** WP_User_Queryを実行または受け取り、ユーザーDataへ変換します。 */
final class UserQuery
{
	private function __construct() {}

	/**
	 * @template TUser of WpUserData
	 * @param array<string, mixed> $args
	 * @param null|\Closure(\WP_User, int): TUser $userFactory
	 * @return ($userFactory is null ? UserQueryData<WpUserData> : UserQueryData<TUser>)
	 */
	public static function Get(
		array $args = [],
		int $avatarSize = 96,
		?\Closure $userFactory = null,
	): UserQueryData
	{
		return UserQueryAdapter::Get($args, $avatarSize, $userFactory);
	}

	/**
	 * @template TUser of WpUserData
	 * @param null|\Closure(\WP_User, int): TUser $userFactory
	 * @return ($userFactory is null ? UserQueryData<WpUserData> : UserQueryData<TUser>)
	 */
	public static function From(
		\WP_User_Query $query,
		int $avatarSize = 96,
		?\Closure $userFactory = null,
	): UserQueryData
	{
		return UserQueryAdapter::From($query, $avatarSize, $userFactory);
	}
}
