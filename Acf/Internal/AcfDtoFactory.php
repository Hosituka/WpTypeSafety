<?php
declare(strict_types=1);

namespace WpTypeSafety\Acf\Internal;

use WpTypeSafety\Core\Data\ChoiceData;
use WpTypeSafety\Core\Data\FileData;
use WpTypeSafety\Core\Data\ImgData;
use WpTypeSafety\Core\Data\LinkData;
use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\TermData;
use WpTypeSafety\Wp\Data\WpUserData;
use WpTypeSafety\Wp\Internal\DtoFactory as WpDtoFactory;
use WpTypeSafety\Wp\Internal\TermAdapter;
use WpTypeSafety\Core\Internal\UrlCheck;

/** @internal ACFの戻り値をWp固有・Core共通のDTOへ変換する。 */
final class AcfDtoFactory
{
	private function __construct() {}

	public static function PostFromAcf(mixed $value): ?PostData
	{
		if ($value instanceof \WP_Post) {
			return WpDtoFactory::PostFromWpPost($value);
		}
		if (! self::isPositiveId($value)) {
			return null;
		}
		$post = \get_post((int) $value);
		return $post instanceof \WP_Post ? WpDtoFactory::PostFromWpPost($post) : null;
	}

	/** @return list<PostData> */
	public static function PostsFromAcf(mixed $value): array
	{
		return self::listFromAcf($value, self::PostFromAcf(...));
	}

	public static function TermFromAcf(mixed $value): ?TermData
	{
		if ($value instanceof \WP_Term) {
			return TermAdapter::FromWpTerm($value);
		}
		if (! self::isPositiveId($value)) {
			return null;
		}
		$term = \get_term((int) $value);
		return $term instanceof \WP_Term ? TermAdapter::FromWpTerm($term) : null;
	}

	/** @return list<TermData> */
	public static function TermsFromAcf(mixed $value): array
	{
		return self::listFromAcf($value, self::TermFromAcf(...));
	}

	public static function UserFromAcf(mixed $value, int $avatarSize = 96): ?WpUserData
	{
		if ($value instanceof \WP_User) {
			return WpDtoFactory::UserFromWpUser($value, $avatarSize);
		}
		$id = is_array($value) ? ($value['ID'] ?? null) : $value;
		if (! self::isPositiveId($id)) {
			return null;
		}
		$user = \get_userdata((int) $id);
		return $user instanceof \WP_User ? WpDtoFactory::UserFromWpUser($user, $avatarSize) : null;
	}

	/** @return list<WpUserData> */
	public static function UsersFromAcf(mixed $value, int $avatarSize = 96): array
	{
		return self::listFromAcf(
			$value,
			static fn (mixed $item): ?WpUserData => self::UserFromAcf($item, $avatarSize),
		);
	}

	/**
	 * @template T of object
	 * @param callable(mixed): ?T $converter
	 * @return list<T>
	 */
	private static function listFromAcf(mixed $value, callable $converter): array
	{
		if (!is_array($value)) {
			return [];
		}

		$result = [];
		foreach ($value as $item) {
			$data = $converter($item);
			if ($data === null) {
				continue;
			}
			$result[] = $data;
		}
		return $result;
	}

	public static function LinkFromAcf(mixed $value): ?LinkData
	{
		if (! is_array($value)) {
			return null;
		}
		$text = static fn (mixed $part): string => is_string($part) && trim($part) !== '' ? $part : '';
		$url = $text($value['url'] ?? null);
		return $url !== ''
			? new LinkData(Url: $url, Title: $text($value['title'] ?? null), Target: $text($value['target'] ?? null))
			: null;
	}

	public static function ChoiceFromAcf(mixed $value): ?ChoiceData
	{
		if (! is_array($value)) {
			return null;
		}
		$choiceValue = self::ChoiceText($value['value'] ?? null);
		$label = self::ChoiceText($value['label'] ?? null);
		return $choiceValue !== null && $label !== null
			? new ChoiceData(Value: $choiceValue, Label: $label)
			: null;
	}

	/** 数値の選択肢キーも文字列化し、0は未選択として扱わない。 */
	public static function ChoiceText(mixed $value): ?string
	{
		return (is_string($value) || is_int($value)) && (string) $value !== ''
			? (string) $value : null;
	}

	public static function ImgFromAcf(mixed $value, string|array $size = 'full'): ?ImgData
	{
		if (self::isPositiveId($value)) {
			return WpDtoFactory::ImgFromAttachmentId((int) $value, $size);
		}
		if (! is_array($value)) {
			return null;
		}

		$url = $value['url'] ?? null;
		$width = $value['width'] ?? null;
		$height = $value['height'] ?? null;
		$alt = $value['alt'] ?? '';
		$idValue = $value['ID'] ?? $value['id'] ?? 0;
		$attachmentId = self::isPositiveId($idValue) ? (int) $idValue : 0;
		$needsAttachmentResolution = $size === 'original'
			|| ! self::isPositiveDimension($width)
			|| ! self::isPositiveDimension($height);

		if ($attachmentId > 0 && $needsAttachmentResolution) {
			$resolved = WpDtoFactory::ImgFromAttachmentId($attachmentId, $size);
			if ($resolved !== null) {
				return new ImgData(
					Url: $resolved->Url,
					Width: $resolved->Width,
					Height: $resolved->Height,
					Alt: is_string($alt) ? $alt : $resolved->Alt,
				);
			}
		}

		if ($size === 'original' && $attachmentId > 0) {
			$originalUrl = \wp_get_original_image_url($attachmentId);
			$originalPath = \wp_get_original_image_path($attachmentId);
			$originalSize = is_string($originalPath) ? \wp_getimagesize($originalPath) : false;
			if (is_string($originalUrl) && $originalUrl !== '') {
				if (! is_array($originalSize)) {
					return null;
				}
				$url = $originalUrl;
			}
			if (is_array($originalSize)) {
				$width = $originalSize[0] ?? null;
				$height = $originalSize[1] ?? null;
			}
		} elseif ($size !== 'full' && $attachmentId > 0) {
			$source = \wp_get_attachment_image_src($attachmentId, $size);
			if (is_array($source)) {
				$url = $source[0] ?? null;
				$width = $source[1] ?? null;
				$height = $source[2] ?? null;
			}
		}

		if (! UrlCheck::IsHttpUrl($url) || ! is_numeric($width) || ! is_numeric($height)) {
			return null;
		}
		$width = (int) $width;
		$height = (int) $height;
		if ($width <= 0 || $height <= 0) {
			return null;
		}
		return new ImgData(
			Url: $url,
			Width: $width,
			Height: $height,
			Alt: is_string($alt) ? $alt : '',
		);
	}

	public static function FileFromAcf(mixed $value): ?FileData
	{
		if (self::isPositiveId($value)) {
			return WpDtoFactory::FileFromAttachmentId((int) $value);
		}
		if (! is_array($value) || ! UrlCheck::IsHttpUrl($value['url'] ?? null)) {
			return null;
		}
		$id = $value['ID'] ?? $value['id'] ?? null;
		$fileSize = self::fileSize($value['filesize'] ?? null);
		if (! self::isPositiveId($id) || $fileSize === null) {
			return null;
		}
		return new FileData(
			Id: (int) $id,
			Url: $value['url'],
			FileName: is_string($value['filename'] ?? null) ? $value['filename'] : '',
			MimeType: is_string($value['mime_type'] ?? null) ? $value['mime_type'] : '',
			FileSize: $fileSize,
		);
	}

	private static function fileSize(mixed $value): ?int
	{
		return is_numeric($value) && $value >= 0 ? (int) $value : null;
	}

	private static function isPositiveDimension(mixed $value): bool
	{
		return is_numeric($value) && (int) $value > 0;
	}

	private static function isPositiveId(mixed $value): bool
	{
		return (is_int($value) || (is_string($value) && ctype_digit($value))) && (int) $value > 0;
	}
}
