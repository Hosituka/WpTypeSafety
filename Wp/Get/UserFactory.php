<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Data\UserMembers;
use WpTypeSafety\Wp\Data\WpUserData;
use WpTypeSafety\Wp\Internal\DtoFactory;

/** WP_UserからユーザーのMembersまたはDataを生成します。 */
final class UserFactory
{
	private function __construct() {}

	public static function Members(\WP_User $user, int $avatarSize = 96): ?UserMembers
	{
		return DtoFactory::UserMembersFromWpUser($user, $avatarSize);
	}

	public static function From(\WP_User $user, int $avatarSize = 96): ?WpUserData
	{
		return DtoFactory::UserFromWpUser($user, $avatarSize);
	}
}
