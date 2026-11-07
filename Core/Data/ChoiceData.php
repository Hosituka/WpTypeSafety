<?php
declare(strict_types=1);

namespace WpTypeSafety\Core\Data;

final readonly class ChoiceData
{
	public function __construct(
		public string $Value = '',
		public string $Label = '',
	) {
	}
}
