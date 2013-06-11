=== Two Factor Auth ===
Contributors: oskarhane
Tags: auth, two factor auth, login, security, authenticate, password, hacking, security plugin, secure
Requires at least: 3.0.1
Tested up to: 3.5.1
Stable tag: 4.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Secure WordPress login with this two factor auth. Users will have to enter an One Time Password when they log in.

== Description ==

Secure WordPress login with this two factor auth. Users will have to enter an One Time Password when they log in.

= Why You Need This =
Users can have common or weak passwords that lets hackers/bots brute-force your WordPress site and gain access to your files and place malware there.
Just like happend not that long ago: [Article on TechCrunch](http://techcrunch.com/2013/04/12/hackers-point-large-botnet-at-wordpress-sites-to-steal-admin-passwords-and-gain-server-access/)

If all sites would have used this plugin, this would never happend.
It doesn't matter how weak your users passwords are, no one can gain access to your WordPress site 
without already having access to the users mobile phone or email inbox (depending on how the user gets his OTP).


= How Does It Work? =
This plugin uses the industry standard algorithm [TOTP](http://en.wikipedia.org/wiki/Time-based_One-time_Password_Algorithm) for creating One Time Passwords.
A OTP is valid for a certain time and after that a new code has to be entered.

You can now choose to use third party apps like [Google Authenticator](http://code.google.com/p/google-authenticator/) which is available for most mobile platforms. You can really use any 
third party app that supports TOTP that generates 6 digits OTP's. 
Or, as before, you can choose to get your One Time Passwords by email.

Since you have to enter a secret code to third party apps, email is the default way of delivering One Time Passwords. Your 
users will have to activate delivery by third party apps themselves.


= Easy To Use =
Just install this plugin and you're all set. There's really nothing more to it. 
If you want to use a third party app, goto Two Factor Auth in the admin menu and activate it and set up your app.
General settings can be found uner Settings -> Two Factor Auth in admin menu. Settings for each individual user 
can be found at the root level of the admin menu, in Two Factor Auth. 
A bit more work to get logged in, but a whole lot more secure!


= Is this really Two Factor Auth? =
Before version 3.0 this plugin had 'kind of' two factor auth where the OTP was delivered to an email address. 
Since version 3.0 you can have real two factor auth if you activate the Third Party Apps delivery type.

Read more about [what two factor auth means >>](http://oskarhane.com/two-factor-auth-explained/).

XMLRPC users will not be affected, this is just for the login to admin pages.

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

= Can I have real Two Factor Auth? =
Yes, since version 3.0 you can activate real Two Factor Auth by activating third party apps option under "Two Factor Auth" in admin menu. Don't forget to set up your app with the secret key.

= Oops, I lost my phone. What to do? =
Hopefully you saved the three Panic Codes when you activated third party apps. Use one of them.

= If I can't reach my email account, can I bypass this plugin and log in anyway? =
If you have Panic Codes, you can use them. Otherwise, no.

== Screenshots ==

1. The admin login page.
2. The admin login page when One Time Password button is clicked.
3. User settings page where they choose delivery type.
4. Admin settings page.

== Changelog ==
= 4.0.2 =
* Added PHP 5.3 check at activation
* Added mcrypt support check at activation
* Removed namespace in the Base32 class for better PHP support.

= 4.0.1 =
* Made the button on the login page blue so it's more clear that it's a button.
* Fixed some typos.

= 4.0 =
* All keys and panic codes are now encrypted in the database, as they should be.
* Panic codes are now based on your key.
* Users find their settings in root level of the admin menu.
* Only user roles with TFA activated see the admin menu item.
* Nicer/cleaner UI for users.
* Upgrade script for older installations. Must be executed by admin right after plugin update. Manually.
* Refactored all code and made it class based.

= 3.0.4 =
Fixed a bug where a OTP could be used twice.

= 3.0.3 =
* Added limitation to one login per time window (30 seconds).

= 3.0.2 =
* Fixed a bug where emails for some installations didn't work. Thanks to Matías at [http://www.periodicoellatino.es](http://www.periodicoellatino.es) for the help.
* Change to jQuery for making a POST request because of easier cross browser support.

= 3.0.1 =
Fixed so users get alerted of they don't enter a username before clicking the OTP button on the login page.

= 3.0 =
* Added TOTP as the OTP generator. Compatible with Google Authenticator and other third party auth apps.
* Added user settings page where they can activate usage of third party apps instead of email delivery of code.
* Added OTP field to standard login form instead of a middle page.
* Added Panic Codes which users can use if they loose their phone, change email etc.
* Removed second login screen.
* Updated admin settings page. Admins can now change user delivery of codes back to email if users loose their phone etc.

= 2.1 =
* Fixed warning message on admin settings page (thanks Joi)
* Hooks into a filter now so other plugins like Better WP Security, Limit Login Attemps etc. get a chance to log a failed login
* Error message are now displayed when the entered code was wrong
* Code length is not fixed any more. It can be 5 or 6 characters. Removed som easy to mix charaters as well (1 and I).

= 2.0 =
* Admin settings menu where you can choose which user roles that will have this activated. There will still be a second screen where the not activated user roles enter their password, but the One Time Password field is hidden.

= 1.1 =
* Removed password field from regular login page and added it to the second page where the user now enters both the emailed code and the password.

= 1.0 =
* Initial release

== Upgrade Notice ==
= 4.0.2 =
Added PHP version and mcrypt support checks at activation.

= 4.0.1 =
Button on login page is now blue to be more clear. Some types fixed as well.

= 4.0 =
You must run database change script after upgrade. The script encrypts the keys in the database. You will be prompted with a button to run it. Until you do, TFA won't be active.

= 3.0.4 =
Fixed a bug where a OTP could be used twice.

= 3.0.3 =
Added limitation to one login per time window.

= 3.0.2 =
Fixed a bug where som users email never got sent.

= 3.0.1 =
Fixed so users get alerted of they don't enter a username before clicking the OTP button on the login page.

= 3.0 =
Major changes. See changelog for more info.

= 2.1 =
A few bugs fixed and changed how the plugin behaves when a wrong code was entered.

= 2.0 =
Nothing special to consider. Just upgrade as usual.

= 1.1 =
Nothing special to consider. Just upgrade as usual.

= 1.0 =
Just released.