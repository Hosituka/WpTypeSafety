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
	 * @template TTerm of TermData
	 * @param array<string, mixed> $args
	 * @param null|\Closure(\WP_Term): TTerm $termFactory
	 * @return ($termFactory is null ? TermQueryData<TermData> : TermQueryData<TTerm>)
	 */
	public static function Get(array $args = [], ?\Closure $termFactory = null): TermQueryData
	{
		return TermQueryAdapter::Get($args, $termFactory);
	}

	/**
	 * @template TTerm of TermData
	 * @param null|\Closure(\WP_Term): TTerm $termFactory
	 * @return ($termFactory is null ? TermQueryData<TermData> : TermQueryData<TTerm>)
	 */
	public static function From(\WP_Term_Query $query, ?\Closure $termFactory = null): TermQueryData
	{
		return TermQueryAdapter::From($query, $termFactory);
	}
}
