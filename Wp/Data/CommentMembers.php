<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

use WpTypeSafety\Core\Data\ImgData;

/**
 * CommentDataを構築する共通メンバ。Dataへ渡す前に値を調整できます。
 *
 * @template TRelatedComment of CommentData = CommentData
 * @template TUser of WpUserData = WpUserData
 * @template TPost of PostData = PostData
 */
final class CommentMembers
{
	/**
	 * @param null|\Closure(): (TUser|null) $UserResolver
	 * @param null|\Closure(int): ?ImgData $AvatarResolver
	 * @param null|\Closure(): (TPost|null) $PostResolver
	 * @param null|\Closure(): (TRelatedComment|null) $ParentResolver
	 * @param null|\Closure(string, string): list<TRelatedComment> $ChildrenResolver
	 */
	public function __construct(
		public int $Id,
		public int $PostId,
		public int $ParentId,
		public int $AuthorUserId,
		public string $AuthorName,
		public string $AuthorUrl,
		public string $Url,
		public string $Content,
		public string $Type,
		public string $Status,
		public ?\DateTimeImmutable $CreatedAt = null,
		public ?int $AvatarSize = null,
		public ?\Closure $UserResolver = null,
		public ?\Closure $AvatarResolver = null,
		public ?\Closure $PostResolver = null,
		public ?\Closure $ParentResolver = null,
		public ?\Closure $ChildrenResolver = null,
	) {
	}
}
