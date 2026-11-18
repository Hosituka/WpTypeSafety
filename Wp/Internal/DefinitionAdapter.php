<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Internal;

use WpTypeSafety\Core\Internal\UrlCheck;
use WpTypeSafety\Wp\Data\PostTypeData;
use WpTypeSafety\Wp\Data\PostTypeProps;
use WpTypeSafety\Wp\Data\TaxonomyData;
use WpTypeSafety\Wp\Data\TaxonomyProps;

/** @internal 投稿タイプ・タクソノミーの登録定義を正規化する。 */
final class DefinitionAdapter
{
	private function __construct() {}

	/** @return list<string> */
	public static function TaxonomyNames(string $name): array
	{
		return $name !== '' ? self::names(\get_object_taxonomies($name, 'names')) : [];
	}


	public static function CurrentPostType(): ?PostTypeData
	{
		$name = MainQueryAdapter::PostTypeName();
		return $name !== null ? self::PostTypeFromName($name) : null;
	}

	public static function PostTypeFromName(string $name): ?PostTypeData
	{
		if ($name === '')
		{return null;}
		$postType = \get_post_type_object($name);
		return $postType instanceof \WP_Post_Type
			? self::PostTypeFromWp($postType)
			: null;
	}

	public static function TaxonomyFromName(string $name): ?TaxonomyData
	{
		if ($name === '')
		{return null;}
		$taxonomy = \get_taxonomy($name);
		return $taxonomy instanceof \WP_Taxonomy
			? self::TaxonomyFromWp($taxonomy)
			: null;
	}

	public static function PostTypeFromWp(\WP_Post_Type $postType): PostTypeData
	{
		return new PostTypeData(self::PostTypePropsFromWp($postType));
	}

	public static function TaxonomyFromWp(\WP_Taxonomy $taxonomy): TaxonomyData
	{
		return new TaxonomyData(self::TaxonomyPropsFromWp($taxonomy));
	}

	public static function PostTypePropsFromWp(\WP_Post_Type $postType): PostTypeProps
	{
		$name = self::text($postType->name);
		$label = self::text($postType->label);
		$archive = $name !== '' ? \get_post_type_archive_link($name) : false;
		return new PostTypeProps(
			Name: $name,
			Label: $label,
			SingularLabel: self::text($postType->labels->singular_name ?? null) ?: $label,
			Description: self::text($postType->description),
			IsPublic: (bool) $postType->public,
			IsPubliclyQueryable: (bool) $postType->publicly_queryable,
			IsHierarchical: (bool) $postType->hierarchical,
			HasArchive: (bool) $postType->has_archive,
			ArchiveUrl: UrlCheck::IsHttpUrl($archive) ? $archive : '',
		);
	}

	public static function TaxonomyPropsFromWp(\WP_Taxonomy $taxonomy): TaxonomyProps
	{
		$label = self::text($taxonomy->label);
		return new TaxonomyProps(
			Name: self::text($taxonomy->name),
			Label: $label,
			SingularLabel: self::text($taxonomy->labels->singular_name ?? null) ?: $label,
			Description: self::text($taxonomy->description),
			ObjectTypes: self::names($taxonomy->object_type),
			IsPublic: (bool) $taxonomy->public,
			IsPubliclyQueryable: (bool) $taxonomy->publicly_queryable,
			IsHierarchical: (bool) $taxonomy->hierarchical,
		);
	}

	private static function text(mixed $value): string
	{
		return is_string($value) ? $value : '';
	}

	/** @return list<string> */
	private static function names(mixed $value): array
	{
		if (! is_array($value))
		{return [];}
		return array_values(array_filter($value, static fn (mixed $name): bool => is_string($name) && $name !== ''));
	}
}
