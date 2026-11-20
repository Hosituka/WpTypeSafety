<?php
declare(strict_types=1);

// 正規のFrom経路で、3階層の初期化・readonly・クラス指定を検証する。
if (PHP_SAPI !== 'cli')
{exit;}
require_once __DIR__ . '/../autoload.php';

use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\PostProps;
use WpTypeSafety\Wp\Get\PostGet;
use WpTypeSafety\Wp\Get\PostQuery;
use WpTypeSafety\Wp\Get\MainQueryGet;
use WpTypeSafety\Acf\Get\AcfGet;

class WP_Post
{
	public function __construct(
		public int $ID = 1,
		public string $post_content = '本文',
		public string $post_status = 'publish',
		public string $post_type = 'post',
		public string $post_name = 'example',
		public string $post_password = '',
		public int $post_author = 2,
	) {}
}
class WP_Query {}
function get_permalink(WP_Post $post): string { return '/example/'; }
function get_post_datetime(WP_Post $post, string $field = 'date'): false { return false; }
function get_the_title(WP_Post $post): string { return 'タイトル'; }
function is_post_publicly_viewable(WP_Post $post): bool { return true; }

class MiddlePost extends PostData
{
	public readonly string $Subtitle;
	public int $Revision = 0;
	public static ?PostProps $LastProps = null;

	protected static function getProps(WP_Post $post): PostProps
	{
		$props = parent::getProps($post);
		$props->Title = '共通：' . $props->Title;
		self::$LastProps = $props;
		return $props;
	}

	protected function initialize(WP_Post $post): void
	{
		parent::initialize($post);
		$GLOBALS['order'][] = 'middle';
		$this->Subtitle = '副題' . $post->ID;
	}
}
class LeafPost extends MiddlePost
{
	public readonly string $Label;

	protected static function getProps(WP_Post $post): PostProps
	{
		$props = parent::getProps($post);
		$props->RawExcerpt = '子階層の抜粋';
		return $props;
	}

	protected function initialize(WP_Post $post): void
	{
		parent::initialize($post);
		$GLOBALS['order'][] = 'leaf';
		$this->Label = '分類' . $post->ID;
	}
}
class MissingParentPost extends MiddlePost
{
	protected function initialize(WP_Post $post): void {}
}
class ThrowingPost extends PostData
{
	protected function initialize(WP_Post $post): void
	{
		parent::initialize($post);
		throw $GLOBALS['generationError'];
	}
}
abstract class AbstractPost extends PostData {}

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (!$ok)
	{throw new RuntimeException($label);}
	$checks++;
};
$source = new WP_Post();
$base = PostData::From($source);
$check($base::class === PostData::class && $base->Title === 'タイトル', '標準クラスを生成');
$GLOBALS['order'] = [];
$middle = MiddlePost::From($source);
$leaf = LeafPost::From($source);
$check($middle::class === MiddlePost::class && $middle->Subtitle === '副題1', '2階層の追加値');
$check($leaf::class === LeafPost::class && $leaf->Subtitle === '副題1' && $leaf->Label === '分類1', '3階層の追加値');
$check($leaf->Title === '共通：タイトル' && $leaf->RawExcerpt === '子階層の抜粋', 'getPropsの親子の変更を維持');
$check($GLOBALS['order'] === ['middle', 'middle', 'leaf'], '初期化の代入は親から子の順');
MiddlePost::$LastProps->Title = '後続変更';
$check($leaf->Title === '共通：タイトル', 'Propsの後続変更は非反映');
foreach ((new ReflectionClass($leaf))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
	if (!$property->isStatic()) {
		$check($property->isInitialized($leaf), '全公開インスタンス値の初期化：' . $property->getName());
	}
}
$leaf->Revision++;
$check($leaf->Revision === 1, '派生側の可変状態は許容');
try {
	$leaf->Subtitle = '再代入';
	$check(false, 'readonly再代入');
} catch (Error) {
	$check(true, 'readonlyを維持');
}
$missing = MissingParentPost::From($source);
$check(!(new ReflectionProperty(MiddlePost::class, 'Subtitle'))->isInitialized($missing), '親呼び忘れは未初期化としてテストで検出');
$check($missing->Id === 1, '親initializeを忘れても基底コンストラクタは実行される');
try {
	new PostData(new PostProps(1, '', '', ''));
	$check(false, '外部new');
} catch (Error) {
	$check(true, '外部newを拒否');
}
$from = new ReflectionMethod(PostData::class, 'From');
$ctor = new ReflectionMethod(PostData::class, '__construct');
$check($from->isFinal() && $from->isPublic() && $from->isStatic(), 'Fromはfinal public static');
$check($ctor->isFinal() && $ctor->isProtected(), 'コンストラクタはfinal protected');
foreach (['getProps', 'initialize'] as $name) {
	$check((new ReflectionMethod(PostData::class, $name))->isProtected(), '拡張点はprotected：' . $name);
}
$check(!method_exists(PostGet::class, 'Props') && !method_exists(PostGet::class, 'From'), '旧変換APIなし');
$GLOBALS['generationError'] = new LogicException('initialize');
try {
	ThrowingPost::From($source);
	$check(false, '初期化例外');
} catch (LogicException $error) {
	$check($error === $GLOBALS['generationError'], '初期化例外をそのまま伝播');
}
$acf = new AcfGet([]);
$lookups = [
	static fn (string $class) => PostGet::Current($class),
	static fn (string $class) => PostGet::Loop($class),
	static fn (string $class) => PostGet::ById(0, $class),
	static fn (string $class) => PostGet::ByPath('', dataClass: $class),
	static fn (string $class) => PostQuery::Get([], $class),
	static fn (string $class) => PostQuery::From(new WP_Query(), $class),
	static fn (string $class) => MainQueryGet::Current($class),
	static fn (string $class) => $acf->Post('missing', $class),
	static fn (string $class) => $acf->Posts('missing', $class),
];
foreach ($lookups as $lookup) {
	foreach (['', 'NoSuchPostClass', stdClass::class, AbstractPost::class] as $invalid) {
		try {
			$lookup($invalid);
			$check(false, '不正なクラス');
		} catch (InvalidArgumentException) {
			$check(true, '検索・取得元確認より前に不正クラスを拒否');
		}
	}
}
echo "Post generation: {$checks} checks passed.\n";
