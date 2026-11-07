<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
	exit;
}

use WpTypeSafety\Acf\Get\AcfGet;
use WpTypeSafety\Acf\Get\AcfGetTerm;
use WpTypeSafety\Acf\Get\AcfGetUser;
use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\TermData;
use WpTypeSafety\Wp\Data\WpUserData;

require_once __DIR__ . '/../Wp/Data/TermData.php';
require_once __DIR__ . '/../Wp/Data/WpUserData.php';
require_once __DIR__ . '/../Wp/Data/PostData.php';
require_once __DIR__ . '/../Acf/Get/AcfGet.php';
require_once __DIR__ . '/../Acf/Get/AcfGetTerm.php';
require_once __DIR__ . '/../Acf/Get/AcfGetUser.php';

final class ProjectAcfGet extends AcfGet
{
	public function RawValue(string $fieldName): mixed
	{
		return $this->raw($fieldName);
	}
}

$values = [
	'post' => [
		'author' => 7,
		'category' => 5,
		'group' => ['author' => 7, 'category' => 5],
		'multiple_users' => [7, 8],
		'multiple_posts' => [3, 'invalid', 4],
		'multiple_terms' => [5, 'invalid', 6],
	],
	'user_7' => ['label' => 'Alice', 'category' => 5, 'friend' => 8],
	'user_8' => ['label' => 'Bob'],
	'term_5' => ['label' => 'Blue', 'author' => 7, 'parent' => 6],
	'term_6' => ['label' => 'Parent'],
];
$read = static function (string $name, mixed $source) use (&$values): mixed {
	$fields = is_array($source) ? $source : ($values[$source] ?? []);
	return $fields[$name] ?? null;
};
$unused = static fn (): mixed => null;
$avatarSizes = [];
AcfGet::Register(
	raw: $read,
	array: static fn (string $name, mixed $source): array => is_array($read($name, $source)) ? $read($name, $source) : [],
	text: static fn (string $name, mixed $source): string => is_string($read($name, $source)) ? $read($name, $source) : '',
	post: $unused,
	posts: static function (string $name, mixed $source) use ($read): array {
		$ids = $read($name, $source);
		if (!is_array($ids)) {
			return [];
		}
		$result = [];
		foreach ($ids as $id) {
			if (is_int($id) && $id > 0) {
				$result[] = new PostData(PostId: $id, Url: '/post/' . $id, Title: 'Post ' . $id, Content: '');
			}
		}
		return $result;
	},
	term: static function (string $name, mixed $source) use ($read): ?TermData {
		$id = $read($name, $source);
		return is_int($id) && $id > 0
			? new TermData($id, 'Term ' . $id, 'term-' . $id, 'category', '/term/' . $id)
			: null;
	},
	terms: static function (string $name, mixed $source) use ($read): array {
		$ids = $read($name, $source);
		if (!is_array($ids)) {
			return [];
		}
		$result = [];
		foreach ($ids as $id) {
			if (is_int($id) && $id > 0) {
				$result[] = new TermData($id, 'Term ' . $id, 'term-' . $id, 'category', '/term/' . $id);
			}
		}
		return $result;
	},
	user: static function (string $name, mixed $source, int $avatarSize) use ($read, &$avatarSizes): ?WpUserData {
		$avatarSizes[] = $avatarSize;
		$id = $read($name, $source);
		return is_int($id) && $id > 0 ? new WpUserData($id, 'User ' . $id) : null;
	},
	users: static function (string $name, mixed $source, int $avatarSize) use ($read, &$avatarSizes): array {
		$avatarSizes[] = $avatarSize;
		$ids = $read($name, $source);
		if (!is_array($ids)) {
			return [];
		}
		$result = [];
		foreach ($ids as $id) {
			if (is_int($id) && $id > 0) {
				$result[] = new WpUserData($id, 'User ' . $id);
			}
		}
		return $result;
	},
	link: $unused,
	number: $unused,
	img: $unused,
	imgUrl: $unused,
	file: $unused,
	fileUrl: $unused,
	gallery: $unused,
	repeater: $unused,
	flexible: $unused,
	checkbox: $unused,
	radio: $unused,
	buttonGroup: $unused,
	bool: $unused,
	date: $unused,
	dateTime: $unused,
	time: $unused,
	radioValue: $unused,
	radioLabel: $unused,
	buttonGroupValue: $unused,
	buttonGroupLabel: $unused,
	checkboxValues: $unused,
	checkboxLabels: $unused,
	select: $unused,
	selectValue: $unused,
	selectLabel: $unused,
	selectValues: $unused,
	selectLabels: $unused,
	selectChoices: $unused,
	url: $unused,
	galleryUrl: $unused,
);
AcfGetUser::RegisterIdResolver(static fn (WpUserData $user): string => 'user_' . $user->Id);
AcfGetTerm::RegisterIdResolver(static fn (TermData $term): string => 'term_' . $term->Id);

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (!$ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};
$post = new AcfGet('post');
$check((new ProjectAcfGet('post'))->RawValue('author') === 7, 'subclass accesses raw field value');
$check($post->UserFields('author')->Text('label') === 'Alice', 'post to user fields');
$check($post->TermFields('category')->Text('label') === 'Blue', 'post to term fields');
$check($post->Nested('group')->UserFields('author')->Text('label') === 'Alice', 'group to user fields');
$check($post->Nested('group')->TermFields('category')->Text('label') === 'Blue', 'group to term fields');
$check($post->UserFields('author')->TermFields('category')->Text('label') === 'Blue', 'user to term fields');
$check($post->TermFields('category')->UserFields('author')->Text('label') === 'Alice', 'term to user fields');
$check($post->UserFields('author')->UserFields('friend')->Text('label') === 'Bob', 'user to user fields');
$check($post->TermFields('category')->TermFields('parent')->Text('label') === 'Parent', 'term to term fields');
$check($post->UserFields('missing')->Text('label') === '', 'missing user has empty fields');
$check($post->TermFields('missing')->Text('label') === '', 'missing term has empty fields');
$check($post->UserFields('multiple_users')->Text('label') === '', 'multiple users are not treated as one');
$check((new AcfGet([]))->UserFields('missing')->Text('label') === '', 'array source missing user');
$check((new AcfGet([]))->TermFields('missing')->Text('label') === '', 'array source missing term');
$check(array_map(static fn (PostData $item): int => $item->PostId, $post->Posts('multiple_posts')) === [3, 4], 'multiple posts');
$check(array_map(static fn (TermData $item): int => $item->Id, $post->Terms('multiple_terms')) === [5, 6], 'multiple terms');
$check(array_map(static fn (WpUserData $item): int => $item->Id, $post->Users('multiple_users', 160)) === [7, 8], 'multiple users');
$check($avatarSizes[array_key_last($avatarSizes)] === 160, 'multiple user avatar size');
$check($post->Posts('missing') === [], 'missing multiple posts');
echo "Related fields: {$checks} checks passed.\n";
