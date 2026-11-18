<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

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

	public function __construct(UserProps $props)
	{
		$this->Id = $props->Id;
		$this->DisplayName = $props->DisplayName;
		$this->avatarSize = $props->AvatarSize;
		$this->ArchiveUrl = $props->ArchiveUrl;
		$this->WebsiteUrl = $props->WebsiteUrl;
		$this->Description = $props->Description;
	}

	public function GetAvatar(?int $size = null): ?ImgData
	{
		return UserAdapter::Avatar($this->Id, max(1, $size ?? $this->avatarSize ?? 96));
	}
}
