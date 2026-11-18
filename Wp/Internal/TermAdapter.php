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
	 * @template TTerm of TermData
	 * @param null|\Closure(\WP_Term): TTerm $termFactory
	 * @return ($termFactory is null ? TermData|null : TTerm|null)
	 */
	public static function Current(?\Closure $termFactory = null): ?TermData
	{
		$term = MainQueryAdapter::QueriedObject();
		return $term instanceof \WP_Term ? self::termData($term, $termFactory) : null;
	}

	/**
	 * @template TTerm of TermData
	 * @param null|\Closure(\WP_Term): TTerm $termFactory
	 * @return ($termFactory is null ? list<TermData> : list<TTerm>)
	 */
	public static function FromPost(
		\WP_Post|PostData|int $post,
		string $taxonomy,
		?\Closure $termFactory = null,
	): array
	{
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
			$data = self::termData($term, $termFactory);
			if ($data !== null) {
				$result[] = $data;
			}
		}
		return $result;
	}

	/**
	 * @template TTerm of TermData
	 * @param list<TTerm> $terms
	 * @param null|\Closure(\WP_Term): TTerm $termFactory
	 * @return ($termFactory is null ? list<TermData> : list<TTerm>)
	 */
	public static function Roots(array $terms, ?\Closure $termFactory = null): array
	{
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
						$rootData = self::termData($root, $termFactory) ?? $term;
					}
				}
			}

			$key = $rootData->TaxonomyName . ':' . $rootData->Id;
			$roots[$key] ??= $rootData;
		}
		return array_values($roots);
	}

	/**
	 * @template TTerm of TermData
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
	 * @template TTerm of TermData
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

	/**
	 * @template TTerm of TermData
	 * @param null|\Closure(\WP_Term): TTerm $termFactory
	 * @return ($termFactory is null ? TermData|null : TTerm)
	 */
	private static function termData(\WP_Term $term, ?\Closure $termFactory): ?TermData
	{
		if ($termFactory === null) {
			return self::FromWpTerm($term);
		}

		$data = $termFactory($term);
		if (! $data instanceof TermData) {
			throw new \UnexpectedValueException('TermGetのtermFactoryはTermDataを返す必要があります。');
		}

		return $data;
	}

	/** @return TermData<TermData>|null */
	public static function FromWpTerm(\WP_Term $term): ?TermData
	{
		$props = self::PropsFromWpTerm($term);
		return $props !== null ? new TermData($props) : null;
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
