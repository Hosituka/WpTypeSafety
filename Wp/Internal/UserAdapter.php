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

	public static function FromId(int $id): ?WpUserData
	{
		if ($id <= 0)
		{return null;}

		$user = \get_userdata($id);
		return $user instanceof \WP_User ? DtoFactory::UserFromWpUser($user) : null;
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
