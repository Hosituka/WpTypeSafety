<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Internal;

use WpTypeSafety\Wp\Data\TermData;

/** @internal WP_Term_QueryをTermDataのリストへ変換します。 */
final class TermQueryAdapter
{
	private function __construct() {}

	/**
	 * @param array<string, mixed> $args
	 * @return list<TermData>
	 */
	public static function Get(array $args): array
	{
		$query = new \WP_Term_Query();
		$query->query($args);

		return self::From($query);
	}

	/** @return list<TermData> */
	public static function From(\WP_Term_Query $query): array
	{
		$fields = is_array($query->query_vars) ? ($query->query_vars['fields'] ?? 'all') : 'all';
		if ($fields !== 'all' && $fields !== 'all_with_object_id') {
			throw new \InvalidArgumentException(
				'TermQueryはWP_Termを必要とするため、fieldsによるID・件数取得には対応していません。',
			);
		}

		$terms = [];
		foreach (is_array($query->terms) ? $query->terms : [] as $term) {
			if (! $term instanceof \WP_Term) {
				throw new \UnexpectedValueException('WP_Term_Queryの結果にWP_Term以外の値が含まれています。');
			}
			$data = TermAdapter::FromWpTerm($term);
			if ($data === null) {
				continue;
			}
			$terms[] = $data;
		}

		return $terms;
	}
}
