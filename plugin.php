<?php
/*
 Plugin Name: WP Two Factor Auth
 Plugin URI: https://github.com/sjinks/wp-two-factor-auth
 Description: Secure your WordPress login with two factor auth. Users will be prompted with a page to enter a One Time Password when they login.
 Author: Oskar Hane, Volodymyr Kolesnykov
 Version: 5.0
 License: GPLv2 or later
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
