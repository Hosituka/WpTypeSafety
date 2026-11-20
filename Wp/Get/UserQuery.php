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
	 * @template TUser of WpUserData = WpUserData
	 * @param array<string, mixed> $args
	 * @param class-string<TUser> $dataClass
	 * @return UserQueryData<TUser>
	 */
	public static function Get(
		array $args = [],
		int $avatarSize = 96,
		string $dataClass = WpUserData::class,
	): UserQueryData
	{
		return UserQueryAdapter::Get($args, $avatarSize, $dataClass);
	}

	/**
	 * @template TUser of WpUserData = WpUserData
	 * @param class-string<TUser> $dataClass
	 * @return UserQueryData<TUser>
	 */
	public static function From(
		\WP_User_Query $query,
		int $avatarSize = 96,
		string $dataClass = WpUserData::class,
	): UserQueryData
	{
		return UserQueryAdapter::From($query, $avatarSize, $dataClass);
	}
}
