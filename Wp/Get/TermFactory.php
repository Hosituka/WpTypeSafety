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

	/** @param null|\Closure(\WP_Term): ?TermData $relatedTermFactory */
	public static function Members(\WP_Term $term, ?\Closure $relatedTermFactory = null): ?TermMembers
	{
		return TermAdapter::MembersFromWpTerm($term, $relatedTermFactory);
	}

	/** @param null|\Closure(\WP_Term): ?TermData $relatedTermFactory */
	public static function From(\WP_Term $term, ?\Closure $relatedTermFactory = null): ?TermData
	{
		return TermAdapter::FromWpTerm($term, $relatedTermFactory);
	}
}
