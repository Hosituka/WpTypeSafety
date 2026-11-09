<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Internal;

use WpTypeSafety\Wp\Data\CommentQueryData;

/** @internal WP_Comment_QueryをCommentQueryDataへ変換します。 */
final class CommentQueryAdapter
{
	private function __construct() {}

	/** @param array<string, mixed> $args */
	public static function Get(array $args, int $avatarSize): CommentQueryData
	{
		$query = new \WP_Comment_Query();
		$query->query($args);

		return self::From($query, $avatarSize);
	}

	public static function From(\WP_Comment_Query $query, int $avatarSize): CommentQueryData
	{
		$queryVars = is_array($query->query_vars) ? $query->query_vars : [];
		$fields = $queryVars['fields'] ?? '';
		if (($queryVars['count'] ?? false) || ($fields !== '' && $fields !== 'all')) {
			throw new \InvalidArgumentException(
				'CommentQueryはWP_Commentを必要とするため、countまたはfieldsによるID取得には対応していません。',
			);
		}

		$comments = [];
		foreach (is_array($query->comments) ? $query->comments : [] as $comment) {
			if (! $comment instanceof \WP_Comment) {
				throw new \UnexpectedValueException('WP_Comment_Queryの結果にWP_Comment以外の値が含まれています。');
			}
			$comments[] = DtoFactory::CommentFromWpComment($comment, $avatarSize);
		}

		$number = $queryVars['number'] ?? null;
		$hasLimit = is_numeric($number) && (int) $number > 0;
		$noFoundRows = (bool) ($queryVars['no_found_rows'] ?? true);

		if (! $hasLimit) {
			$totalCount = count($comments);
			$maxPageCount = $comments === [] ? 0 : 1;
		} elseif (! $noFoundRows) {
			$totalCount = max(0, (int) $query->found_comments);
			$maxPageCount = max(0, (int) $query->max_num_pages);
		} else {
			$totalCount = null;
			$maxPageCount = null;
		}

		return new CommentQueryData(
			Comments: $comments,
			TotalCount: $totalCount,
			MaxPageCount: $maxPageCount,
			CurrentPageNumber: self::pageNumber($queryVars['paged'] ?? null),
		);
	}

	private static function pageNumber(mixed $value): int
	{
		return is_numeric($value) && (int) $value > 0 ? (int) $value : 1;
	}
}
