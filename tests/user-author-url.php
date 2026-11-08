<?php
declare(strict_types=1);

// WordPress APIスタブによるテスト。DBは変更しない。
if (PHP_SAPI !== 'cli') {
	exit;
}

use WpTypeSafety\Wp\Data\WpUserData;
use WpTypeSafety\Wp\Data\UserMembers;
use WpTypeSafety\Wp\Get\UserFactory;

class WP_User
{
	public function __construct(
		public int $ID,
		public string $display_name = '名前',
		public mixed $description = '',
	) {}
	public function exists(): bool { return $this->ID > 0; }
}

function get_author_posts_url(int $id): mixed
{
	$GLOBALS['authorCalls'][] = $id;
	return $GLOBALS['authorResult'];
}

require_once __DIR__ . '/../Wp/Data/WpUserData.php';
require_once __DIR__ . '/../Wp/Data/UserMembers.php';
require_once __DIR__ . '/../Core/Internal/UrlCheck.php';
require_once __DIR__ . '/../Wp/Internal/DtoFactory.php';
require_once __DIR__ . '/../Wp/Get/UserFactory.php';

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (!$ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};
$plain = new WpUserData(new UserMembers(1, '名前'));
$check($plain->AuthorUrl === '', 'direct construction default');
$direct = new WpUserData(new UserMembers(Id: 2, DisplayName: '名前', AuthorUrl: 'https://example.com/author/test/'));
$check($direct->AuthorUrl === 'https://example.com/author/test/', 'direct construction with URL');
$withAvatarSize = new WpUserData(new UserMembers(3, '名前', 64, null));
$check($withAvatarSize->AuthorUrl === '' && $withAvatarSize->GetAvatar() === null, 'Membersの位置引数を利用する');
foreach ([
	['https://example.com/author/test/', 'https://example.com/author/test/'],
	['http://localhost:8080/?author=7', 'http://localhost:8080/?author=7'],
	['https://example.com/著者/', 'https://example.com/著者/'],
	['', ''],
	['javascript:alert(1)', ''],
	['/author/test/', ''],
	['mailto:author@example.com', ''],
	['not a url', ''],
	[false, ''],
	[null, ''],
	[[], ''],
] as [$value, $expected]) {
	$GLOBALS['authorCalls'] = [];
	$GLOBALS['authorResult'] = $value;
	$user = UserFactory::From(new WP_User(7));
	$check($GLOBALS['authorCalls'] === [7], 'URL acquired once at construction');
	$check($user->AuthorUrl === $expected, 'factory URL normalization');
	$check($user->Id === 7 && $user->DisplayName === '名前', 'existing fields preserved');
	$url = $user->AuthorUrl;
	$check($GLOBALS['authorCalls'] === [7], 'reading does not reacquire URL');
}
$GLOBALS['authorResult'] = 'https://example.com/author/7/';
$members = UserFactory::Members(new WP_User(7));
$check($members instanceof UserMembers && $members->Id === 7, 'public factory creates UserMembers');
$GLOBALS['authorCalls'] = [];
$check(UserFactory::From(new WP_User(0)) === null, 'nonexistent user');
$check($GLOBALS['authorCalls'] === [], 'nonexistent user does not acquire URL');
$check((new ReflectionProperty(WpUserData::class, 'AuthorUrl'))->isReadOnly(), 'readonly URL');
$check((new WpUserData(new UserMembers(1, '名前')))->Description === '', 'description defaults to empty');
$direct = new WpUserData(new UserMembers(1, '名前', Description: "自己紹介\n2行目"));
$check($direct->Description === "自己紹介\n2行目", 'description direct construction');
$check((new ReflectionProperty(WpUserData::class, 'Description'))->isReadOnly(), 'readonly description');
foreach (["自己紹介\n2行目", '<strong>紹介文</strong>', '', '0', null, false, 123, []] as $value) {
	$user = UserFactory::From(new WP_User(7, '名前', $value));
	$check($user->Description === (is_string($value) ? $value : ''), 'factory description normalization');
}
echo "User data: {$checks} checks passed.\n";
