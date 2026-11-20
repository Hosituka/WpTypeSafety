<?php
declare(strict_types=1);

// WordPress APIスタブによるテスト。DBは変更しない。
if (PHP_SAPI !== 'cli') {
	exit;
}

use WpTypeSafety\Wp\Data\WpUserData;
use WpTypeSafety\Wp\Data\UserProps;
use WpTypeSafety\Wp\Get\UserGet;

class WP_User
{
	public function __construct(
		public int $ID,
		public string $display_name = '名前',
		public mixed $description = '',
		public mixed $user_url = '',
	) {}
	public function exists(): bool { return $this->ID > 0; }
}

function get_author_posts_url(int $id): mixed
{
	$GLOBALS['authorCalls'][] = $id;
	return $GLOBALS['authorResult'];
}

require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/fixtures/data-props.php';
function get_avatar_url(mixed $subject, array $args): false { return false; }
require_once __DIR__ . '/../Wp/Data/WpUserData.php';
require_once __DIR__ . '/../Wp/Data/UserProps.php';
require_once __DIR__ . '/../Core/Internal/UrlCheck.php';
require_once __DIR__ . '/../Wp/Internal/DtoFactory.php';
require_once __DIR__ . '/../Wp/Get/UserGet.php';

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (!$ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};
$plain = TestUserData::FromProps(new UserProps(1, '名前'));
$check($plain->WebsiteUrl === '', 'website defaults to empty');
$withWebsite = TestUserData::FromProps(new UserProps(1, '名前', WebsiteUrl: 'https://example.com/'));
$check($withWebsite->WebsiteUrl === 'https://example.com/' && $withWebsite->ArchiveUrl === '', 'website and archive are independent');
$check((new ReflectionProperty(WpUserData::class, 'WebsiteUrl'))->isReadOnly(), 'readonly website');
$check($plain->ArchiveUrl === '', 'direct construction default');
$direct = TestUserData::FromProps(new UserProps(Id: 2, DisplayName: '名前', ArchiveUrl: 'https://example.com/author/test/'));
$check($direct->ArchiveUrl === 'https://example.com/author/test/', 'direct construction with URL');
$withAvatarSize = TestUserData::FromProps(new UserProps(3, '名前', 64));
$check($withAvatarSize->ArchiveUrl === '' && $withAvatarSize->GetAvatar() === null, 'Membersの位置引数を利用する');
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
	$user = \WpTypeSafety\Wp\Data\WpUserData::From(new WP_User(7));
	$check($GLOBALS['authorCalls'] === [7], 'URL acquired once at construction');
	$check($user->ArchiveUrl === $expected, 'factory URL normalization');
	$check($user->Id === 7 && $user->DisplayName === '名前', 'existing fields preserved');
	$url = $user->ArchiveUrl;
	$check($GLOBALS['authorCalls'] === [7], 'reading does not reacquire URL');
}
$GLOBALS['authorResult'] = 'https://example.com/author/7/';
$props = \WpTypeSafety\Wp\Internal\DtoFactory::UserPropsFromWpUser(new WP_User(7));
$check($props instanceof UserProps && $props->Id === 7, 'public factory creates UserProps');
$GLOBALS['authorCalls'] = [];
$check(\WpTypeSafety\Wp\Data\WpUserData::From(new WP_User(0)) === null, 'nonexistent user');
$check($GLOBALS['authorCalls'] === [], 'nonexistent user does not acquire URL');
$check((new ReflectionProperty(WpUserData::class, 'ArchiveUrl'))->isReadOnly(), 'readonly URL');
$check((TestUserData::FromProps(new UserProps(1, '名前')))->Description === '', 'description defaults to empty');
$direct = TestUserData::FromProps(new UserProps(1, '名前', Description: "自己紹介\n2行目"));
$check($direct->Description === "自己紹介\n2行目", 'description direct construction');
$check((new ReflectionProperty(WpUserData::class, 'Description'))->isReadOnly(), 'readonly description');
foreach (["自己紹介\n2行目", '<strong>紹介文</strong>', '', '0', null, false, 123, []] as $value) {
	$user = \WpTypeSafety\Wp\Data\WpUserData::From(new WP_User(7, '名前', $value));
	$check($user->Description === (is_string($value) ? $value : ''), 'factory description normalization');
}
foreach ([
	['https://example.com/', 'https://example.com/'],
	['http://localhost:8080/', 'http://localhost:8080/'],
	['https://example.com/紹介/', 'https://example.com/紹介/'],
	['', ''],
	['/relative/', ''],
	['javascript:alert(1)', ''],
	['mailto:author@example.com', ''],
	['not a url', ''],
	[null, ''],
	[false, ''],
	[123, ''],
	[[], ''],
] as [$value, $expected]) {
	$user = \WpTypeSafety\Wp\Data\WpUserData::From(new WP_User(7, user_url: $value));
	$check($user->WebsiteUrl === $expected, 'factory website normalization');
	$check($user->ArchiveUrl === $GLOBALS['authorResult'], 'website does not replace archive');
}

echo "User data: {$checks} checks passed.\n";
