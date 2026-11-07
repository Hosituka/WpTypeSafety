<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

use WpTypeSafety\Core\Data\ImgData;

readonly class WpUserData
{
	/**
	 * @param null|\Closure(int): ?ImgData $avatarResolver
	 */
	public function __construct(
		public int $Id,
		public string $DisplayName,
		private ?int $avatarSize = null,
		private ?\Closure $avatarResolver = null,
		public string $AuthorUrl = '',
		public string $Description = '',
	) {
	}

	public function GetAvatar(?int $size = null): ?ImgData
	{
		return $this->avatarResolver !== null
			? ($this->avatarResolver)(max(1, $size ?? $this->avatarSize ?? 96))
			: null;
	}
}
