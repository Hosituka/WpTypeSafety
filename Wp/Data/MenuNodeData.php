<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

use WpTypeSafety\Core\Data\ImgData;
use WpTypeSafety\Core\Data\LinkData;
use WpTypeSafety\Wp\Internal\MenuAdapter;

/** @template TChild of MenuNodeData = MenuNodeData */
class MenuNodeData
{
	public readonly int $Id;
	public readonly int $ParentId;
	public readonly string $Title;
	public readonly string $Url;
	public readonly string $Target;
	public readonly string $AttributeTitle;
	public readonly string $Xfn;
	public readonly string $Description;
	/** @var list<string> */
	public readonly array $Classes;
	public readonly int $Order;
	public readonly MenuItemType $Type;
	public readonly string $Object;
	public readonly int $ObjectId;
	/** @var list<TChild> */
	public readonly array $Children;

	/** @param MenuNodeProps<TChild> $props */
	public function __construct(MenuNodeProps $props)
	{
		$this->Id = $props->Id;
		$this->ParentId = $props->ParentId;
		$this->Title = $props->Title;
		$this->Url = $props->Url;
		$this->Target = $props->Target;
		$this->AttributeTitle = $props->AttributeTitle;
		$this->Xfn = $props->Xfn;
		$this->Description = $props->Description;
		$this->Classes = $props->Classes;
		$this->Order = $props->Order;
		$this->Type = $props->Type;
		$this->Object = $props->Object;
		$this->ObjectId = $props->ObjectId;
		$this->Children = $props->Children;
	}

	/** @param string|array{0: int, 1: int} $size */
	public function GetThumbnail(string|array $size = 'full'): ?ImgData
	{
		return MenuAdapter::Thumbnail($this->Type, $this->Object, $this->ObjectId, $size);
	}

	public function GetLink(): LinkData
	{
		return new LinkData(
			Url: $this->Url,
			Title: $this->Title,
			Target: $this->Target,
		);
	}

	public function IsNewTab(): bool
	{
		return $this->Target === '_blank';
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
