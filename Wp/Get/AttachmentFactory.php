<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Core\Data\FileData;
use WpTypeSafety\Core\Data\ImgData;
use WpTypeSafety\Wp\Internal\DtoFactory;

/** WordPressの添付ファイルIDからCoreのDataを生成します。 */
final class AttachmentFactory
{
	private function __construct() {}

	public static function ImgFromId(int $attachmentId, string|array $size = 'full'): ?ImgData
	{
		return DtoFactory::ImgFromAttachmentId($attachmentId, $size);
	}

	public static function FileFromId(int $attachmentId): ?FileData
	{
		return DtoFactory::FileFromAttachmentId($attachmentId);
	}
}
