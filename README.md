# WpTypeSafety

[![Latest Stable Version](https://img.shields.io/packagist/v/hosituka/wp-type-safety.svg)](https://packagist.org/packages/hosituka/wp-type-safety)
[![PHP Version](https://img.shields.io/packagist/dependency-v/hosituka/wp-type-safety/php.svg)](https://packagist.org/packages/hosituka/wp-type-safety)
[![License](https://img.shields.io/packagist/l/hosituka/wp-type-safety.svg)](https://packagist.org/packages/hosituka/wp-type-safety)

**English** | [日本語](README.ja.md)

WpTypeSafety is a PHP library that checks and normalizes loosely shaped values returned by WordPress and Advanced Custom Fields (ACF) into predictable return types.

Its primary guarantees are the PHP return types of public accessors and their fallback rules for missing or invalid values. It does not statically verify ACF field definitions or field names supplied as strings.

It is intended for PHP developers building WordPress themes or plugins who repeatedly check WordPress and ACF return values throughout a project. Basic familiarity with PHP namespaces and nullable types is assumed. PHP 8.2 or later is required.

It does not require rewriting an existing theme.

- Add it to check the result of a single native API call.
- Keep using native WordPress and ACF APIs alongside it.
- Preserve the standard WordPress template hierarchy and `get_template_part()`.
- Move only selected areas to `AcfGet` or Data objects.
- Treat project-specific Data and View classes as optional.

WpTypeSafety is not a framework that enforces a particular theme architecture. It adds type checks and normalization only at the data boundaries where a project needs them.

## Start with the smallest useful scope

You can check one result from the native ACF API. `TypeCheck` does not convert values.

```php
<?php
use WpTypeSafety\Core\Check\TypeCheck;

$title = TypeCheck::String(get_field('title')) ?? '';
```

An existing template part can check only the `$args` it receives.

```php
<?php
use WpTypeSafety\Core\Check\ArrayGet;

$argsGet = new ArrayGet($args);
$title = $argsGet->String('title');
```

You can also normalize a single ACF field into a purpose-specific fixed type.

```php
<?php
use WpTypeSafety\Acf\Get\AcfGet;

$postGet = new AcfGet($post);
$mainImg = $postGet->Img('main_img');
```

Unmodified code, code using only `TypeCheck`, code using `AcfGet`, and code using dedicated Data objects can coexist in the same theme. Simple areas do not need dedicated Data or View classes.

## What it simplifies

ACF's `get_field()` can return different value types depending on the field configuration and whether a value has been entered. WpTypeSafety centralizes post-retrieval checks and normalization in purpose-specific accessors.

```php
<?php
$raw = get_field('title', $post->ID);
$title = is_string($raw) && trim($raw) !== '' ? $raw : '';
```

```php
<?php
use WpTypeSafety\Acf\Get\AcfGet;

$postGet = new AcfGet($post);
$title = $postGet->Text('title'); // string; '' when missing or invalid
$mainImg = $postGet->Img('main_img'); // ?ImgData; null when unavailable

foreach ($postGet->Repeater('items') as $rowGet) {
	$label = $rowGet->Text('label');
}
```

Groups, repeaters, Flexible Content, and post, user, or term references use the same retrieval model. You can traverse missing structures without repeating `isset()` or `is_array()` at every level and receive predictable return types at each point. See [ACF accessors](docs/acf.md) for the detailed contracts for nesting, custom `AcfGet` subclasses, and referenced subjects. The detailed documentation is currently written in Japanese.

## Features

- Purpose-specific accessors with fixed return types, including `Text(): string`, `Float(): ?float`, `Int(): ?int`, `Img(): ?ImgData`, `IconClass(): ?string`, `IconImg(): ?ImgData`, `IconUrl(): ?string`, `Posts(): list<PostData>`, `Terms(): list<TermData>`, `Users(): list<WpUserData>`, `Repeater(): list<AcfGet>`, and `Flexible(): list<FlexibleNode>`.
- `Native(): mixed` as an explicit escape hatch that returns the normal ACF value without WpTypeSafety conversion for unsupported or custom field types.
- `PostData` retains the post status, post type, slug, password-protection state, and WordPress public-viewability result without exposing the password itself.
- Incremental adoption alongside existing WordPress and ACF APIs. Data objects can also be constructed directly with `new`.
- Core type checks and accessors for arrays and public object properties that do not depend on WordPress or ACF.
- Installation without Composer commands or a `vendor` directory. Optional PSR-4 registration is available for project-specific Data and View classes.

## Installation and loading

PHP 8.2 or later is required. WordPress-specific features require WordPress, and ACF-specific features require ACF. The PHP `intl` extension is recommended when validating internationalized domain names. Automated tests use API stubs and do not guarantee integration with a particular WordPress or ACF version.

### Install with Composer

Run the following command from your project root:

```bash
composer require hosituka/wp-type-safety
```

Load the `vendor/autoload.php` generated by Composer during project bootstrap. No additional include is needed if it is already loaded.

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';
```

When installed through Composer, you do not need to load `WpTypeSafety/autoload.php`. Composer's PSR-4 Autoloader resolves classes in the `WpTypeSafety\` namespace.

### Install without Composer

Place the entire repository in your theme or another application directory and load `autoload.php` once during bootstrap. The following example assumes the library is located at `WpTypeSafety/` directly under the theme root; adjust the path to match your project.

```php
<?php
// functions.php
require_once __DIR__ . '/WpTypeSafety/autoload.php';
```

`autoload.php` registers the PSR-4 mapping for `WpTypeSafety\`. Classes under Core, Wp, and Acf are loaded only when referenced. Composer commands and `vendor/autoload.php` are not required for this installation method. See [Autoloading and optional PSR-4 mappings](docs/autoload.md) for details. The detailed documentation is currently written in Japanese.

### Production usage

WpTypeSafety has been continuously used and improved in a production WordPress project. It is being published after its retrieval APIs and type boundaries reached a useful level of maturity through real-world implementation.

At the time of publication, stable operation has been confirmed in the following environment:

- WordPress 7.0.8
- Advanced Custom Fields PRO 6.8.6

This is a record of production usage, not a compatibility guarantee for minimum or maximum supported versions. Automated tests continue to use API stubs, so verify the library in your target environment when using other WordPress or ACF versions.

## Quick start

After making the classes available through Composer or the bundled `autoload.php`, you can use them in a template as follows:

```php
<?php
use WpTypeSafety\Acf\Get\AcfGet;

$post = get_post();
if (! $post instanceof WP_Post) {
	return;
}

$postGet = new AcfGet($post);
$title = $postGet->Text('title'); // string; '' when missing or invalid
$mainImg = $postGet->Img('main_img'); // ?ImgData; null when unavailable
?>

<article>
	<h1><?= esc_html($title) ?></h1>

	<?php if ($mainImg !== null): ?>
		<img
			src="<?= esc_url($mainImg->Url) ?>"
			width="<?= esc_attr($mainImg->Width) ?>"
			height="<?= esc_attr($mainImg->Height) ?>"
			alt="<?= esc_attr($mainImg->Alt) ?>"
		>
	<?php endif; ?>
</article>
```

Passing a nonexistent field name such as `Text('titel')` also returns an empty string. This fallback keeps templates concise, but it does not report retrieval failures.

- Normalize values that may be absent from the rendered output with purpose-specific accessors.
- Explicitly validate values at the project level when a missing value should be treated as an error.
- Use `Native()` or the native ACF API when investigating custom fields.
- Combine project constants or static analysis tools such as PHPStan or Psalm when field names require static guarantees.

Use a Factory to create Data objects from WordPress values.

```php
<?php
use WpTypeSafety\Wp\Get\CommentFactory;
use WpTypeSafety\Wp\Get\PostFactory;

$postData = PostFactory::From($post); // PostData
$commentData = CommentFactory::From($comment); // CommentData
```

Use a target-specific Query to run a native WordPress query and convert all results into Data objects.

```php
<?php
use WpTypeSafety\Wp\Get\PostQuery;

$result = PostQuery::Get([
	'post_type' => 'staff',
	'posts_per_page' => 12,
	'paged' => 1,
]);

foreach ($result->Posts as $postData) {
	echo esc_html($postData->Title);
}
```

ACF fields can be normalized with the accessor matching their purpose. Use Core when you only need to check the result of a native API or read template-part `$args`.

```php
<?php
use WpTypeSafety\Core\Check\ArrayGet;
use WpTypeSafety\Acf\Get\AcfGet;
use WpTypeSafety\Core\Check\ObjectGet;
use WpTypeSafety\Core\Check\TypeCheck;

$postGet = new AcfGet($post);
$argsGet = new ArrayGet($args);
$resultGet = new ObjectGet($resultObject);

$count = $postGet->Int('count');                                  // ?int
$postObject = TypeCheck::Instance(get_post(123), WP_Post::class); // ?WP_Post
$title = $argsGet->String('title');                                // string
$items = $argsGet->NestedList('items');                            // list<ArrayGet>
$status = $resultGet->String('status');                            // string
```

`AcfGet::Int()` normalizes integers, integer-valued floats, and integer-formatted numeric strings into `int`. `TypeCheck` performs no conversion and returns a value only when the input already has the requested PHP type. `ArrayGet` reads array keys, while `ObjectGet` reads public instance properties; both use the same strict rules by default. A boundary can opt into `ValueMode::NumericStr`, `ValueMode::BoolStr`, `ValueMode::BoolInt`, `ValueMode::EmptyStrAsFalse`, or customized `ValueRules`. Multiple modes can be composed with `ValueRules::FromModes()`. `BackedEnum()` and `BackedEnumList()` normalize string or integer backing values into enum cases. Missing values bypass normalizers, while an explicitly stored `null` is passed to custom normalizers. Nested accessors inherit the selected rules. `ObjectGet` reads the current value from the same object on every accessor call and ignores private, protected, static, uninitialized, and magic-only properties. See [Core type checks and array/object access](docs/core.md) and [Examples](docs/examples.md) for details. An empty string alone cannot distinguish an intentionally empty value from a failed lookup.

## Choosing an entry point

| Goal | Main entry point |
|---|---|
| Read ACF fields through typed accessors | `AcfGet` |
| Convert WordPress objects into Data objects | `PostFactory`, `CommentFactory`, `UserFactory`, `TermFactory`, `MenuNodeFactory`, and `AttachmentFactory` |
| Run WordPress queries and convert results into Data objects | `PostQuery`, `TermQuery`, `UserQuery`, and `CommentQuery` |
| Convert ACF block information into Data objects | `AcfDataFactory` |
| Check the type of a standalone value | `TypeCheck` |
| Validate a complete string-keyed dictionary | `DictCheck` |
| Read typed values from an array | `ArrayGet` |
| Read typed values from public object properties | `ObjectGet` |

## Choose the guarantee at each data boundary

These entry points are not maturity levels. They are options that can coexist within the same project and can be selected according to the shape of the data and the guarantees required.

| Scope | Main entry point | Purpose |
|---|---|---|
| Check one value | `TypeCheck` | Check a native API value without converting it |
| Read values from an array or object | `ArrayGet`, `ObjectGet` | Read `$args` or external API structures without changing the surrounding code |
| Validate a complete dictionary | `DictCheck` | Accept only string-keyed dictionaries whose values all satisfy one type contract |
| Normalize ACF values | `AcfGet` | Retrieve images, numbers, choices, references, and nested values through purpose-specific fixed types |
| Convert WordPress values into Data | Target-specific factories | Convert WordPress objects into shared Data objects |
| Convert WordPress query results into Data | Target-specific queries | Convert native query arguments or existing queries into lists of Data objects |
| Build project-specific Data | Members and derived Data | Express the input contract for complex components and project-specific post types |

See [Examples](docs/examples.md) for code samples. The detailed documentation is currently written in Japanese.

## Choose the presentation architecture independently

Presentation is independent from the guarantee selected at a data boundary. Using `AcfGet` or Data objects does not require adopting View classes.

| Presentation style | Status |
|---|---|
| Standard PHP templates | Supported |
| `get_template_part()` | Supported |
| Passing Data through `$args` | Optional |
| `XxxView::Render()` | Optional |
| Optional PSR-4 loading for project classes | Optional |

See [Autoloading and optional PSR-4 mappings](docs/autoload.md) for configuration and View examples. The detailed documentation is currently written in Japanese.

## Design scope and guarantees

WpTypeSafety guarantees the PHP return types of its public accessors and their fallback rules for missing or invalid values. Supported ACF accessors normalize values into purpose-specific fixed types.

Public methods and public properties intentionally use UpperCamelCase. Write names such as `Text()`, `From()`, and `Title`. PHP magic methods and APIs whose names are defined by WordPress or external libraries continue to follow those contracts.

| Item | Guarantee |
|---|---|
| PHP return types of public accessors | Yes |
| Results for missing or invalid values | Yes |
| ACF normalization performed by supported accessors | Yes |
| Semantic validity of values passed directly to Data constructors | No |
| Existence of a field name supplied as a string | No |
| Safety of rendered HTML | No |
| Integration with a particular runtime environment | Must also be verified by the adopter |

Data constructors accept types expressible through PHP declarations, but they do not universally validate conditions such as URL formats or positive IDs and dimensions. When constructing Data directly, validate those conditions at the call site. Accessor-specific restrictions, such as image and file fields configured to return only URLs or arrays whose ACF field definitions are unavailable, are documented in the detailed guides.

HTML composition and output escaping remain the responsibility of the caller. Use escaping appropriate for each output context, such as `esc_html()` for plain text and `esc_url()` for URLs.

## What WpTypeSafety does not do

- Statically verify ACF field names.
- Generate PHP types from ACF field definitions in the current release.
- Replace static analysis tools such as PHPStan or Psalm.
- Automatically escape rendered HTML.
- Enforce a project-wide theme architecture.
- Guarantee integration with every WordPress and ACF version.

## Dependencies by feature area

Installing the complete library does not require using every feature area.

| Area | PHP | WordPress | ACF |
|---|---:|---:|---:|
| `Core` | Required | Not required | Not required |
| `Wp` | Required | Required | Not required |
| `Acf` | Required | Required | Required |
| `Support\Esc` | Required | Required | Not required |
| Optional PSR-4 loading | Required | Not required | Not required |

## Detailed documentation

The detailed documentation is currently available in Japanese.

| Topic | Document | Audience |
|---|---|---|
| ACF accessors, groups, repeaters, related objects, and blocks | [ACF](docs/acf.md) | Users |
| WordPress accessors and Factory | [WordPress](docs/wordpress.md) | Users |
| Type checks and array/object access | [Core](docs/core.md) | Users |
| Data, Resolvers, and derived classes | [Data](docs/data.md) | Users |
| `autoload.php` and optional PSR-4 mappings | [Autoload](docs/autoload.md) | Users |
| Theme-development helpers | [Support](docs/support.md) | Users |
| Nested ACF, derived Data, and ACF block examples | [Examples](docs/examples.md) | Users |
| Internal architecture and verification | [Architecture](docs/architecture.md) | Maintainers |
| Migration from older APIs | [Migration](docs/migration.md) | Migration |

## Tests

The PHP files under `tests/` are independent API-stub tests. Treat them separately from integration tests against a real WordPress and ACF environment. See [Architecture and verification](docs/architecture.md) for commands that run all tests and syntax checks. The detailed documentation is currently written in Japanese.

## License

WpTypeSafety is released under the [MIT License](LICENSE). The bundled Composer `ClassLoader.php` retains its [separate license notice](Autoload/Internal/Composer/LICENSE).
