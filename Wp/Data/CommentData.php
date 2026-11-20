<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

use WpTypeSafety\Wp\Internal\DtoFactory;

use WpTypeSafety\Core\Data\ImgData;
use WpTypeSafety\Wp\Get\PostGet;
use WpTypeSafety\Wp\Internal\CommentAdapter;
use WpTypeSafety\Wp\Internal\UserAdapter;

/**
 * @template TRelatedComment of CommentData = CommentData
 * @template TUser of WpUserData = WpUserData
 * @template TPost of PostData = PostData
 */
class CommentData
{
	public readonly int $Id;
	public readonly int $PostId;
	public readonly int $ParentId;
	public readonly int $AuthorUserId;
	public readonly string $AuthorName;
	public readonly string $WebsiteUrl;
	public readonly string $Url;
	public readonly string $Content;
	public readonly string $Type;
	public readonly string $Status;
	public readonly ?\DateTimeImmutable $CreatedAt;
	private readonly ?int $avatarSize;

	final protected function __construct(CommentProps $props)
	{
		$this->Id = $props->Id;
		$this->PostId = $props->PostId;
		$this->ParentId = $props->ParentId;
		$this->AuthorUserId = $props->AuthorUserId;
		$this->AuthorName = $props->AuthorName;
		$this->WebsiteUrl = $props->WebsiteUrl;
		$this->Url = $props->Url;
		$this->Content = $props->Content;
		$this->Type = $props->Type;
		$this->Status = $props->Status;
		$this->CreatedAt = $props->CreatedAt;
		$this->avatarSize = $props->AvatarSize;
	}

	final public static function From(\WP_Comment $comment, int $avatarSize = 96): static
	{
		$props = static::getProps($comment, $avatarSize);
		$data = new static($props);
		$data->initialize($comment, $avatarSize);
		return $data;
	}

	protected static function getProps(\WP_Comment $comment, int $avatarSize = 96): CommentProps
	{
		return DtoFactory::CommentPropsFromWpComment($comment, $avatarSize);
	}

	protected function initialize(\WP_Comment $comment, int $avatarSize = 96): void
	{
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
		// @phpstan-ignore return.type (既定型はWpUserData。別のTUserを宣言する派生クラスは上書きする。)
		return UserAdapter::ById($this->AuthorUserId);
	}

	public function GetAvatar(?int $size = null): ?ImgData
	{
		return CommentAdapter::Avatar($this, max(1, $size ?? $this->avatarSize ?? 96));
	}

	/** @return TPost|null */
	public function GetPost(): ?PostData
	{
		// @phpstan-ignore return.type (既定型はPostData。別のTPostを宣言する派生クラスは上書きする。)
		return PostGet::ById($this->PostId);
	}

	/** @return TRelatedComment|null */
	public function GetParent(): ?CommentData
	{
		return CommentAdapter::Parent($this->ParentId, $this->createRelatedComment(...));
	}

	/** @return list<TRelatedComment> */
	public function GetChildren(string $status = 'approve', string $order = 'ASC'): array
	{
		return CommentAdapter::Children($this->Id, $status, $order, $this->createRelatedComment(...));
	}
	/** @return TRelatedComment|null 親・子の共通生成フック。nullなら対象を除外する。 */
	protected function createRelatedComment(\WP_Comment $comment): ?CommentData
	{
		// @phpstan-ignore return.type (既定型はCommentData。別のTRelatedCommentを宣言する派生クラスは上書きする。)
		return CommentData::From($comment, $this->avatarSize ?? 96);
	}
}
