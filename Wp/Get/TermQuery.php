<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Data\TermData;
use WpTypeSafety\Wp\Internal\TermQueryAdapter;

/** WP_Term_Queryを実行または受け取り、タームDataへ変換します。 */
final class TermQuery
{
	private function __construct() {}

	/**
	 * @param array<string, mixed> $args
	 * @return list<TermData>
	 */
	public static function Get(array $args = []): array
	{
		return TermQueryAdapter::Get($args);
	}

	/** @return list<TermData> */
	public static function From(\WP_Term_Query $query): array
	{
		return TermQueryAdapter::From($query);
	}
}
