=== Plugin Name ===

Tags: sidebar, posts, archives, nav
Requires at least: 2.7
Tested up to: 3.1
Stable tag: 2.1.3

Allows you to group your archives by month and years with several customization options.

== Description ==

WARNING: This plugin is no longer being supported by its developer and may not work with future versions of WordPress. A replacement by the same developer is available called [PW_Archives](http://wordpress.org/extend/plugins/pw-archives/). It's faster, more efficient, and implements the latest WordPress security features.

This plugin makes the posts in your nav easily-groupable by month and year. It gives you
quite a few easy-to-implement options that allow you to customize both which date groups are
listed as well as how the markup is structured.

Perhaps the primary benefit of this particular plugin over other plugins that do similar
things is its ability to enclose the post count within the anchor tag. This solves countless
problems for anyone using the CSS style display: block on anchor tags, which produces the
undesirable result of forcing the post count down to the next line.

== Installation ==

1. Download Simple Nav Archives through the Plugins menu of the Wordpress admin, or download from here, unzip, and manually upload the `simple_nav_archives` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in the WordPress admin
3. If your theme supports widgets, use the widget editor to place the plugin where you want, otherwise you can include the code `<?php if(function_exists('simple_nav_archives')) { simple_nav_archives(); } ?>` anywhere in your templates



