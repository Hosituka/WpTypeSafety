<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

use WpTypeSafety\Core\Data\ImgData;
use WpTypeSafety\Core\Data\LinkData;

final readonly class MenuNodeData
{
	/**
	 * @param list<MenuNodeData> $Children
	 * @param null|\Closure(string|array): ?ImgData $thumbnailResolver
	 */
	public function __construct(
		public int $Id,
		public int $ParentId,
		public string $Title,
		public string $Url,
		public string $Description,
		public array $Classes,
		public int $Order,
		public MenuItemType $Type,
		public string $Object,
		public int $ObjectId,
		public array $Children = [],
		private ?\Closure $thumbnailResolver = null,
	) {
	}

	/** @param string|array{0: int, 1: int} $size */
	public function GetThumbnail(string|array $size = 'full'): ?ImgData
	{
		return $this->thumbnailResolver !== null ? ($this->thumbnailResolver)($size) : null;
	}

	public function GetLink(): LinkData
	{
		return new LinkData(Url: $this->Url, Title: $this->Title);
	}

	public function HasChildren(): bool
	{
		return $this->Children !== [];
	}

	public function IsTaxonomy(): bool
	{
		return $this->Type === MenuItemType::Taxonomy;
	}
}
