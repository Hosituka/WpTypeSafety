<?php
declare(strict_types=1);

// Test-only Props fixtures use the production final From lifecycle.

if (!class_exists('WP_Term')) {
	class WP_Term {}
}
trait TestTermProps
{
	public static function FromProps(\WpTypeSafety\Wp\Data\TermProps $props): static
	{
		$source = (new ReflectionClass(WP_Term::class))->newInstanceWithoutConstructor();
		$GLOBALS['testTermProps'] ??= new WeakMap();
		$GLOBALS['testTermProps'][$source] = $props;
		try {
			return static::From($source);
		} finally {
			unset($GLOBALS['testTermProps'][$source]);
		}
	}

	protected static function getProps(WP_Term $term): ?\WpTypeSafety\Wp\Data\TermProps
	{
		return $GLOBALS['testTermProps'][$term] ?? parent::getProps($term);
	}
}
class TestTermData extends \WpTypeSafety\Wp\Data\TermData
{
	use TestTermProps;
}

if (!class_exists('WP_User')) {
	class WP_User {}
}
trait TestUserProps
{
	public static function FromProps(\WpTypeSafety\Wp\Data\UserProps $props): static
	{
		$source = (new ReflectionClass(WP_User::class))->newInstanceWithoutConstructor();
		$GLOBALS['testUserProps'] ??= new WeakMap();
		$GLOBALS['testUserProps'][$source] = $props;
		try {
			return static::From($source, $props->AvatarSize ?? 96);
		} finally {
			unset($GLOBALS['testUserProps'][$source]);
		}
	}

	protected static function getProps(WP_User $user, int $avatarSize = 96): ?\WpTypeSafety\Wp\Data\UserProps
	{
		return $GLOBALS['testUserProps'][$user] ?? parent::getProps($user, $avatarSize);
	}
}
class TestUserData extends \WpTypeSafety\Wp\Data\WpUserData
{
	use TestUserProps;
}

if (!class_exists('WP_Comment')) {
	class WP_Comment {}
}
trait TestCommentProps
{
	public static function FromProps(\WpTypeSafety\Wp\Data\CommentProps $props): static
	{
		$source = (new ReflectionClass(WP_Comment::class))->newInstanceWithoutConstructor();
		$GLOBALS['testCommentProps'] ??= new WeakMap();
		$GLOBALS['testCommentProps'][$source] = $props;
		try {
			return static::From($source, $props->AvatarSize ?? 96);
		} finally {
			unset($GLOBALS['testCommentProps'][$source]);
		}
	}

	protected static function getProps(WP_Comment $comment, int $avatarSize = 96): \WpTypeSafety\Wp\Data\CommentProps
	{
		return $GLOBALS['testCommentProps'][$comment] ?? parent::getProps($comment, $avatarSize);
	}
}
class TestCommentData extends \WpTypeSafety\Wp\Data\CommentData
{
	use TestCommentProps;
}

if (!class_exists('WP_Post')) {
	class WP_Post {}
}
trait TestMenuNodeProps
{
	public static function FromProps(\WpTypeSafety\Wp\Data\MenuNodeProps $props): static
	{
		$source = (new ReflectionClass(WP_Post::class))->newInstanceWithoutConstructor();
		$GLOBALS['testMenuNodeProps'] ??= new WeakMap();
		$GLOBALS['testMenuNodeProps'][$source] = $props;
		try {
			return static::From($source, $props->Children);
		} finally {
			unset($GLOBALS['testMenuNodeProps'][$source]);
		}
	}

	protected static function getProps(WP_Post $item, array $children = []): ?\WpTypeSafety\Wp\Data\MenuNodeProps
	{
		return $GLOBALS['testMenuNodeProps'][$item] ?? parent::getProps($item, $children);
	}
}
class TestMenuNodeData extends \WpTypeSafety\Wp\Data\MenuNodeData
{
	use TestMenuNodeProps;
}

if (!class_exists('WP_Term')) {
	class WP_Term {}
}
trait TestMenuProps
{
	public static function FromProps(\WpTypeSafety\Wp\Data\MenuProps $props): static
	{
		$source = (new ReflectionClass(WP_Term::class))->newInstanceWithoutConstructor();
		$GLOBALS['testMenuProps'] ??= new WeakMap();
		$GLOBALS['testMenuProps'][$source] = $props;
		try {
			return static::From($source);
		} finally {
			unset($GLOBALS['testMenuProps'][$source]);
		}
	}

	protected static function getProps(WP_Term $term): ?\WpTypeSafety\Wp\Data\MenuProps
	{
		return $GLOBALS['testMenuProps'][$term] ?? parent::getProps($term);
	}
}
class TestMenuData extends \WpTypeSafety\Wp\Data\MenuData
{
	use TestMenuProps;
}

if (!class_exists('WP_Post_Type')) {
	class WP_Post_Type {}
}
trait TestPostTypeProps
{
	public static function FromProps(\WpTypeSafety\Wp\Data\PostTypeProps $props): static
	{
		$source = (new ReflectionClass(WP_Post_Type::class))->newInstanceWithoutConstructor();
		$GLOBALS['testPostTypeProps'] ??= new WeakMap();
		$GLOBALS['testPostTypeProps'][$source] = $props;
		try {
			return static::From($source);
		} finally {
			unset($GLOBALS['testPostTypeProps'][$source]);
		}
	}

	protected static function getProps(WP_Post_Type $postType): \WpTypeSafety\Wp\Data\PostTypeProps
	{
		return $GLOBALS['testPostTypeProps'][$postType] ?? parent::getProps($postType);
	}
}
class TestPostTypeData extends \WpTypeSafety\Wp\Data\PostTypeData
{
	use TestPostTypeProps;
}

if (!class_exists('WP_Taxonomy')) {
	class WP_Taxonomy {}
}
trait TestTaxonomyProps
{
	public static function FromProps(\WpTypeSafety\Wp\Data\TaxonomyProps $props): static
	{
		$source = (new ReflectionClass(WP_Taxonomy::class))->newInstanceWithoutConstructor();
		$GLOBALS['testTaxonomyProps'] ??= new WeakMap();
		$GLOBALS['testTaxonomyProps'][$source] = $props;
		try {
			return static::From($source);
		} finally {
			unset($GLOBALS['testTaxonomyProps'][$source]);
		}
	}

	protected static function getProps(WP_Taxonomy $taxonomy): \WpTypeSafety\Wp\Data\TaxonomyProps
	{
		return $GLOBALS['testTaxonomyProps'][$taxonomy] ?? parent::getProps($taxonomy);
	}
}
class TestTaxonomyData extends \WpTypeSafety\Wp\Data\TaxonomyData
{
	use TestTaxonomyProps;
}
