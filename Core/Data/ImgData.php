<?php
declare(strict_types=1);

namespace WpTypeSafety\Core\Data;

final readonly class ImgData
{
	public function __construct(
		public string $Url,
		public int $Width,
		public int $Height,
		public string $Alt = '',
	) {
	}
}
