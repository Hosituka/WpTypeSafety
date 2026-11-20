<?php
declare(strict_types=1);
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/fixtures/data-props.php';

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (!$ok)
	{throw new RuntimeException($label);}
	$checks++;
};

class ParentTerm extends \WpTypeSafety\Wp\Data\TermData
{
	use TestTermProps;
	public readonly string $ParentValue;
	protected function initialize(WP_Term $source): void
	{
		parent::initialize($source);
		$this->ParentValue = 'parent';
	}
}
class ChildTerm extends ParentTerm
{
	public readonly string $ChildValue;
	public int $Revision = 0;
	protected static function getProps(WP_Term $source): ?\WpTypeSafety\Wp\Data\TermProps
	{
		$props = parent::getProps($source);
		if ($props === null)
		{return null;}
		$props->Id = 9;
		return $props;
	}
	protected function initialize(WP_Term $source): void
	{
		parent::initialize($source);
		$this->ChildValue = $this->ParentValue . ':child';
	}
}
class ThrowingTerm extends ParentTerm
{
	protected function initialize(WP_Term $source): void { throw $GLOBALS['generationError']; }
}
$props = new \WpTypeSafety\Wp\Data\TermProps(1, 'term', 'term', 'category', '/');
$value = ChildTerm::FromProps($props);
$check($value instanceof ChildTerm && $value->ChildValue === 'parent:child', 'Term: parent before child');
$check($value->Id === 9, 'Term: changed Props');
$value->Revision++;
$check($value->Revision === 1, 'Term: subclass mutable state');
$ctor = new ReflectionMethod(\WpTypeSafety\Wp\Data\TermData::class, '__construct');
$from = new ReflectionMethod(\WpTypeSafety\Wp\Data\TermData::class, 'From');
$check($ctor->isFinal() && $ctor->isProtected() && $from->isFinal() && $from->isPublic() && $from->isStatic(), 'Term: fixed construction contract');
try {
	$value->Id = 10;
	$check(false, 'Term: readonly');
} catch (Error) {
	$checks++;
}
$GLOBALS['generationError'] = new LogicException('Term initialization');
try {
	ThrowingTerm::FromProps($props);
	$check(false, 'Term: initialize exception');
} catch (LogicException $error) {
	$check($error === $GLOBALS['generationError'], 'Term: exception identity');
}

class ExcludedTerm extends ParentTerm
{
	protected static function getProps(WP_Term $source): ?\WpTypeSafety\Wp\Data\TermProps { return null; }
	protected function initialize(WP_Term $source): void { throw new LogicException('must not initialize'); }
}
$source = (new ReflectionClass(WP_Term::class))->newInstanceWithoutConstructor();
$check(ExcludedTerm::From($source) === null, 'Term: null before constructor/initialize');

class ParentUser extends \WpTypeSafety\Wp\Data\WpUserData
{
	use TestUserProps;
	public readonly string $ParentValue;
	protected function initialize(WP_User $source, int $avatarSize = 96): void
	{
		parent::initialize($source, $avatarSize);
		$this->ParentValue = 'parent';
	}
}
class ChildUser extends ParentUser
{
	public readonly string $ChildValue;
	public int $Revision = 0;
	protected static function getProps(WP_User $source, int $avatarSize = 96): ?\WpTypeSafety\Wp\Data\UserProps
	{
		$props = parent::getProps($source, $avatarSize);
		if ($props === null)
		{return null;}
		$props->Id = 9;
		return $props;
	}
	protected function initialize(WP_User $source, int $avatarSize = 96): void
	{
		parent::initialize($source, $avatarSize);
		$this->ChildValue = $this->ParentValue . ':child';
	}
}
class ThrowingUser extends ParentUser
{
	protected function initialize(WP_User $source, int $avatarSize = 96): void { throw $GLOBALS['generationError']; }
}
$props = new \WpTypeSafety\Wp\Data\UserProps(1, 'user', 64);
$value = ChildUser::FromProps($props);
$check($value instanceof ChildUser && $value->ChildValue === 'parent:child', 'User: parent before child');
$check($value->Id === 9, 'User: changed Props');
$value->Revision++;
$check($value->Revision === 1, 'User: subclass mutable state');
$ctor = new ReflectionMethod(\WpTypeSafety\Wp\Data\WpUserData::class, '__construct');
$from = new ReflectionMethod(\WpTypeSafety\Wp\Data\WpUserData::class, 'From');
$check($ctor->isFinal() && $ctor->isProtected() && $from->isFinal() && $from->isPublic() && $from->isStatic(), 'User: fixed construction contract');
try {
	$value->Id = 10;
	$check(false, 'User: readonly');
} catch (Error) {
	$checks++;
}
$GLOBALS['generationError'] = new LogicException('User initialization');
try {
	ThrowingUser::FromProps($props);
	$check(false, 'User: initialize exception');
} catch (LogicException $error) {
	$check($error === $GLOBALS['generationError'], 'User: exception identity');
}

class ExcludedUser extends ParentUser
{
	protected static function getProps(WP_User $source, int $avatarSize = 96): ?\WpTypeSafety\Wp\Data\UserProps { return null; }
	protected function initialize(WP_User $source, int $avatarSize = 96): void { throw new LogicException('must not initialize'); }
}
$source = (new ReflectionClass(WP_User::class))->newInstanceWithoutConstructor();
$check(ExcludedUser::From($source) === null, 'User: null before constructor/initialize');

class ParentComment extends \WpTypeSafety\Wp\Data\CommentData
{
	use TestCommentProps;
	public readonly string $ParentValue;
	protected function initialize(WP_Comment $source, int $avatarSize = 96): void
	{
		parent::initialize($source, $avatarSize);
		$this->ParentValue = 'parent';
	}
}
class ChildComment extends ParentComment
{
	public readonly string $ChildValue;
	public int $Revision = 0;
	protected static function getProps(WP_Comment $source, int $avatarSize = 96): \WpTypeSafety\Wp\Data\CommentProps
	{
		$props = parent::getProps($source, $avatarSize);
		$props->Id = 9;
		return $props;
	}
	protected function initialize(WP_Comment $source, int $avatarSize = 96): void
	{
		parent::initialize($source, $avatarSize);
		$this->ChildValue = $this->ParentValue . ':child';
	}
}
class ThrowingComment extends ParentComment
{
	protected function initialize(WP_Comment $source, int $avatarSize = 96): void { throw $GLOBALS['generationError']; }
}
$props = new \WpTypeSafety\Wp\Data\CommentProps(1, 1, 0, 0, '', '', '/', '', 'comment', 'approved');
$value = ChildComment::FromProps($props);
$check($value instanceof ChildComment && $value->ChildValue === 'parent:child', 'Comment: parent before child');
$check($value->Id === 9, 'Comment: changed Props');
$value->Revision++;
$check($value->Revision === 1, 'Comment: subclass mutable state');
$ctor = new ReflectionMethod(\WpTypeSafety\Wp\Data\CommentData::class, '__construct');
$from = new ReflectionMethod(\WpTypeSafety\Wp\Data\CommentData::class, 'From');
$check($ctor->isFinal() && $ctor->isProtected() && $from->isFinal() && $from->isPublic() && $from->isStatic(), 'Comment: fixed construction contract');
try {
	$value->Id = 10;
	$check(false, 'Comment: readonly');
} catch (Error) {
	$checks++;
}
$GLOBALS['generationError'] = new LogicException('Comment initialization');
try {
	ThrowingComment::FromProps($props);
	$check(false, 'Comment: initialize exception');
} catch (LogicException $error) {
	$check($error === $GLOBALS['generationError'], 'Comment: exception identity');
}

class ParentPostType extends \WpTypeSafety\Wp\Data\PostTypeData
{
	use TestPostTypeProps;
	public readonly string $ParentValue;
	protected function initialize(WP_Post_Type $source): void
	{
		parent::initialize($source);
		$this->ParentValue = 'parent';
	}
}
class ChildPostType extends ParentPostType
{
	public readonly string $ChildValue;
	public int $Revision = 0;
	protected static function getProps(WP_Post_Type $source): \WpTypeSafety\Wp\Data\PostTypeProps
	{
		$props = parent::getProps($source);
		$props->Label = 'adjusted';
		return $props;
	}
	protected function initialize(WP_Post_Type $source): void
	{
		parent::initialize($source);
		$this->ChildValue = $this->ParentValue . ':child';
	}
}
class ThrowingPostType extends ParentPostType
{
	protected function initialize(WP_Post_Type $source): void { throw $GLOBALS['generationError']; }
}
$props = new \WpTypeSafety\Wp\Data\PostTypeProps('post', 'Posts', 'Post');
$value = ChildPostType::FromProps($props);
$check($value instanceof ChildPostType && $value->ChildValue === 'parent:child', 'PostType: parent before child');
$check($value->Label === 'adjusted', 'PostType: changed Props');
$value->Revision++;
$check($value->Revision === 1, 'PostType: subclass mutable state');
$ctor = new ReflectionMethod(\WpTypeSafety\Wp\Data\PostTypeData::class, '__construct');
$from = new ReflectionMethod(\WpTypeSafety\Wp\Data\PostTypeData::class, 'From');
$check($ctor->isFinal() && $ctor->isProtected() && $from->isFinal() && $from->isPublic() && $from->isStatic(), 'PostType: fixed construction contract');
try {
	$value->Label = 'other';
	$check(false, 'PostType: readonly');
} catch (Error) {
	$checks++;
}
$GLOBALS['generationError'] = new LogicException('PostType initialization');
try {
	ThrowingPostType::FromProps($props);
	$check(false, 'PostType: initialize exception');
} catch (LogicException $error) {
	$check($error === $GLOBALS['generationError'], 'PostType: exception identity');
}

class ParentTaxonomy extends \WpTypeSafety\Wp\Data\TaxonomyData
{
	use TestTaxonomyProps;
	public readonly string $ParentValue;
	protected function initialize(WP_Taxonomy $source): void
	{
		parent::initialize($source);
		$this->ParentValue = 'parent';
	}
}
class ChildTaxonomy extends ParentTaxonomy
{
	public readonly string $ChildValue;
	public int $Revision = 0;
	protected static function getProps(WP_Taxonomy $source): \WpTypeSafety\Wp\Data\TaxonomyProps
	{
		$props = parent::getProps($source);
		$props->Label = 'adjusted';
		return $props;
	}
	protected function initialize(WP_Taxonomy $source): void
	{
		parent::initialize($source);
		$this->ChildValue = $this->ParentValue . ':child';
	}
}
class ThrowingTaxonomy extends ParentTaxonomy
{
	protected function initialize(WP_Taxonomy $source): void { throw $GLOBALS['generationError']; }
}
$props = new \WpTypeSafety\Wp\Data\TaxonomyProps('category', 'Categories', 'Category');
$value = ChildTaxonomy::FromProps($props);
$check($value instanceof ChildTaxonomy && $value->ChildValue === 'parent:child', 'Taxonomy: parent before child');
$check($value->Label === 'adjusted', 'Taxonomy: changed Props');
$value->Revision++;
$check($value->Revision === 1, 'Taxonomy: subclass mutable state');
$ctor = new ReflectionMethod(\WpTypeSafety\Wp\Data\TaxonomyData::class, '__construct');
$from = new ReflectionMethod(\WpTypeSafety\Wp\Data\TaxonomyData::class, 'From');
$check($ctor->isFinal() && $ctor->isProtected() && $from->isFinal() && $from->isPublic() && $from->isStatic(), 'Taxonomy: fixed construction contract');
try {
	$value->Label = 'other';
	$check(false, 'Taxonomy: readonly');
} catch (Error) {
	$checks++;
}
$GLOBALS['generationError'] = new LogicException('Taxonomy initialization');
try {
	ThrowingTaxonomy::FromProps($props);
	$check(false, 'Taxonomy: initialize exception');
} catch (LogicException $error) {
	$check($error === $GLOBALS['generationError'], 'Taxonomy: exception identity');
}

class ParentMenuNode extends \WpTypeSafety\Wp\Data\MenuNodeData
{
	use TestMenuNodeProps;
	public readonly string $ParentValue;
	protected function initialize(WP_Post $source, array $children = []): void
	{
		parent::initialize($source, $children);
		$this->ParentValue = 'parent';
	}
}
class ChildMenuNode extends ParentMenuNode
{
	public readonly string $ChildValue;
	public int $Revision = 0;
	protected static function getProps(WP_Post $source, array $children = []): ?\WpTypeSafety\Wp\Data\MenuNodeProps
	{
		$props = parent::getProps($source, $children);
		if ($props === null)
		{return null;}
		$props->Id = 9;
		return $props;
	}
	protected function initialize(WP_Post $source, array $children = []): void
	{
		parent::initialize($source, $children);
		$this->ChildValue = $this->ParentValue . ':child';
	}
}
class ThrowingMenuNode extends ParentMenuNode
{
	protected function initialize(WP_Post $source, array $children = []): void { throw $GLOBALS['generationError']; }
}
$props = new \WpTypeSafety\Wp\Data\MenuNodeProps(1, 0, '', '/', '', [], 0, \WpTypeSafety\Wp\Data\MenuItemType::Custom, '', 0);
$value = ChildMenuNode::FromProps($props);
$check($value instanceof ChildMenuNode && $value->ChildValue === 'parent:child', 'MenuNode: parent before child');
$check($value->Id === 9, 'MenuNode: changed Props');
$value->Revision++;
$check($value->Revision === 1, 'MenuNode: subclass mutable state');
$ctor = new ReflectionMethod(\WpTypeSafety\Wp\Data\MenuNodeData::class, '__construct');
$from = new ReflectionMethod(\WpTypeSafety\Wp\Data\MenuNodeData::class, 'From');
$check($ctor->isFinal() && $ctor->isProtected() && $from->isFinal() && $from->isPublic() && $from->isStatic(), 'MenuNode: fixed construction contract');
try {
	$value->Id = 10;
	$check(false, 'MenuNode: readonly');
} catch (Error) {
	$checks++;
}
$GLOBALS['generationError'] = new LogicException('MenuNode initialization');
try {
	ThrowingMenuNode::FromProps($props);
	$check(false, 'MenuNode: initialize exception');
} catch (LogicException $error) {
	$check($error === $GLOBALS['generationError'], 'MenuNode: exception identity');
}

class ExcludedMenuNode extends ParentMenuNode
{
	protected static function getProps(WP_Post $source, array $children = []): ?\WpTypeSafety\Wp\Data\MenuNodeProps { return null; }
	protected function initialize(WP_Post $source, array $children = []): void { throw new LogicException('must not initialize'); }
}
$source = (new ReflectionClass(WP_Post::class))->newInstanceWithoutConstructor();
$check(ExcludedMenuNode::From($source) === null, 'MenuNode: null before constructor/initialize');

class ParentMenu extends \WpTypeSafety\Wp\Data\MenuData
{
	use TestMenuProps;
	public readonly string $ParentValue;
	protected function initialize(WP_Term $source): void
	{
		parent::initialize($source);
		$this->ParentValue = 'parent';
	}
}
class ChildMenu extends ParentMenu
{
	public readonly string $ChildValue;
	public int $Revision = 0;
	protected static function getProps(WP_Term $source): ?\WpTypeSafety\Wp\Data\MenuProps
	{
		$props = parent::getProps($source);
		if ($props === null)
		{return null;}
		$props->Id = 9;
		return $props;
	}
	protected function initialize(WP_Term $source): void
	{
		parent::initialize($source);
		$this->ChildValue = $this->ParentValue . ':child';
	}
}
class ThrowingMenu extends ParentMenu
{
	protected function initialize(WP_Term $source): void { throw $GLOBALS['generationError']; }
}
$props = new \WpTypeSafety\Wp\Data\MenuProps(1, 'menu', 'menu', '', []);
$value = ChildMenu::FromProps($props);
$check($value instanceof ChildMenu && $value->ChildValue === 'parent:child', 'Menu: parent before child');
$check($value->Id === 9, 'Menu: changed Props');
$value->Revision++;
$check($value->Revision === 1, 'Menu: subclass mutable state');
$ctor = new ReflectionMethod(\WpTypeSafety\Wp\Data\MenuData::class, '__construct');
$from = new ReflectionMethod(\WpTypeSafety\Wp\Data\MenuData::class, 'From');
$check($ctor->isFinal() && $ctor->isProtected() && $from->isFinal() && $from->isPublic() && $from->isStatic(), 'Menu: fixed construction contract');
try {
	$value->Id = 10;
	$check(false, 'Menu: readonly');
} catch (Error) {
	$checks++;
}
$GLOBALS['generationError'] = new LogicException('Menu initialization');
try {
	ThrowingMenu::FromProps($props);
	$check(false, 'Menu: initialize exception');
} catch (LogicException $error) {
	$check($error === $GLOBALS['generationError'], 'Menu: exception identity');
}

class ExcludedMenu extends ParentMenu
{
	protected static function getProps(WP_Term $source): ?\WpTypeSafety\Wp\Data\MenuProps { return null; }
	protected function initialize(WP_Term $source): void { throw new LogicException('must not initialize'); }
}
$source = (new ReflectionClass(WP_Term::class))->newInstanceWithoutConstructor();
$check(ExcludedMenu::From($source) === null, 'Menu: null before constructor/initialize');


$acf = new \WpTypeSafety\Acf\Get\AcfGet(1);
abstract class AbstractTerm extends \WpTypeSafety\Wp\Data\TermData {}
abstract class AbstractUser extends \WpTypeSafety\Wp\Data\WpUserData {}
abstract class AbstractComment extends \WpTypeSafety\Wp\Data\CommentData {}
abstract class AbstractPostType extends \WpTypeSafety\Wp\Data\PostTypeData {}
abstract class AbstractTaxonomy extends \WpTypeSafety\Wp\Data\TaxonomyData {}
abstract class AbstractMenuNode extends \WpTypeSafety\Wp\Data\MenuNodeData {}
abstract class AbstractMenu extends \WpTypeSafety\Wp\Data\MenuData {}
foreach ([
	[AbstractTerm::class, static fn (string $class) => \WpTypeSafety\Wp\Get\TermGet::ById(0, dataClass: $class)],
	[AbstractTerm::class, static fn (string $class) => \WpTypeSafety\Wp\Get\TermGet::BySlug('', '', $class)],
	[AbstractTerm::class, static fn (string $class) => \WpTypeSafety\Wp\Get\TermGet::Current($class)],
	[AbstractTerm::class, static fn (string $class) => \WpTypeSafety\Wp\Get\TermGet::FromPost(0, '', $class)],
	[AbstractTerm::class, static fn (string $class) => \WpTypeSafety\Wp\Get\TermGet::Roots([], $class)],
	[AbstractTerm::class, static fn (string $class) => \WpTypeSafety\Wp\Get\TermQuery::Get([], $class)],
	[AbstractTerm::class, static fn (string $class) => $acf->Term('missing', $class)],
	[AbstractTerm::class, static fn (string $class) => $acf->Terms('missing', $class)],
	[AbstractUser::class, static fn (string $class) => \WpTypeSafety\Wp\Get\UserGet::ById(0, dataClass: $class)],
	[AbstractUser::class, static fn (string $class) => \WpTypeSafety\Wp\Get\UserGet::BySlug('', dataClass: $class)],
	[AbstractUser::class, static fn (string $class) => \WpTypeSafety\Wp\Get\UserGet::Current(dataClass: $class)],
	[AbstractUser::class, static fn (string $class) => \WpTypeSafety\Wp\Get\UserGet::LoggedIn(dataClass: $class)],
	[AbstractUser::class, static fn (string $class) => \WpTypeSafety\Wp\Get\UserQuery::Get([], dataClass: $class)],
	[AbstractUser::class, static fn (string $class) => $acf->User('missing', dataClass: $class)],
	[AbstractUser::class, static fn (string $class) => $acf->Users('missing', dataClass: $class)],
	[AbstractComment::class, static fn (string $class) => \WpTypeSafety\Wp\Get\CommentGet::ById(0, dataClass: $class)],
	[AbstractComment::class, static fn (string $class) => \WpTypeSafety\Wp\Get\CommentQuery::Get([], dataClass: $class)],
	[AbstractMenu::class, static fn (string $class) => \WpTypeSafety\Wp\Get\MenuGet::ById(0, $class)],
	[AbstractMenu::class, static fn (string $class) => \WpTypeSafety\Wp\Get\MenuGet::BySlug('', $class)],
	[AbstractMenu::class, static fn (string $class) => \WpTypeSafety\Wp\Get\MenuGet::ByLocation('', $class)],
	[AbstractPostType::class, static fn (string $class) => \WpTypeSafety\Wp\Get\PostTypeGet::Current($class)],
	[AbstractPostType::class, static fn (string $class) => \WpTypeSafety\Wp\Get\PostTypeGet::FromName('', $class)],
	[AbstractTaxonomy::class, static fn (string $class) => \WpTypeSafety\Wp\Get\TaxonomyGet::FromName('', $class)],
] as [$abstractClass, $call]) {
	foreach (['', 'MissingGenerationClass', stdClass::class, $abstractClass] as $class) {
		try {
			$call($class);
			$check(false, 'invalid class accepted');
		} catch (InvalidArgumentException) {
			$checks++;
		}
	}
}

echo "WP Data generation: {$checks} checks passed.\n";
