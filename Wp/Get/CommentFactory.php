<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Data\CommentData;
use WpTypeSafety\Wp\Data\CommentMembers;
use WpTypeSafety\Wp\Internal\DtoFactory;

/** WP_CommentからコメントのMembersまたはDataを生成します。 */
final class CommentFactory
{
	private function __construct() {}

	public static function Members(\WP_Comment $comment, int $avatarSize = 96): CommentMembers
	{
		return DtoFactory::CommentMembersFromWpComment($comment, $avatarSize);
	}

	public static function From(\WP_Comment $comment, int $avatarSize = 96): CommentData
	{
		return DtoFactory::CommentFromWpComment($comment, $avatarSize);
	}
}
