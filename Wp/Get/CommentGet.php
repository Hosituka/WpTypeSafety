<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Data\CommentData;
use WpTypeSafety\Wp\Data\CommentProps;
use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\WpUserData;
use WpTypeSafety\Wp\Internal\DtoFactory;

/** WP_Commentから構築用PropsまたはDataを生成する。 */
final class CommentGet
{
	private function __construct() {}

	public static function Props(\WP_Comment $comment, int $avatarSize = 96): CommentProps
	{
		return DtoFactory::CommentPropsFromWpComment($comment, $avatarSize);
	}

	/** @return CommentData<CommentData, WpUserData, PostData> */
	public static function From(\WP_Comment $comment, int $avatarSize = 96): CommentData
	{
		return DtoFactory::CommentFromWpComment($comment, $avatarSize);
	}
}
