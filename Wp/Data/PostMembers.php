<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

use WpTypeSafety\Core\Data\ImgData;

/** PostDataを構築する共通メンバ。Dataへ渡す前に値を調整できます。 */
final class PostMembers
{
	/**
	 * @param null|\Closure(): ?WpUserData $AuthorResolver
	 * @param null|\Closure(): string $ExcerptResolver
	 * @param null|\Closure(): bool $ExcerptPresenceResolver
	 * @param null|\Closure(string|array): ?ImgData $ThumbnailResolver
	 * @param null|\Closure(string|array, string): list<PostData> $ChildrenResolver
	 * @param null|\Closure(): ?PostData $PreviousPostResolver
	 * @param null|\Closure(): ?PostData $NextPostResolver
	 * @param null|\Closure(): string $TemplateSlugResolver
	 */
	public function __construct(
		public int $PostId,
		public string $Url,
		public string $Title,
		public string $Content,
		public ?\DateTimeImmutable $PublishedAt = null,
		public ?\DateTimeImmutable $ModifiedAt = null,
		public ?\Closure $AuthorResolver = null,
		public ?\Closure $ExcerptResolver = null,
		public ?\Closure $ExcerptPresenceResolver = null,
		public ?\Closure $ThumbnailResolver = null,
		public ?\Closure $ChildrenResolver = null,
		public ?\Closure $PreviousPostResolver = null,
		public ?\Closure $NextPostResolver = null,
		public ?\Closure $TemplateSlugResolver = null,
		public string $Status = '',
		public string $PostType = '',
		public string $Slug = '',
		public bool $IsPasswordProtected = false,
		public bool $IsPubliclyViewable = false,
	) {
	}
}
