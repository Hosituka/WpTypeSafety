# WpTypeSafety

[![Latest Stable Version](https://img.shields.io/packagist/v/hosituka/wp-type-safety.svg)](https://packagist.org/packages/hosituka/wp-type-safety)
[![PHP Version](https://img.shields.io/packagist/dependency-v/hosituka/wp-type-safety/php.svg)](https://packagist.org/packages/hosituka/wp-type-safety)
[![License](https://img.shields.io/packagist/l/hosituka/wp-type-safety.svg)](https://packagist.org/packages/hosituka/wp-type-safety)

**English** | [日本語](README.ja.md)

WpTypeSafety is a PHP library for reading loosely shaped WordPress and Advanced Custom Fields (ACF) values through predictable, purpose-specific return contracts. Its WordPress-independent Core can also read raw ACF values, template arguments, and other PHP arrays at data boundaries.

```php
<?php
use WpTypeSafety\Acf\Get\AcfGet;

$postGet = new AcfGet($post);

$title = $postGet->Text('title');       // string
$mainImg = $postGet->Img('main_img');   // ?ImgData
$author = $postGet->User('author');     // ?WpUserData
```

Missing-value and type checks are centralized at the retrieval boundary instead of repeated across templates. You do not need to rebuild an entire theme: adopt one `TypeCheck` call, ACF accessors, WordPress metadata, Data, or Query APIs only where they are useful.

WpTypeSafety is not a framework. Standard PHP templates, the WordPress template hierarchy, and `get_template_part()` can remain unchanged.

## What changes

```php
<?php
$raw = get_field('title', $post->ID);
$title = is_string($raw) && trim($raw) !== '' ? $raw : '';
```

```php
<?php
use WpTypeSafety\Acf\Get\AcfGet;

$postGet = new AcfGet($post);
$title = $postGet->Text('title'); // '' when missing or invalid
```

The library guarantees the PHP return types of its public accessors and their fallback rules for missing or invalid values. It does not statically verify ACF field definitions or field names supplied as strings.

Public methods and public properties consistently use UpperCamelCase.

## Installation

PHP 8.2 or later is required.

### Install with Composer

```bash
composer require hosituka/wp-type-safety
```

Load Composer's Autoloader during project bootstrap. No additional include is needed if it is already loaded.

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';
```

### Install without Composer

Place the repository in your theme or application and load `autoload.php` once. The following example assumes it is located at `WpTypeSafety/` directly under the theme root.

```php
<?php
// functions.php
require_once __DIR__ . '/WpTypeSafety/autoload.php';
```

Composer commands and a `vendor/` directory are not required. See [Autoloading](docs/autoload.md) for details. The detailed documentation is currently written in Japanese.

## Quick start

```php
<?php
use WpTypeSafety\Acf\Get\AcfGet;

$post = get_post();
if (! $post instanceof WP_Post)
{return;}

$postGet = new AcfGet($post);
$title = $postGet->Text('title');
$mainImg = $postGet->Img('main_img');
$relatedPosts = $postGet->Posts('related_posts');
```

- `Text()` always returns `string`
- `Img()` returns `?ImgData`
- `Posts()` returns `list<PostData>`
- Each accessor has a fixed fallback contract for missing or invalid values

Continue to escape HTML for its output context with functions such as `esc_html()` and `esc_url()`. WpTypeSafety does not escape rendered HTML automatically.

Core is useful where values intentionally remain native PHP arrays, such as ACF native values and template-part or block arguments. It checks the boundary without interpreting ACF field semantics; use `AcfGet` when the field purpose is known.

```php
<?php
use WpTypeSafety\Core\Check\ArrayGet;
use WpTypeSafety\Wp\Get\MetaGet;

$argsGet = new ArrayGet($args);
$heading = $argsGet->String('heading');

$metaGet = new MetaGet($post);
$viewCount = $metaGet->Int('view_count');
```

`ObjectGet` and `TreeGet` are also available for plugin objects and mixed API trees, but they are not required for ordinary theme templates. See the [TreeTypeReader engine details](Internal/TreeTypeReader/README.md) when those boundaries or extension points are needed.

## Choose an entry point

These entry points are choices that can coexist in one project, not adoption levels.

| Boundary | Entry point | Main purpose |
|---|---|---|
| Raw PHP value or array | `TypeCheck` / `ArrayGet` | Read ACF native values, template `$args`, and API arrays |
| WordPress metadata | `MetaGet` | Read post, user, term, and comment metadata |
| ACF | `AcfGet` | Normalize ACF values by purpose, including images, choices, and references |
| WordPress object | Target-specific `Factory` | Convert `WP_Post` and related objects into Data |
| WordPress query | Target-specific `Query` | Convert query results into typed Data |

`DictCheck`, `AcfDataFactory`, Members, and derived Data are covered by the detailed guides linked below.

## Choose between WpTypeSafety Data and native WordPress types

ACF and metadata references can be returned as WpTypeSafety Data or as native WordPress objects.

```php
$related = $postGet->Post('related_post');       // ?PostData
$wpRelated = $postGet->WpPost('related_post');   // ?WP_Post
$custom = $postGet->Native('custom_field');      // mixed
```

- `Post()`: normalize into `PostData`
- `WpPost()`: normalize into `WP_Post`
- `Native()`: return a value without WpTypeSafety conversion

## Value constraints

Use `predicate` on supported Core and `MetaGet` Getters when a normalized value must also satisfy a value-level condition.

```php
$positiveCount = $metaGet->Int(
	'view_count',
	predicate: static fn(int $value): bool => $value > 0,
);
```

Core uses strict type checks by default. Advanced features such as `NumericMode`, `BoolMode`, `ValueRules`, `Transform()`, object and mixed-tree traversal, and accessor inheritance are documented in [Core](docs/core.md) and the [TreeTypeReader engine details](Internal/TreeTypeReader/README.md). The detailed documentation is currently written in Japanese.

## Representative return-value rules

| Form | Example | Missing or invalid |
|---|---|---|
| Non-nullable value | `AcfGet::Text(): string`, `ArrayGet::String(): string` | `''` |
| Nullable value | `Int(): ?int` | `null` |
| List | `Posts(): list<PostData>` | `[]` |
| Data | `Post(): ?PostData` | `null` |
| Native WordPress object | `WpPost(): ?WP_Post` | `null` |
| Unconverted value | `Native(): mixed` | See the contract of the corresponding API |

An empty string, empty array, or `null` cannot always distinguish missing input from an invalid value. When absence is an error, combine `Exists()`, project-specific validation, and static analysis such as PHPStan or Psalm.

## Requirements and verification

- PHP 8.2 or later
- WordPress is required for `Wp`
- WordPress and ACF are required for `Acf`
- PHP's `intl` extension is recommended when validating internationalized domain names

The library is continuously used in a production project and has been verified in the following environment:

- WordPress 7.0.8
- Advanced Custom Fields PRO 6.8.6

This is a record of production usage, not a minimum or maximum compatibility guarantee. Automated tests use WordPress and ACF API stubs and are separate from integration tests against a real runtime.

## Dependencies by feature area

| Area | PHP | WordPress | ACF |
|---|---:|---:|---:|
| `Core` | Required | Not required | Not required |
| `Wp` | Required | Required | Not required |
| `Acf` | Required | Required | Required |
| Optional PSR-4 loading | Required | Not required | Not required |

## Detailed documentation

The detailed documentation is currently available in Japanese.

| Topic | Document |
|---|---|
| ACF accessors, groups, repeaters, references, and blocks | [ACF](docs/acf.md) |
| WordPress accessors, metadata, Factory, and Query | [WordPress](docs/wordpress.md) |
| Type checks, normalization, arrays, objects, and mixed trees | [Core](docs/core.md) |
| Generic typed-tree engine and extension contracts | [TreeTypeReader](Internal/TreeTypeReader/README.md) |
| Data, Resolvers, and derived classes | [Data](docs/data.md) |
| `autoload.php` and optional PSR-4 mappings | [Autoloading](docs/autoload.md) |
| Nested ACF, derived Data, and ACF block examples | [Examples](docs/examples.md) |
| Internal architecture, tests, and verification | [Architecture](docs/architecture.md) |
| Migration from older APIs | [Migration](docs/migration.md) |

## What WpTypeSafety does not do

- Statically verify ACF field names
- Generate PHP types from ACF field definitions in the current release
- Replace static analysis tools such as PHPStan or Psalm
- Automatically escape rendered HTML
- Enforce a project-wide theme architecture
- Universally guarantee the semantic validity of values passed directly to Data
- Guarantee integration with every WordPress and ACF version

## License

WpTypeSafety is released under the [MIT License](LICENSE). The bundled Composer `ClassLoader.php` retains its [separate license notice](Autoload/Internal/Composer/LICENSE).
