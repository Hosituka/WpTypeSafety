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
use WpTypeSafety\Wp\Data\MenuData;
use WpTypeSafety\Wp\Data\MenuProps;
use WpTypeSafety\Wp\Get\MenuGet;
use WpTypeSafety\Wp\Get\PostGet;
use WpTypeSafety\Wp\Get\TermGet;
use WpTypeSafety\Wp\Get\UserGet;
use WpTypeSafety\Wp\Get\CommentGet;
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
/** @extends MenuData<Node> */
class Menu extends MenuData
{
	protected static function getNodeClass(): string { return Node::class; }
}

class ThirdAuthor extends Author {}
class SitePostType extends \WpTypeSafety\Wp\Data\PostTypeData {}
class SiteTaxonomy extends \WpTypeSafety\Wp\Data\TaxonomyData {}

function Verify(Category $category, Review $review, Node $node, TermProps $term, CommentProps $comment, \WP_Post $post): void
{
	$editable = Article::From($post);
	$editable->Revision = 1;
	$editable->Revision++;
	assertType('WpTypeSafetyTests\\PropsTypes\\Article|null', PostGet::ById(1, Article::class));
	assertType('WpTypeSafetyTests\\PropsTypes\\Article|null', PostGet::ByPath('company/about', dataClass: Article::class));
	assertType('WpTypeSafetyTests\\PropsTypes\\Category|null', TermGet::ById(1, dataClass: Category::class));
	$userFactory = Author::class;
	assertType('WpTypeSafetyTests\\PropsTypes\\Author|null', UserGet::ById(1, dataClass: $userFactory));
	assertType('WpTypeSafetyTests\\PropsTypes\\Author|null', UserGet::BySlug('name', dataClass: $userFactory));
	assertType('WpTypeSafetyTests\\PropsTypes\\Author|null', UserGet::ByEmail('user@example.com', dataClass: $userFactory));
	assertType('WpTypeSafetyTests\\PropsTypes\\Author|null', UserGet::ByLogin('account', dataClass: $userFactory));
	assertType('WpTypeSafetyTests\\PropsTypes\\Review|null', CommentGet::ById(1, dataClass: Review::class));
	assertType('WpTypeSafety\\Wp\\Data\\PostData|null', PostGet::ById(1));
	assertType('WpTypeSafety\\Wp\\Data\\PostData|null', PostGet::ByPath('about'));
	assertType('WpTypeSafety\\Wp\\Data\\TermData|null', TermGet::ById(1));
	assertType('WpTypeSafety\\Wp\\Data\\WpUserData|null', UserGet::ById(1));
	assertType('WpTypeSafety\\Wp\\Data\\CommentData|null', CommentGet::ById(1));
	assertType('WpTypeSafetyTests\\PropsTypes\\Category|null', \WpTypeSafety\Wp\Get\TermGet::BySlug(
		'category',
		'knowledge',
		Category::class,
	));
	assertType('WpTypeSafetyTests\\PropsTypes\\Category|null', $category->GetParent());
	assertType('list<WpTypeSafetyTests\\PropsTypes\\Category>', $category->GetChildren());
	assertType('WpTypeSafetyTests\\PropsTypes\\Review|null', $review->GetParent());
	assertType('list<WpTypeSafetyTests\\PropsTypes\\Review>', $review->GetChildren());
	assertType('WpTypeSafetyTests\\PropsTypes\\Author|null', $review->GetUser());
	assertType('WpTypeSafetyTests\\PropsTypes\\Article|null', $review->GetPost());
	assertType('list<WpTypeSafetyTests\\PropsTypes\\Node>', $node->Children);
	$copy = Node::From($post, [$node]);
	if ($copy !== null) {
		assertType('list<WpTypeSafetyTests\\PropsTypes\\Node>', $copy->Children);
	}
	$customMenu = MenuGet::ByLocation('primary', Menu::class);
	assertType('WpTypeSafetyTests\\PropsTypes\\Menu|null', $customMenu);
	assertType('WpTypeSafetyTests\\PropsTypes\\Menu|null', MenuGet::ById(1, Menu::class));
	assertType('WpTypeSafetyTests\\PropsTypes\\Menu|null', MenuGet::BySlug('main', Menu::class));
	assertType('WpTypeSafetyTests\\PropsTypes\\Menu|null', Menu::From(new \WP_Term()));
	assertType('WpTypeSafety\\Wp\\Data\\MenuData|null', MenuGet::ByLocation('primary'));
	if ($customMenu !== null) {
		assertType('WpTypeSafetyTests\\PropsTypes\\Node|null', $customMenu->FindCurrent());
		assertType('list<WpTypeSafetyTests\\PropsTypes\\Node>', $customMenu->PathToId(1));
	}
	assertType('WpTypeSafetyTests\\PropsTypes\\SitePostType|null', \WpTypeSafety\Wp\Get\PostTypeGet::FromName('post', SitePostType::class));
	assertType('WpTypeSafetyTests\\PropsTypes\\SitePostType|null', \WpTypeSafety\Wp\Get\PostTypeGet::Current(SitePostType::class));
	assertType('WpTypeSafetyTests\\PropsTypes\\SiteTaxonomy|null', \WpTypeSafety\Wp\Get\TaxonomyGet::FromName('category', SiteTaxonomy::class));
	assertType('WpTypeSafetyTests\\PropsTypes\\ThirdAuthor|null', ThirdAuthor::From(new \WP_User()));
	assertType('list<WpTypeSafetyTests\\PropsTypes\\Category>', \WpTypeSafety\Wp\Get\TermQuery::Get([], Category::class)->Terms);
	assertType('list<WpTypeSafetyTests\\PropsTypes\\Author>', \WpTypeSafety\Wp\Get\UserQuery::Get([], dataClass: Author::class)->Users);
	assertType('list<WpTypeSafetyTests\\PropsTypes\\Review>', \WpTypeSafety\Wp\Get\CommentQuery::Get([], dataClass: Review::class)->Comments);
	assertType('list<WpTypeSafetyTests\\PropsTypes\\Category>', TermGet::Roots([$category], Category::class));
	$acf = new \WpTypeSafety\Acf\Get\AcfGet(1);
	assertType('WpTypeSafetyTests\\PropsTypes\\Category|null', $acf->Term('term', Category::class));
	assertType('list<WpTypeSafetyTests\\PropsTypes\\Category>', $acf->Terms('terms', Category::class));
	assertType('WpTypeSafetyTests\\PropsTypes\\Author|null', $acf->User('user', dataClass: Author::class));
	assertType('list<WpTypeSafetyTests\\PropsTypes\\Author>', $acf->Users('users', dataClass: Author::class));

	assertType('WpTypeSafety\\Wp\\Data\\PostData', PostData::From($post));
}
