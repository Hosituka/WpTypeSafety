<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

use WpTypeSafety\Core\Data\ImgData;

/** WpUserDataを構築する共通メンバ。Dataへ渡す前に値を調整できます。 */
final class UserMembers
{
	/** @param null|\Closure(int): ?ImgData $AvatarResolver */
	public function __construct(
		public int $Id,
		public string $DisplayName,
		public ?int $AvatarSize = null,
		public ?\Closure $AvatarResolver = null,
		public string $AuthorUrl = '',
		public string $Description = '',
	) {
	}
}
