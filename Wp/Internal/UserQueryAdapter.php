<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Internal;

use WpTypeSafety\Wp\Data\UserQueryData;

/** @internal WP_User_QueryをUserQueryDataへ変換します。 */
final class UserQueryAdapter
{
	private function __construct() {}

	/** @param array<string, mixed> $args */
	public static function Get(array $args, int $avatarSize): UserQueryData
	{
		$query = new \WP_User_Query();
		$query->prepare_query($args);
		$query->query();

		return self::From($query, $avatarSize);
	}

	public static function From(\WP_User_Query $query, int $avatarSize): UserQueryData
	{
		$fields = $query->get('fields');
		if ($fields !== 'all' && $fields !== 'all_with_meta') {
			throw new \InvalidArgumentException(
				'UserQueryはWP_Userを必要とするため、fieldsによる部分取得には対応していません。',
			);
		}

		$users = [];
		foreach ($query->get_results() as $user) {
			if (! $user instanceof \WP_User) {
				throw new \UnexpectedValueException('WP_User_Queryの結果にWP_User以外の値が含まれています。');
			}
			$data = DtoFactory::UserFromWpUser($user, $avatarSize);
			if ($data === null) {
				continue;
			}
			$users[] = $data;
		}

		$hasTotal = (bool) $query->get('count_total');
		$totalCount = $hasTotal ? max(0, (int) $query->get_total()) : null;

		return new UserQueryData(
			Users: $users,
			TotalCount: $totalCount,
			MaxPageCount: $hasTotal ? self::maxPageCount($totalCount ?? 0, $query->get('number')) : null,
			CurrentPageNumber: self::pageNumber($query->get('paged')),
		);
	}

	private static function maxPageCount(int $totalCount, mixed $number): int
	{
		if (! is_numeric($number) || (int) $number <= 0) {
			return $totalCount > 0 ? 1 : 0;
		}

		return (int) ceil($totalCount / (int) $number);
	}

	private static function pageNumber(mixed $value): int
	{
		return is_numeric($value) && (int) $value > 0 ? (int) $value : 1;
	}
}
