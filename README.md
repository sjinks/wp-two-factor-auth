wp-two-factor-auth
==================

Secure WordPress login with Two Factor Auth. Users will have to enter an One Time Password when they log in.

## Description

Secure WordPress login with this two factor auth. Users will have to enter an One Time Password when they log in.

### Why You Need This
Users can have common or weak passwords that lets hackers/bots brute-force your WordPress site and gain access to your files and place malware there.
Just like happend not that long ago: [Article on TechCrunch](http://techcrunch.com/2013/04/12/hackers-point-large-botnet-at-wordpress-sites-to-steal-admin-passwords-and-gain-server-access/)

If all sites would have used this plugin, this would never happend.
It doesn't matter how weak your users passwords are, no one can gain access to your WordPress site
without already having access to the users mobile phone or email inbox (depending on how the user gets his OTP).


### How Does It Work?
This plugin uses the industry standard algorithm [TOTP](http://en.wikipedia.org/wiki/Time-based_One-time_Password_Algorithm) or [HOTP](http://en.wikipedia.org/wiki/HMAC-based_One-time_Password_Algorithm) for creating One Time Passwords.
A OTP is valid for a certain time and after that a new code has to be entered.

You can now choose to use third party apps like [Google Authenticator](http://code.google.com/p/google-authenticator/) which is available for most mobile platforms. You can really use any
third party app that supports TOTP/HOTP that generates 6 digits OTP's.
Or, as before, you can choose to get your One Time Passwords by email.

Since you have to enter a secret code to third party apps, email is the default way of delivering One Time Passwords. Your
users will have to activate delivery by third party apps themselves.


### Easy To Use
Just install this plugin and you're all set. There's really nothing more to it.
If you want to use a third party app, goto Two Factor Auth in the admin menu and activate it and set up your app.
General settings can be found uner Settings -> Two Factor Auth in admin menu. Settings for each individual user
can be found at the root level of the admin menu, in Two Factor Auth.
A bit more work to get logged in, but a whole lot more secure!

If you use WooCommerce or other plugins that make custom login forms, you will not be able to login through those anymore.
I will be adding a plugin that puts a One Time Password field to WooCommerce. If you use some other plugin that needs
support for this, let me know in the support forum.


### TOTP or HOTP
Which algorithm you and your users choose doesn't really matter. The time based TOTP is a bit more secure since a One Time
Password is valid only for a certain amount of time. But this requires the server time to be in sync the clients time (if
the OTP isn't delivered by email). This is often hard to do with embedded clients and the event based HOTP is then a better choice. 
If you have a somewhat slow email server and have chosen email delivery, you might not get the TOTP in time.

Conslusion: Choose which ever you want. TOTP is a little bit safer since OTP:s only are valid for a short period.

Note that email delivery users always uses the site default algorithm, which you can set on the settings page. Third party
apps users can choose which one they want.


### Is this really Two Factor Auth?
Before version 3.0 this plugin had 'kind of' two factor auth where the OTP was delivered to an email address.
Since version 3.0 you can have real two factor auth if you activate the Third Party Apps delivery type.

Read more about [what two factor auth means >>](http://oskarhane.com/two-factor-auth-explained/).

See http://oskarhane.com/plugin-two-factor-auth-for-wordpress/ for more info.


## Installation

Easy installation.

This plugin requires PHP version 5.3 or higher and support for [PHP mcrypt](http://www.php.net/manual/en/mcrypt.installation.php).

1. Upload *two-factor-auth.zip* through the 'Plugins' menu in WordPress
2. Activate the plugin through the 'Plugins' menu in WordPress

or

1. Search for 'Two Factor Auth' in the 'Plugins' menu in WordPress.
2. Click the 'Install' button. (Make sure you picks the right one)
3. Activate the plugin through the 'Plugins' menu in WordPress

## Frequently Asked Questions

### Can I have real Two Factor Auth?
Yes, since version 3.0 you can activate real Two Factor Auth by activating third party apps option under "Two Factor Auth" in admin menu. Don't forget to set up your app with the secret key.

### Oops, I lost my phone. What to do?
Hopefully you saved the three Panic Codes when you activated third party apps. Use one of them.

### If I can't reach my email account, can I bypass this plugin and log in anyway?
If you have Panic Codes, you can use them. Otherwise, no.


Just released.
