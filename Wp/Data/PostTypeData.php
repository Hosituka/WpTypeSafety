<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

/** 投稿1件ではなく、投稿タイプの登録定義を表す。 */
readonly class PostTypeData
{
	public string $Name;
	public string $Label;
	public string $SingularLabel;
	public string $Description;
	public bool $IsPublic;
	public bool $IsPubliclyQueryable;
	public bool $IsHierarchical;
	public bool $HasArchive;
	public ?string $ArchiveUrl;
	/** @var null|\Closure(): list<string> */
	private ?\Closure $taxonomyNamesResolver;

	public function __construct(PostTypeMembers $members)
	{
		$this->Name = $members->Name;
		$this->Label = $members->Label;
		$this->SingularLabel = $members->SingularLabel;
		$this->Description = $members->Description;
		$this->IsPublic = $members->IsPublic;
		$this->IsPubliclyQueryable = $members->IsPubliclyQueryable;
		$this->IsHierarchical = $members->IsHierarchical;
		$this->HasArchive = $members->HasArchive;
		$this->ArchiveUrl = $members->ArchiveUrl;
		$this->taxonomyNamesResolver = $members->TaxonomyNamesResolver;
	}

	/** @return list<string> 呼び出すたびに関連タクソノミー名を取得する。 */
	public function GetTaxonomyNames(): array
	{
		if ($this->taxonomyNamesResolver === null)
		{return [];}

		$names = ($this->taxonomyNamesResolver)();
		if (! is_array($names) || ! array_is_list($names)) {
			throw new \UnexpectedValueException('TaxonomyNamesResolver must return a list of strings.');
		}
		foreach ($names as $name) {
			if (! is_string($name)) {
				throw new \UnexpectedValueException('TaxonomyNamesResolver must return a list of strings.');
			}
		}
		return $names;
	}
}
