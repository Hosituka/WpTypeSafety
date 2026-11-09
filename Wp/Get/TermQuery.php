<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Data\TermQueryData;
use WpTypeSafety\Wp\Internal\TermQueryAdapter;

/** WP_Term_Queryを実行または受け取り、タームDataへ変換します。 */
final class TermQuery
{
	private function __construct() {}

	/** @param array<string, mixed> $args */
	public static function Get(array $args = []): TermQueryData
	{
		return TermQueryAdapter::Get($args);
	}

	public static function From(\WP_Term_Query $query): TermQueryData
	{
		return TermQueryAdapter::From($query);
	}
}
