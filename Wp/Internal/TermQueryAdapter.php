<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Internal;

use WpTypeSafety\Wp\Data\TermData;
use WpTypeSafety\Wp\Data\TermQueryData;

/** @internal WP_Term_QueryをTermDataのリストへ変換します。 */
final class TermQueryAdapter
{
	private function __construct() {}

	/**
	 * @template TTerm of TermData
	 * @param array<string, mixed> $args
	 * @param null|\Closure(\WP_Term): TTerm $termFactory
	 * @return ($termFactory is null ? TermQueryData<TermData> : TermQueryData<TTerm>)
	 */
	public static function Get(array $args, ?\Closure $termFactory = null): TermQueryData
	{
		$query = new \WP_Term_Query();
		$query->query($args);

		return self::From($query, $termFactory);
	}

	/**
	 * @template TTerm of TermData
	 * @param null|\Closure(\WP_Term): TTerm $termFactory
	 * @return ($termFactory is null ? TermQueryData<TermData> : TermQueryData<TTerm>)
	 */
	public static function From(\WP_Term_Query $query, ?\Closure $termFactory = null): TermQueryData
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
			$data = $termFactory !== null
				? $termFactory($term)
				: TermAdapter::FromWpTerm($term);
			if ($termFactory !== null && ! $data instanceof TermData) {
				throw new \UnexpectedValueException('TermQueryのtermFactoryはTermDataを返す必要があります。');
			}
			if ($data === null) {
				continue;
			}
			$terms[] = $data;
		}

		return new TermQueryData($terms);
	}
}
