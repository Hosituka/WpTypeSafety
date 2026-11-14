<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

/**
 * 投稿クエリの変換結果とページ情報を保持します。
 *
 * @template-covariant TPost of PostData
 */
final readonly class PostQueryData
{
	/**
	 * @param list<TPost> $Posts
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
