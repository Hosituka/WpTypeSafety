<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

use WpTypeSafety\Wp\Internal\DtoFactory;

use WpTypeSafety\Core\Data\ImgData;
use WpTypeSafety\Wp\Internal\UserAdapter;

class WpUserData
{
	public readonly int $Id;
	public readonly string $DisplayName;
	private readonly ?int $avatarSize;
	public readonly string $ArchiveUrl;
	public readonly string $WebsiteUrl;
	public readonly string $Description;

	final protected function __construct(UserProps $props)
	{
		$this->Id = $props->Id;
		$this->DisplayName = $props->DisplayName;
		$this->avatarSize = $props->AvatarSize;
		$this->ArchiveUrl = $props->ArchiveUrl;
		$this->WebsiteUrl = $props->WebsiteUrl;
		$this->Description = $props->Description;
	}

	final public static function From(\WP_User $user, int $avatarSize = 96): ?static
	{
		$props = static::getProps($user, $avatarSize);
		if ($props === null)
		{return null;}
		$data = new static($props);
		$data->initialize($user, $avatarSize);
		return $data;
	}

	protected static function getProps(\WP_User $user, int $avatarSize = 96): ?UserProps
	{
		return DtoFactory::UserPropsFromWpUser($user, $avatarSize);
	}

	protected function initialize(\WP_User $user, int $avatarSize = 96): void
	{
	}

	public function GetAvatar(?int $size = null): ?ImgData
	{
		return UserAdapter::Avatar($this->Id, max(1, $size ?? $this->avatarSize ?? 96));
	}
}
