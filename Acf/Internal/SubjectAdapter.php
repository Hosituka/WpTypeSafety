<?php
declare(strict_types=1);

namespace WpTypeSafety\Acf\Internal;

use WpTypeSafety\Acf\Get\AcfGetTerm;
use WpTypeSafety\Acf\Get\AcfGetUser;
use WpTypeSafety\Wp\Data\TermData;
use WpTypeSafety\Wp\Data\WpUserData;

/** @internal ユーザー・タームのACF参照先IDを組み立てる。 */
final class SubjectAdapter
{
	private function __construct() {}

	public static function Register(): void
	{
		AcfGetUser::RegisterIdResolver(self::userPostId(...));
		AcfGetTerm::RegisterIdResolver(self::termPostId(...));
	}

	private static function userPostId(\WP_User|WpUserData $user): string
	{
		$id = $user instanceof WpUserData ? $user->Id : (int) $user->ID;
		return 'user_' . $id;
	}

	private static function termPostId(\WP_Term|TermData $term): string
	{
		return $term instanceof TermData
			? $term->Taxonomy . '_' . $term->Id
			: $term->taxonomy . '_' . $term->term_id;
	}
}
