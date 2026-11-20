<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

use WpTypeSafety\Wp\Internal\DefinitionAdapter;

/** ターム1件ではなく、タクソノミーの登録定義を表す。 */
class TaxonomyData
{
	public readonly string $Name;
	public readonly string $Label;
	public readonly string $SingularLabel;
	public readonly string $Description;
	/** @var list<string> */
	public readonly array $ObjectTypes;
	public readonly bool $IsPublic;
	public readonly bool $IsPubliclyQueryable;
	public readonly bool $IsHierarchical;

	final protected function __construct(TaxonomyProps $props)
	{
		$this->Name = $props->Name;
		$this->Label = $props->Label;
		$this->SingularLabel = $props->SingularLabel;
		$this->Description = $props->Description;
		$this->ObjectTypes = $props->ObjectTypes;
		$this->IsPublic = $props->IsPublic;
		$this->IsPubliclyQueryable = $props->IsPubliclyQueryable;
		$this->IsHierarchical = $props->IsHierarchical;
	}

	final public static function From(\WP_Taxonomy $taxonomy): static
	{
		$props = static::getProps($taxonomy);
		$data = new static($props);
		$data->initialize($taxonomy);
		return $data;
	}

	protected static function getProps(\WP_Taxonomy $taxonomy): TaxonomyProps
	{
		return DefinitionAdapter::TaxonomyPropsFromWp($taxonomy);
	}

	protected function initialize(\WP_Taxonomy $taxonomy): void
	{
	}

	public function HasObjectType(string $postType): bool
	{
		return in_array($postType, $this->ObjectTypes, true);
	}
}
