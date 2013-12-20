=== Simple Site Lockdown ===
Contributors: philipjohn
Tags: privacy, block, blocking, maintenance
Requires at least: 2.0.0
Tested up to: 3.8
Stable tag: trunk
License: WTFPL
License URI: http://www.wtfpl.net

Provides a really simple mechanism for locking down a site so that it's private to all but logged in admin users.

== Description ==

Need to make sure that a site is private for all but the administrators? Just activate this plugin.

There are no settings, no configuration - it just forces anyone that isn't an admin to go to the login page. They won't see anything of the site at all. Great for hiding sites really quickly.

== Installation ==

It's recommended that you install this plugin by searching for it in your WordPress Dashboard.

1. Go to Plugins > Add New
2. Search for "Simple Site Lockdown"
3. The first result should be this plugin, check the author is 'philipjohn' to double check
4. Click Install Now
5. Click Activate Plugin

Installing any other way carries the risk that it's from a source which could have injected malicious software into the plugin.

== Frequently Asked Questions ==

= Can I allow non-admins to view my site with this plugin? =

No, this is only for admins. If you want to allow non-admins in, I'd recommend using the more feature-rich [Maintenance Mode](http://wordpress.org/extend/plugins/maintenance-mode/) plugin instead.

== Changelog ==

= 1.1 =
* Changed license from GPL to WTFPL
* Prevented direct access
* Added readme

= 1.0 =
* Initial plugin