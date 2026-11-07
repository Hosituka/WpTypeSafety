<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Data;

enum MenuItemType: string
{
	case PostType = 'post_type';
	case Taxonomy = 'taxonomy';
	case Custom = 'custom';
	case PostTypeArchive = 'post_type_archive';
	case Unknown = 'unknown';
}
