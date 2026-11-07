<?php
declare(strict_types=1);

namespace WpTypeSafety\Acf\Internal;

use WpTypeSafety\Acf\Get\AcfGet;
use WpTypeSafety\Core\Data\TimeData;

/** @internal ACFグループの整形済み値・未整形値・子フィールド定義を保持する。 */
final class AcfNestedGet extends AcfGet
{
	/**
	 * @param array<mixed> $values
	 * @param array<mixed> $rawValues
	 * @param array<mixed> $subFields
	 */
	public function __construct(
		array $values,
		private readonly array $rawValues,
		private readonly array $subFields,
	) {
		parent::__construct($values);
	}

	public function Nested(string $fieldName): AcfGet
	{
		$values = $this->Array($fieldName);
		$field = $this->field($fieldName);
		if ($field === null || ($field['type'] ?? null) !== 'group') {
			return new AcfGet($values);
		}

		$rawValues = $this->rawValue($field);
		$subFields = $field['sub_fields'] ?? null;
		return new self(
			values: $values,
			rawValues: is_array($rawValues) ? $rawValues : [],
			subFields: is_array($subFields) ? $subFields : [],
		);
	}

	public function Date(string $fieldName): ?\DateTimeImmutable
	{
		$field = $this->temporalField($fieldName, 'date_picker');
		return $field === null
			? null
			: TemporalValue::Date($this->rawValue($field));
	}

	public function DateTime(string $fieldName): ?\DateTimeImmutable
	{
		$field = $this->temporalField($fieldName, 'date_time_picker');
		return $field === null
			? null
			: TemporalValue::DateTime($this->rawValue($field));
	}

	public function Time(string $fieldName): ?TimeData
	{
		$field = $this->temporalField($fieldName, 'time_picker');
		return $field === null
			? null
			: TemporalValue::Time($this->rawValue($field));
	}

	/** @return null|array<mixed> */
	private function temporalField(string $fieldName, string $expectedType): ?array
	{
		$field = $this->field($fieldName);
		return $field !== null && ($field['type'] ?? null) === $expectedType
			? $field
			: null;
	}

	/** @return null|array<mixed> */
	private function field(string $fieldName): ?array
	{
		foreach ($this->subFields as $field) {
			if (! is_array($field)) {
				continue;
			}

			$name = $field['_name'] ?? $field['name'] ?? null;
			if ($name === $fieldName) {
				return $field;
			}
		}

		return null;
	}

	/** @param array<mixed> $field */
	private function rawValue(array $field): mixed
	{
		foreach ([$field['key'] ?? null, $field['_name'] ?? null, $field['name'] ?? null] as $key) {
			if (is_string($key) && array_key_exists($key, $this->rawValues)) {
				return $this->rawValues[$key];
			}
		}

		return null;
	}
}
