<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Internal;

use WpTypeSafety\Wp\Data\PostQueryData;

/** @internal WP_QueryをPostQueryDataへ変換します。 */
final class PostQueryAdapter
{
	private function __construct() {}

	/** @param array<string, mixed> $args */
	public static function Get(array $args): PostQueryData
	{
		$query = new \WP_Query();
		$query->query($args);

		return self::From($query);
	}

	public static function From(\WP_Query $query): PostQueryData
	{
		$fields = $query->get('fields');
		if ($fields === 'ids' || $fields === 'id=>parent') {
			throw new \InvalidArgumentException(
				'PostQueryはWP_Postを必要とするため、fieldsによるID取得には対応していません。',
			);
		}

		$posts = [];
		foreach (is_array($query->posts) ? $query->posts : [] as $post) {
			if (! $post instanceof \WP_Post) {
				throw new \UnexpectedValueException('WP_Queryの結果にWP_Post以外の値が含まれています。');
			}
			$posts[] = DtoFactory::PostFromWpPost($post);
		}

		$hasTotal = ! (bool) $query->get('no_found_rows');

		return new PostQueryData(
			Posts: $posts,
			TotalCount: $hasTotal ? max(0, (int) $query->found_posts) : null,
			MaxPageCount: $hasTotal ? max(0, (int) $query->max_num_pages) : null,
			CurrentPageNumber: QueryPagination::PageNumber($query->get('paged')),
		);
	}
}
