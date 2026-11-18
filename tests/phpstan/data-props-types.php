<?php
declare(strict_types=1);

namespace WpTypeSafetyTests\PropsTypes;

use WpTypeSafety\Wp\Data\TermData;
use WpTypeSafety\Wp\Data\TermProps;
use WpTypeSafety\Wp\Data\CommentData;
use WpTypeSafety\Wp\Data\CommentProps;
use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\PostProps;
use WpTypeSafety\Wp\Data\WpUserData;
use WpTypeSafety\Wp\Data\MenuNodeData;
use WpTypeSafety\Wp\Data\MenuNodeProps;
use function PHPStan\Testing\assertType;

/** @extends TermData<Category> */
class Category extends TermData
{
	protected function createRelatedTerm(\WP_Term $term): ?self { return null; }
}
class Author extends WpUserData {}
class Article extends PostData { public int $Revision = 0; }
/** @extends CommentData<Review, Author, Article> */
class Review extends CommentData
{
	protected function createRelatedComment(\WP_Comment $comment): ?self { return null; }
	public function GetUser(): ?Author { return null; }
	public function GetPost(): ?Article { return null; }
}
/** @extends MenuNodeData<Node> */
class Node extends MenuNodeData {}

function Verify(Category $category, Review $review, Node $node, TermProps $term, CommentProps $comment, PostProps $post): void
{
	$editable = new Article($post);
	$editable->Revision = 1;
	$editable->Revision++;
	assertType('WpTypeSafetyTests\\PropsTypes\\Category|null', $category->GetParent());
	assertType('list<WpTypeSafetyTests\\PropsTypes\\Category>', $category->GetChildren());
	assertType('WpTypeSafetyTests\\PropsTypes\\Review|null', $review->GetParent());
	assertType('list<WpTypeSafetyTests\\PropsTypes\\Review>', $review->GetChildren());
	assertType('WpTypeSafetyTests\\PropsTypes\\Author|null', $review->GetUser());
	assertType('WpTypeSafetyTests\\PropsTypes\\Article|null', $review->GetPost());
	assertType('list<WpTypeSafetyTests\\PropsTypes\\Node>', $node->Children);
	$props = new MenuNodeProps(1, 0, '', '', '', [], 0, \WpTypeSafety\Wp\Data\MenuItemType::Custom, '', 0, Children: [$node]);
	$copy = new MenuNodeData($props);
	assertType('list<WpTypeSafetyTests\\PropsTypes\\Node>', $copy->Children);
	assertType('WpTypeSafety\\Wp\\Data\\TermData<WpTypeSafety\\Wp\\Data\\TermData>', new TermData($term));
	assertType('WpTypeSafety\\Wp\\Data\\CommentData<WpTypeSafety\\Wp\\Data\\CommentData, WpTypeSafety\\Wp\\Data\\WpUserData, WpTypeSafety\\Wp\\Data\\PostData>', new CommentData($comment));
	assertType('WpTypeSafety\\Wp\\Data\\PostData<WpTypeSafety\\Wp\\Data\\PostData, WpTypeSafety\\Wp\\Data\\WpUserData>', new PostData($post));
}
