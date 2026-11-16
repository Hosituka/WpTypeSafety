<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

use WpTypeSafety\Core\Data\ImgData;

/**
 * @template TRelatedComment of CommentData = CommentData
 * @template TUser of WpUserData = WpUserData
 * @template TPost of PostData = PostData
 */
readonly class CommentData
{
	public int $Id;
	public int $PostId;
	public int $ParentId;
	public int $AuthorUserId;
	public string $AuthorName;
	public string $WebsiteUrl;
	public string $Url;
	public string $Content;
	public string $Type;
	public string $Status;
	public ?\DateTimeImmutable $CreatedAt;
	private ?int $avatarSize;
	/** @var null|\Closure(): (TUser|null) */
	private ?\Closure $userResolver;
	private ?\Closure $avatarResolver;
	/** @var null|\Closure(): (TPost|null) */
	private ?\Closure $postResolver;
	/** @var null|\Closure(): (TRelatedComment|null) */
	private ?\Closure $parentResolver;
	/** @var null|\Closure(string, string): list<TRelatedComment> */
	private ?\Closure $childrenResolver;

	/** @param CommentMembers<TRelatedComment, TUser, TPost> $members */
	public function __construct(CommentMembers $members)
	{
		$this->Id = $members->Id;
		$this->PostId = $members->PostId;
		$this->ParentId = $members->ParentId;
		$this->AuthorUserId = $members->AuthorUserId;
		$this->AuthorName = $members->AuthorName;
		$this->WebsiteUrl = $members->WebsiteUrl;
		$this->Url = $members->Url;
		$this->Content = $members->Content;
		$this->Type = $members->Type;
		$this->Status = $members->Status;
		$this->CreatedAt = $members->CreatedAt;
		$this->avatarSize = $members->AvatarSize;
		$this->userResolver = $members->UserResolver;
		$this->avatarResolver = $members->AvatarResolver;
		$this->postResolver = $members->PostResolver;
		$this->parentResolver = $members->ParentResolver;
		$this->childrenResolver = $members->ChildrenResolver;
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

	/** @return TUser|null */
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

	/** @return TPost|null */
	public function GetPost(): ?PostData
	{
		return $this->postResolver !== null ? ($this->postResolver)() : null;
	}

	/** @return TRelatedComment|null */
	public function GetParent(): ?CommentData
	{
		return $this->HasParent() && $this->parentResolver !== null
			? ($this->parentResolver)()
			: null;
	}

	/** @return list<TRelatedComment> */
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
