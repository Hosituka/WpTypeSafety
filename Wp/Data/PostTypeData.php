<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

use WpTypeSafety\Wp\Internal\DefinitionAdapter;

/** 投稿1件ではなく、投稿タイプの登録定義を表す。 */
class PostTypeData
{
	public readonly string $Name;
	public readonly string $Label;
	public readonly string $SingularLabel;
	public readonly string $Description;
	public readonly bool $IsPublic;
	public readonly bool $IsPubliclyQueryable;
	public readonly bool $IsHierarchical;
	public readonly bool $HasArchive;
	public readonly string $ArchiveUrl;

	final protected function __construct(PostTypeProps $props)
	{
		$this->Name = $props->Name;
		$this->Label = $props->Label;
		$this->SingularLabel = $props->SingularLabel;
		$this->Description = $props->Description;
		$this->IsPublic = $props->IsPublic;
		$this->IsPubliclyQueryable = $props->IsPubliclyQueryable;
		$this->IsHierarchical = $props->IsHierarchical;
		$this->HasArchive = $props->HasArchive;
		$this->ArchiveUrl = $props->ArchiveUrl;
	}

	final public static function From(\WP_Post_Type $postType): static
	{
		$props = static::getProps($postType);
		$data = new static($props);
		$data->initialize($postType);
		return $data;
	}

	protected static function getProps(\WP_Post_Type $postType): PostTypeProps
	{
		return DefinitionAdapter::PostTypePropsFromWp($postType);
	}

	protected function initialize(\WP_Post_Type $postType): void
	{
	}

	/** @return list<string> 呼び出すたびに関連タクソノミー名を取得する。 */
	public function GetTaxonomyNames(): array
	{
		return DefinitionAdapter::TaxonomyNames($this->Name);
	}
}
