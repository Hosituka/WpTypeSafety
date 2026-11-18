<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Data\PostTypeData;
use WpTypeSafety\Wp\Data\PostTypeProps;
use WpTypeSafety\Wp\Internal\DefinitionAdapter;

/** 投稿タイプの登録定義を取得・変換する公開入口。 */
final class PostTypeGet
{
	private function __construct() {}

	/** 元のメインクエリの投稿詳細・投稿タイプアーカイブの登録定義。それ以外はnull。 */
	public static function Current(): ?PostTypeData
	{
		return DefinitionAdapter::CurrentPostType();
	}

	public static function Props(\WP_Post_Type $postType): PostTypeProps
	{
		return DefinitionAdapter::PostTypePropsFromWp($postType);
	}

	public static function From(\WP_Post_Type $postType): PostTypeData
	{
		return DefinitionAdapter::PostTypeFromWp($postType);
	}

	/** 未登録の場合はnull。登録完了後に呼び出す。 */
	public static function FromName(string $name): ?PostTypeData
	{
		return DefinitionAdapter::PostTypeFromName($name);
	}
}
