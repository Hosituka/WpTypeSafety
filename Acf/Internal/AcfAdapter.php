<?php
declare(strict_types=1);

namespace WpTypeSafety\Acf\Internal;

use WpTypeSafety\Core\Check\ValueMode;
use WpTypeSafety\Core\Check\ValueRules;
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
		return self::asArray($this->GetRaw($fieldName, $source));
	}

	/** @return array{values: array<mixed>, rawValues: null|array<mixed>, subFields: null|array<mixed>} */
	public function GetNested(string $fieldName, \WP_Post|PostData|int|string|array|null $source): array
	{
		return is_array($source)
			? ['values' => $this->GetArray($fieldName, $source), 'rawValues' => null, 'subFields' => null]
			: self::nested($fieldName, $source);
	}

	public function GetText(string $fieldName, \WP_Post|PostData|int|string|array|null $source): string
	{
		return self::textValue($this->GetRaw($fieldName, $source));
	}

	public function GetPost(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?PostData
	{
		return AcfDtoFactory::PostFromAcf($this->GetRaw($fieldName, $source));
	}

	public function GetWpPost(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?\WP_Post
	{
		return AcfDtoFactory::WpPostFromAcf($this->GetRaw($fieldName, $source));
	}

	/** @return list<PostData> */
	public function GetPosts(string $fieldName, \WP_Post|PostData|int|string|array|null $source): array
	{
		return AcfDtoFactory::PostsFromAcf($this->GetRaw($fieldName, $source));
	}

	/** @return list<\WP_Post> */
	public function GetWpPosts(string $fieldName, \WP_Post|PostData|int|string|array|null $source): array
	{
		return AcfDtoFactory::WpPostsFromAcf($this->GetRaw($fieldName, $source));
	}

	public function GetTerm(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?TermData
	{
		return AcfDtoFactory::TermFromAcf($this->GetRaw($fieldName, $source));
	}

	public function GetWpTerm(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?\WP_Term
	{
		return AcfDtoFactory::WpTermFromAcf($this->GetRaw($fieldName, $source));
	}

	/** @return list<TermData> */
	public function GetTerms(string $fieldName, \WP_Post|PostData|int|string|array|null $source): array
	{
		return AcfDtoFactory::TermsFromAcf($this->GetRaw($fieldName, $source));
	}

	/** @return list<\WP_Term> */
	public function GetWpTerms(string $fieldName, \WP_Post|PostData|int|string|array|null $source): array
	{
		return AcfDtoFactory::WpTermsFromAcf($this->GetRaw($fieldName, $source));
	}

	public function GetUser(string $fieldName, \WP_Post|PostData|int|string|array|null $source, int $avatarSize): ?WpUserData
	{
		return AcfDtoFactory::UserFromAcf($this->GetRaw($fieldName, $source), $avatarSize);
	}

	public function GetWpUser(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?\WP_User
	{
		return AcfDtoFactory::WpUserFromAcf($this->GetRaw($fieldName, $source));
	}

	/** @return list<WpUserData> */
	public function GetUsers(string $fieldName, \WP_Post|PostData|int|string|array|null $source, int $avatarSize): array
	{
		return AcfDtoFactory::UsersFromAcf($this->GetRaw($fieldName, $source), $avatarSize);
	}

	/** @return list<\WP_User> */
	public function GetWpUsers(string $fieldName, \WP_Post|PostData|int|string|array|null $source): array
	{
		return AcfDtoFactory::WpUsersFromAcf($this->GetRaw($fieldName, $source));
	}

	public function GetLink(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?LinkData
	{
		return AcfDtoFactory::LinkFromAcf($this->GetRaw($fieldName, $source));
	}

	public function GetFloat(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?float
	{
		return self::numericRules()->NormalizeFloat($this->GetRaw($fieldName, $source));
	}

	public function GetInt(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?int
	{
		return self::numericRules()->NormalizeInt($this->GetRaw($fieldName, $source));
	}

	public function GetImg(string $fieldName, \WP_Post|PostData|int|string|array|null $source, string|array $size): ?ImgData
	{
		return AcfDtoFactory::ImgFromAcf($this->GetRaw($fieldName, $source), $size);
	}

	public function GetImgUrl(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?string
	{
		return self::httpUrl($this->GetRaw($fieldName, $source));
	}

	public function GetIconClass(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?string
	{
		return $this->ConvertIconClass(self::iconFieldValue($fieldName, $source));
	}

	public function GetIconImg(
		string $fieldName,
		\WP_Post|PostData|int|string|array|null $source,
		string|array $size,
	): ?ImgData {
		return $this->ConvertIconImg(self::iconFieldValue($fieldName, $source), $size);
	}

	public function GetIconUrl(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?string
	{
		return $this->ConvertIconUrl(self::iconFieldValue($fieldName, $source));
	}

	public function ConvertIconClass(mixed $value): ?string
	{
		$icon = self::iconValue($value);
		if ($icon === null || $icon['type'] !== 'dashicons' || ! is_string($icon['value'])) {
			return null;
		}

		return preg_match('/^dashicons-[a-z0-9-]+$/D', $icon['value']) === 1
			? $icon['value']
			: null;
	}

	public function ConvertIconImg(mixed $value, string|array $size): ?ImgData
	{
		$icon = self::iconValue($value);
		return $icon !== null && $icon['type'] === 'media_library'
			? AcfDtoFactory::ImgFromAcf($icon['value'], $size)
			: null;
	}

	public function ConvertIconUrl(mixed $value): ?string
	{
		$icon = self::iconValue($value);
		return $icon !== null && $icon['type'] === 'url'
			? self::httpUrl($icon['value'])
			: null;
	}

	public function GetFile(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?FileData
	{
		return AcfDtoFactory::FileFromAcf($this->GetRaw($fieldName, $source));
	}

	public function GetFileUrl(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?string
	{
		return self::httpUrl($this->GetRaw($fieldName, $source));
	}

	/** @return list<ImgData> */
	public function GetGallery(string $fieldName, \WP_Post|PostData|int|string|array|null $source, string|array $size): array
	{
		return self::galleryValue($this->GetRaw($fieldName, $source), $size);
	}

	/** @return list<string> */
	public function GetGalleryUrl(string $fieldName, \WP_Post|PostData|int|string|array|null $source): array
	{
		return self::galleryUrls($this->GetRaw($fieldName, $source));
	}

	/** @return list<array{values: array<mixed>, rawValues: null|array<mixed>, subFields: null|array<mixed>}> */
	public function GetRepeater(string $fieldName, \WP_Post|PostData|int|string|array|null $source): array
	{
		$values = $this->GetRaw($fieldName, $source);
		if (is_array($source) || ! function_exists('get_field_object'))
		{return $this->ConvertRepeater($values);}

		$field = AcfFieldDefinition::From(
			\get_field_object($fieldName, self::postId($source), false, true),
		);
		if ($field === null || ! $field->IsType('repeater'))
		{return $this->ConvertRepeater($values);}

		return $this->ConvertRepeater(
			values: $values,
			rawValues: $field->Value(),
			subFields: $field->SubFields(),
		);
	}

	/** @return list<array{layout: string, values: array<mixed>, rawValues: null|array<mixed>, subFields: null|array<mixed>}> */
	public function GetFlexible(string $fieldName, \WP_Post|PostData|int|string|array|null $source): array
	{
		$values = $this->GetRaw($fieldName, $source);
		if (is_array($source) || ! function_exists('get_field_object'))
		{return $this->ConvertFlexible($values);}

		$field = AcfFieldDefinition::From(
			\get_field_object($fieldName, self::postId($source), false, true),
		);
		if ($field === null || ! $field->IsType('flexible_content'))
		{return $this->ConvertFlexible($values);}

		return $this->ConvertFlexible(
			values: $values,
			rawValues: $field->Value(),
			layouts: $field->Layouts(),
		);
	}

	/** @return list<array{values: array<mixed>, rawValues: null|array<mixed>, subFields: null|array<mixed>}> */
	public function ConvertRepeater(mixed $values, mixed $rawValues = null, mixed $subFields = null): array
	{
		return self::rowContexts($values, $rawValues, $subFields);
	}

	/** @return list<array{layout: string, values: array<mixed>, rawValues: null|array<mixed>, subFields: null|array<mixed>}> */
	public function ConvertFlexible(mixed $values, mixed $rawValues = null, mixed $layouts = null): array
	{
		$nodes = [];
		$contextFields = is_array($layouts) ? [] : null;
		foreach (self::rowContexts($values, $rawValues, $contextFields) as $row) {
			$layout = self::textValue($row['values']['acf_fc_layout'] ?? null);
			if ($layout === '')
			{continue;}

			$subFields = self::layoutSubFields($layouts, $layout);
			$nodes[] = [
				'layout' => $layout,
				'values' => $row['values'],
				'rawValues' => $subFields === null ? null : ($row['rawValues'] ?? []),
				'subFields' => $subFields,
			];
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
		return self::boolRules()->NormalizeBool($this->GetRaw($fieldName, $source));
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

	/** @return array{values: array<mixed>, rawValues: null|array<mixed>, subFields: null|array<mixed>} */
	private static function nested(
		string $fieldName,
		\WP_Post|PostData|int|string|null $postId = null,
	): array {
		$values = self::asArray(self::field($fieldName, $postId));
		if (! function_exists('get_field_object')) {
			return ['values' => $values, 'rawValues' => null, 'subFields' => null];
		}

		$field = AcfFieldDefinition::From(
			\get_field_object($fieldName, self::postId($postId), false, true),
		);
		if ($field === null || ! $field->IsType('group')) {
			return ['values' => $values, 'rawValues' => null, 'subFields' => null];
		}

		$rawValues = $field->Value();
		return [
			'values' => $values,
			'rawValues' => is_array($rawValues) ? $rawValues : [],
			'subFields' => $field->SubFields() ?? [],
		];
	}

	private static function iconFieldValue(
		string $fieldName,
		\WP_Post|PostData|int|string|array|null $source,
	): mixed {
		if (is_array($source)) {
			return self::valueFrom($fieldName, $source);
		}

		$field = AcfFieldDefinition::From(
			\get_field_object($fieldName, self::postId($source), false, true),
		);
		return $field !== null && $field->IsType('icon_picker')
			? $field->Value()
			: null;
	}

	/** @return null|array{type: string, value: mixed} */
	private static function iconValue(mixed $value): ?array
	{
		if (is_array($value)) {
			$type = $value['type'] ?? null;
			return is_string($type) && array_key_exists('value', $value)
				? ['type' => $type, 'value' => $value['value']]
				: null;
		}

		if (! is_string($value)) {
			return null;
		}

		if (preg_match('/^dashicons-[a-z0-9-]+$/D', $value) === 1) {
			return ['type' => 'dashicons', 'value' => $value];
		}

		return UrlCheck::IsHttpUrl($value)
			? ['type' => 'url', 'value' => $value]
			: null;
	}

	/** @return list<array{values: array<mixed>, rawValues: null|array<mixed>, subFields: null|array<mixed>}> */
	private static function rowContexts(mixed $values, mixed $rawValues = null, mixed $subFields = null): array
	{
		if (! is_array($values))
		{return [];}

		$hasContext = is_array($subFields);
		$rawRows = is_array($rawValues) ? $rawValues : [];
		$rows = [];
		foreach ($values as $key => $row) {
			if (! is_array($row))
			{continue;}

			$rawRow = $rawRows[$key] ?? null;
			$rows[] = [
				'values' => $row,
				'rawValues' => $hasContext ? (is_array($rawRow) ? $rawRow : []) : null,
				'subFields' => $hasContext ? $subFields : null,
			];
		}
		return $rows;
	}

	/** @return null|array<mixed> */
	private static function layoutSubFields(mixed $layouts, string $layoutName): ?array
	{
		if (! is_array($layouts))
		{return null;}
		foreach ($layouts as $layout) {
			$definition = AcfFieldDefinition::From($layout);
			if ($definition === null || $definition->Name() !== $layoutName)
			{continue;}

			return $definition->SubFields() ?? [];
		}
		return null;
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
		$field = AcfFieldDefinition::From(
			\get_field_object($fieldName, self::postId($postId), false, true),
		);
		if ($field === null || ! $field->IsType($expectedType)) {
			return null;
		}

		return $field->Value();
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

	private static function numericRules(): ValueRules
	{
		static $rules;
		return $rules ??= ValueRules::FromMode(ValueMode::NumericStr);
	}

	private static function boolRules(): ValueRules
	{
		static $rules;
		return $rules ??= ValueRules::FromModes(
			ValueMode::BoolStr,
			ValueMode::BoolInt,
		);
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
		$field = AcfFieldDefinition::From(\get_field_object($name, self::postId($postId)));
		if ($field === null || ! $field->IsType($type) || $field->ReturnFormat() !== $format) {
			return null;
		}
		return $field->Value();
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
		$field = AcfFieldDefinition::From(\get_field_object($name, self::postId($source)));
		if ($field === null || ! $field->IsType('select')
			|| $field->ReturnFormat() !== $format
			|| $field->IsMultiple() !== $multiple) {
			return null;
		}
		return $field->Value();
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

}
