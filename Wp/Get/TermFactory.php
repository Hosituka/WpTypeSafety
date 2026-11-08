<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Data\TermData;
use WpTypeSafety\Wp\Data\TermMembers;
use WpTypeSafety\Wp\Internal\TermAdapter;

/** WP_TermからタームのMembersまたはDataを生成します。 */
final class TermFactory
{
	private function __construct() {}

	public static function Members(\WP_Term $term): ?TermMembers
	{
		return TermAdapter::MembersFromWpTerm($term);
	}

	public static function From(\WP_Term $term): ?TermData
	{
		return TermAdapter::FromWpTerm($term);
	}
}
