<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

readonly class TermData
{
	public int $Id;
	public string $Name;
	public string $Slug;
	public string $Taxonomy;
	public string $Url;
	public int $ParentId;
	public string $Description;
	private ?\Closure $childrenResolver;
	private ?\Closure $parentResolver;

	public function __construct(TermMembers $members)
	{
		$this->Id = $members->Id;
		$this->Name = $members->Name;
		$this->Slug = $members->Slug;
		$this->Taxonomy = $members->Taxonomy;
		$this->Url = $members->Url;
		$this->ParentId = $members->ParentId;
		$this->Description = $members->Description;
		$this->childrenResolver = $members->ChildrenResolver;
		$this->parentResolver = $members->ParentResolver;
	}

	/** @return list<TermData> */
	public function GetChildren(bool $hideEmpty = false, string $orderby = 'name', string $order = 'ASC'): array
	{
		if ($this->childrenResolver === null) {
			return [];
		}

		$children = ($this->childrenResolver)($hideEmpty, $orderby, $order);
		if (! is_array($children) || ! array_is_list($children)) {
			throw new \UnexpectedValueException('GetChildren must return a list of TermData.');
		}
		foreach ($children as $child) {
			if (! $child instanceof self) {
				throw new \UnexpectedValueException('GetChildren must return a list of TermData.');
			}
		}

		return $children;
	}

	public function HasParent(): bool
	{
		return $this->ParentId !== 0;
	}

	/** 直接の親を取得する。親なし・Resolver省略時はnull。 */
	public function GetParent(): ?TermData
	{
		return $this->HasParent() && $this->parentResolver !== null
			? ($this->parentResolver)()
			: null;
	}
}
