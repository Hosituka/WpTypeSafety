<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

use WpTypeSafety\Core\Data\ImgData;
use WpTypeSafety\Core\Data\LinkData;
use WpTypeSafety\Wp\Internal\DtoFactory;
use WpTypeSafety\Wp\Internal\DataClass;
use WpTypeSafety\Wp\Internal\DefinitionAdapter;
use WpTypeSafety\Wp\Internal\PostAdapter;
use WpTypeSafety\Wp\Internal\TermAdapter;

/**
 * @template TRelatedPost of PostData = PostData
 * @template TAuthor of WpUserData = WpUserData
 */
class PostData
{
	public readonly int $Id;
	public readonly int $ParentId;
	public readonly int $AuthorId;
	public readonly string $Url;
	public readonly string $Title;
	public readonly string $RawContent;
	public readonly string $RawExcerpt;
	public readonly ?\DateTimeImmutable $PublishedAt;
	public readonly ?\DateTimeImmutable $ModifiedAt;
	public readonly string $Status;
	public readonly string $PostTypeName;
	public readonly string $Slug;
	public readonly bool $IsPasswordProtected;
	public readonly bool $IsPubliclyViewable;

	final protected function __construct(PostProps $props)
	{
		$this->Id = $props->Id;
		$this->ParentId = $props->ParentId;
		$this->AuthorId = $props->AuthorId;
		$this->Url = $props->Url;
		$this->Title = $props->Title;
		$this->RawContent = $props->RawContent;
		$this->RawExcerpt = $props->RawExcerpt;
		$this->PublishedAt = $props->PublishedAt;
		$this->ModifiedAt = $props->ModifiedAt;
		$this->Status = $props->Status;
		$this->PostTypeName = $props->PostTypeName;
		$this->Slug = $props->Slug;
		$this->IsPasswordProtected = $props->IsPasswordProtected;
		$this->IsPubliclyViewable = $props->IsPubliclyViewable;
	}

	/** 指定クラスの生成手順を固定する。追加値はinitializeで設定する。 */
	final public static function From(\WP_Post $post): static
	{
		$props = static::getProps($post);
		$data = new static($props);
		$data->initialize($post);
		return $data;
	}

	protected static function getProps(\WP_Post $post): PostProps
	{
		return DtoFactory::PostPropsFromWpPost($post);
	}

	/** 派生クラスは先頭でparent::initialize($post)を呼ぶ。 */
	protected function initialize(\WP_Post $post): void
	{
	}

	/** 登録定義を遅延取得する。空の登録名・未登録時はnull。 */
	public function GetPostType(): ?PostTypeData
	{
		return $this->PostTypeName !== '' ? DefinitionAdapter::PostTypeFromName($this->PostTypeName) : null;
	}

	/** 選択済みテンプレートの相対パス。デフォルト・取得失敗は空文字。実際の適用ファイルの判定ではありません。 */
	public function GetTemplateSlug(): string
	{
		return PostAdapter::TemplateSlug($this->Id);
	}

	/** @return TAuthor|null */
	public function GetAuthor(): ?WpUserData
	{
		// @phpstan-ignore return.type (既定型はWpUserData。別のTAuthorを宣言する派生クラスはこのメソッドを上書きする。)
		return PostAdapter::Author($this->AuthorId);
	}

	public function HasContent(): bool
	{
		return trim($this->RawContent) !== '';
	}

	/** 保持済みRawContentから描画用HTMLを取得。投稿が存在しなければ空文字。出力・キャッシュは行わない。 */
	public function GetRenderedContent(): string
	{
		return PostAdapter::RenderedContent($this);
	}

	/**
	 * @template TTerm of TermData
	 * @param class-string<TTerm> $dataClass
	 * @return list<TTerm>
	 */
	public function GetTerms(string $taxonomy, string $dataClass = TermData::class): array
	{
		DataClass::Validate($dataClass, TermData::class);
		if ($this->Id <= 0 || $taxonomy === '')
		{return [];}
		$terms = TermAdapter::FromPost($this->Id, $taxonomy, $dataClass);
		if (! array_is_list($terms)) {
			throw new \UnexpectedValueException('GetTerms must return a list of TermData.');
		}
		foreach ($terms as $term) {
			if (! $term instanceof TermData) {
				throw new \UnexpectedValueException('GetTerms must return a list of TermData.');
			}
		}
		return $terms;
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

	/** 手入力の抜粋を保持値から取得する。本文からの自動生成はしない。 */
	public function GetExcerpt(): string
	{
		return PostAdapter::Excerpt($this->RawExcerpt);
	}

	/** 手入力の抜粋の有無。WordPressのhas_excerptフィルターは適用しない。 */
	public function HasExcerpt(): bool
	{
		return $this->RawExcerpt !== '';
	}

	/** @param string|array{0: int, 1: int} $size */
	public function GetThumbnail(string|array $size = 'medium_large'): ?ImgData
	{
		return PostAdapter::Thumbnail($this->Id, $size);
	}

	/**
	 * @param string|array<array-key, string> $orderby
	 * @return list<TRelatedPost>
	 */
	public function GetChildren(string|array $orderby = 'menu_order title ID', string $order = 'ASC'): array
	{
		$children = PostAdapter::Children($this->Id, $orderby, $order, $this->createRelatedPost(...));
		if (! array_is_list($children)) {
			throw new \UnexpectedValueException('GetChildren must return a list of PostData.');
		}
		foreach ($children as $child) {
			if (! $child instanceof self) {
				throw new \UnexpectedValueException('GetChildren must return a list of PostData.');
			}
		}

		return $children;
	}

	/** @return TRelatedPost|null */
	public function GetPreviousPost(): ?PostData
	{
		return PostAdapter::Adjacent($this->Id, true, $this->createRelatedPost(...));
	}

	/** 保存済みの親IDの有無。親投稿の存在確認は行わない。 */
	public function HasParent(): bool
	{
		return $this->ParentId > 0;
	}

	/**
	 * 直接の親を取得する。親なし・取得失敗時はnull。
	 * @return TRelatedPost|null
	 */
	public function GetParent(): ?PostData
	{
		return PostAdapter::Related($this->ParentId, $this->createRelatedPost(...));
	}

	/** @return TRelatedPost|null */
	public function GetNextPost(): ?PostData
	{
		return PostAdapter::Adjacent($this->Id, false, $this->createRelatedPost(...));
	}
	/**
	 * 親・子・前後投稿で共通の生成フック。nullを返すと対象を除外する。
	 * 派生クラスはTRelatedPostに対応するDataを返すように上書きする。
	 * @return TRelatedPost|null
	 */
	protected function createRelatedPost(\WP_Post $post): ?PostData
	{
		// @phpstan-ignore return.type (既定型はPostData。別のTRelatedPostを宣言する派生クラスはこのフックを上書きする。)
		return PostData::From($post);
	}
}
