<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

/**
 * TermDataの構築用の値。Dataへ渡す前に値を調整できます。
 */
final class TermProps
{
	public function __construct(
		public int $Id,
		public string $Name,
		public string $Slug,
		public string $TaxonomyName,
		public string $Url,
		public int $ParentId = 0,
		public string $Description = '',
	) {
	}
}
