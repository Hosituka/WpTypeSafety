<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

/** 投稿クエリの変換結果とページ情報を保持します。 */
final readonly class PostQueryData
{
	/**
	 * @param list<PostData> $Posts
	 */
	public function __construct(
		public array $Posts,
		public ?int $TotalCount,
		public ?int $MaxPageCount,
		public int $CurrentPageNumber,
	) {}

	public function HasPosts(): bool
	{
		return $this->Posts !== [];
	}
}
