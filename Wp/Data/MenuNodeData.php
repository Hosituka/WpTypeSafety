<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

use WpTypeSafety\Core\Data\ImgData;
use WpTypeSafety\Core\Data\LinkData;

readonly class MenuNodeData
{
	public int $Id;
	public int $ParentId;
	public string $Title;
	public string $Url;
	public string $Description;
	/** @var list<string> */
	public array $Classes;
	public int $Order;
	public MenuItemType $Type;
	public string $Object;
	public int $ObjectId;
	/** @var list<MenuNodeData> */
	public array $Children;
	private ?\Closure $thumbnailResolver;

	public function __construct(MenuNodeMembers $members)
	{
		$this->Id = $members->Id;
		$this->ParentId = $members->ParentId;
		$this->Title = $members->Title;
		$this->Url = $members->Url;
		$this->Description = $members->Description;
		$this->Classes = $members->Classes;
		$this->Order = $members->Order;
		$this->Type = $members->Type;
		$this->Object = $members->Object;
		$this->ObjectId = $members->ObjectId;
		$this->Children = $members->Children;
		$this->thumbnailResolver = $members->ThumbnailResolver;
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
