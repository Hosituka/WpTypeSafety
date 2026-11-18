<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

/** 投稿タイプ定義の構築用の値。 */
final class PostTypeProps
{
	public function __construct(
		public string $Name,
		public string $Label,
		public string $SingularLabel,
		public string $Description = '',
		public bool $IsPublic = false,
		public bool $IsPubliclyQueryable = false,
		public bool $IsHierarchical = false,
		public bool $HasArchive = false,
		public string $ArchiveUrl = '',
	) {}
}
