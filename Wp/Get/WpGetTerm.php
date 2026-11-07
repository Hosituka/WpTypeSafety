<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\TermData;

/** 投稿のターム取得と、ターム群の階層による選別。 */
final class WpGetTerm
{
	/** @var null|\Closure(\WP_Post|PostData|int, string): list<TermData> */
	private static ?\Closure $fromPost = null;

	/** @var null|\Closure(list<TermData>): list<TermData> */
	private static ?\Closure $roots = null;

	/** @var null|\Closure(list<TermData>): list<TermData> */
	private static ?\Closure $deepest = null;

	/** @var null|\Closure(list<TermData>): list<TermData> */
	private static ?\Closure $shallowest = null;

	private function __construct() {}

	/**
	 * @internal Internalから取得処理を一度だけ登録します。
	 * @param \Closure(\WP_Post|PostData|int, string): list<TermData> $fromPost
	 * @param \Closure(list<TermData>): list<TermData> $roots
	 * @param \Closure(list<TermData>): list<TermData> $deepest
	 * @param \Closure(list<TermData>): list<TermData> $shallowest
	 */
	public static function Register(
		\Closure $fromPost,
		\Closure $roots,
		\Closure $deepest,
		\Closure $shallowest,
	): void {
		if (self::$fromPost !== null) {
			throw new \LogicException('WpGetTerm implementation is already registered.');
		}
		self::$fromPost = $fromPost;
		self::$roots = $roots;
		self::$deepest = $deepest;
		self::$shallowest = $shallowest;
	}

	/** @return list<TermData> */
	public static function FromPost(\WP_Post|PostData|int $post, string $taxonomy): array
	{
		$resolver = self::$fromPost ?? throw new \LogicException('WpGetTerm implementation is not registered.');
		return $resolver($post, $taxonomy);
	}

	/**
	 * @param list<TermData> $terms
	 * @return list<TermData>
	 */
	public static function Roots(array $terms): array
	{
		$resolver = self::$roots ?? throw new \LogicException('WpGetTerm implementation is not registered.');
		return $resolver($terms);
	}

	/**
	 * @param list<TermData> $terms
	 * @return list<TermData>
	 */
	public static function Deepest(array $terms): array
	{
		$resolver = self::$deepest ?? throw new \LogicException('WpGetTerm implementation is not registered.');
		return $resolver($terms);
	}

	/**
	 * @param list<TermData> $terms
	 * @return list<TermData>
	 */
	public static function Shallowest(array $terms): array
	{
		$resolver = self::$shallowest ?? throw new \LogicException('WpGetTerm implementation is not registered.');
		return $resolver($terms);
	}
}
