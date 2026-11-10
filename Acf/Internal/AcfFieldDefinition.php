<?php
declare(strict_types=1);

namespace WpTypeSafety\Acf\Internal;

use WpTypeSafety\Core\Check\ArrayGet;
use WpTypeSafety\Core\Check\ValueMode;
use WpTypeSafety\Core\Check\ValueRules;

/** @internal ACFのフィールド定義配列を読み取ります。 */
final class AcfFieldDefinition
{
	/** @var array<array-key, mixed> */
	private readonly array $field;

	private readonly ArrayGet $fieldGet;

	/** @param array<array-key, mixed> $field */
	private function __construct(array $field)
	{
		$this->field = $field;
		$this->fieldGet = new ArrayGet(
			$field,
			self::fieldRules(),
		);
	}

	public static function From(mixed $field): ?self
	{
		return is_array($field) ? new self($field) : null;
	}

	public function IsType(string $expectedType): bool
	{
		return $this->fieldGet->String('type') === $expectedType;
	}

	public function ReturnFormat(): string
	{
		return $this->fieldGet->String('return_format') ?: 'value';
	}

	public function IsMultiple(): bool
	{
		return $this->fieldGet->Bool('multiple') ?? false;
	}

	public function Value(): mixed
	{
		return $this->field['value'] ?? null;
	}

	/** @return null|array<mixed> */
	public function SubFields(): ?array
	{
		$subFields = $this->field['sub_fields'] ?? null;
		return is_array($subFields) ? $subFields : null;
	}

	/** @return null|array<mixed> */
	public function Layouts(): ?array
	{
		$layouts = $this->field['layouts'] ?? null;
		return is_array($layouts) ? $layouts : null;
	}

	public function Name(): string
	{
		return $this->fieldGet->String('_name') ?: $this->fieldGet->String('name');
	}

	/** @return list<string> */
	public function RawValueKeys(): array
	{
		$keys = [];
		foreach (['key', '_name', 'name'] as $property) {
			$key = $this->fieldGet->String($property);
			if ($key === '' || in_array($key, $keys, true))
			{continue;}

			$keys[] = $key;
		}

		return $keys;
	}

	private static function fieldRules(): ValueRules
	{
		static $rules;
		return $rules ??= ValueRules::FromModes(ValueMode::BoolStr, ValueMode::BoolInt);
	}
}
