<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Data\UserQueryData;
use WpTypeSafety\Wp\Internal\UserQueryAdapter;

/** WP_User_Queryを実行または受け取り、ユーザーDataへ変換します。 */
final class UserQuery
{
	private function __construct() {}

	/** @param array<string, mixed> $args */
	public static function Get(array $args = [], int $avatarSize = 96): UserQueryData
	{
		return UserQueryAdapter::Get($args, $avatarSize);
	}

	public static function From(\WP_User_Query $query, int $avatarSize = 96): UserQueryData
	{
		return UserQueryAdapter::From($query, $avatarSize);
	}
}
