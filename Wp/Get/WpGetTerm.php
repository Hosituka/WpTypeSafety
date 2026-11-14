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

	/**
	 * @template TTerm of TermData
	 * @param null|\Closure(\WP_Term): TTerm $termFactory
	 * @return ($termFactory is null ? list<TermData> : list<TTerm>)
	 */
	public static function FromPost(
		\WP_Post|PostData|int $post,
		string $taxonomy,
		?\Closure $termFactory = null,
	): array
	{
		return TermAdapter::FromPost($post, $taxonomy, $termFactory);
	}

	/**
	 * @template TTerm of TermData
	 * @param list<TTerm> $terms
	 * @param null|\Closure(\WP_Term): TTerm $termFactory
	 * @return ($termFactory is null ? list<TermData> : list<TTerm>)
	 */
	public static function Roots(array $terms, ?\Closure $termFactory = null): array
	{
		return TermAdapter::Roots($terms, $termFactory);
	}

	/**
	 * @template TTerm of TermData
	 * @param list<TTerm> $terms
	 * @return list<TTerm>
	 */
	public static function Deepest(array $terms): array
	{
		return TermAdapter::Deepest($terms);
	}

	/**
	 * @template TTerm of TermData
	 * @param list<TTerm> $terms
	 * @return list<TTerm>
	 */
	public static function Shallowest(array $terms): array
	{
		return TermAdapter::Shallowest($terms);
	}
}
