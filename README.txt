=== Public Media Platform ===
Contributors: publicmediaplatform, innlabs
Tags: pmp,pubmedia,publicmediaplatform,apm,npr,pri,prx,pbs,media,news
Requires at least: 4.1
Tested up to: 4.9
Requires PHP: 5.5
Stable tag: 0.2.11
License: MIT
License URI: https://github.com/publicmediaplatform/pmp-wordpress/blob/master/LICENSE

Integrate your site's content with the Public Media Platform.

== Description ==

The [Public Media Platform](http://publicmediaplatform.org) is a cross-media distribution system for digital content (audio, video, stories, and images).  You can use it both to bring additional public media produced content to your site, and to expand the reach of your content to external web and mobile destinations.

The PMP was founded by a collaboration of APM, NPR, PBS, PRI and PRX, with the goal of bringing public media content to a wider audience.  It contains more than 300K pieces of digital content from our founding partners, and is growing every day.  For more information on what's available, feel free to [search the PMP](https://support.pmp.io/search?profile=story&has=image).

Built by [INN Labs](https://labs.inn.org/).

= Current plugin features: =

* **Search** Find available content via filters and full-text search
* **Saved Searches** The ability to save a search for later
* **Pull** Create draft or published Posts from any PMP search result
* **Automated Pull** Publish PMP content automatically while you're away
* **Images** Set featured images from PMP content metadata
* **Audio** Embed audio players when available for PMP content
* **Push** Send a Post to the PMP for further distribution
* **Permissions** Restrict distribution of your content to a whitelist of PMP users

= In the works: =

* **Video** Embed video players for PMP content
* **And More** Keep checking [the Github project](https://github.com/publicmediaplatform/pmp-wordpress) for upcoming features and fixes.

== Installation ==

Installation through WordPress.org's plugin repository:

1. Register for your PMP account at https://support.pmp.io/register
2. Install the Public Media Platform plugin via the Wordpress.org plugin directory
3. Activate the plugin
4. Navigate to the Admin -> Public Media Platform -> Settings page
5. Enter your PMP Credentials
6. Away you go!

For manual installation instructions, see [these instructions on manual installation](https://github.com/npr/pmp-wordpress-plugin/blob/master/docs/installation-development.md).

For more information on plugin setup and usage, see the [PMP-Wordpress Github project](https://github.com/npr/pmp-wordpress-plugin#pmp-wordpress-plugin).

For information on the PMP in general, head to [support.pmp.io](https://support.pmp.io).

== Frequently Asked Questions ==

= Where can I learn more about the plugin's functionality? =

See the [documentation on Github](https://github.com/publicmediaplatform/pmp-wordpress).

== Changelog ==

= 0.2.11 =

- Adds support for PHP 7.
- Removes support for PHP versions before 5.5, and sets a `Requires PHP: 5.5` flag in `readme.txt`.
- Add support for WordPress versions up to 4.9.
- Drops support for WordPress versions before 4.1.
- Updates to [PMP PHP SDK version 2.0.2](https://github.com/npr/pmp-php-sdk/releases/tag/v2.0.2). (Pull request [#143](https://github.com/npr/pmp-wordpress-plugin/pull/143) for issue [#142](https://github.com/npr/pmp-wordpress-plugin/issues/142)) and [#145](https://github.com/npr/pmp-wordpress-plugin/issues/145). Changes affecting this plugin are as follows:
	- Upgrades to Guzzle 6
	- Adds support for PHP 7.0 and later
	- Removes support for PHP versions before 5.5
	- Provides a new-and-updated PHAR file to bundle in this plugin
- Allows sites to unset credentials in the plugin admin. (Pull request [#146](https://github.com/npr/pmp-wordpress-plugin/pull/146) for issue [#130](https://github.com/npr/pmp-wordpress-plugin/issues/130).)
- For PHP 7 compatibility, changes how the `PMP_NOTIFICATIONS_SECRET` constant is defined in order to prevent an "invalid salt" error causing white-screen errors. (Pull request [#144](https://github.com/npr/pmp-wordpress-plugin/pull/144) for [issue #133](https://github.com/npr/pmp-wordpress-plugin/issues/133))
- Catches a Guzzle runtime exception that prevents the plugin from working properly, and warn users about the cause. ([#134](https://github.com/npr/pmp-wordpress-plugin/pull/134)).
- Changes the default environment of the plugin to the sandbox environment: new installations of the plugin will not automatically use production credentials but must be configured to. ([#132](https://github.com/npr/pmp-wordpress-plugin/pull/132)).
- Improved test coverage. (Pull request [#131](https://github.com/npr/pmp-wordpress-plugin/pull/131)).
- Improved documentation for installation through channels other than WordPress.org.

= 0.2.10 =

- version number bump to fix the wordpress.org plugin listing

= 0.2.9 =

- Allow user to set multiple groups for a story or document

= 0.2.8 =

- Better support for attachment media (push/pull for multiple images and audio files)
- Fix bug with saved-searches missing content
- Fix bug with images not showing up consistently

= 0.2.7 =

- Query interface enhancements
- Stay on search page when pulling content
- Ability to use PMP push-notifications

= 0.2.6 =

- Fix a bug causing duplicate saved-searches

= 0.2.5 =

- Fixes for saved search labeling and duplication
- Non-uncategorized saved searches
- Ability to unset group/series/property on a Post
- More mega-box

= 0.2.4 =

- Saved searches!
- Categories for saved searches
- PMP Content meta box
- Fix image crops for pushed Posts
- Prevent pulling duplicate PMP stories

= 0.2.3 =

- Better styling on the edit post page
- Fix hook priority conflicts with other plugins

= 0.2.2 =

- Make deploys to the official Wordpress.org plugin repo to work more better

= 0.2.1 =

- Ability to build non-PHAR version of the plugin (use composer to install dependencies)
- Makefile for helping to run unit tests

= 0.2.0 =

- Group and permissions administration page
- Series administration page
- Property management page
- Ability to push posts and featured images to PMP

= 0.1.0 =

Initial release including pull functionality.
