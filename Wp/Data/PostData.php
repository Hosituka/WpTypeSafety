<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

use WpTypeSafety\Core\Data\ImgData;
use WpTypeSafety\Core\Data\LinkData;

readonly class PostData
{
	/**
	 * 取得関数は案件側で用意し、通常の引数として渡します。
	 * 取得関数を指定しなかったGet系は空値を返します。
	 *
	 * @param null|\Closure(): ?WpUserData $authorResolver
	 * @param null|\Closure(): string $excerptResolver
	 * @param null|\Closure(): bool $excerptPresenceResolver
	 * @param null|\Closure(string|array): ?ImgData $thumbnailResolver
	 * @param null|\Closure(string|array, string): list<PostData> $childrenResolver
	 * @param null|\Closure(): ?PostData $previousPostResolver
	 * @param null|\Closure(): ?PostData $nextPostResolver
	 * @param null|\Closure(): string $templateSlugResolver
	 */
	public function __construct(
		public int $PostId,
		public string $Url,
		public string $Title,
		public string $Content,
		public ?\DateTimeImmutable $PublishedAt = null,
		public ?\DateTimeImmutable $ModifiedAt = null,
		private ?\Closure $authorResolver = null,
		private ?\Closure $excerptResolver = null,
		private ?\Closure $excerptPresenceResolver = null,
		private ?\Closure $thumbnailResolver = null,
		private ?\Closure $childrenResolver = null,
		private ?\Closure $previousPostResolver = null,
		private ?\Closure $nextPostResolver = null,
		private ?\Closure $templateSlugResolver = null,
	) {
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
