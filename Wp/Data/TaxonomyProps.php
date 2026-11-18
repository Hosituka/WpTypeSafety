<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

/** タクソノミー定義の構築用の値。 */
final class TaxonomyProps
{
	/** @param list<string> $ObjectTypes */
	public function __construct(
		public string $Name,
		public string $Label,
		public string $SingularLabel,
		public string $Description = '',
		public array $ObjectTypes = [],
		public bool $IsPublic = false,
		public bool $IsPubliclyQueryable = false,
		public bool $IsHierarchical = false,
	) {}
}
