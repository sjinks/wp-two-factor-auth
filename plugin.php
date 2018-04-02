<?php
/*
 Plugin Name: WP Two Factor Auth
 Plugin URI: https://github.com/sjinks/wp-two-factor-auth
 Description: HOTP/TOTP based two factor authentication for WordPress
 Author: Volodymyr Kolesnykov
 Version: 5.0
 License: MIT
 */

defined('ABSPATH') || die();

if (defined('VENDOR_PATH')) {
	require VENDOR_PATH . '/vendor/autoload.php';
}
elseif (file_exists(__DIR__ . '/vendor/autoload.php')) {
	require __DIR__ . '/vendor/autoload.php';
}
elseif (file_exists(ABSPATH . 'vendor/autoload.php')) {
	require ABSPATH . 'vendor/autoload.php';
}

WildWolf\TFA\Plugin::instance();
