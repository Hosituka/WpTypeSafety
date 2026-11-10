<?php
declare(strict_types=1);

namespace WpTypeSafety\Core\Internal;

/** @internal 配列キーとpublicインスタンスプロパティを同じ存在契約で解決します。 */
final class ValueResolver
{
	private function __construct()
	{
	}

	public static function Resolve(mixed $source, int|string $segment): ResolvedValue
	{
		if (is_array($source)) {
			return self::ResolveArray($source, $segment);
		}

		if (is_object($source) && is_string($segment)) {
			return self::ResolveObject($source, $segment);
		}

		return self::missing();
	}

	/** @param array<array-key, mixed> $source */
	public static function ResolveArray(array $source, int|string $key): ResolvedValue
	{
		if (! array_key_exists($key, $source)) {
			return self::missing();
		}

		return new ResolvedValue(
			Exists: true,
			Value: $source[$key],
		);
	}

	public static function ResolveObject(?object $source, string $propertyName): ResolvedValue
	{
		if ($source === null) {
			return self::missing();
		}

		$reflection = new \ReflectionObject($source);
		if (! $reflection->hasProperty($propertyName)) {
			return self::missing();
		}

		$property = $reflection->getProperty($propertyName);
		if (! $property->isPublic() || $property->isStatic()) {
			return self::missing();
		}

		if (! $property->isInitialized($source)) {
			return self::missing();
		}

		if (
			method_exists($property, 'isVirtual')
			&& $property->isVirtual()
			&& ! isset($property->getHooks()['get'])
		) {
			return self::missing();
		}

		return new ResolvedValue(
			Exists: true,
			Value: $property->getValue($source),
		);
	}

	private static function missing(): ResolvedValue
	{
		return new ResolvedValue(
			Exists: false,
			Value: null,
		);
	}
}
