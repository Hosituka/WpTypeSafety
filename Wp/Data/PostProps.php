<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

/** PostData構築用の値。構築前に変更でき、構築後の変更はDataへ反映されません。 */
final class PostProps
{
	public function __construct(
		public int $Id,
		public string $Url,
		public string $Title,
		public string $RawContent,
		public ?\DateTimeImmutable $PublishedAt = null,
		public ?\DateTimeImmutable $ModifiedAt = null,
		public string $Status = '',
		public string $PostTypeName = '',
		public string $Slug = '',
		public bool $IsPasswordProtected = false,
		public bool $IsPubliclyViewable = false,
		public int $ParentId = 0,
		public int $AuthorId = 0,
		public string $RawExcerpt = '',
	) {}
}
