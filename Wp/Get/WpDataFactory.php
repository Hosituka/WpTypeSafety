<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Core\Data\FileData;
use WpTypeSafety\Core\Data\ImgData;
use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\TermData;
use WpTypeSafety\Wp\Data\WpUserData;
use WpTypeSafety\Wp\Internal\DtoFactory;
use WpTypeSafety\Wp\Internal\TermAdapter;

/** WordPressの値からWp固有・Core共通のデータクラスを生成する公開入口。 */
final class WpDataFactory
{
	private function __construct() {}

	public static function PostFromWpPost(\WP_Post $post): PostData
	{
		return DtoFactory::PostFromWpPost($post);
	}

	public static function TermFromWpTerm(\WP_Term $term): ?TermData
	{
		return TermAdapter::FromWpTerm($term);
	}

	public static function UserFromWpUser(\WP_User $user, int $avatarSize = 96): ?WpUserData
	{
		return DtoFactory::UserFromWpUser($user, $avatarSize);
	}

	public static function ImgFromAttachmentId(int $attachmentId, string|array $size = 'full'): ?ImgData
	{
		return DtoFactory::ImgFromAttachmentId($attachmentId, $size);
	}

	public static function FileFromAttachmentId(int $attachmentId): ?FileData
	{
		return DtoFactory::FileFromAttachmentId($attachmentId);
	}
}
