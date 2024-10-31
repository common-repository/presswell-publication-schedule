=== Presswell Publication Schedule ===
Contributors: presswell, benplum
Tags: publish, posts, schedule, auto-schedule, future
Requires at least: 4.0
Tested up to: 5.2
Stable tag: trunk
License: GNU General Public License v2.0 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Simple and precise post publishing control for WordPress based on day of the week and time of day.

== Description ==

Presswell Publication Schedule adds precise, automated control over how often new posts are published, and is the perfect solution for ensuring a consistent editorial calendar. Simply configure the days and times when posts should be published and new posts will be automatically scheduled for the next available time slot.

**Features**

* Automatically schedule posts based on simple day and time rules
* Ability to override scheduled date for Admins and Editors
* 'Publish Now' actions for Admins and Editors

== Installation ==

Install using the WordPress plugin installer, or manually as [outlined in the Codex](https://codex.wordpress.org/Managing_Plugins).

**Configuration**

Once activated, navigate to *Settings* -> *Publication Schedule* to set the active days and times for posts to be published.

== Frequently Asked Questions ==

= Why do I need to schedule my posts? =

You may want to avoid having new content publish during times of low user engagement, such as overnight or weekends. The plugin will ensure your editorial calendar is consistent and predictable.

= How are publish dates and times determined? =

When a new post is saved, the plugin will check all previously scheduled posts against the publication schedule and update the new post's publish date to the next available time slot.

= Can I set a specific publish date and time? =

Yes! Admins and Editors can use the 'Publish Now' to publish a post immediately, or manually override the scheduled date on the Post and Quick Edit screens. Authors do not have the ability to set a custom date.

= What happens to a time slot if I manually publish (or override) a scheduled post? =

The time slot that the post was originally scheduled for is freed and becomes available for the next new post. Posts already scheduled will not be modified.

== Screenshots ==

1. Plugin settings screen
2. Schedule notice on post edit screen
3. Manual override on post edit screen
4. Manual override on quick edit screen
5. Publish Now action on post list

== Changelog ==

= 1.0.2 =
* Admin settings link and style tweaks.

= 1.0.1 =
* UI bug fixes.

= 1.0.0 =
* First public release.
