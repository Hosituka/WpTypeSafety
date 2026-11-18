<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

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

	public function __construct(TaxonomyProps $props)
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

	public function HasObjectType(string $postType): bool
	{
		return in_array($postType, $this->ObjectTypes, true);
	}
}
