<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

use WpTypeSafety\Wp\Internal\MenuAdapter;

/** @template TNode of MenuNodeData = MenuNodeData */
class MenuData
{
	public readonly int $Id;
	public readonly string $Name;
	public readonly string $Slug;
	public readonly string $Description;
	/** @var list<TNode> */
	public readonly array $Nodes;

	/** @param MenuProps<TNode> $props */
	final protected function __construct(MenuProps $props)
	{
		$this->Id = $props->Id;
		$this->Name = $props->Name;
		$this->Slug = $props->Slug;
		$this->Description = $props->Description;
		$this->Nodes = $props->Nodes;
	}

	/** メニュー定義から、関連項目の取得・ツリー構築を伴って生成する。 */
	final public static function From(\WP_Term $term): ?static
	{
		$props = static::getProps($term);
		if ($props === null)
		{return null;}
		$data = new static($props);
		$data->initialize($term);
		return $data;
	}

	/** @return class-string<TNode> */
	protected static function getNodeClass(): string
	{
		// @phpstan-ignore return.type (既定型はMenuNodeData。別のTNodeを宣言する派生クラスは上書きする。)
		return MenuNodeData::class;
	}

	/** @return MenuProps<TNode>|null */
	protected static function getProps(\WP_Term $term): ?MenuProps
	{
		return MenuAdapter::PropsFromWpTerm($term, static::getNodeClass());
	}

	protected function initialize(\WP_Term $term): void
	{
	}

	/**
	 * 取得時点で現在のページを直接指していた最初のノード。
	 * @return TNode|null
	 */
	public function FindCurrent(): ?MenuNodeData
	{
		return MenuAdapter::FindCurrent($this->Nodes);
	}

	/** @return TNode|null */
	public function FindByPageId(int $pageId): ?MenuNodeData
	{
		return MenuAdapter::FindByPageId($this->Nodes, $pageId);
	}

	/** @return TNode|null */
	public function FindById(int $menuItemId): ?MenuNodeData
	{
		return MenuAdapter::FindById($this->Nodes, $menuItemId);
	}

	/** @return TNode|null */
	public function FindByObject(MenuItemType $type, string $object, int $objectId): ?MenuNodeData
	{
		return MenuAdapter::FindByObject($this->Nodes, $type, $object, $objectId);
	}

	/** @return TNode|null */
	public function FindByUrl(string $url): ?MenuNodeData
	{
		return MenuAdapter::FindByUrl($this->Nodes, $url);
	}

	/** @return list<TNode> */
	public function PathToId(int $menuItemId): array
	{
		return MenuAdapter::PathToId($this->Nodes, $menuItemId);
	}

	/** @return list<TNode> */
	public function PathToObject(MenuItemType $type, string $object, int $objectId): array
	{
		return MenuAdapter::PathToObject($this->Nodes, $type, $object, $objectId);
	}

	/** @return list<TNode> */
	public function PathToUrl(string $url): array
	{
		return MenuAdapter::PathToUrl($this->Nodes, $url);
	}
}
