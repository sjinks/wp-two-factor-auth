=== Two Factor Auth ===
Contributors: oskarhane
Tags: auth, two factor auth, login, security, authenticate, password
Requires at least: 3.0.1
Tested up to: 3.5.1
Stable tag: 1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add extra security to your WordPress login with this two factor auth. Users will be prompted with a page to enter a one time code that was emailed to them.

== Description ==


= Why you need this =
Users can have common or weak passwords that lets hackers/bots brute-force your WordPress site and gain access to your files and place malware there.
Just like happend not that long ago: [Article on TechCrunch](http://techcrunch.com/2013/04/12/hackers-point-large-botnet-at-wordpress-sites-to-steal-admin-passwords-and-gain-server-access/)

If all sites would have used this plugin, this would never happend.
It doesn't matter how weak your users passwords are, noone can gain access to your WordPress site without already having access to the user accounts email inbox as well.


= How Does It Work =
The technology behind this is simple. It uses one time codes that are email to you when you're about to log in.
That means that noone can just guess/sniff/break your real password and gain access, they'll need to guess this one time code as well. 
And they only have one shot. After the first attempt, a new one time code is generated and emailed to you.


= Easy To Use =
Just install this plugin and you're all set. There's really nothing more to it. 
When you are about to login, a one time password is sent to your email account and you just enter it on the login in page.
A bit more work to get logged in, but a whole lot more secure!

XMLRPC users will not be affected, this is just for the login to admin pages.

Notice that right now the "Remember me" cookie overrides this which means that you will still be auto logged in if you click that checkbox.

See http://oskarhane.com/plugin-two-factor-auth-for-wordpress/ for more info.

= Coming Soon =
* Option to override "Remember me" cookie.
* Choose which user roles that will have this plugin activated.

== Installation ==

Easy installation.

1. Upload *two-factor-auth.zip* through the 'Plugins' menu in WordPress
2. Activate the plugin through the 'Plugins' menu in WordPress

or

1. Search for 'Two Factor Auth' in the 'Plugins' menu in WordPress.
2. Click the 'Install' button. (Make sure you picks the right one)
3. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= If I can't reach my email account, can I bypass this plugin and log in anyway? =
Nope. If you have access to the database you can look in the table *user_meta* for meta_key = *two_factor_login_code* and look at the meta_vaule.

== Screenshots ==

1. The normal login page where the password field is removed.
2. After the first login in page, this is shown and an email with the code is sent to the users email.

== Changelog ==

= 1.1 =
* Removed password field from regular login page and added it to the second page where the user now enters both the emailed code and the password.

= 1.0 =
* Initial release

== Upgrade Notice ==

= 1.1 =
Nothing special to consider. Just upgrade as usual.

= 1.0 =
Just released.
