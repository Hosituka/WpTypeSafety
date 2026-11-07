<?php
declare(strict_types=1);

namespace WpTypeSafety\Support;

/** WordPressのwp_kses()を使って、許可するHTMLタグを限定する。 */
final class Esc
{
	private function __construct() {}

	/** 改行を<br>に変換します。<br>以外のHTMLタグは文字表示せず除去します。 */
	public static function WithBr(string $text): string
	{
		return \wp_kses(\nl2br($text), ['br' => []]);
	}

	/** <small>だけを許可します。その他のHTMLタグと属性は除去します。 */
	public static function WithSmall(string $text): string
	{
		return \wp_kses($text, ['small' => []]);
	}

	/** <strong>だけを許可します。その他のHTMLタグと属性は除去します。 */
	public static function WithStrong(string $text): string
	{
		return \wp_kses($text, ['strong' => []]);
	}
}
