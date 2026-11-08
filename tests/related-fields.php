<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
	exit;
}

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
		'group' => ['author' => 7, 'category' => 5, 'featured_post' => 3],
		'multiple_users' => [7, 8],
		'multiple_posts' => [3, 'invalid', 4],
		'multiple_terms' => [5, 'invalid', 6],
	],
	'3' => ['label' => 'Featured'],
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
$check($projectGet->PostFields('featured_post') instanceof ProjectAcfGet, 'PostFields preserves derived type');
$check($projectGet->UserFields('author') instanceof ProjectAcfGet, 'UserFields preserves derived type');
$check($projectGet->TermFields('category') instanceof ProjectAcfGet, 'TermFields preserves derived type');
$check($post->PostFields('featured_post')->Text('label') === 'Featured', 'post to post fields');
$check($post->UserFields('author')->Text('label') === 'Alice', 'post to user fields');
$check($post->TermFields('category')->Text('label') === 'Blue', 'post to term fields');
$check($post->Nested('group')->PostFields('featured_post')->Text('label') === 'Featured', 'group to post fields');
$check($post->Nested('group')->UserFields('author')->Text('label') === 'Alice', 'group to user fields');
$check($post->Nested('group')->TermFields('category')->Text('label') === 'Blue', 'group to term fields');
$check($post->UserFields('author')->TermFields('category')->Text('label') === 'Blue', 'user to term fields');
$check($post->TermFields('category')->UserFields('author')->Text('label') === 'Alice', 'term to user fields');
$check($post->UserFields('author')->UserFields('friend')->Text('label') === 'Bob', 'user to user fields');
$check($post->TermFields('category')->TermFields('parent')->Text('label') === 'Parent', 'term to term fields');
$check($post->UserFields('missing')->Text('label') === '', 'missing user has empty fields');
$check($post->TermFields('missing')->Text('label') === '', 'missing term has empty fields');
$check($post->PostFields('missing')->Text('label') === '', 'missing post has empty fields');
$check($post->UserFields('multiple_users')->Text('label') === '', 'multiple users are not treated as one');
$check((new AcfGet([], $adapter))->UserFields('missing')->Text('label') === '', 'array source missing user');
$check((new AcfGet([], $adapter))->TermFields('missing')->Text('label') === '', 'array source missing term');
$check(array_map(static fn (PostData $item): int => $item->PostId, $post->Posts('multiple_posts')) === [3, 4], 'multiple posts');
$check(array_map(static fn (TermData $item): int => $item->Id, $post->Terms('multiple_terms')) === [5, 6], 'multiple terms');
$check(array_map(static fn (WpUserData $item): int => $item->Id, $post->Users('multiple_users', 160)) === [7, 8], 'multiple users');
$check($avatarSizes[array_key_last($avatarSizes)] === 160, 'multiple user avatar size');
$check($post->Posts('missing') === [], 'missing multiple posts');
echo "Related fields: {$checks} checks passed.\n";
