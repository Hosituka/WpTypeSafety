<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

/** TermDataを構築する共通メンバ。Dataへ渡す前に値を調整できます。 */
final class TermMembers
{
	/**
	 * @param null|\Closure(bool, string, string): list<TermData> $ChildrenResolver
	 * @param null|\Closure(): ?TermData $ParentResolver
	 */
	public function __construct(
		public int $Id,
		public string $Name,
		public string $Slug,
		public string $Taxonomy,
		public string $Url,
		public int $ParentId = 0,
		public string $Description = '',
		public ?\Closure $ChildrenResolver = null,
		public ?\Closure $ParentResolver = null,
	) {
	}
}
