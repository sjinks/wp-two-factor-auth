=== Two Factor Auth ===
Contributors: oskarhane
Tags: auth, two factor auth, login, security, authenticate, password
Requires at least: 3.0.1
Tested up to: 3.5.1
Stable tag: 2.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Secure your WordPress login with this two factor auth. Users will be prompted with a page to enter a one time code that was emailed to them.

== Description ==


= Why You Need This =
Users can have common or weak passwords that lets hackers/bots brute-force your WordPress site and gain access to your files and place malware there.
Just like happend not that long ago: [Article on TechCrunch](http://techcrunch.com/2013/04/12/hackers-point-large-botnet-at-wordpress-sites-to-steal-admin-passwords-and-gain-server-access/)

If all sites would have used this plugin, this would never happend.
It doesn't matter how weak your users passwords are, no one can gain access to your WordPress site without already having access to the user accounts email inbox as well.


= How Does It Work? =
The technology behind this is simple. It uses one time codes that are email to you when you're about to log in.
That means that no one can just guess/sniff/break your real password and gain access, they'll need to guess this one time code as well. 
And they only have one shot. After the first attempt, a new one time code is generated and emailed to you.


= Easy To Use =
Just install this plugin and you're all set. There's really nothing more to it. 
When you are about to login, a one time password is sent to your email account and you just enter it on the login in page.
A bit more work to get logged in, but a whole lot more secure!


= Is this really Two Factor Auth? =
Well, it depends on how you define ["Something the user has"](http://en.wikipedia.org/wiki/Multi-factor_authentication#Possession_factors:_.22something_the_user_has.22) 
The principle as getting a text message to your phone and getting an email is the same, with the exception that you can get access to a mail account from anywhere but you have to actually have the physical phone to read a text message.
Having to have physical access to something is, of course, even more secure. It also makes it more difficult for users to register, verify phone numer, change phone number etc.

I, for sure, find this email solution secure and no automated login attemps will ever get passed it.


XMLRPC users will not be affected, this is just for the login to admin pages.

Notice that right now the "Remember me" cookie overrides this which means that you will still be auto logged in if you click that checkbox.

See http://oskarhane.com/plugin-two-factor-auth-for-wordpress/ for more info.


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
If you have access to the databse you can look for the code there. Otherwise, no.

== Screenshots ==

1. The normal login page where the password field is removed. Don't mind the language on this screenshot, the plugin is all in english.
2. After the first login in page, this is shown and an email with the code is sent to the users email.
3. Admin settings page. Again, the button is localized so don't mind the language.

== Changelog ==

= 2.1 =
* Fixed warning message on admin settings page
* Hooks into a filter now so other plugins like Better WP Security, Limit Login Attemps etc. get a chance to log a failed login
* Error message are now displayed when the entered code was wrong
* Code length is not fixed any more. It can be 5 or 6 characters. Removed som easy to mix charaters as well (1 and I).

= 2.0 =
* Admin settings menu where you can choose which user roles that will have this activated. There will still be a second screen where the not activated user roles enter their password, but the one time code field is hidden.

= 1.1 =
* Removed password field from regular login page and added it to the second page where the user now enters both the emailed code and the password.

= 1.0 =
* Initial release

== Upgrade Notice ==

= 2.1 =
Just click upgrade.

= 2.0 =
Nothing special to consider. Just upgrade as usual.

= 1.1 =
Nothing special to consider. Just upgrade as usual.

= 1.0 =
Just released.
