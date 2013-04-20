=== Two Factor Auth ===
Contributors: oskarhane
Donate link: http://oskarhane.com/
Tags: auth, two factor auth, login, security, authenticate
Requires at least: 3.0.1
Tested up to: 3.5.1
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add extra security to your WordPress login with this two factor auth. Users will be prompted with a page to enter a code that was emailed to them.

== Description ==

Add extra security to your WordPress login with this two factor auth. Users will be prompted with a page to enter a code that was emailed to them.
XMLRPC users will not be affected, this is just for the login to admin pages.

== Installation ==

Easy installation.

1. Upload `two-factor-auth.zip` through the 'Plugins' menu in WordPress
2. Activate the plugin through the 'Plugins' menu in WordPress

or

1. Search for 'Two Factor Auth' in the 'Plugins' menu in WordPress.
2. Click the 'Install' button. (Make sure you picks the right one)
3. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= If I can't reach my email account, can I bypass this plugin and log in anyway? =

Nope. If you have access to the database you can look in the table 'user_meta' for meta_key = 'two_factor_login_code' and look at the meta_vaule.


== Screenshots ==

= This is how the plugin behaves =

1. The normal login page is not affected
2. After the first login in page, this is shown and an email with the code is sent to the users email.


== Upgrade Notice ==
Just install.

== Changelog ==

= 1.0 =
* Initial release
