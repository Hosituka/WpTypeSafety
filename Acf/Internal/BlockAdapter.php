<?php
declare(strict_types=1);

namespace WpTypeSafety\Acf\Internal;

use WpTypeSafety\Acf\Data\AcfBlockData;
use WpTypeSafety\Core\Check\ArrayGet;

/** @internal ブロック情報を正規化し、ラッパー属性の描画処理を注入する。 */
final class BlockAdapter
{
	private function __construct() {}

	/** @param array<array-key, mixed> $block */
	public static function FromAcf(array $block, bool $isPreview): AcfBlockData
	{
		$blockGet = new ArrayGet($block);
		$className = $blockGet->String('className');
		$classes = preg_split('/\s+/', $className, -1, PREG_SPLIT_NO_EMPTY) ?: [];
		$classes = array_values(array_unique($classes));
		return new AcfBlockData(
			Name: $blockGet->String('name'),
			Id: $blockGet->String('id'),
			Anchor: $blockGet->String('anchor'),
			Classes: $classes,
			IsPreview: $isPreview,
			wrapperAttributesResolver: static fn (string $className): string => self::wrapperAttributes($className, $classes, $isPreview),
		);
	}

	/** @param list<string> $classes */
	private static function wrapperAttributes(string $className, array $classes, bool $isPreview): string
	{
		if (! $isPreview) {
			// 現在の描画コンテキストのクラス・アンカー等はWordPressに任せる。
			return \get_block_wrapper_attributes(['class' => $className]);
		}

		// ACFプレビューの内側へ、基本クラス・スタイル・追加クラスだけを付ける。
		$classes = preg_split('/\s+/', implode(' ', [$className, ...$classes]), -1, PREG_SPLIT_NO_EMPTY) ?: [];
		$className = implode(' ', array_unique($classes));
		return $className !== '' ? 'class="' . \esc_attr($className) . '"' : '';
	}
}
