<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

/** ターム1件ではなく、タクソノミーの登録定義を表す。 */
readonly class TaxonomyData
{
	public string $Name;
	public string $Label;
	public string $SingularLabel;
	public string $Description;
	/** @var list<string> */
	public array $ObjectTypes;
	public bool $IsPublic;
	public bool $IsPubliclyQueryable;
	public bool $IsHierarchical;

	public function __construct(TaxonomyMembers $members)
	{
		$this->Name = $members->Name;
		$this->Label = $members->Label;
		$this->SingularLabel = $members->SingularLabel;
		$this->Description = $members->Description;
		$this->ObjectTypes = $members->ObjectTypes;
		$this->IsPublic = $members->IsPublic;
		$this->IsPubliclyQueryable = $members->IsPubliclyQueryable;
		$this->IsHierarchical = $members->IsHierarchical;
	}

	public function HasObjectType(string $postType): bool
	{
		return in_array($postType, $this->ObjectTypes, true);
	}
}
