<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

use WpTypeSafety\Core\Data\ImgData;
use WpTypeSafety\Core\Data\LinkData;

readonly class PostData
{
	public int $PostId;
	public string $Url;
	public string $Title;
	public string $Content;
	public ?\DateTimeImmutable $PublishedAt;
	public ?\DateTimeImmutable $ModifiedAt;
	private ?\Closure $authorResolver;
	private ?\Closure $excerptResolver;
	private ?\Closure $excerptPresenceResolver;
	private ?\Closure $thumbnailResolver;
	private ?\Closure $childrenResolver;
	private ?\Closure $previousPostResolver;
	private ?\Closure $nextPostResolver;
	private ?\Closure $templateSlugResolver;
	public string $Status;
	public string $PostType;
	public string $Slug;
	public bool $IsPasswordProtected;
	public bool $IsPubliclyViewable;

	public function __construct(PostMembers $members)
	{
		$this->PostId = $members->PostId;
		$this->Url = $members->Url;
		$this->Title = $members->Title;
		$this->Content = $members->Content;
		$this->PublishedAt = $members->PublishedAt;
		$this->ModifiedAt = $members->ModifiedAt;
		$this->authorResolver = $members->AuthorResolver;
		$this->excerptResolver = $members->ExcerptResolver;
		$this->excerptPresenceResolver = $members->ExcerptPresenceResolver;
		$this->thumbnailResolver = $members->ThumbnailResolver;
		$this->childrenResolver = $members->ChildrenResolver;
		$this->previousPostResolver = $members->PreviousPostResolver;
		$this->nextPostResolver = $members->NextPostResolver;
		$this->templateSlugResolver = $members->TemplateSlugResolver;
		$this->Status = $members->Status;
		$this->PostType = $members->PostType;
		$this->Slug = $members->Slug;
		$this->IsPasswordProtected = $members->IsPasswordProtected;
		$this->IsPubliclyViewable = $members->IsPubliclyViewable;
	}

	/** 選択済みテンプレートの相対パス。デフォルト・取得失敗は空文字。実際の適用ファイルの判定ではありません。 */
	public function GetTemplateSlug(): string
	{
		return $this->templateSlugResolver !== null ? ($this->templateSlugResolver)() : '';
	}

	public function GetAuthor(): ?WpUserData
	{
		return $this->authorResolver !== null ? ($this->authorResolver)() : null;
	}

	public function HasContent(): bool
	{
		return trim($this->Content) !== '';
	}

	/** WordPressの投稿状態がpublishかを返す。投稿タイプの公開可否は判定しない。 */
	public function IsPublished(): bool
	{
		return $this->Status === 'publish';
	}

	public function GetLink(): LinkData
	{
		return new LinkData(Url: $this->Url, Title: $this->Title);
	}

	public function GetExcerpt(): string
	{
		return $this->excerptResolver !== null ? ($this->excerptResolver)() : '';
	}

	public function HasExcerpt(): bool
	{
		return $this->excerptPresenceResolver !== null ? ($this->excerptPresenceResolver)() : false;
	}

	/** @param string|array{0: int, 1: int} $size */
	public function GetThumbnail(string|array $size = 'medium_large'): ?ImgData
	{
		return $this->thumbnailResolver !== null ? ($this->thumbnailResolver)($size) : null;
	}

	/**
	 * @param string|array<string, string> $orderby
	 * @return list<PostData>
	 */
	public function GetChildren(string|array $orderby = 'menu_order title ID', string $order = 'ASC'): array
	{
		if ($this->childrenResolver === null) {
			return [];
		}

		$children = ($this->childrenResolver)($orderby, $order);
		if (! is_array($children) || ! array_is_list($children)) {
			throw new \UnexpectedValueException('GetChildren must return a list of PostData.');
		}
		foreach ($children as $child) {
			if (! $child instanceof self) {
				throw new \UnexpectedValueException('GetChildren must return a list of PostData.');
			}
		}

		return $children;
	}

	public function GetPreviousPost(): ?PostData
	{
		return $this->previousPostResolver !== null ? ($this->previousPostResolver)() : null;
	}

	public function GetNextPost(): ?PostData
	{
		return $this->nextPostResolver !== null ? ($this->nextPostResolver)() : null;
	}
}
