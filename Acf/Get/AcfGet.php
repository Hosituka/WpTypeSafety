<?php
declare(strict_types=1);

namespace WpTypeSafety\Acf\Get;

use WpTypeSafety\Acf\Data\FlexibleNode;
use WpTypeSafety\Acf\Internal\AcfAdapter;
use WpTypeSafety\Core\Data\ChoiceData;
use WpTypeSafety\Core\Data\FileData;
use WpTypeSafety\Core\Data\ImgData;
use WpTypeSafety\Core\Data\LinkData;
use WpTypeSafety\Core\Data\TimeData;
use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\TermData;
use WpTypeSafety\Wp\Data\WpUserData;

/** 投稿・ACF参照先ID・取得済み配列からフィールドを取得する。 */
class AcfGet
{
	/** @var \WP_Post|PostData|int|string|array<mixed>|null */
	private \WP_Post|PostData|int|string|array|null $source;

	private AcfAdapter $adapter;

	private bool $hasNestedContext = false;

	/** @var array<mixed> */
	private array $nestedRawValues = [];

	/** @var array<mixed> */
	private array $nestedSubFields = [];

	/**
	 * @param \WP_Post|PostData|\WP_User|WpUserData|\WP_Term|TermData|int|string|array<mixed>|null $source
	 */
	public function __construct(
		\WP_Post|PostData|\WP_User|WpUserData|\WP_Term|TermData|int|string|array|null $source = null,
		?AcfAdapter $adapter = null,
	) {
		$this->adapter = $adapter ?? new AcfAdapter();
		$this->source = $this->normalizeSource($source);
	}

	/** WpTypeSafetyで型変換していないACFネイティブ値を返す。 */
	public function Native(string $fieldName): mixed
	{
		return $this->adapter->GetRaw($fieldName, $this->source);
	}

	/**
	 * 派生型と注入済みのAdapterを維持した子取得口を作る。
	 *
	 * @param \WP_Post|PostData|\WP_User|WpUserData|\WP_Term|TermData|int|string|array<mixed>|null $source
	 * @param null|array<mixed> $rawValues
	 * @param null|array<mixed> $subFields
	 */
	protected function createChild(
		\WP_Post|PostData|\WP_User|WpUserData|\WP_Term|TermData|int|string|array|null $source,
		?array $rawValues = null,
		?array $subFields = null,
	): static {
		$child = clone $this;
		$child->source = $child->normalizeSource($source);
		$child->hasNestedContext = $rawValues !== null && $subFields !== null;
		$child->nestedRawValues = $rawValues ?? [];
		$child->nestedSubFields = $subFields ?? [];
		return $child;
	}

	/**
	 * @param list<PostData|WpUserData|TermData> $sources
	 * @return list<static>
	 */
	protected function createChildren(array $sources): array
	{
		$children = [];
		foreach ($sources as $source) {
			$children[] = $this->createChild($source);
		}
		return $children;
	}

	/** @return array<mixed> */
	public function Array(string $fieldName): array
	{
		return $this->adapter->GetArray($fieldName, $this->source);
	}

	/** グループ内のフィールドを取得する。未設定・配列以外は空の取得元になる。 */
	public function Nested(string $fieldName): static
	{
		$data = $this->hasNestedContext
			? $this->nestedData($fieldName)
			: $this->adapter->GetNested($fieldName, $this->source);

		return $this->createChild(
			source: $data['values'],
			rawValues: $data['rawValues'],
			subFields: $data['subFields'],
		);
	}

	public function Text(string $fieldName): string
	{
		return $this->adapter->GetText($fieldName, $this->source);
	}

	public function Post(string $fieldName): ?PostData
	{
		return $this->adapter->GetPost($fieldName, $this->source);
	}

	/** 単一選択の投稿を参照先として、そのACFフィールドを取得する。 */
	public function PostGet(string $fieldName): static
	{
		return $this->createChild($this->Post($fieldName) ?? []);
	}

	/** @return list<PostData> */
	public function Posts(string $fieldName): array
	{
		return $this->adapter->GetPosts($fieldName, $this->source);
	}

	/** @return list<static> */
	public function PostGets(string $fieldName): array
	{
		return $this->createChildren($this->Posts($fieldName));
	}

	public function Term(string $fieldName): ?TermData
	{
		return $this->adapter->GetTerm($fieldName, $this->source);
	}

	/** @return list<TermData> */
	public function Terms(string $fieldName): array
	{
		return $this->adapter->GetTerms($fieldName, $this->source);
	}

	/** 単一選択のタームを参照先として、そのACFフィールドを取得する。 */
	public function TermGet(string $fieldName): static
	{
		return $this->createChild($this->Term($fieldName) ?? []);
	}

	/** @return list<static> */
	public function TermGets(string $fieldName): array
	{
		return $this->createChildren($this->Terms($fieldName));
	}

	public function User(string $fieldName, int $avatarSize = 96): ?WpUserData
	{
		return $this->adapter->GetUser($fieldName, $this->source, $avatarSize);
	}

	/** @return list<WpUserData> */
	public function Users(string $fieldName, int $avatarSize = 96): array
	{
		return $this->adapter->GetUsers($fieldName, $this->source, $avatarSize);
	}

	/** 単一選択のユーザーを参照先として、そのACFフィールドを取得する。 */
	public function UserGet(string $fieldName): static
	{
		return $this->createChild($this->User($fieldName) ?? []);
	}

	/** @return list<static> */
	public function UserGets(string $fieldName): array
	{
		return $this->createChildren($this->Users($fieldName));
	}

	/** HTTP(S)の絶対URLを返す。未入力・不正値はnull。エスケープは描画側で行う。 */
	public function Url(string $fieldName): ?string
	{
		return $this->adapter->GetUrl($fieldName, $this->source);
	}

	public function Link(string $fieldName): ?LinkData
	{
		return $this->adapter->GetLink($fieldName, $this->source);
	}

	public function Float(string $fieldName): ?float
	{
		return $this->adapter->GetFloat($fieldName, $this->source);
	}

	public function Int(string $fieldName): ?int
	{
		return $this->adapter->GetInt($fieldName, $this->source);
	}

	public function Img(string $fieldName, string|array $size = 'full'): ?ImgData
	{
		return $this->adapter->GetImg($fieldName, $this->source, $size);
	}

	public function ImgUrl(string $fieldName): ?string
	{
		return $this->adapter->GetImgUrl($fieldName, $this->source);
	}

	/** Icon PickerのDashiconクラス名を返す。 */
	public function IconClass(string $fieldName): ?string
	{
		if ($this->hasNestedContext) {
			$field = $this->nestedFieldOfType($fieldName, 'icon_picker');
			return $field === null ? null : $this->adapter->ConvertIconClass($this->nestedRawValue($field));
		}

		return $this->adapter->GetIconClass($fieldName, $this->source);
	}

	/** Icon Pickerのメディアライブラリ画像を返す。 */
	public function IconImg(string $fieldName, string|array $size = 'full'): ?ImgData
	{
		if ($this->hasNestedContext) {
			$field = $this->nestedFieldOfType($fieldName, 'icon_picker');
			return $field === null
				? null
				: $this->adapter->ConvertIconImg($this->nestedRawValue($field), $size);
		}

		return $this->adapter->GetIconImg($fieldName, $this->source, $size);
	}

	/** Icon PickerのURLを返す。 */
	public function IconUrl(string $fieldName): ?string
	{
		if ($this->hasNestedContext) {
			$field = $this->nestedFieldOfType($fieldName, 'icon_picker');
			return $field === null ? null : $this->adapter->ConvertIconUrl($this->nestedRawValue($field));
		}

		return $this->adapter->GetIconUrl($fieldName, $this->source);
	}

	public function File(string $fieldName): ?FileData
	{
		return $this->adapter->GetFile($fieldName, $this->source);
	}

	public function FileUrl(string $fieldName): ?string
	{
		return $this->adapter->GetFileUrl($fieldName, $this->source);
	}

	/** @return list<ImgData> */
	public function Gallery(string $fieldName, string|array $size = 'full'): array
	{
		return $this->adapter->GetGallery($fieldName, $this->source, $size);
	}

	/** @return list<string> */
	public function GalleryUrl(string $fieldName): array
	{
		return $this->adapter->GetGalleryUrl($fieldName, $this->source);
	}

	/** @return list<static> */
	public function Repeater(string $fieldName): array
	{
		$rows = [];
		$data = $this->hasNestedContext
			? $this->repeaterData($fieldName)
			: $this->adapter->GetRepeater($fieldName, $this->source);
		foreach ($data as $row) {
			$rows[] = $this->createChild(
				source: $row['values'],
				rawValues: $row['rawValues'],
				subFields: $row['subFields'],
			);
		}
		return $rows;
	}

	/** @return list<FlexibleNode<static>> */
	public function Flexible(string $fieldName): array
	{
		$nodes = [];
		$data = $this->hasNestedContext
			? $this->flexibleData($fieldName)
			: $this->adapter->GetFlexible($fieldName, $this->source);
		foreach ($data as $node) {
			$nodes[] = new FlexibleNode(
				Layout: $node['layout'],
				Get: $this->createChild(
					source: $node['values'],
					rawValues: $node['rawValues'],
					subFields: $node['subFields'],
				),
			);
		}
		return $nodes;
	}

	/** @return list<ChoiceData> */
	public function Checkbox(string $fieldName): array
	{
		return $this->adapter->GetCheckbox($fieldName, $this->source);
	}

	public function Radio(string $fieldName): ?ChoiceData
	{
		return $this->adapter->GetRadio($fieldName, $this->source);
	}

	public function RadioValue(string $fieldName): ?string
	{
		return $this->adapter->GetRadioValue($fieldName, $this->source);
	}

	public function RadioLabel(string $fieldName): ?string
	{
		return $this->adapter->GetRadioLabel($fieldName, $this->source);
	}

	public function ButtonGroup(string $fieldName): ?ChoiceData
	{
		return $this->adapter->GetButtonGroup($fieldName, $this->source);
	}

	public function ButtonGroupValue(string $fieldName): ?string
	{
		return $this->adapter->GetButtonGroupValue($fieldName, $this->source);
	}

	public function ButtonGroupLabel(string $fieldName): ?string
	{
		return $this->adapter->GetButtonGroupLabel($fieldName, $this->source);
	}

	/** @return list<string> */
	public function CheckboxValues(string $fieldName): array
	{
		return $this->adapter->GetCheckboxValues($fieldName, $this->source);
	}

	/** @return list<string> */
	public function CheckboxLabels(string $fieldName): array
	{
		return $this->adapter->GetCheckboxLabels($fieldName, $this->source);
	}

	public function Select(string $fieldName): ?ChoiceData
	{
		return $this->adapter->GetSelect($fieldName, $this->source);
	}

	public function SelectValue(string $fieldName): ?string
	{
		return $this->adapter->GetSelectValue($fieldName, $this->source);
	}

	public function SelectLabel(string $fieldName): ?string
	{
		return $this->adapter->GetSelectLabel($fieldName, $this->source);
	}

	/** @return list<string> */
	public function SelectValues(string $fieldName): array
	{
		return $this->adapter->GetSelectValues($fieldName, $this->source);
	}

	/** @return list<string> */
	public function SelectLabels(string $fieldName): array
	{
		return $this->adapter->GetSelectLabels($fieldName, $this->source);
	}

	/** @return list<ChoiceData> */
	public function SelectChoices(string $fieldName): array
	{
		return $this->adapter->GetSelectChoices($fieldName, $this->source);
	}

	public function Bool(string $fieldName): ?bool
	{
		return $this->adapter->GetBool($fieldName, $this->source);
	}

	/** Date Pickerをサイトタイムゾーンの00:00:00として返す。 */
	public function Date(string $fieldName): ?\DateTimeImmutable
	{
		if ($this->hasNestedContext) {
			$field = $this->nestedFieldOfType($fieldName, 'date_picker');
			return $field === null ? null : $this->adapter->ConvertDate($this->nestedRawValue($field));
		}

		return $this->adapter->GetDate($fieldName, $this->source);
	}

	/** Date Time Pickerをサイトタイムゾーンの日時として返す。 */
	public function DateTime(string $fieldName): ?\DateTimeImmutable
	{
		if ($this->hasNestedContext) {
			$field = $this->nestedFieldOfType($fieldName, 'date_time_picker');
			return $field === null ? null : $this->adapter->ConvertDateTime($this->nestedRawValue($field));
		}

		return $this->adapter->GetDateTime($fieldName, $this->source);
	}

	/** Time Pickerを日付・タイムゾーンを含まない時刻として返す。 */
	public function Time(string $fieldName): ?TimeData
	{
		if ($this->hasNestedContext) {
			$field = $this->nestedFieldOfType($fieldName, 'time_picker');
			return $field === null ? null : $this->adapter->ConvertTime($this->nestedRawValue($field));
		}

		return $this->adapter->GetTime($fieldName, $this->source);
	}

	/** @return array{values: array<mixed>, rawValues: null|array<mixed>, subFields: null|array<mixed>} */
	private function nestedData(string $fieldName): array
	{
		$values = $this->Array($fieldName);
		$field = $this->nestedField($fieldName);
		if ($field === null || ($field['type'] ?? null) !== 'group') {
			return ['values' => $values, 'rawValues' => null, 'subFields' => null];
		}

		$rawValues = $this->nestedRawValue($field);
		$subFields = $field['sub_fields'] ?? null;
		return [
			'values' => $values,
			'rawValues' => is_array($rawValues) ? $rawValues : [],
			'subFields' => is_array($subFields) ? $subFields : [],
		];
	}

	/** @return list<array{values: array<mixed>, rawValues: null|array<mixed>, subFields: null|array<mixed>}> */
	private function repeaterData(string $fieldName): array
	{
		$values = $this->Array($fieldName);
		$field = $this->nestedField($fieldName);
		if ($field === null || ($field['type'] ?? null) !== 'repeater')
		{return $this->adapter->ConvertRepeater($values);}

		return $this->adapter->ConvertRepeater(
			values: $values,
			rawValues: $this->nestedRawValue($field),
			subFields: $field['sub_fields'] ?? null,
		);
	}

	/** @return list<array{layout: string, values: array<mixed>, rawValues: null|array<mixed>, subFields: null|array<mixed>}> */
	private function flexibleData(string $fieldName): array
	{
		$values = $this->Array($fieldName);
		$field = $this->nestedField($fieldName);
		if ($field === null || ($field['type'] ?? null) !== 'flexible_content')
		{return $this->adapter->ConvertFlexible($values);}

		return $this->adapter->ConvertFlexible(
			values: $values,
			rawValues: $this->nestedRawValue($field),
			layouts: $field['layouts'] ?? null,
		);
	}

	/** @return null|array<mixed> */
	private function nestedFieldOfType(string $fieldName, string $expectedType): ?array
	{
		$field = $this->nestedField($fieldName);
		return $field !== null && ($field['type'] ?? null) === $expectedType
			? $field
			: null;
	}

	/** @return null|array<mixed> */
	private function nestedField(string $fieldName): ?array
	{
		foreach ($this->nestedSubFields as $field) {
			if (! is_array($field))
			{continue;}

			$name = $field['_name'] ?? $field['name'] ?? null;
			if ($name === $fieldName) {
				return $field;
			}
		}

		return null;
	}

	/** @param array<mixed> $field */
	private function nestedRawValue(array $field): mixed
	{
		foreach ([$field['key'] ?? null, $field['_name'] ?? null, $field['name'] ?? null] as $key) {
			if (is_string($key) && array_key_exists($key, $this->nestedRawValues)) {
				return $this->nestedRawValues[$key];
			}
		}

		return null;
	}

	/**
	 * @param \WP_Post|PostData|\WP_User|WpUserData|\WP_Term|TermData|int|string|array<mixed>|null $source
	 * @return \WP_Post|PostData|int|string|array<mixed>|null
	 */
	private function normalizeSource(
		\WP_Post|PostData|\WP_User|WpUserData|\WP_Term|TermData|int|string|array|null $source,
	): \WP_Post|PostData|int|string|array|null {
		if ($source instanceof \WP_User || $source instanceof WpUserData)
		{return $this->adapter->UserPostId($source);}

		if ($source instanceof \WP_Term || $source instanceof TermData)
		{return $this->adapter->TermPostId($source);}

		return $source;
	}
}
