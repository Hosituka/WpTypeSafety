<?php
declare(strict_types=1);

namespace WpTypeSafetyTests\PostTypes;

use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\PostProps;
use WpTypeSafety\Wp\Data\TermData;
use WpTypeSafety\Wp\Data\WpUserData;
use WpTypeSafety\Wp\Get\PostGet;
use function PHPStan\Testing\assertType;

/** @extends PostData<Article, Author> */
class Article extends PostData
{
	protected function createRelatedPost(\WP_Post $post): ?self
	{
		return self::From($post);
	}
	public function GetAuthor(): ?Author { return null; }
}
class Author extends WpUserData {}
class Category extends TermData {}

function VerifyPostTypes(
	\WP_Post $post,
	PostProps $props,
	Article $article,
	Author $author,
	Category $category,
): void {
	$termClass = Category::class;
	$data = Article::From($post);
	assertType('WpTypeSafetyTests\\PostTypes\\Article', $data);
	assertType('WpTypeSafetyTests\\PostTypes\\Article|null', $data->GetParent());
	assertType('list<WpTypeSafetyTests\\PostTypes\\Article>', $data->GetChildren());
	assertType('WpTypeSafetyTests\\PostTypes\\Article|null', $data->GetPreviousPost());
	assertType('WpTypeSafetyTests\\PostTypes\\Article|null', $data->GetNextPost());
	assertType('WpTypeSafetyTests\\PostTypes\\Author|null', $data->GetAuthor());
	assertType('list<WpTypeSafetyTests\\PostTypes\\Category>', $data->GetTerms('category', $termClass));
	assertType('WpTypeSafetyTests\\PostTypes\\Article|null', $article->GetParent());
	assertType('WpTypeSafetyTests\\PostTypes\\Author|null', $article->GetAuthor());
	$from = PostData::From($post);
	assertType('list<WpTypeSafety\\Wp\\Data\\PostData>', $from->GetChildren());
	assertType('WpTypeSafety\\Wp\\Data\\WpUserData|null', $from->GetAuthor());
	assertType('WpTypeSafetyTests\\PostTypes\\Article', Article::From($post));
	assertType('WpTypeSafetyTests\\PostTypes\\Article|null', PostGet::Current(Article::class));
	assertType('WpTypeSafetyTests\\PostTypes\\Article|null', PostGet::Loop(Article::class));
	assertType('WpTypeSafetyTests\\PostTypes\\Article|null', PostGet::ById(1, Article::class));
	assertType('WpTypeSafetyTests\\PostTypes\\Article|null', PostGet::ByPath('article', dataClass: Article::class));
	assertType('WpTypeSafety\\Wp\\Data\\PostQueryData<WpTypeSafetyTests\\PostTypes\\Article>', \WpTypeSafety\Wp\Get\PostQuery::Get([], Article::class));
	assertType('WpTypeSafety\\Wp\\Data\\PostQueryData<WpTypeSafetyTests\\PostTypes\\Article>', \WpTypeSafety\Wp\Get\MainQueryGet::Current(Article::class));
}
