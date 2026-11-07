<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Internal;

use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\TermData;

/** @internal WordPressのタームをCoreのTermDataへ変換する。 */
final class TermAdapter
{
	private function __construct() {}

	/** @return list<TermData> */
	public static function FromPost(\WP_Post|PostData|int $post, string $taxonomy): array
	{
		if ($post instanceof \WP_Post) {
			$postId = $post->ID;
		} elseif ($post instanceof PostData) {
			$postId = $post->PostId;
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
			$data = self::FromWpTerm($term);
			if ($data !== null) {
				$result[] = $data;
			}
		}
		return $result;
	}

	/**
	 * @param list<TermData> $terms
	 * @return list<TermData>
	 */
	public static function Roots(array $terms): array
	{
		$roots = [];
		foreach ($terms as $term) {
			if (! $term instanceof TermData) {
				continue;
			}

			$rootData = $term;
			if ($term->HasParent()) {
				$ancestors = \get_ancestors($term->Id, $term->Taxonomy, 'taxonomy');
				if ($ancestors !== []) {
					$root = \get_term((int) end($ancestors), $term->Taxonomy);
					if ($root instanceof \WP_Term) {
						$rootData = self::FromWpTerm($root) ?? $term;
					}
				}
			}

			$key = $rootData->Taxonomy . ':' . $rootData->Id;
			$roots[$key] ??= $rootData;
		}
		return array_values($roots);
	}

	/**
	 * @param list<TermData> $terms
	 * @return list<TermData>
	 */
	public static function Deepest(array $terms): array
	{
		$selected = [];
		$deepestLevel = -1;
		foreach ($terms as $term) {
			if (! $term instanceof TermData) {
				continue;
			}
			$level = count(\get_ancestors($term->Id, $term->Taxonomy, 'taxonomy'));
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
	 * @param list<TermData> $terms
	 * @return list<TermData>
	 */
	public static function Shallowest(array $terms): array
	{
		$selected = [];
		$shallowestLevel = null;
		foreach ($terms as $term) {
			if (! $term instanceof TermData) {
				continue;
			}
			$level = count(\get_ancestors($term->Id, $term->Taxonomy, 'taxonomy'));
			if ($shallowestLevel === null || $level < $shallowestLevel) {
				$selected = [$term];
				$shallowestLevel = $level;
			} elseif ($level === $shallowestLevel) {
				$selected[] = $term;
			}
		}
		return $selected;
	}

	public static function FromWpTerm(\WP_Term $term): ?TermData
	{
		$url = \get_term_link($term);
		if (\is_wp_error($url) || ! is_string($url)) {
			return null;
		}

		$parentId = (int) $term->parent;
		$taxonomy = (string) $term->taxonomy;

		return new TermData(
			Id: $term->term_id,
			Name: $term->name,
			Slug: $term->slug,
			Taxonomy: $term->taxonomy,
			Url: $url,
			ParentId: $term->parent,
			Description: $term->description,
			childrenResolver: static fn (bool $hideEmpty, string $orderby, string $order): array =>
				self::childrenOf($term->term_id, $term->taxonomy, $hideEmpty, $orderby, $order),
			parentResolver: static function () use ($parentId, $taxonomy): ?TermData {
				if ($parentId <= 0 || $taxonomy === '') {
					return null;
				}
				$parent = \get_term($parentId, $taxonomy);
				return $parent instanceof \WP_Term ? self::FromWpTerm($parent) : null;
			},
		);
	}

	/** @return list<TermData> */
	private static function childrenOf(
		int $parentId,
		string $taxonomy,
		bool $hideEmpty,
		string $orderby,
		string $order,
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
			$data = self::FromWpTerm($term);
			if ($data !== null) {
				$children[] = $data;
			}
		}
		return $children;
	}
}
