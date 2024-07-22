=== MB Rest API ===
Contributors: metabox, rilwis, barcavn2
Donate link: https://metabox.io/pricing/
Tags: meta box, custom fields, rest api
Requires at least: 4.1
Tested up to: 6.6
Stable tag: 2.0.3
Requires PHP: 7.0
License: GPLv2 or later

Get and update Meta Box custom fields to the WordPress REST API responses.

== Description ==

[**MB Rest API**](https://metabox.io/plugins/mb-rest-api/) is an extension for [Meta Box](https://metabox.io) which helps you to get and update custom fields' values (meta value) from posts, pages, custom post types, terms via the WordPress REST API.

### Plugin Links

- [Project Page](https://metabox.io/plugins/mb-rest-api/)
- [Documentation](https://docs.metabox.io/extensions/mb-rest-api/)
- [Github Repo](https://github.com/rilwis/mb-rest-api/)

See more [Meta Box plugins](https://metabox.io/plugins/).

### You might also like

If you like this plugin, you might also like our other WordPress products:

- [Meta Box](https://metabox.io) - A powerful WordPress plugin for creating custom post types and custom fields.
- [Slim SEO](https://wpslimseo.com) - A fast, lightweight and full-featured SEO plugin for WordPress with minimal configuration.
- [Slim SEO Schema](https://wpslimseo.com/products/slim-seo-schema/) - An advanced, powerful and flexible plugin to add schemas to WordPress.
- [Slim SEO Link Manager](https://wpslimseo.com/products/slim-seo-link-manager/) - Build internal link easier in WordPress with real-time reports.
- [GretaThemes](https://gretathemes.com) - Free and premium WordPress themes that clean, simple and just work.
- [Auto Listings](https://wpautolistings.com) - A car sale and dealership plugin for WordPress.

== Installation ==

You need to install [Meta Box](https://metabox.io) plugin and WordPress REST API first

- Go to **Plugins | Add New** and search for **Meta Box**
- Click **Install Now** button to install the plugin
- After installing, click **Activate Plugin** to activate the plugin

Repeat the same process for **WP REST API** and **MB Rest API**.

== Frequently Asked Questions ==

== Screenshots ==

== Changelog ==

- 2.0.3 - 2024-07-22 =
- Fix PHP warning in WordPress 6.6

= 2.0.2 - 2024-07-02 =
- Fix not updating WooCommerce products

= 2.0.1 - 2023-10-14 =
- Ensure group value is always an array. Fix PHP warning when group has no values.

= 2.0.0 - 2023-10-11 =
- Complete rewrite the plugin for clarity and maintainability
- Add support for settings page, allow you to get and update data for settings pages
- Show errors when updating non-existing fields

= 1.5.1 - 2023-08-16 =
- Fix cannot update user meta in a custom table (#18)

= 1.5.0 - 2023-07-29
- Remove fields in the Rest API responses with hide_from_rest = true
- Do not show MB User Profile fields in the Rest API responses

= 1.4.1 - 2023-05-17 =
- Fix not working with `_filter`.

= 1.4.0 - 2019-12-12 =
- Add support for comment meta. Requires MB Comment Meta plugin.

= 1.3.6 - 2019-11-07 =
- Fix term meta not available.

= 1.3.5 =
* Fixed not updating fields in custom tables.

= 1.3.4 =
* Make it safe to include into AIO plugin.
* Removed _state from returned value for groups.

= 1.3.3 =
* Fixed not updating user meta.

= 1.3.2 =
* Fixed custom fields for terms not saving for POST request. Props Mirza Pandzo.
* Fixed wrong key for `post_tag`. Props Mirza Pandzo.

= 1.3.1 =
* Removed fields that have no values from the response (divider, heading, etc.).

= 1.3 =
* Added fully support for terms and users. Both get and update meta values.

= 1.2 =
* Improvement: The update callback now can accept array of params

= 1.1 =
* Improvement: Add update callback
* Fix: Make sure the returned values of image/file fields are always array

= 1.0.1 =
* Fix: error when MB Term Meta is not installed

= 1.0.0 =
* Initial release

== Upgrade Notice ==
