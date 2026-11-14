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

	/**
	 * @template TRelatedTerm of TermData = TermData
	 * @param null|\Closure(\WP_Term): (TRelatedTerm|null) $relatedTermFactory
	 * @return TermMembers<TRelatedTerm>|null
	 */
	public static function Members(\WP_Term $term, ?\Closure $relatedTermFactory = null): ?TermMembers
	{
		return TermAdapter::MembersFromWpTerm($term, $relatedTermFactory);
	}

	/**
	 * @template TRelatedTerm of TermData = TermData
	 * @param null|\Closure(\WP_Term): (TRelatedTerm|null) $relatedTermFactory
	 * @return TermData<TRelatedTerm>|null
	 */
	public static function From(\WP_Term $term, ?\Closure $relatedTermFactory = null): ?TermData
	{
		return TermAdapter::FromWpTerm($term, $relatedTermFactory);
	}
}
