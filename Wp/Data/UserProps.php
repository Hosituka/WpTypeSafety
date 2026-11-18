<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

/** WpUserDataの構築用の値。Dataへ渡す前に値を調整できます。 */
final class UserProps
{
	public function __construct(
		public int $Id,
		public string $DisplayName,
		public ?int $AvatarSize = null,
		public string $ArchiveUrl = '',
		public string $Description = '',
		public string $WebsiteUrl = '',
	) {
	}
}
