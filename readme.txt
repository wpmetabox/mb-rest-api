=== MB Rest API ===
Contributors: metabox, rilwis
Donate link: https://paypal.me/anhtnt
Tags: meta-box, custom fields, custom field, meta, meta-boxes, field, rest, rest api, api, wp api, wp rest api, json
Requires at least: 4.1
Tested up to: 4.7.3
Stable tag: 1.2
License: GPLv2 or later

Add Meta Box custom fields to the WordPress REST API responses.

== Description ==

[**MB Rest API**](https://metabox.io/plugins/mb-rest-api/) is an extension of the [Meta Box](https://metabox.io) plugin which pulls all custom fields' values (meta value) from posts, pages, custom post types, terms into the WordPress REST API responses under 'meta_box' key.

This plugin requires the [WordPress REST API v2](https://wordpress.org/plugins/rest-api/) and [Meta Box v4.8.5+](https://metabox.io).

### Plugin Links

- [Project Page](https://metabox.io/plugins/mb-rest-api/)
- [Github Repo](https://github.com/rilwis/mb-rest-api/)

See more extensions for the Meta Box plugin [here](https://metabox.io/plugins/).

== Installation ==

You need to install [Meta Box](https://metabox.io) plugin and WordPress REST API first

- Go to **Plugins | Add New** and search for **Meta Box**
- Click **Install Now** button to install the plugin
- After installing, click **Activate Plugin** to activate the plugin

Repeat the same process for **WP REST API** and **MB Rest API**.

== Frequently Asked Questions ==

== Screenshots ==

== Changelog ==

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
