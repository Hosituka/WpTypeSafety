<?php
declare(strict_types=1);

namespace WpTypeSafety\Acf\Get;

use WpTypeSafety\Acf\Internal\AcfAdapter;
use WpTypeSafety\Wp\Data\WpUserData;

/** ユーザーを対象にしたACFフィールド取得。 */
final class AcfGetUser extends AcfGet
{
	/** 対象なしは空の取得元として扱い、現在の投稿へフォールバックしない。 */
	public function __construct(\WP_User|WpUserData|null $user, ?AcfAdapter $adapter = null)
	{
		$adapter ??= new AcfAdapter();
		if ($user === null) {
			parent::__construct([], $adapter);
			return;
		}

		parent::__construct($adapter->UserPostId($user), $adapter);
	}
}
