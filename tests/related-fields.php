<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
	exit;
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

require_once __DIR__ . '/../Wp/Data/TermData.php';
require_once __DIR__ . '/../Wp/Data/TermMembers.php';
require_once __DIR__ . '/../Wp/Data/WpUserData.php';
require_once __DIR__ . '/../Wp/Data/UserMembers.php';
require_once __DIR__ . '/../Wp/Data/PostData.php';
require_once __DIR__ . '/../Wp/Data/PostMembers.php';
require_once __DIR__ . '/../Acf/Get/AcfGet.php';
require_once __DIR__ . '/../Acf/Internal/AcfAdapter.php';

final class ProjectAcfGet extends AcfGet {}

final class ProjectAcfAdapter extends AcfAdapter
{
	/** @param array<string, array<string, mixed>> $values */
	public function __construct(
		private array $values,
		private \Closure $avatarSizeRecorder,
	) {}

	public function GetRaw(string $fieldName, \WP_Post|PostData|int|string|array|null $source): mixed
	{
		if ($source instanceof PostData)
		{$source = $source->PostId;}

		$fields = is_array($source) ? $source : ($this->values[(string) $source] ?? []);
		return $fields[$fieldName] ?? null;
	}

	/** @return array<mixed> */
	public function GetArray(string $fieldName, \WP_Post|PostData|int|string|array|null $source): array
	{
		$value = $this->GetRaw($fieldName, $source);
		return is_array($value) ? $value : [];
	}

	/** @return array{values: array<mixed>, rawValues: null, subFields: null} */
	public function GetNested(string $fieldName, \WP_Post|PostData|int|string|array|null $source): array
	{
		return [
			'values' => $this->GetArray($fieldName, $source),
			'rawValues' => null,
			'subFields' => null,
		];
	}

	public function GetText(string $fieldName, \WP_Post|PostData|int|string|array|null $source): string
	{
		$value = $this->GetRaw($fieldName, $source);
		return is_string($value) ? $value : '';
	}

	public function GetPost(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?PostData
	{
		$id = $this->GetRaw($fieldName, $source);
		return is_int($id) && $id > 0
			? new PostData(new PostMembers(PostId: $id, Url: '/post/' . $id, Title: 'Post ' . $id, Content: ''))
			: null;
	}

	/** @return list<PostData> */
	public function GetPosts(string $fieldName, \WP_Post|PostData|int|string|array|null $source): array
	{
		$result = [];
		foreach ($this->GetArray($fieldName, $source) as $id) {
			if (is_int($id) && $id > 0) {
				$result[] = new PostData(new PostMembers(PostId: $id, Url: '/post/' . $id, Title: 'Post ' . $id, Content: ''));
			}
		}
		return $result;
	}

	public function GetTerm(string $fieldName, \WP_Post|PostData|int|string|array|null $source): ?TermData
	{
		$id = $this->GetRaw($fieldName, $source);
		return is_int($id) && $id > 0
			? new TermData(new TermMembers($id, 'Term ' . $id, 'term-' . $id, 'category', '/term/' . $id))
			: null;
	}

	/** @return list<TermData> */
	public function GetTerms(string $fieldName, \WP_Post|PostData|int|string|array|null $source): array
	{
		$result = [];
		foreach ($this->GetArray($fieldName, $source) as $id) {
			if (is_int($id) && $id > 0) {
				$result[] = new TermData(new TermMembers($id, 'Term ' . $id, 'term-' . $id, 'category', '/term/' . $id));
			}
		}
		return $result;
	}

	public function GetUser(
		string $fieldName,
		\WP_Post|PostData|int|string|array|null $source,
		int $avatarSize,
	): ?WpUserData {
		($this->avatarSizeRecorder)($avatarSize);
		$id = $this->GetRaw($fieldName, $source);
		return is_int($id) && $id > 0 ? new WpUserData(new UserMembers($id, 'User ' . $id)) : null;
	}

	/** @return list<WpUserData> */
	public function GetUsers(
		string $fieldName,
		\WP_Post|PostData|int|string|array|null $source,
		int $avatarSize,
	): array {
		($this->avatarSizeRecorder)($avatarSize);
		$result = [];
		foreach ($this->GetArray($fieldName, $source) as $id) {
			if (is_int($id) && $id > 0) {
				$result[] = new WpUserData(new UserMembers($id, 'User ' . $id));
			}
		}
		return $result;
	}

	public function UserPostId(\WP_User|WpUserData $user): string
	{
		return 'user_' . ($user instanceof WpUserData ? $user->Id : $user->ID);
	}

	public function TermPostId(\WP_Term|TermData $term): string
	{
		return 'term_' . ($term instanceof TermData ? $term->Id : $term->term_id);
	}
}

$values = [
	'post' => [
		'author' => 7,
		'category' => 5,
		'featured_post' => 3,
		'group' => [
			'author' => 7,
			'category' => 5,
			'featured_post' => 3,
			'multiple_users' => [7, 8],
			'multiple_posts' => [3, 4],
			'multiple_terms' => [5, 6],
		],
		'multiple_users' => [7, 8],
		'multiple_posts' => [3, 'invalid', 4],
		'multiple_terms' => [5, 'invalid', 6],
	],
	'3' => ['label' => 'Featured'],
	'4' => ['label' => 'Secondary'],
	'user_7' => ['label' => 'Alice', 'category' => 5, 'friend' => 8],
	'user_8' => ['label' => 'Bob'],
	'term_5' => ['label' => 'Blue', 'author' => 7, 'parent' => 6],
	'term_6' => ['label' => 'Parent'],
];
$avatarSizes = [];
$adapter = new ProjectAcfAdapter(
	values: $values,
	avatarSizeRecorder: static function (int $avatarSize) use (&$avatarSizes): void {
		$avatarSizes[] = $avatarSize;
	},
);

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (!$ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};
$post = new AcfGet('post', $adapter);
$projectGet = new ProjectAcfGet('post', $adapter);
$check($projectGet->Native('author') === 7, 'subclass inherits Native accessor');
$check($projectGet->PostGet('featured_post') instanceof ProjectAcfGet, 'PostGet preserves derived type');
$check($projectGet->UserGet('author') instanceof ProjectAcfGet, 'UserGet preserves derived type');
$check($projectGet->TermGet('category') instanceof ProjectAcfGet, 'TermGet preserves derived type');
$check($post->PostGet('featured_post')->Text('label') === 'Featured', 'post to post get');
$check($post->UserGet('author')->Text('label') === 'Alice', 'post to user get');
$check($post->TermGet('category')->Text('label') === 'Blue', 'post to term get');
$check($post->Nested('group')->PostGet('featured_post')->Text('label') === 'Featured', 'group to post get');
$check($post->Nested('group')->UserGet('author')->Text('label') === 'Alice', 'group to user get');
$check($post->Nested('group')->TermGet('category')->Text('label') === 'Blue', 'group to term get');
$check($post->UserGet('author')->TermGet('category')->Text('label') === 'Blue', 'user to term get');
$check($post->TermGet('category')->UserGet('author')->Text('label') === 'Alice', 'term to user get');
$check($post->UserGet('author')->UserGet('friend')->Text('label') === 'Bob', 'user to user get');
$check($post->TermGet('category')->TermGet('parent')->Text('label') === 'Parent', 'term to term get');
$check($post->UserGet('missing')->Text('label') === '', 'missing user has empty get');
$check($post->TermGet('missing')->Text('label') === '', 'missing term has empty get');
$check($post->PostGet('missing')->Text('label') === '', 'missing post has empty get');
$check($post->UserGet('multiple_users')->Text('label') === '', 'multiple users are not treated as one');
$check((new AcfGet([], $adapter))->UserGet('missing')->Text('label') === '', 'array source missing user');
$check((new AcfGet([], $adapter))->TermGet('missing')->Text('label') === '', 'array source missing term');
$check(array_map(static fn (PostData $item): int => $item->PostId, $post->Posts('multiple_posts')) === [3, 4], 'multiple posts');
$check(array_map(static fn (TermData $item): int => $item->Id, $post->Terms('multiple_terms')) === [5, 6], 'multiple terms');
$check(array_map(static fn (WpUserData $item): int => $item->Id, $post->Users('multiple_users', 160)) === [7, 8], 'multiple users');
$check($avatarSizes[array_key_last($avatarSizes)] === 160, 'multiple user avatar size');
$check($post->Posts('missing') === [], 'missing multiple posts');
$check(
	array_map(static fn (AcfGet $get): string => $get->Text('label'), $post->PostGets('multiple_posts'))
		=== ['Featured', 'Secondary'],
	'複数投稿のACF取得口を返す',
);
$check(
	array_map(static fn (AcfGet $get): string => $get->Text('label'), $post->TermGets('multiple_terms'))
		=== ['Blue', 'Parent'],
	'複数タームのACF取得口を返す',
);
$check(
	array_map(static fn (AcfGet $get): string => $get->Text('label'), $post->UserGets('multiple_users'))
		=== ['Alice', 'Bob'],
	'複数ユーザーのACF取得口を返す',
);
$check(
	array_map(static fn (AcfGet $get): string => $get->Text('label'), $post->Nested('group')->PostGets('multiple_posts'))
		=== ['Featured', 'Secondary'],
	'Nested内でも複数投稿のACF取得口を返す',
);
$projectPostGets = $projectGet->PostGets('multiple_posts');
$projectTermGets = $projectGet->TermGets('multiple_terms');
$projectUserGets = $projectGet->UserGets('multiple_users');
$check(
	count($projectPostGets) === 2
		&& $projectPostGets[0] instanceof ProjectAcfGet
		&& $projectTermGets[0] instanceof ProjectAcfGet
		&& $projectUserGets[0] instanceof ProjectAcfGet,
	'複数参照先でも派生型を維持する',
);
$check($post->PostGets('missing') === [], '未選択の複数投稿は空配列');
$check($post->TermGets('missing') === [], '未選択の複数タームは空配列');
$check($post->UserGets('missing') === [], '未選択の複数ユーザーは空配列');
echo "Related fields: {$checks} checks passed.\n";
