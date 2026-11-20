<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Internal;

use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\TermData;
use WpTypeSafety\Wp\Data\TermProps;
use WpTypeSafety\Wp\Data\TaxonomyData;

/** @internal WordPressのタームをCoreのTermDataへ変換する。 */
final class TermAdapter
{
	private function __construct() {}

	/**
	 * @template TTerm of TermData = TermData
	 * @param class-string<TTerm> $dataClass
	 * @return TTerm|null
	 */
	public static function ById(int $termId, ?string $taxonomy = null, string $dataClass = TermData::class): ?TermData
	{
		DataClass::Validate($dataClass, TermData::class);
		if ($termId <= 0 || $taxonomy === '')
		{return null;}
		$term = \get_term($termId, $taxonomy ?? '');
		if (!$term instanceof \WP_Term || ($taxonomy !== null && $term->taxonomy !== $taxonomy))
		{return null;}
		return $dataClass::From($term);
	}

	/**
	 * @template TTerm of TermData = TermData
	 * @param class-string<TTerm> $dataClass
	 * @return TTerm|null
	 */
	public static function BySlug(
		string $taxonomy,
		string $slug,
		string $dataClass = TermData::class,
	): ?TermData
	{
		DataClass::Validate($dataClass, TermData::class);
		if ($taxonomy === '' || $slug === '')
		{return null;}

		$term = \get_term_by('slug', $slug, $taxonomy);
		return $term instanceof \WP_Term ? $dataClass::From($term) : null;
	}

	/**
	 * @template TTerm of TermData = TermData
	 * @param class-string<TTerm> $dataClass
	 * @return TTerm|null
	 */
	public static function Current(string $dataClass = TermData::class): ?TermData
	{
		DataClass::Validate($dataClass, TermData::class);
		$term = MainQueryAdapter::QueriedObject();
		return $term instanceof \WP_Term ? $dataClass::From($term) : null;
	}

	/**
	 * @template TTerm of TermData = TermData
	 * @param class-string<TTerm> $dataClass
	 * @return list<TTerm>
	 */
	public static function FromPost(
		\WP_Post|PostData|int $post,
		string $taxonomy,
		string $dataClass = TermData::class,
	): array
	{
		DataClass::Validate($dataClass, TermData::class);
		if ($post instanceof \WP_Post) {
			$postId = $post->ID;
		} elseif ($post instanceof PostData) {
			$postId = $post->Id;
		} else {
			$postId = $post;
		}
		$terms = \get_the_terms($postId, $taxonomy);
		if (! is_array($terms)) {
			return [];
		}

		$result = [];
		foreach ($terms as $term) {
			if (! $term instanceof \WP_Term) {
				continue;
			}
			$data = $dataClass::From($term);
			if ($data !== null) {
				$result[] = $data;
			}
		}
		return $result;
	}

	/**
	 * @template TInput of TermData
	 * @template TTerm of TermData = TermData
	 * @param list<TInput> $terms
	 * @param class-string<TTerm> $dataClass
	 * @return list<TInput|TTerm>
	 */
	public static function Roots(array $terms, string $dataClass = TermData::class): array
	{
		DataClass::Validate($dataClass, TermData::class);
		$roots = [];
		foreach ($terms as $term) {
			if (! $term instanceof TermData) {
				continue;
			}

			$rootData = $term;
			if ($term->HasParent()) {
				$ancestors = \get_ancestors($term->Id, $term->TaxonomyName, 'taxonomy');
				if ($ancestors !== []) {
					$root = \get_term((int) end($ancestors), $term->TaxonomyName);
					if ($root instanceof \WP_Term) {
						$rootData = $dataClass::From($root) ?? $term;
					}
				}
			}

			$key = $rootData->TaxonomyName . ':' . $rootData->Id;
			$roots[$key] ??= $rootData;
		}
		return array_values($roots);
	}

	/**
	 * @template TTerm of TermData = TermData
	 * @param list<TTerm> $terms
	 * @return list<TTerm>
	 */
	public static function Deepest(array $terms): array
	{
		$selected = [];
		$deepestLevel = -1;
		foreach ($terms as $term) {
			if (! $term instanceof TermData) {
				continue;
			}
			$level = count(\get_ancestors($term->Id, $term->TaxonomyName, 'taxonomy'));
			if ($level > $deepestLevel) {
				$selected = [$term];
				$deepestLevel = $level;
			} elseif ($level === $deepestLevel) {
				$selected[] = $term;
			}
		}
		return $selected;
	}

	/**
	 * @template TTerm of TermData = TermData
	 * @param list<TTerm> $terms
	 * @return list<TTerm>
	 */
	public static function Shallowest(array $terms): array
	{
		$selected = [];
		$shallowestLevel = null;
		foreach ($terms as $term) {
			if (! $term instanceof TermData) {
				continue;
			}
			$level = count(\get_ancestors($term->Id, $term->TaxonomyName, 'taxonomy'));
			if ($shallowestLevel === null || $level < $shallowestLevel) {
				$selected = [$term];
				$shallowestLevel = $level;
			} elseif ($level === $shallowestLevel) {
				$selected[] = $term;
			}
		}
		return $selected;
	}

	/** @return TermData<TermData>|null */
	public static function FromWpTerm(\WP_Term $term): ?TermData
	{
		return TermData::From($term);
	}

	public static function PropsFromWpTerm(\WP_Term $term): ?TermProps
	{
		$url = \get_term_link($term);
		if (\is_wp_error($url) || ! is_string($url))
		{return null;}

		return new TermProps(
			Id: $term->term_id,
			Name: $term->name,
			Slug: $term->slug,
			TaxonomyName: $term->taxonomy,
			Url: $url,
			ParentId: $term->parent,
			Description: $term->description,
		);
	}

	/**
	 * @template TTerm of TermData
	 * @param \Closure(\WP_Term): (TTerm|null) $createRelatedTerm
	 * @return TTerm|null
	 */
	public static function Parent(int $parentId, string $taxonomy, \Closure $createRelatedTerm): ?TermData
	{
		if ($parentId <= 0 || $taxonomy === '')
		{return null;}

		$parent = \get_term($parentId, $taxonomy);
		return $parent instanceof \WP_Term ? self::relatedTerm($parent, $createRelatedTerm) : null;
	}

	/**
	 * @template TRelatedTerm of TermData = TermData
	 * @param \Closure(\WP_Term): (TRelatedTerm|null) $createRelatedTerm
	 * @return list<TRelatedTerm>
	 */
	public static function Children(
		int $parentId,
		string $taxonomy,
		bool $hideEmpty,
		string $orderby,
		string $order,
		\Closure $createRelatedTerm,
	): array {
		if ($parentId <= 0 || $taxonomy === '' || ! \is_taxonomy_hierarchical($taxonomy)) {
			return [];
		}
		if (! \get_term($parentId, $taxonomy) instanceof \WP_Term) {
			return [];
		}

		$terms = \get_terms([
			'taxonomy' => $taxonomy,
			'parent' => $parentId,
			'hide_empty' => $hideEmpty,
			'orderby' => $orderby,
			'order' => $order,
		]);
		if (! is_array($terms)) {
			return [];
		}

		$children = [];
		foreach ($terms as $term) {
			if (! $term instanceof \WP_Term) {
				continue;
			}
			$data = self::relatedTerm($term, $createRelatedTerm);
			if ($data !== null) {
				$children[] = $data;
			}
		}
		return $children;
	}

	/**
	 * @template TRelatedTerm of TermData = TermData
	 * @param null|\Closure(\WP_Term): (TRelatedTerm|null) $relatedTermFactory
	 * @return TRelatedTerm|null
	 */
	private static function relatedTerm(\WP_Term $term, ?\Closure $relatedTermFactory): ?TermData
	{
		$data = $relatedTermFactory !== null ? $relatedTermFactory($term) : self::FromWpTerm($term);
		if ($data !== null && ! $data instanceof TermData) {
			throw new \UnexpectedValueException('Related term factory must return TermData or null.');
		}
		return $data;
	}
}
