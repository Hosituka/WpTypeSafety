<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

use WpTypeSafety\Core\Data\ImgData;

/**
 * PostDataを構築する共通メンバ。Dataへ渡す前に値を調整できます。
 *
 * @template TRelatedPost of PostData = PostData
 * @template TAuthor of WpUserData = WpUserData
 */
final class PostMembers
{
	/**
	 * @param null|\Closure(): (TAuthor|null) $AuthorResolver
	 * @param null|\Closure(): string $ExcerptResolver
	 * @param null|\Closure(): bool $ExcerptPresenceResolver
	 * @param null|\Closure(string|array{0: int, 1: int}): ?ImgData $ThumbnailResolver
	 * @param null|\Closure(string|array<array-key, string>, string): list<TRelatedPost> $ChildrenResolver
	 * @param null|\Closure(): (TRelatedPost|null) $PreviousPostResolver
	 * @param null|\Closure(): (TRelatedPost|null) $NextPostResolver
	 * @param null|\Closure(): string $TemplateSlugResolver
	 */
	public function __construct(
		public int $Id,
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
