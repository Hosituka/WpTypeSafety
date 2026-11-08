<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

use WpTypeSafety\Core\Data\ImgData;

readonly class WpUserData
{
	public int $Id;
	public string $DisplayName;
	private ?int $avatarSize;
	private ?\Closure $avatarResolver;
	public string $AuthorUrl;
	public string $Description;

	public function __construct(UserMembers $members)
	{
		$this->Id = $members->Id;
		$this->DisplayName = $members->DisplayName;
		$this->avatarSize = $members->AvatarSize;
		$this->avatarResolver = $members->AvatarResolver;
		$this->AuthorUrl = $members->AuthorUrl;
		$this->Description = $members->Description;
	}

	public function GetAvatar(?int $size = null): ?ImgData
	{
		return $this->avatarResolver !== null
			? ($this->avatarResolver)(max(1, $size ?? $this->avatarSize ?? 96))
			: null;
	}
}
