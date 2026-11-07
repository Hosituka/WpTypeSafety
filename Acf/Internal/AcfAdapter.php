<?php
declare(strict_types=1);

namespace WpTypeSafety\Acf\Internal;

use WpTypeSafety\Acf\Data\FlexibleNode;
use WpTypeSafety\Acf\Get\AcfGet;
use WpTypeSafety\Core\Data\ChoiceData;
use WpTypeSafety\Core\Data\FileData;
use WpTypeSafety\Core\Data\ImgData;
use WpTypeSafety\Core\Data\LinkData;
use WpTypeSafety\Core\Data\TimeData;
use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\TermData;
use WpTypeSafety\Wp\Data\WpUserData;
use WpTypeSafety\Core\Internal\UrlCheck;

/** @internal */
final class AcfAdapter
{
	private function __construct() {}

	public static function Register(): void
	{
		AcfGet::Register(
			raw: static fn ($fieldName, $source) => is_array($source)
				? self::valueFrom($fieldName, $source)
				: self::field($fieldName, $source),
			array: static fn ($fieldName, $source) => is_array($source)
				? self::arrayFrom($fieldName, $source)
				: self::arrayField($fieldName, $source),
			text: static fn ($fieldName, $source) => is_array($source)
				? self::textFrom($fieldName, $source)
				: self::text($fieldName, $source),
			post: static fn ($fieldName, $source) => is_array($source)
				? self::postFrom($fieldName, $source)
				: self::post($fieldName, $source),
			posts: static fn ($fieldName, $source) => is_array($source)
				? self::postsFrom($fieldName, $source)
				: self::posts($fieldName, $source),
			term: static fn ($fieldName, $source) => is_array($source)
				? self::termFrom($fieldName, $source)
				: self::term($fieldName, $source),
			terms: static fn ($fieldName, $source) => is_array($source)
				? self::termsFrom($fieldName, $source)
				: self::terms($fieldName, $source),
			user: static fn ($fieldName, $source, $avatarSize) => is_array($source)
				? self::userFrom($fieldName, $source, $avatarSize)
				: self::user($fieldName, $source, $avatarSize),
			users: static fn ($fieldName, $source, $avatarSize) => is_array($source)
				? self::usersFrom($fieldName, $source, $avatarSize)
				: self::users($fieldName, $source, $avatarSize),
			link: static fn ($fieldName, $source) => is_array($source)
				? self::linkFrom($fieldName, $source)
				: self::link($fieldName, $source),
			number: static fn ($fieldName, $source) => is_array($source)
				? self::numberFrom($fieldName, $source)
				: self::number($fieldName, $source),
			int: static fn ($fieldName, $source) => is_array($source)
				? self::intFrom($fieldName, $source)
				: self::int($fieldName, $source),
			img: static fn ($fieldName, $source, $size) => is_array($source)
				? self::imgFrom($fieldName, $source, $size)
				: self::img($fieldName, $source, $size),
			imgUrl: static fn ($fieldName, $source) => is_array($source)
				? self::imgUrlFrom($fieldName, $source)
				: self::imgUrl($fieldName, $source),
			file: static fn ($fieldName, $source) => is_array($source)
				? self::fileFrom($fieldName, $source)
				: self::file($fieldName, $source),
			fileUrl: static fn ($fieldName, $source) => is_array($source)
				? self::fileUrlFrom($fieldName, $source)
				: self::fileUrl($fieldName, $source),
			gallery: static fn ($fieldName, $source, $size) => is_array($source)
				? self::galleryFrom($fieldName, $source, $size)
				: self::gallery($fieldName, $source, $size),
			repeater: static fn ($fieldName, $source) => is_array($source)
				? self::repeaterFrom($fieldName, $source)
				: self::repeater($fieldName, $source),
			flexible: static fn ($fieldName, $source) => is_array($source)
				? self::flexibleFrom($fieldName, $source)
				: self::flexible($fieldName, $source),
			checkbox: static fn ($fieldName, $source) => is_array($source)
				? self::checkboxFrom($fieldName, $source)
				: self::checkbox($fieldName, $source),
			radio: static fn ($fieldName, $source) => is_array($source)
				? self::radioFrom($fieldName, $source)
				: self::radio($fieldName, $source),
			buttonGroup: static fn ($fieldName, $source) => self::buttonGroup($fieldName, $source),
			bool: static fn ($fieldName, $source) => is_array($source)
				? self::boolFrom($fieldName, $source)
				: self::bool($fieldName, $source),
			date: static fn ($fieldName, $source) => is_array($source)
				? null
				: self::date($fieldName, $source),
			dateTime: static fn ($fieldName, $source) => is_array($source)
				? null
				: self::dateTime($fieldName, $source),
			time: static fn ($fieldName, $source) => is_array($source)
				? null
				: self::time($fieldName, $source),
			radioValue: static fn ($fieldName, $source) => self::radioText($fieldName, $source, 'value'),
			radioLabel: static fn ($fieldName, $source) => self::radioText($fieldName, $source, 'label'),
			buttonGroupValue: static fn ($fieldName, $source) => self::buttonGroupText($fieldName, $source, 'value'),
			buttonGroupLabel: static fn ($fieldName, $source) => self::buttonGroupText($fieldName, $source, 'label'),
			checkboxValues: static fn ($fieldName, $source) => self::checkboxTexts($fieldName, $source, 'value'),
			checkboxLabels: static fn ($fieldName, $source) => self::checkboxTexts($fieldName, $source, 'label'),
			select: static fn ($fieldName, $source) => self::selectChoice($fieldName, $source),
			selectValue: static fn ($fieldName, $source) => self::selectText($fieldName, $source, 'value'),
			selectLabel: static fn ($fieldName, $source) => self::selectText($fieldName, $source, 'label'),
			selectValues: static fn ($fieldName, $source) => self::selectTexts($fieldName, $source, 'value'),
			selectLabels: static fn ($fieldName, $source) => self::selectTexts($fieldName, $source, 'label'),
			selectChoices: static fn ($fieldName, $source) => self::selectChoices($fieldName, $source),
			url: static fn ($fieldName, $source) => self::httpUrl(is_array($source)
				? self::valueFrom($fieldName, $source)
				: self::field($fieldName, $source)),
			galleryUrl: static fn ($fieldName, $source) => self::galleryUrls(is_array($source)
				? self::valueFrom($fieldName, $source)
				: self::field($fieldName, $source)),
		);
	}

	/** @return list<string> */
	private static function galleryUrls(mixed $value): array
	{
		if (!is_array($value)) {
			return [];
		}

		$urls = [];
		foreach ($value as $item) {
			$url = self::httpUrl($item);
			if ($url !== null) {
				$urls[] = $url;
			}
		}
		return $urls;
	}

	/** @return array<mixed> */
	private static function arrayField(string $fieldName, \WP_Post|PostData|int|string|null $postId = null): array
	{
		return self::asArray(self::field($fieldName, $postId));
	}

	/** @return array<mixed> */
	private static function arrayFrom(string $key, mixed $parent): array
	{
		return self::asArray(self::valueFrom($key, $parent));
	}

	private static function text(string $fieldName, \WP_Post|PostData|int|string|null $postId = null): string
	{
		return self::textValue(self::field($fieldName, $postId));
	}

	private static function textFrom(string $key, mixed $parent): string
	{
		return self::textValue(self::valueFrom($key, $parent));
	}

	private static function post(string $fieldName, \WP_Post|PostData|int|string|null $postId = null): ?PostData
	{
		return AcfDtoFactory::PostFromAcf(self::field($fieldName, $postId));
	}

	private static function postFrom(string $key, mixed $parent): ?PostData
	{
		return AcfDtoFactory::PostFromAcf(self::valueFrom($key, $parent));
	}

	/** @return list<PostData> */
	private static function posts(string $fieldName, \WP_Post|PostData|int|string|null $postId = null): array
	{
		return AcfDtoFactory::PostsFromAcf(self::field($fieldName, $postId));
	}

	/** @return list<PostData> */
	private static function postsFrom(string $key, mixed $parent): array
	{
		return AcfDtoFactory::PostsFromAcf(self::valueFrom($key, $parent));
	}

	private static function term(string $fieldName, \WP_Post|PostData|int|string|null $postId = null): ?TermData
	{
		return AcfDtoFactory::TermFromAcf(self::field($fieldName, $postId));
	}

	private static function termFrom(string $key, mixed $parent): ?TermData
	{
		return AcfDtoFactory::TermFromAcf(self::valueFrom($key, $parent));
	}

	/** @return list<TermData> */
	private static function terms(string $fieldName, \WP_Post|PostData|int|string|null $postId = null): array
	{
		return AcfDtoFactory::TermsFromAcf(self::field($fieldName, $postId));
	}

	/** @return list<TermData> */
	private static function termsFrom(string $key, mixed $parent): array
	{
		return AcfDtoFactory::TermsFromAcf(self::valueFrom($key, $parent));
	}

	private static function user(string $fieldName, \WP_Post|PostData|int|string|null $postId = null, int $avatarSize = 96): ?WpUserData
	{
		return AcfDtoFactory::UserFromAcf(self::field($fieldName, $postId), $avatarSize);
	}

	private static function userFrom(string $key, mixed $parent, int $avatarSize = 96): ?WpUserData
	{
		return AcfDtoFactory::UserFromAcf(self::valueFrom($key, $parent), $avatarSize);
	}

	/** @return list<WpUserData> */
	private static function users(string $fieldName, \WP_Post|PostData|int|string|null $postId = null, int $avatarSize = 96): array
	{
		return AcfDtoFactory::UsersFromAcf(self::field($fieldName, $postId), $avatarSize);
	}

	/** @return list<WpUserData> */
	private static function usersFrom(string $key, mixed $parent, int $avatarSize = 96): array
	{
		return AcfDtoFactory::UsersFromAcf(self::valueFrom($key, $parent), $avatarSize);
	}

	private static function link(string $fieldName, \WP_Post|PostData|int|string|null $postId = null): ?LinkData
	{
		return AcfDtoFactory::LinkFromAcf(self::field($fieldName, $postId));
	}

	private static function linkFrom(string $key, mixed $parent): ?LinkData
	{
		return AcfDtoFactory::LinkFromAcf(self::valueFrom($key, $parent));
	}

	private static function number(string $fieldName, \WP_Post|PostData|int|string|null $postId = null): ?float
	{
		return self::numberValue(self::field($fieldName, $postId));
	}

	private static function numberFrom(string $key, mixed $parent): ?float
	{
		return self::numberValue(self::valueFrom($key, $parent));
	}

	private static function int(string $fieldName, \WP_Post|PostData|int|string|null $postId = null): ?int
	{
		return self::intValue(self::field($fieldName, $postId));
	}

	private static function intFrom(string $key, mixed $parent): ?int
	{
		return self::intValue(self::valueFrom($key, $parent));
	}

	private static function img(string $fieldName, \WP_Post|PostData|int|string|null $postId = null, string|array $size = 'full'): ?ImgData
	{
		return AcfDtoFactory::ImgFromAcf(self::field($fieldName, $postId), $size);
	}

	private static function imgFrom(string $key, mixed $parent, string|array $size = 'full'): ?ImgData
	{
		return AcfDtoFactory::ImgFromAcf(self::valueFrom($key, $parent), $size);
	}

	private static function imgUrl(string $fieldName, \WP_Post|PostData|int|string|null $postId = null): ?string
	{
		return self::httpUrl(self::field($fieldName, $postId));
	}

	private static function imgUrlFrom(string $key, mixed $parent): ?string
	{
		return self::httpUrl(self::valueFrom($key, $parent));
	}

	private static function file(string $fieldName, \WP_Post|PostData|int|string|null $postId = null): ?FileData
	{
		return AcfDtoFactory::FileFromAcf(self::field($fieldName, $postId));
	}

	private static function fileFrom(string $key, mixed $parent): ?FileData
	{
		return AcfDtoFactory::FileFromAcf(self::valueFrom($key, $parent));
	}

	private static function fileUrl(string $fieldName, \WP_Post|PostData|int|string|null $postId = null): ?string
	{
		return self::httpUrl(self::field($fieldName, $postId));
	}

	private static function fileUrlFrom(string $key, mixed $parent): ?string
	{
		return self::httpUrl(self::valueFrom($key, $parent));
	}

	/** @return list<ImgData> */
	private static function gallery(string $fieldName, \WP_Post|PostData|int|string|null $postId = null, string|array $size = 'full'): array
	{
		return self::galleryValue(self::field($fieldName, $postId), $size);
	}

	/** @return list<ImgData> */
	private static function galleryFrom(string $key, mixed $parent, string|array $size = 'full'): array
	{
		return self::galleryValue(self::valueFrom($key, $parent), $size);
	}

	/** @return list<AcfGet> */
	private static function repeater(string $fieldName, \WP_Post|PostData|int|string|null $postId = null): array
	{
		return self::rowGets(self::field($fieldName, $postId));
	}

	/** @return list<AcfGet> */
	private static function repeaterFrom(string $key, mixed $parent): array
	{
		return self::rowGets(self::valueFrom($key, $parent));
	}

	/** @return list<AcfGet> */
	private static function rowGets(mixed $value): array
	{
		if (! is_array($value))
		{return [];}

		$rows = [];
		foreach ($value as $row) {
			if (is_array($row)) {
				$rows[] = new AcfGet($row);
			}
		}
		return $rows;
	}

	/** @return list<FlexibleNode> */
	private static function flexible(string $fieldName, \WP_Post|PostData|int|string|null $postId = null): array
	{
		return self::flexibleNodes(self::field($fieldName, $postId));
	}

	/** @return list<FlexibleNode> */
	private static function flexibleFrom(string $key, mixed $parent): array
	{
		return self::flexibleNodes(self::valueFrom($key, $parent));
	}

	/** @return list<FlexibleNode> */
	private static function flexibleNodes(mixed $value): array
	{
		$nodes = [];
		foreach (self::rowGets($value) as $rowGet) {
			$layout = $rowGet->Text('acf_fc_layout');
			if ($layout === '')
			{continue;}

			$nodes[] = new FlexibleNode(
				Layout: $layout,
				Get: $rowGet,
			);
		}

		return $nodes;
	}

	/** @return list<ChoiceData> */
	private static function checkbox(string $fieldName, \WP_Post|PostData|int|string|null $postId = null): array
	{
		return self::checkboxField($fieldName, $postId);
	}

	/** @return list<ChoiceData> */
	private static function checkboxFrom(string $key, mixed $parent): array
	{
		return self::choices(self::valueFrom($key, $parent));
	}

	private static function radio(string $fieldName, \WP_Post|PostData|int|string|null $postId = null): ?ChoiceData
	{
		return self::radioField($fieldName, $postId);
	}

	private static function radioFrom(string $key, mixed $parent): ?ChoiceData
	{
		return AcfDtoFactory::ChoiceFromAcf(self::valueFrom($key, $parent));
	}

	private static function buttonGroup(
		string $fieldName,
		\WP_Post|PostData|int|string|array|null $source,
	): ?ChoiceData {
		$value = is_array($source)
			? self::valueFrom($fieldName, $source)
			: self::choiceFieldValue($fieldName, $source, 'button_group', 'array');
		return AcfDtoFactory::ChoiceFromAcf($value);
	}

	private static function bool(string $fieldName, \WP_Post|PostData|int|string|null $postId = null): ?bool
	{
		return self::boolValue(self::field($fieldName, $postId));
	}

	private static function boolFrom(string $key, mixed $parent): ?bool
	{
		return self::boolValue(self::valueFrom($key, $parent));
	}

	private static function date(string $fieldName, \WP_Post|PostData|int|string|null $postId = null): ?\DateTimeImmutable
	{
		$value = self::rawTemporalField($fieldName, $postId, 'date_picker');
		return self::dateTimeValue($value, 'Ymd');
	}

	private static function dateTime(string $fieldName, \WP_Post|PostData|int|string|null $postId = null): ?\DateTimeImmutable
	{
		$value = self::rawTemporalField($fieldName, $postId, 'date_time_picker');
		return self::dateTimeValue($value, 'Y-m-d H:i:s');
	}

	private static function time(string $fieldName, \WP_Post|PostData|int|string|null $postId = null): ?TimeData
	{
		$value = self::rawTemporalField($fieldName, $postId, 'time_picker');
		if (! is_string($value) || preg_match('/^(\d{2}):(\d{2}):(\d{2})$/', $value, $matches) !== 1) {
			return null;
		}

		$hour = (int) $matches[1];
		$minute = (int) $matches[2];
		$second = (int) $matches[3];
		if ($hour > 23 || $minute > 59 || $second > 59) {
			return null;
		}

		return new TimeData(
			Hour: $hour,
			Minute: $minute,
			Second: $second,
		);
	}

	private static function dateTimeValue(mixed $value, string $format): ?\DateTimeImmutable
	{
		if (! is_string($value) || $value === '') {
			return null;
		}

		$dateTime = \DateTimeImmutable::createFromFormat(
			'!' . $format,
			$value,
			\wp_timezone(),
		);
		return $dateTime !== false && $dateTime->format($format) === $value
			? $dateTime
			: null;
	}

	private static function rawTemporalField(
		string $fieldName,
		\WP_Post|PostData|int|string|null $postId,
		string $expectedType,
	): mixed {
		$field = \get_field_object($fieldName, self::postId($postId), false, true);
		if (! is_array($field) || ($field['type'] ?? null) !== $expectedType) {
			return null;
		}

		return $field['value'] ?? null;
	}

	private static function field(string $name, \WP_Post|PostData|int|string|null $postId): mixed
	{
		return \get_field($name, self::postId($postId));
	}

	private static function postId(\WP_Post|PostData|int|string|null $postId): int|string|null
	{
		if ($postId instanceof \WP_Post) {
			return $postId->ID;
		}

		return $postId instanceof PostData ? $postId->PostId : $postId;
	}

	private static function valueFrom(string $key, mixed $parent): mixed
	{
		return is_array($parent) ? ($parent[$key] ?? null) : null;
	}

	/** @return array<mixed> */
	private static function asArray(mixed $value): array
	{
		return is_array($value) ? $value : [];
	}

	private static function textValue(mixed $value): string
	{
		return is_string($value) && trim($value) !== '' ? $value : '';
	}

	private static function numberValue(mixed $value): ?float
	{
		return is_numeric($value) ? (float) $value : null;
	}

	private static function intValue(mixed $value): ?int
	{
		if (is_int($value)) {
			return $value;
		}
		if (is_float($value)) {
			if (! is_finite($value) || floor($value) !== $value) {
				return null;
			}
			$integer = (int) $value;
			return (float) $integer === $value ? $integer : null;
		}
		if (! is_string($value) || preg_match('/^-?\d+$/D', $value) !== 1) {
			return null;
		}
		$negative = str_starts_with($value, '-');
		$digits = ltrim($negative ? substr($value, 1) : $value, '0');
		$digits = $digits === '' ? '0' : $digits;
		$limit = $negative ? ltrim((string) PHP_INT_MIN, '-') : (string) PHP_INT_MAX;
		if (strlen($digits) > strlen($limit)
			|| (strlen($digits) === strlen($limit) && strcmp($digits, $limit) > 0)
		) {
			return null;
		}
		return (int) (($negative ? '-' : '') . $digits);
	}

	private static function httpUrl(mixed $value): ?string
	{
		return UrlCheck::IsHttpUrl($value) ? $value : null;
	}

	/** @return list<ImgData> */
	private static function galleryValue(mixed $value, string|array $size): array
	{
		if (! is_array($value)) {
			return [];
		}
		$images = [];
		foreach ($value as $item) {
			$image = AcfDtoFactory::ImgFromAcf($item, $size);
			if ($image !== null) {
				$images[] = $image;
			}
		}
		return $images;
	}

	/** @return list<ChoiceData> */
	private static function choices(mixed $value): array
	{
		if (! is_array($value) || ! array_is_list($value)) {
			return [];
		}
		$choices = [];
		foreach ($value as $item) {
			$choice = AcfDtoFactory::ChoiceFromAcf($item);
			if ($choice !== null) {
				$choices[] = $choice;
			}
		}
		return $choices;
	}

	/** @return list<ChoiceData> */
	private static function checkboxField(string $name, \WP_Post|PostData|int|string|null $postId): array
	{
		return self::choices(self::choiceFieldValue($name, $postId, 'checkbox', 'array'));
	}

	private static function radioField(string $name, \WP_Post|PostData|int|string|null $postId): ?ChoiceData
	{
		return AcfDtoFactory::ChoiceFromAcf(self::choiceFieldValue($name, $postId, 'radio', 'array'));
	}

	/** フィールド型・返り値設定が合わなければ変換せずnullを返す。 */
	private static function choiceFieldValue(string $name, \WP_Post|PostData|int|string|null $postId, string $type, string $format): mixed
	{
		$field = \get_field_object($name, self::postId($postId));
		if (! is_array($field) || ($field['type'] ?? null) !== $type
			|| ($field['return_format'] ?? 'value') !== $format) {
			return null;
		}
		return $field['value'] ?? null;
	}

	private static function radioText(string $name, \WP_Post|PostData|int|string|array|null $source, string $format): ?string
	{
		$value = is_array($source)
			? self::valueFrom($name, $source)
			: self::choiceFieldValue($name, $source, 'radio', $format);
		return AcfDtoFactory::ChoiceText($value);
	}

	private static function buttonGroupText(
		string $name,
		\WP_Post|PostData|int|string|array|null $source,
		string $format,
	): ?string {
		$value = is_array($source)
			? self::valueFrom($name, $source)
			: self::choiceFieldValue($name, $source, 'button_group', $format);
		return AcfDtoFactory::ChoiceText($value);
	}

	/** @return list<string> */
	private static function checkboxTexts(string $name, \WP_Post|PostData|int|string|array|null $source, string $format): array
	{
		$value = is_array($source)
			? self::valueFrom($name, $source)
			: self::choiceFieldValue($name, $source, 'checkbox', $format);
		if (! is_array($value) || ! array_is_list($value)) {
			return [];
		}
		$result = [];
		foreach ($value as $item) {
			$text = AcfDtoFactory::ChoiceText($item);
			if ($text !== null) {
				$result[] = $text;
			}
		}
		return $result;
	}

	/** Selectの型・単一/複数・返り値設定を検証する。取得済み配列では値の形状のみ検証する。 */
	private static function selectFieldValue(string $name, \WP_Post|PostData|int|string|array|null $source, string $format, bool $multiple): mixed
	{
		if (is_array($source)) {
			return self::valueFrom($name, $source);
		}
		$field = \get_field_object($name, self::postId($source));
		if (! is_array($field) || ($field['type'] ?? null) !== 'select'
			|| ($field['return_format'] ?? 'value') !== $format
			|| (bool) ($field['multiple'] ?? false) !== $multiple) {
			return null;
		}
		return $field['value'] ?? null;
	}

	private static function selectChoice(string $name, \WP_Post|PostData|int|string|array|null $source): ?ChoiceData
	{
		return AcfDtoFactory::ChoiceFromAcf(self::selectFieldValue($name, $source, 'array', false));
	}

	private static function selectText(string $name, \WP_Post|PostData|int|string|array|null $source, string $format): ?string
	{
		return AcfDtoFactory::ChoiceText(self::selectFieldValue($name, $source, $format, false));
	}

	/** @return list<ChoiceData> */
	private static function selectChoices(string $name, \WP_Post|PostData|int|string|array|null $source): array
	{
		return self::choices(self::selectFieldValue($name, $source, 'array', true));
	}

	/** @return list<string> */
	private static function selectTexts(string $name, \WP_Post|PostData|int|string|array|null $source, string $format): array
	{
		$value = self::selectFieldValue($name, $source, $format, true);
		if (! is_array($value) || ! array_is_list($value)) {
			return [];
		}
		$result = [];
		foreach ($value as $item) {
			$text = AcfDtoFactory::ChoiceText($item);
			if ($text !== null) {
				$result[] = $text;
			}
		}
		return $result;
	}

	private static function boolValue(mixed $value): ?bool
	{
		return match ($value) {
			true, 1, '1' => true,
			false, 0, '0' => false,
			default => null,
		};
	}
}
