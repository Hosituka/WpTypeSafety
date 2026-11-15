<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

/** 投稿タイプ定義の構築用メンバ。 */
final class PostTypeMembers
{
	/** @param null|\Closure(): list<string> $TaxonomyNamesResolver */
	public function __construct(
		public string $Name,
		public string $Label,
		public string $SingularLabel,
		public string $Description = '',
		public bool $IsPublic = false,
		public bool $IsPubliclyQueryable = false,
		public bool $IsHierarchical = false,
		public bool $HasArchive = false,
		public ?string $ArchiveUrl = null,
		public ?\Closure $TaxonomyNamesResolver = null,
	) {}
}
