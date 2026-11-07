<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

use WpTypeSafety\Core\Data\ImgData;

readonly class CommentData
{
	/**
	 * @param null|\Closure(): ?WpUserData $userResolver
	 * @param null|\Closure(int): ?ImgData $avatarResolver
	 * @param null|\Closure(): ?PostData $postResolver
	 * @param null|\Closure(): ?CommentData $parentResolver
	 * @param null|\Closure(string, string): list<CommentData> $childrenResolver
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
		private ?int $avatarSize = null,
		private ?\Closure $userResolver = null,
		private ?\Closure $avatarResolver = null,
		private ?\Closure $postResolver = null,
		private ?\Closure $parentResolver = null,
		private ?\Closure $childrenResolver = null,
	) {
	}

	public function HasUser(): bool
	{
		return $this->AuthorUserId > 0;
	}

	public function HasContent(): bool
	{
		return trim($this->Content) !== '';
	}

	public function HasParent(): bool
	{
		return $this->ParentId > 0;
	}

	public function IsApproved(): bool
	{
		return $this->Status === 'approved';
	}

	public function GetUser(): ?WpUserData
	{
		return $this->HasUser() && $this->userResolver !== null
			? ($this->userResolver)()
			: null;
	}

	public function GetAvatar(?int $size = null): ?ImgData
	{
		return $this->avatarResolver !== null
			? ($this->avatarResolver)(max(1, $size ?? $this->avatarSize ?? 96))
			: null;
	}

	public function GetPost(): ?PostData
	{
		return $this->postResolver !== null ? ($this->postResolver)() : null;
	}

	public function GetParent(): ?CommentData
	{
		return $this->HasParent() && $this->parentResolver !== null
			? ($this->parentResolver)()
			: null;
	}

	/** @return list<CommentData> */
	public function GetChildren(string $status = 'approve', string $order = 'ASC'): array
	{
		if ($this->childrenResolver === null) {
			return [];
		}

		$children = ($this->childrenResolver)($status, $order);
		if (! is_array($children) || ! array_is_list($children)) {
			throw new \UnexpectedValueException('GetChildren must return a list of CommentData.');
		}
		foreach ($children as $child) {
			if (! $child instanceof self) {
				throw new \UnexpectedValueException('GetChildren must return a list of CommentData.');
			}
		}

		return $children;
	}
}
