<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\TermData;
use WpTypeSafety\Wp\Data\TermProps;
use WpTypeSafety\Wp\Internal\TermAdapter;

/** タームの取得・Data生成・階層による選別の公開入口。 */
final class TermGet
{
	private function __construct() {}

	/**
	 * クエリ確定後、現在のタームアーカイブを取得する。投稿の割り当てタームは選ばない。
	 * @template TTerm of TermData
	 * @param null|\Closure(\WP_Term): TTerm $termFactory
	 * @return ($termFactory is null ? TermData|null : TTerm|null)
	 */
	public static function Current(?\Closure $termFactory = null): ?TermData
	{
		return TermAdapter::Current($termFactory);
	}

	public static function Props(\WP_Term $term): ?TermProps
	{
		return TermAdapter::PropsFromWpTerm($term);
	}

	/** @return TermData<TermData>|null */
	public static function From(\WP_Term $term): ?TermData
	{
		return TermAdapter::FromWpTerm($term);
	}

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
