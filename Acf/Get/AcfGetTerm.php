<?php
declare(strict_types=1);

namespace WpTypeSafety\Acf\Get;

use WpTypeSafety\Wp\Data\TermData;

/** タームを対象にしたACFフィールド取得。 */
final class AcfGetTerm extends AcfGet
{
	/** @var null|\Closure(\WP_Term|TermData): string */
	private static ?\Closure $idResolver = null;

	/** @internal @param \Closure(\WP_Term|TermData): string $idResolver */
	public static function RegisterIdResolver(\Closure $idResolver): void
	{
		if (self::$idResolver !== null) {
			throw new \LogicException('AcfGetTerm implementation is already registered.');
		}
		self::$idResolver = $idResolver;
	}

	/** 対象なしは空の取得元として扱い、現在の投稿へフォールバックしない。 */
	public function __construct(\WP_Term|TermData|null $term)
	{
		if ($term === null) {
			parent::__construct([]);
			return;
		}

		$resolver = self::$idResolver ?? throw new \LogicException('AcfGetTerm implementation is not registered.');
		parent::__construct($resolver($term));
	}
}
