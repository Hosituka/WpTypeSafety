<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Internal;

use WpTypeSafety\Core\Data\ImgData;
use WpTypeSafety\Core\Internal\UrlCheck;
use WpTypeSafety\Wp\Data\CommentData;

/** @internal コメントDataの標準WordPress接続処理。 */
final class CommentAdapter
{
	private function __construct() {}

	/**
	 * @template TComment of CommentData
	 * @param \Closure(\WP_Comment): (TComment|null) $createRelatedComment
	 * @return TComment|null
	 */
	public static function Parent(int $parentId, \Closure $createRelatedComment): ?CommentData
	{
		if ($parentId <= 0)
		{return null;}

		$comment = \get_comment($parentId);
		return $comment instanceof \WP_Comment ? self::create($comment, $createRelatedComment) : null;
	}

	/**
	 * @template TComment of CommentData
	 * @param \Closure(\WP_Comment): (TComment|null) $createRelatedComment
	 * @return list<TComment>
	 */
	public static function Children(int $id, string $status, string $order, \Closure $createRelatedComment): array
	{
		if ($id <= 0)
		{return [];}

		$comments = \get_comments([
			'parent' => $id,
			'status' => $status,
			'orderby' => 'comment_date_gmt comment_ID',
			'order' => $order,
			'hierarchical' => false,
		]);
		$children = [];
		foreach (is_array($comments) ? $comments : [] as $comment) {
			if (! $comment instanceof \WP_Comment)
			{continue;}

			$data = self::create($comment, $createRelatedComment);
			if ($data !== null) {
				$children[] = $data;
			}
		}
		return $children;
	}

	/**
	 * ゲストのメールアドレス等は現在のコメントから補う。
	 * 保存済みオブジェクトは変更せず、保持した著者情報を複製へ反映する。
	 */
	public static function Avatar(CommentData $data, int $size): ?ImgData
	{
		if ($data->Id <= 0)
		{return null;}

		$comment = \get_comment($data->Id);
		if (! $comment instanceof \WP_Comment)
		{return null;}

		$comment = clone $comment;
		$comment->user_id = $data->AuthorUserId;
		$comment->comment_author = $data->AuthorName;
		$comment->comment_author_url = $data->WebsiteUrl;
		$size = max(1, $size);
		$url = \get_avatar_url($comment, ['size' => $size]);
		return is_string($url) && UrlCheck::IsHttpUrl($url)
			? new ImgData(Url: $url, Width: $size, Height: $size)
			: null;
	}

	/**
	 * @template TComment of CommentData
	 * @param \Closure(\WP_Comment): (TComment|null) $createRelatedComment
	 * @return TComment|null
	 */
	private static function create(\WP_Comment $comment, \Closure $createRelatedComment): ?CommentData
	{
		$data = $createRelatedComment($comment);
		if ($data !== null && ! $data instanceof CommentData)
		{throw new \UnexpectedValueException('Related comment hook must return CommentData or null.');}

		return $data;
	}
}
