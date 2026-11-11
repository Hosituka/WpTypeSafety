<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Internal;

use WpTypeSafety\Internal\TreeTypeReader\Data\ResolvedValue;

/** @internal WordPressメタデータの欠損と保存済みの値を区別して取得します。 */
final class MetaAdapter
{
	public function __construct(
		private readonly string $metaType,
		private readonly int $objectId,
	) {}

	public function Exists(string $key): bool
	{
		return $this->objectId > 0
			&& $key !== ''
			&& \metadata_exists($this->metaType, $this->objectId, $key);
	}

	public function Resolve(string $key): ResolvedValue
	{
		if (! $this->Exists($key))
		{return new ResolvedValue(Exists: false, Value: null);}

		return new ResolvedValue(
			Exists: true,
			Value: \get_metadata($this->metaType, $this->objectId, $key, true),
		);
	}

	public function ResolveList(string $key): ResolvedValue
	{
		if (! $this->Exists($key))
		{return new ResolvedValue(Exists: false, Value: null);}

		$values = \get_metadata($this->metaType, $this->objectId, $key, false);
		return new ResolvedValue(
			Exists: true,
			Value: is_array($values) ? array_values($values) : [],
		);
	}
}
