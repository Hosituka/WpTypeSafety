<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

/**
 * タームクエリの変換結果を保持します。
 *
 * @template-covariant TTerm of TermData
 */
final readonly class TermQueryData
{
	/** @param list<TTerm> $Terms */
	public function __construct(
		public array $Terms,
	) {}

	public function HasTerms(): bool
	{
		return $this->Terms !== [];
	}
}
