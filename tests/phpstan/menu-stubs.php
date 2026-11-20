<?php
declare(strict_types=1);

// メニューAPIの静的解析専用。実行しない。
class WP_Post
{
	public int $ID;
	public string $post_type;
	public int|string $menu_item_parent;
	public int|string $object_id;
	public int $menu_order;
	public string $title;
	public string $url;
	public string $description;
	public string $type;
	public string $object;
	/** @var list<mixed> */
	public array $classes;
	public string $target;
	public string $attr_title;
	public string $xfn;
	public bool $current;
}
class WP_Term
{
	public int $term_id;
	public string $taxonomy;
	public string $name;
	public string $slug;
	public string $description;
}
class WP_Error {}
class WP_Query {}
function get_term(int $id, string $taxonomy): WP_Term|WP_Error|null {}
function get_term_by(string $field, string $value, string $taxonomy): WP_Term|false {}
/** @return array<string, string> */
function get_registered_nav_menus(): array {}
/** @return array<string, int> */
function get_nav_menu_locations(): array {}
/** @return list<WP_Post>|false */
function wp_get_nav_menu_items(int $id): array|false {}
/** @param list<WP_Post> $items */
function _wp_menu_item_classes_by_context(array &$items): void {}
function get_post_thumbnail_id(int $id): int|false {}
