<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

use WpTypeSafety\Core\Data\ImgData;

/** MenuNodeDataを構築する共通メンバ。Dataへ渡す前に値を調整できます。 */
final class MenuNodeMembers
{
	/**
	 * @param list<string> $Classes
	 * @param list<MenuNodeData> $Children
	 * @param null|\Closure(string|array): ?ImgData $ThumbnailResolver
	 */
	public function __construct(
		public int $Id,
		public int $ParentId,
		public string $Title,
		public string $Url,
		public string $Description,
		public array $Classes,
		public int $Order,
		public MenuItemType $Type,
		public string $Object,
		public int $ObjectId,
		public array $Children = [],
		public ?\Closure $ThumbnailResolver = null,
	) {
	}
}
