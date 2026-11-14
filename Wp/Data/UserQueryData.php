<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

/**
 * ユーザークエリの変換結果とページ情報を保持します。
 *
 * @template-covariant TUser of WpUserData
 */
final readonly class UserQueryData
{
	/**
	 * @param list<TUser> $Users
	 */
	public function __construct(
		public array $Users,
		public ?int $TotalCount,
		public ?int $MaxPageCount,
		public int $CurrentPageNumber,
	) {}

	public function HasUsers(): bool
	{
		return $this->Users !== [];
	}
}
