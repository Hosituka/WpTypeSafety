<?php
declare(strict_types=1);

namespace WpTypeSafety\Core\Data;

final readonly class LinkData
{
	public function __construct(
		public string $Url,
		public string $Title = '',
		public string $Target = '',
	) {
	}

	public function IsNewTab(): bool
	{
		return $this->Target === '_blank';
	}
}
