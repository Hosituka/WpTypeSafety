<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Wp\Data\CommentData;
use WpTypeSafety\Wp\Internal\CommentAdapter;

/** WP_Commentから構築用PropsまたはDataを生成する。 */
final class CommentGet
{
	private function __construct() {}

	/**
	 * @template TComment of CommentData = CommentData
	 * @param class-string<TComment> $dataClass
	 * @return TComment|null
	 */
	public static function ById(int $commentId, int $avatarSize = 96, string $dataClass = CommentData::class): ?CommentData
	{
		return CommentAdapter::ById($commentId, $avatarSize, $dataClass);
	}
}
