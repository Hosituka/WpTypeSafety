<?php
declare(strict_types=1);

namespace WpTypeSafety\Acf\Data;

/** ACFから渡されたブロック情報の読み取り専用スナップショット。 */
final readonly class AcfBlockData
{
	/**
	 * 正規化済みの値を受け取る。ACF・Internalの読み込みや静的登録は不要。
	 * @param list<string> $Classes classNameに明示されたクラスのみ。自動生成クラスは含めない。
	 * @param null|\Closure(string): string $wrapperAttributesResolver エスケープ済みのHTML属性を返す処理。
	 */
	public function __construct(
		public string $Name,
		public string $Id,
		public string $Anchor = '',
		public array $Classes = [],
		public bool $IsPreview = false,
		private ?\Closure $wrapperAttributesResolver = null,
	) {
	}

	public function HasClass(string $className): bool
	{
		return in_array($className, $this->Classes, true);
	}

	/** スタイル未選択をdefaultとは推測しない。 */
	public function HasStyle(string $styleName): bool
	{
		return $styleName !== '' && $this->HasClass('is-style-' . $styleName);
	}

	/** 注入された描画処理へ委譲する。戻り値はHTML属性としてそのまま出力する。 */
	public function GetWrapperAttributes(string $className = ''): string
	{
		$resolver = $this->wrapperAttributesResolver
			?? throw new \LogicException('AcfBlockData wrapper attributes resolver is not provided.');
		return $resolver($className);
	}
}
