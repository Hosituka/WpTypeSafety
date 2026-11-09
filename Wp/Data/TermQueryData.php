<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

/** タームクエリの変換結果を保持します。 */
final readonly class TermQueryData
{
	/** @param list<TermData> $Terms */
	public function __construct(
		public array $Terms,
	) {}

	public function HasTerms(): bool
	{
		return $this->Terms !== [];
	}
}
