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
use WpTypeSafety\Wp\Data\PostProps;
use WpTypeSafety\Wp\Data\TermData;
use WpTypeSafety\Wp\Data\TermProps;
use WpTypeSafety\Wp\Data\UserProps;
use WpTypeSafety\Wp\Data\WpUserData;

class ProjectAcfPostData extends PostData
{
	protected static function getProps(WP_Post $post): PostProps
	{
		return new PostProps($post->ID, '/post/' . $post->ID, '投稿' . $post->ID, '');
	}
}
class ThrowingAcfPostData extends ProjectAcfPostData
{
	protected function initialize(WP_Post $post): void { throw $GLOBALS['acfPostError']; }
}
class ProjectAcfTermData extends TermData
{
	protected static function getProps(WP_Term $term): ?TermProps
	{
		return new TermProps($term->term_id, $term->name, $term->slug, $term->taxonomy, '/term/' . $term->slug);
	}
}
class ProjectAcfUserData extends WpUserData
{
	protected static function getProps(WP_User $user, int $avatarSize = 96): ?UserProps
	{
		$GLOBALS['factoryAvatarSizes'][] = $avatarSize;
		return new UserProps($user->ID, $user->display_name, $avatarSize);
	}
}

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

$termFactory = ProjectAcfTermData::class;
$userFactory = ProjectAcfUserData::class;
$factoryAvatarSizes = [];

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};

$check($acfGet->Post('post', ProjectAcfPostData::class) instanceof ProjectAcfPostData, '単一投稿で派生Dataを返す');
$check($acfGet->Posts('posts', ProjectAcfPostData::class)[1] instanceof ProjectAcfPostData, '複数投稿で派生Dataを返す');
$check($acfGet->Term('term', $termFactory) instanceof ProjectAcfTermData, '単一タームで派生Dataを返す');
$check($acfGet->Terms('terms', $termFactory)[1] instanceof ProjectAcfTermData, '複数タームで派生Dataを返す');
$check($acfGet->User('user', avatarSize: 160, dataClass: $userFactory) instanceof ProjectAcfUserData, '単一ユーザーで派生Dataを返す');
$check($acfGet->Users('users', avatarSize: 180, dataClass: $userFactory)[1] instanceof ProjectAcfUserData, '複数ユーザーで派生Dataを返す');
$check($factoryAvatarSizes === [160, 180, 180], 'ユーザーFactoryへアバター寸法を渡す');

$missing = $acfGet->Post('missing', ThrowingAcfPostData::class);
$check($missing === null, '未選択の単一参照ではinitializeを呼ばない');
$check($acfGet->Posts('missing', ThrowingAcfPostData::class) === [], '未選択の複数参照は空配列');

foreach (['Post', 'Posts'] as $method) {
	foreach (['', 'MissingAcfPostClass', stdClass::class] as $badClass) {
		foreach (['post', 'posts', 'missing'] as $field) {
			try {
				$acfGet->$method($field, dataClass: $badClass);
				throw new RuntimeException('不正なACF dataClassを受け入れた');
			} catch (InvalidArgumentException) {
				$checks++;
			}
		}
	}
}
$GLOBALS['acfPostError'] = new LogicException('ACF initialize');
foreach (['Post' => 'post', 'Posts' => 'posts'] as $method => $field) {
	try {
		$acfGet->$method($field, ThrowingAcfPostData::class);
		throw new RuntimeException('initialize例外を握りつぶした');
	} catch (LogicException $error) {
		$check($error === $GLOBALS['acfPostError'], 'ACF投稿生成の例外をそのまま伝播');
	}
}

foreach ([
	static fn () => $acfGet->Term('term', stdClass::class),
	static fn () => $acfGet->Terms('terms', stdClass::class),
	static fn () => $acfGet->User('user', dataClass: stdClass::class),
	static fn () => $acfGet->Users('users', dataClass: stdClass::class),
] as $invalidFactory) {
	try {
		$invalidFactory();
		throw new RuntimeException('ACF参照のFactoryがnullを返せた');
	} catch (InvalidArgumentException) {
		$check(true, 'ACF参照のFactoryは対応するDataを必ず返す');
	}
}

class ExcludedAcfTerm extends ProjectAcfTermData
{
	protected static function getProps(WP_Term $term): ?TermProps { return null; }
}
class ExcludedAcfUser extends ProjectAcfUserData
{
	protected static function getProps(WP_User $user, int $avatarSize = 96): ?UserProps { return null; }
}
$check($acfGet->Term('term', ExcludedAcfTerm::class) === null && $acfGet->Terms('terms', ExcludedAcfTerm::class) === [], 'ACF Termのnull生成を単数・複数で処理');
$check($acfGet->User('user', dataClass: ExcludedAcfUser::class) === null && $acfGet->Users('users', dataClass: ExcludedAcfUser::class) === [], 'ACF Userのnull生成を単数・複数で処理');

echo "ACF derived references: {$checks} checks passed.\n";
