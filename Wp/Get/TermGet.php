<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Data\TermData;
use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Internal\TermAdapter;

/** タームの取得・Data生成・階層による選別の公開入口。 */
final class TermGet
{
	private function __construct() {}

	/**
	 * taxonomy指定時は所属タクソノミーも検証する。
	 * @template TTerm of TermData = TermData
	 * @param class-string<TTerm> $dataClass
	 * @return TTerm|null
	 */
	public static function ById(int $termId, ?string $taxonomy = null, string $dataClass = TermData::class): ?TermData
	{
		return TermAdapter::ById($termId, $taxonomy, $dataClass);
	}

	/**
	 * タクソノミー名とスラッグから単一タームを取得する。該当なしはnull。
	 * @template TTerm of TermData = TermData
	 * @param class-string<TTerm> $dataClass
	 * @return TTerm|null
	 */
	public static function BySlug(
		string $taxonomy,
		string $slug,
		string $dataClass = TermData::class,
	): ?TermData
	{
		return TermAdapter::BySlug($taxonomy, $slug, $dataClass);
	}

	/**
	 * クエリ確定後、現在のタームアーカイブを取得する。投稿の割り当てタームは選ばない。
	 * @template TTerm of TermData = TermData
	 * @param class-string<TTerm> $dataClass
	 * @return TTerm|null
	 */
	public static function Current(string $dataClass = TermData::class): ?TermData
	{
		return TermAdapter::Current($dataClass);
	}

	/**
	 * @template TTerm of TermData = TermData
	 * @param class-string<TTerm> $dataClass
	 * @return list<TTerm>
	 */
	public static function FromPost(
		\WP_Post|PostData|int $post,
		string $taxonomy,
		string $dataClass = TermData::class,
	): array
	{
		return TermAdapter::FromPost($post, $taxonomy, $dataClass);
	}

	/**
	 * @template TInput of TermData
	 * @template TTerm of TermData = TermData
	 * @param list<TInput> $terms
	 * @param class-string<TTerm> $dataClass
	 * @return list<TInput|TTerm>
	 */
	public static function Roots(array $terms, string $dataClass = TermData::class): array
	{
		return TermAdapter::Roots($terms, $dataClass);
	}

	/**
	 * @template TTerm of TermData = TermData
	 * @param list<TTerm> $terms
	 * @return list<TTerm>
	 */
	public static function Deepest(array $terms): array
	{
		return TermAdapter::Deepest($terms);
	}

	/**
	 * @template TTerm of TermData = TermData
	 * @param list<TTerm> $terms
	 * @return list<TTerm>
	 */
	public static function Shallowest(array $terms): array
	{
		return TermAdapter::Shallowest($terms);
	}
}
