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
	 * @template TTerm of TermData = TermData
	 * @param array<string, mixed> $args
	 * @param class-string<TTerm> $dataClass
	 * @return TermQueryData<TTerm>
	 */
	public static function Get(array $args, string $dataClass = TermData::class): TermQueryData
	{
		DataClass::Validate($dataClass, TermData::class);
		$query = new \WP_Term_Query();
		$query->query($args);

		return self::From($query, $dataClass);
	}

	/**
	 * @template TTerm of TermData = TermData
	 * @param class-string<TTerm> $dataClass
	 * @return TermQueryData<TTerm>
	 */
	public static function From(\WP_Term_Query $query, string $dataClass = TermData::class): TermQueryData
	{
		DataClass::Validate($dataClass, TermData::class);
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
			$data = $dataClass::From($term);
			if ($data === null) {
				continue;
			}
			$terms[] = $data;
		}

		return new TermQueryData($terms);
	}
}
