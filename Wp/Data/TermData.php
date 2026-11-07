<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

readonly class TermData
{
	/**
	 * @param null|\Closure(bool, string, string): list<TermData> $childrenResolver
	 * @param null|\Closure(): ?TermData $parentResolver
	 */
	public function __construct(
		public int $Id,
		public string $Name,
		public string $Slug,
		public string $Taxonomy,
		public string $Url,
		public int $ParentId = 0,
		public string $Description = '',
		private ?\Closure $childrenResolver = null,
		private ?\Closure $parentResolver = null,
	) {
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
