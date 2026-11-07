<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\TermData;
use WpTypeSafety\Wp\Internal\TermAdapter;

/** 投稿のターム取得と、ターム群の階層による選別。 */
final class WpGetTerm
{
	private function __construct() {}

	/** @return list<TermData> */
	public static function FromPost(\WP_Post|PostData|int $post, string $taxonomy): array
	{
		return TermAdapter::FromPost($post, $taxonomy);
	}

	/**
	 * @param list<TermData> $terms
	 * @return list<TermData>
	 */
	public static function Roots(array $terms): array
	{
		return TermAdapter::Roots($terms);
	}

	/**
	 * @param list<TermData> $terms
	 * @return list<TermData>
	 */
	public static function Deepest(array $terms): array
	{
		return TermAdapter::Deepest($terms);
	}

	/**
	 * @param list<TermData> $terms
	 * @return list<TermData>
	 */
	public static function Shallowest(array $terms): array
	{
		return TermAdapter::Shallowest($terms);
	}
}
