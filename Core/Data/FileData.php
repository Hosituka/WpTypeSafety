<?php
declare(strict_types=1);

namespace WpTypeSafety\Core\Data;

final readonly class FileData
{
	public function __construct(
		public int $Id,
		public string $Url,
		public string $FileName,
		public string $MimeType,
		public int $FileSize,
	) {
	}
}
