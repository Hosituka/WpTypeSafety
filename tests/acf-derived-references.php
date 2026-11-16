<?php
declare(strict_types=1);

// ACF参照フィールドが案件固有Dataを維持できることを検証する。DBは変更しない。
if (PHP_SAPI !== 'cli') {
	exit;
}

class WP_Post
{
	public function __construct(public int $ID) {}
}

class WP_Term
{
	public function __construct(
		public int $term_id,
		public string $name,
		public string $slug,
		public string $taxonomy = 'category',
	) {}
}

class WP_User
{
	public function __construct(
		public int $ID,
		public string $display_name,
	) {}
}

require_once __DIR__ . '/../autoload.php';

use WpTypeSafety\Acf\Get\AcfGet;
use WpTypeSafety\Acf\Internal\AcfAdapter;
use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\PostMembers;
use WpTypeSafety\Wp\Data\TermData;
use WpTypeSafety\Wp\Data\TermMembers;
use WpTypeSafety\Wp\Data\UserMembers;
use WpTypeSafety\Wp\Data\WpUserData;

readonly class ProjectAcfPostData extends PostData {}
readonly class ProjectAcfTermData extends TermData {}
readonly class ProjectAcfUserData extends WpUserData {}

final class DerivedReferenceAdapter extends AcfAdapter
{
	/** @param array<string, mixed> $values */
	public function __construct(private array $values) {}

	public function GetWpPost(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?\WP_Post
	{
		$value = $this->values[$fieldName] ?? null;
		return $value instanceof \WP_Post ? $value : null;
	}

	/** @return list<\WP_Post> */
	public function GetWpPosts(string $fieldName, \WP_Post|PostData|int|string|array|null $source): array
	{
		$value = $this->values[$fieldName] ?? null;
		return is_array($value)
			? array_values(array_filter($value, static fn (mixed $item): bool => $item instanceof \WP_Post))
			: [];
	}

	public function GetWpTerm(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?\WP_Term
	{
		$value = $this->values[$fieldName] ?? null;
		return $value instanceof \WP_Term ? $value : null;
	}

	/** @return list<\WP_Term> */
	public function GetWpTerms(string $fieldName, \WP_Post|PostData|int|string|array|null $source): array
	{
		$value = $this->values[$fieldName] ?? null;
		return is_array($value)
			? array_values(array_filter($value, static fn (mixed $item): bool => $item instanceof \WP_Term))
			: [];
	}

	public function GetWpUser(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?\WP_User
	{
		$value = $this->values[$fieldName] ?? null;
		return $value instanceof \WP_User ? $value : null;
	}

	/** @return list<\WP_User> */
	public function GetWpUsers(string $fieldName, \WP_Post|PostData|int|string|array|null $source): array
	{
		$value = $this->values[$fieldName] ?? null;
		return is_array($value)
			? array_values(array_filter($value, static fn (mixed $item): bool => $item instanceof \WP_User))
			: [];
	}
}

$adapter = new DerivedReferenceAdapter([
	'post' => new WP_Post(10),
	'posts' => [new WP_Post(11), 'invalid', new WP_Post(12)],
	'term' => new WP_Term(20, '地域', 'area', 'area'),
	'terms' => [new WP_Term(21, '横浜市', 'yokohama', 'area'), new WP_Term(22, '藤沢市', 'fujisawa', 'area')],
	'user' => new WP_User(30, '担当者'),
	'users' => [new WP_User(31, '担当者A'), new WP_User(32, '担当者B')],
]);
$acfGet = new AcfGet(1, $adapter);

$postFactory = static fn (WP_Post $post): ProjectAcfPostData => new ProjectAcfPostData(
	new PostMembers(
		Id: $post->ID,
		Url: '/post/' . $post->ID,
		Title: '投稿' . $post->ID,
		RawContent: '',
	),
);
$termFactory = static fn (WP_Term $term): ProjectAcfTermData => new ProjectAcfTermData(
	new TermMembers(
		Id: $term->term_id,
		Name: $term->name,
		Slug: $term->slug,
		Taxonomy: $term->taxonomy,
		Url: '/term/' . $term->slug,
	),
);
$factoryAvatarSizes = [];
$userFactory = static function (WP_User $user, int $avatarSize) use (&$factoryAvatarSizes): ProjectAcfUserData {
	$factoryAvatarSizes[] = $avatarSize;
	return new ProjectAcfUserData(
		new UserMembers(
			Id: $user->ID,
			DisplayName: $user->display_name,
			AvatarSize: $avatarSize,
		),
	);
};

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};

$check($acfGet->Post('post', $postFactory) instanceof ProjectAcfPostData, '単一投稿で派生Dataを返す');
$check($acfGet->Posts('posts', $postFactory)[1] instanceof ProjectAcfPostData, '複数投稿で派生Dataを返す');
$check($acfGet->Term('term', $termFactory) instanceof ProjectAcfTermData, '単一タームで派生Dataを返す');
$check($acfGet->Terms('terms', $termFactory)[1] instanceof ProjectAcfTermData, '複数タームで派生Dataを返す');
$check($acfGet->User('user', avatarSize: 160, userFactory: $userFactory) instanceof ProjectAcfUserData, '単一ユーザーで派生Dataを返す');
$check($acfGet->Users('users', avatarSize: 180, userFactory: $userFactory)[1] instanceof ProjectAcfUserData, '複数ユーザーで派生Dataを返す');
$check($factoryAvatarSizes === [160, 180, 180], 'ユーザーFactoryへアバター寸法を渡す');

$calls = 0;
$missing = $acfGet->Post('missing', static function (WP_Post $post) use (&$calls): ProjectAcfPostData {
	$calls++;
	throw new RuntimeException('欠損フィールドでFactoryが呼ばれた');
});
$check($missing === null && $calls === 0, '未選択の単一参照ではFactoryを呼ばない');
$check($acfGet->Posts('missing', $postFactory) === [], '未選択の複数参照は空配列');

foreach ([
	static fn () => $acfGet->Post('post', static fn (WP_Post $post): mixed => null),
	static fn () => $acfGet->Posts('posts', static fn (WP_Post $post): mixed => null),
	static fn () => $acfGet->Term('term', static fn (WP_Term $term): mixed => null),
	static fn () => $acfGet->Terms('terms', static fn (WP_Term $term): mixed => null),
	static fn () => $acfGet->User('user', userFactory: static fn (WP_User $user, int $avatarSize): mixed => null),
	static fn () => $acfGet->Users('users', userFactory: static fn (WP_User $user, int $avatarSize): mixed => null),
] as $invalidFactory) {
	try {
		$invalidFactory();
		throw new RuntimeException('ACF参照のFactoryがnullを返せた');
	} catch (UnexpectedValueException) {
		$check(true, 'ACF参照のFactoryは対応するDataを必ず返す');
	}
}

echo "ACF derived references: {$checks} checks passed.\n";
