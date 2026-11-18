<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

/**
 * CommentDataの構築用の値。Dataへ渡す前に値を調整できます。
 */
final class CommentProps
{
	public function __construct(
		public int $Id,
		public int $PostId,
		public int $ParentId,
		public int $AuthorUserId,
		public string $AuthorName,
		public string $WebsiteUrl,
		public string $Url,
		public string $Content,
		public string $Type,
		public string $Status,
		public ?\DateTimeImmutable $CreatedAt = null,
		public ?int $AvatarSize = null,
	) {
	}
}
