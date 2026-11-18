<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

use WpTypeSafety\Wp\Get\TermGet;
use WpTypeSafety\Wp\Internal\TermAdapter;
use WpTypeSafety\Wp\Internal\DefinitionAdapter;

/** @template TRelatedTerm of TermData = TermData */
class TermData
{
	public readonly int $Id;
	public readonly string $Name;
	public readonly string $Slug;
	public readonly string $TaxonomyName;
	public readonly string $Url;
	public readonly int $ParentId;
	public readonly string $Description;

	public function __construct(TermProps $props)
	{
		$this->Id = $props->Id;
		$this->Name = $props->Name;
		$this->Slug = $props->Slug;
		$this->TaxonomyName = $props->TaxonomyName;
		$this->Url = $props->Url;
		$this->ParentId = $props->ParentId;
		$this->Description = $props->Description;
	}

	/** 登録定義を遅延取得する。空の登録名・未登録時はnull。 */
	public function GetTaxonomy(): ?TaxonomyData
	{
		return $this->TaxonomyName !== '' ? DefinitionAdapter::TaxonomyFromName($this->TaxonomyName) : null;
	}

	/** @return list<TRelatedTerm> */
	public function GetChildren(bool $hideEmpty = false, string $orderby = 'name', string $order = 'ASC'): array
	{
		return TermAdapter::Children($this->Id, $this->TaxonomyName, $hideEmpty, $orderby, $order, $this->createRelatedTerm(...));
	}

	public function HasParent(): bool
	{
		return $this->ParentId > 0;
	}

	/**
	 * 直接の親を取得する。親なし時はnull。
	 *
	 * @return TRelatedTerm|null
	 */
	public function GetParent(): ?TermData
	{
		return TermAdapter::Parent($this->ParentId, $this->TaxonomyName, $this->createRelatedTerm(...));
	}
	/** @return TRelatedTerm|null 親・子の共通生成フック。nullなら対象を除外する。 */
	protected function createRelatedTerm(\WP_Term $term): ?TermData
	{
		// @phpstan-ignore return.type (既定型はTermData。別のTRelatedTermを宣言する派生クラスは上書きする。)
		return TermGet::From($term);
	}
}
