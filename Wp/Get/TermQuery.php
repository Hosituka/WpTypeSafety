<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Data\TermData;
use WpTypeSafety\Wp\Data\TermQueryData;
use WpTypeSafety\Wp\Internal\TermQueryAdapter;

/** WP_Term_Queryを実行または受け取り、タームDataへ変換します。 */
final class TermQuery
{
	private function __construct() {}

	/**
	 * @template TTerm of TermData = TermData
	 * @param array<string, mixed> $args
	 * @param class-string<TTerm> $dataClass
	 * @return TermQueryData<TTerm>
	 */
	public static function Get(array $args = [], string $dataClass = TermData::class): TermQueryData
	{
		return TermQueryAdapter::Get($args, $dataClass);
	}

	/**
	 * @template TTerm of TermData = TermData
	 * @param class-string<TTerm> $dataClass
	 * @return TermQueryData<TTerm>
	 */
	public static function From(\WP_Term_Query $query, string $dataClass = TermData::class): TermQueryData
	{
		return TermQueryAdapter::From($query, $dataClass);
	}
}
