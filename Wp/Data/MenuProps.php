<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

/**
 * メニュー構築前に調整できる値。
 * @template TNode of MenuNodeData = MenuNodeData
 */
final class MenuProps
{
	/** @param list<TNode> $Nodes */
	public function __construct(
		public int $Id,
		public string $Name,
		public string $Slug,
		public string $Description,
		public array $Nodes = [],
	) {}
}
