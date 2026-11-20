<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Internal;

use WpTypeSafety\Core\Data\ImgData;
use WpTypeSafety\Core\Internal\UrlCheck;
use WpTypeSafety\Wp\Data\WpUserData;

/** @internal ユーザーDataの標準WordPress接続処理。 */
final class UserAdapter
{
	private function __construct() {}

	/**
	 * @template TUser of WpUserData = WpUserData
	 * @param class-string<TUser> $dataClass
	 * @return TUser|null
	 */
	public static function ById(int $id, int $avatarSize = 96, string $dataClass = WpUserData::class): ?WpUserData
	{
		DataClass::Validate($dataClass, WpUserData::class);
		if ($id <= 0)
		{return null;}

		$user = \get_userdata($id);
		return $user instanceof \WP_User ? $dataClass::From($user, $avatarSize) : null;
	}

	/**
	 * @template TUser of WpUserData = WpUserData
	 * @param 'slug'|'email'|'login' $field
	 * @param class-string<TUser> $dataClass
	 * @return TUser|null
	 */
	public static function ByField(string $field, string $value, int $avatarSize = 96, string $dataClass = WpUserData::class): ?WpUserData
	{
		DataClass::Validate($dataClass, WpUserData::class);
		if ($value === '')
		{return null;}
		$user = \get_user_by($field, $value);
		return $user instanceof \WP_User ? $dataClass::From($user, $avatarSize) : null;
	}

	public static function Avatar(int $id, int $size): ?ImgData
	{
		if ($id <= 0)
		{return null;}

		$size = max(1, $size);
		$url = \get_avatar_url($id, ['size' => $size]);
		return is_string($url) && UrlCheck::IsHttpUrl($url)
			? new ImgData(Url: $url, Width: $size, Height: $size)
			: null;
	}
}
