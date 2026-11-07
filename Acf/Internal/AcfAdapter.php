<?php
declare(strict_types=1);

namespace WpTypeSafety\Acf\Internal;

use WpTypeSafety\Core\Data\ChoiceData;
use WpTypeSafety\Core\Data\FileData;
use WpTypeSafety\Core\Data\ImgData;
use WpTypeSafety\Core\Data\LinkData;
use WpTypeSafety\Core\Data\TimeData;
use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\TermData;
use WpTypeSafety\Wp\Data\WpUserData;
use WpTypeSafety\Core\Internal\UrlCheck;

/** ACFの値取得とWpTypeSafetyの型への変換を担当するAdapter。 */
class AcfAdapter
{
	public function GetRaw(string $fieldName, \WP_Post|PostData|int|string|array|null $source): mixed
	{
		return is_array($source)
			? self::valueFrom($fieldName, $source)
			: self::field($fieldName, $source);
	}

	/** @return array<mixed> */
	public function GetArray(string $fieldName, \WP_Post|PostData|int|string|array|null $source): array
	{
		return is_array($source)
			? self::arrayFrom($fieldName, $source)
			: self::arrayField($fieldName, $source);
	}

	/** @return array{values: array<mixed>, rawValues: null|array<mixed>, subFields: null|array<mixed>} */
	public function GetNested(string $fieldName, \WP_Post|PostData|int|string|array|null $source): array
	{
		return is_array($source)
			? ['values' => self::arrayFrom($fieldName, $source), 'rawValues' => null, 'subFields' => null]
			: self::nested($fieldName, $source);
	}

	public function GetText(string $fieldName, \WP_Post|PostData|int|string|array|null $source): string
	{
		return is_array($source) ? self::textFrom($fieldName, $source) : self::text($fieldName, $source);
	}

	public function GetPost(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?PostData
	{
		return is_array($source) ? self::postFrom($fieldName, $source) : self::post($fieldName, $source);
	}

	/** @return list<PostData> */
	public function GetPosts(string $fieldName, \WP_Post|PostData|int|string|array|null $source): array
	{
		return is_array($source) ? self::postsFrom($fieldName, $source) : self::posts($fieldName, $source);
	}

	public function GetTerm(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?TermData
	{
		return is_array($source) ? self::termFrom($fieldName, $source) : self::term($fieldName, $source);
	}

	/** @return list<TermData> */
	public function GetTerms(string $fieldName, \WP_Post|PostData|int|string|array|null $source): array
	{
		return is_array($source) ? self::termsFrom($fieldName, $source) : self::terms($fieldName, $source);
	}

	public function GetUser(string $fieldName, \WP_Post|PostData|int|string|array|null $source, int $avatarSize): ?WpUserData
	{
		return is_array($source)
			? self::userFrom($fieldName, $source, $avatarSize)
			: self::user($fieldName, $source, $avatarSize);
	}

	/** @return list<WpUserData> */
	public function GetUsers(string $fieldName, \WP_Post|PostData|int|string|array|null $source, int $avatarSize): array
	{
		return is_array($source)
			? self::usersFrom($fieldName, $source, $avatarSize)
			: self::users($fieldName, $source, $avatarSize);
	}

	public function GetLink(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?LinkData
	{
		return is_array($source) ? self::linkFrom($fieldName, $source) : self::link($fieldName, $source);
	}

	public function GetNumber(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?float
	{
		return is_array($source) ? self::numberFrom($fieldName, $source) : self::number($fieldName, $source);
	}

	public function GetInt(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?int
	{
		return is_array($source) ? self::intFrom($fieldName, $source) : self::int($fieldName, $source);
	}

	public function GetImg(string $fieldName, \WP_Post|PostData|int|string|array|null $source, string|array $size): ?ImgData
	{
		return is_array($source) ? self::imgFrom($fieldName, $source, $size) : self::img($fieldName, $source, $size);
	}

	public function GetImgUrl(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?string
	{
		return is_array($source) ? self::imgUrlFrom($fieldName, $source) : self::imgUrl($fieldName, $source);
	}

	public function GetFile(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?FileData
	{
		return is_array($source) ? self::fileFrom($fieldName, $source) : self::file($fieldName, $source);
	}

	public function GetFileUrl(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?string
	{
		return is_array($source) ? self::fileUrlFrom($fieldName, $source) : self::fileUrl($fieldName, $source);
	}

	/** @return list<ImgData> */
	public function GetGallery(string $fieldName, \WP_Post|PostData|int|string|array|null $source, string|array $size): array
	{
		return is_array($source)
			? self::galleryFrom($fieldName, $source, $size)
			: self::gallery($fieldName, $source, $size);
	}

	/** @return list<string> */
	public function GetGalleryUrl(string $fieldName, \WP_Post|PostData|int|string|array|null $source): array
	{
		return self::galleryUrls($this->GetRaw($fieldName, $source));
	}

	/** @return list<array<mixed>> */
	public function GetRepeater(string $fieldName, \WP_Post|PostData|int|string|array|null $source): array
	{
		return self::rows($this->GetRaw($fieldName, $source));
	}

	/** @return list<array{layout: string, values: array<mixed>}> */
	public function GetFlexible(string $fieldName, \WP_Post|PostData|int|string|array|null $source): array
	{
		$nodes = [];
		foreach (self::rows($this->GetRaw($fieldName, $source)) as $row) {
			$layout = self::textValue($row['acf_fc_layout'] ?? null);
			if ($layout === '')
			{continue;}

			$nodes[] = ['layout' => $layout, 'values' => $row];
		}
		return $nodes;
	}

	/** @return list<ChoiceData> */
	public function GetCheckbox(string $fieldName, \WP_Post|PostData|int|string|array|null $source): array
	{
		return is_array($source) ? self::checkboxFrom($fieldName, $source) : self::checkbox($fieldName, $source);
	}

	public function GetRadio(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?ChoiceData
	{
		return is_array($source) ? self::radioFrom($fieldName, $source) : self::radio($fieldName, $source);
	}

	public function GetButtonGroup(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?ChoiceData
	{
		return self::buttonGroup($fieldName, $source);
	}

	public function GetBool(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?bool
	{
		return is_array($source) ? self::boolFrom($fieldName, $source) : self::bool($fieldName, $source);
	}

	public function GetDate(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?\DateTimeImmutable
	{
		return is_array($source) ? null : self::date($fieldName, $source);
	}

	public function GetDateTime(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?\DateTimeImmutable
	{
		return is_array($source) ? null : self::dateTime($fieldName, $source);
	}

	public function GetTime(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?TimeData
	{
		return is_array($source) ? null : self::time($fieldName, $source);
	}

	public function ConvertDate(mixed $value): ?\DateTimeImmutable
	{
		return TemporalValue::Date($value);
	}

	public function ConvertDateTime(mixed $value): ?\DateTimeImmutable
	{
		return TemporalValue::DateTime($value);
	}

	public function ConvertTime(mixed $value): ?TimeData
	{
		return TemporalValue::Time($value);
	}

	public function GetRadioValue(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?string
	{
		return self::radioText($fieldName, $source, 'value');
	}

	public function GetRadioLabel(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?string
	{
		return self::radioText($fieldName, $source, 'label');
	}

	public function GetButtonGroupValue(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?string
	{
		return self::buttonGroupText($fieldName, $source, 'value');
	}

	public function GetButtonGroupLabel(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?string
	{
		return self::buttonGroupText($fieldName, $source, 'label');
	}

	/** @return list<string> */
	public function GetCheckboxValues(string $fieldName, \WP_Post|PostData|int|string|array|null $source): array
	{
		return self::checkboxTexts($fieldName, $source, 'value');
	}

	/** @return list<string> */
	public function GetCheckboxLabels(string $fieldName, \WP_Post|PostData|int|string|array|null $source): array
	{
		return self::checkboxTexts($fieldName, $source, 'label');
	}

	public function GetSelect(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?ChoiceData
	{
		return self::selectChoice($fieldName, $source);
	}

	public function GetSelectValue(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?string
	{
		return self::selectText($fieldName, $source, 'value');
	}

	public function GetSelectLabel(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?string
	{
		return self::selectText($fieldName, $source, 'label');
	}

	/** @return list<string> */
	public function GetSelectValues(string $fieldName, \WP_Post|PostData|int|string|array|null $source): array
	{
		return self::selectTexts($fieldName, $source, 'value');
	}

	/** @return list<string> */
	public function GetSelectLabels(string $fieldName, \WP_Post|PostData|int|string|array|null $source): array
	{
		return self::selectTexts($fieldName, $source, 'label');
	}

	/** @return list<ChoiceData> */
	public function GetSelectChoices(string $fieldName, \WP_Post|PostData|int|string|array|null $source): array
	{
		return self::selectChoices($fieldName, $source);
	}

	public function GetUrl(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?string
	{
		return self::httpUrl($this->GetRaw($fieldName, $source));
	}

	public function UserPostId(\WP_User|WpUserData $user): string
	{
		$id = $user instanceof WpUserData ? $user->Id : (int) $user->ID;
		return 'user_' . $id;
	}

	public function TermPostId(\WP_Term|TermData $term): string
	{
		$id = $term instanceof TermData ? $term->Id : (int) $term->term_id;
		$taxonomy = $term instanceof TermData ? $term->Taxonomy : (string) ($term->taxonomy ?? '');
		return ($taxonomy !== '' ? $taxonomy : 'term') . '_' . $id;
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

	/** @return array{values: array<mixed>, rawValues: null|array<mixed>, subFields: null|array<mixed>} */
	private static function nested(
		string $fieldName,
		\WP_Post|PostData|int|string|null $postId = null,
	): array {
		$values = self::arrayField($fieldName, $postId);
		if (! function_exists('get_field_object')) {
			return ['values' => $values, 'rawValues' => null, 'subFields' => null];
		}

		$field = \get_field_object($fieldName, self::postId($postId), false, true);
		if (! is_array($field) || ($field['type'] ?? null) !== 'group') {
			return ['values' => $values, 'rawValues' => null, 'subFields' => null];
		}

		$rawValues = $field['value'] ?? null;
		$subFields = $field['sub_fields'] ?? null;
		return [
			'values' => $values,
			'rawValues' => is_array($rawValues) ? $rawValues : [],
			'subFields' => is_array($subFields) ? $subFields : [],
		];
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

	/** @return list<array<mixed>> */
	private static function rows(mixed $value): array
	{
		if (! is_array($value))
		{return [];}

		$rows = [];
		foreach ($value as $row) {
			if (is_array($row)) {
				$rows[] = $row;
			}
		}
		return $rows;
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
		return TemporalValue::Date($value);
	}

	private static function dateTime(string $fieldName, \WP_Post|PostData|int|string|null $postId = null): ?\DateTimeImmutable
	{
		$value = self::rawTemporalField($fieldName, $postId, 'date_time_picker');
		return TemporalValue::DateTime($value);
	}

	private static function time(string $fieldName, \WP_Post|PostData|int|string|null $postId = null): ?TimeData
	{
		$value = self::rawTemporalField($fieldName, $postId, 'time_picker');
		return TemporalValue::Time($value);
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
