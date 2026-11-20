<?php
declare(strict_types=1);

namespace WpTypeSafetyTests\PostGeneration;

use WpTypeSafety\Acf\Get\AcfGet;
use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\PostProps;
use WpTypeSafety\Wp\Get\PostGet;
use WpTypeSafety\Wp\Get\PostQuery;
use WpTypeSafety\Wp\Get\MainQueryGet;
use function PHPStan\Testing\assertType;

class Article extends PostData
{
	protected static function getProps(\WP_Post $post): PostProps
	{
		$props = parent::getProps($post);
		$props->Title = 'adjusted';
		return $props;
	}
}
class Feature extends Article {}
class SpecialFeature extends Feature {}

function Verify(\WP_Post $post, \WP_Query $query, AcfGet $acfGet): void
{
	assertType('WpTypeSafety\\Wp\\Data\\PostData', PostData::From($post));
	assertType('WpTypeSafetyTests\\PostGeneration\\Article', Article::From($post));
	assertType('WpTypeSafetyTests\\PostGeneration\\SpecialFeature', SpecialFeature::From($post));
	assertType('WpTypeSafety\\Wp\\Data\\PostData|null', PostGet::Current());
	assertType('WpTypeSafetyTests\\PostGeneration\\Article|null', PostGet::Current(Article::class));
	assertType('WpTypeSafetyTests\\PostGeneration\\Article|null', PostGet::Loop(Article::class));
	assertType('WpTypeSafetyTests\\PostGeneration\\Article|null', PostGet::ById(1, Article::class));
	assertType('WpTypeSafetyTests\\PostGeneration\\Article|null', PostGet::ByPath('article', dataClass: Article::class));
	assertType('WpTypeSafety\\Wp\\Data\\PostQueryData<WpTypeSafety\\Wp\\Data\\PostData>', PostQuery::Get());
	assertType('WpTypeSafety\\Wp\\Data\\PostQueryData<WpTypeSafetyTests\\PostGeneration\\Article>', PostQuery::Get([], Article::class));
	assertType('WpTypeSafety\\Wp\\Data\\PostQueryData<WpTypeSafetyTests\\PostGeneration\\Article>', PostQuery::From($query, Article::class));
	assertType('WpTypeSafety\\Wp\\Data\\PostQueryData<WpTypeSafetyTests\\PostGeneration\\Article>', MainQueryGet::Current(Article::class));
	assertType('WpTypeSafety\\Wp\\Data\\PostData|null', $acfGet->Post('post'));
	assertType('list<WpTypeSafety\\Wp\\Data\\PostData>', $acfGet->Posts('posts'));
	assertType('WpTypeSafetyTests\\PostGeneration\\Article|null', $acfGet->Post('post', Article::class));
	assertType('list<WpTypeSafetyTests\\PostGeneration\\Article>', $acfGet->Posts('posts', Article::class));
	assertType('list<WpTypeSafetyTests\\PostGeneration\\SpecialFeature>', $acfGet->Posts('posts', SpecialFeature::class));
}

/** @template TPost of PostData
 * @param class-string<TPost> $dataClass
 * @return TPost|null
 */
function Forward(AcfGet $acfGet, string $dataClass): ?PostData
{
	return $acfGet->Post('post', $dataClass);
}
