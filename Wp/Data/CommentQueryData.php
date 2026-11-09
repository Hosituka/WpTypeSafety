<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

/** コメントクエリの変換結果とページ情報を保持します。 */
final readonly class CommentQueryData
{
	/**
	 * @param list<CommentData> $Comments
	 */
	public function __construct(
		public array $Comments,
		public ?int $TotalCount,
		public ?int $MaxPageCount,
		public int $CurrentPageNumber,
	) {}

	public function HasComments(): bool
	{
		return $this->Comments !== [];
	}
}
