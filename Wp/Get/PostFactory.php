<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\PostMembers;
use WpTypeSafety\Wp\Internal\DtoFactory;

/** WP_Postから投稿のMembersまたはDataを生成します。 */
final class PostFactory
{
	private function __construct() {}

	public static function Members(\WP_Post $post): PostMembers
	{
		return DtoFactory::PostMembersFromWpPost($post);
	}

	public static function From(\WP_Post $post): PostData
	{
		return DtoFactory::PostFromWpPost($post);
	}
}
