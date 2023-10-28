<?php

require_once __DIR__ . '/../vendor/autoload.php';

$_tests_dir = getenv('WP_TESTS_DIR');

if (!$_tests_dir) {
    $_tests_dir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- CLI
	throw new Exception( "Could not find {$_tests_dir}/includes/functions.php" ); // NOSONAR
}

// Give access to tests_add_filter() function.
/** @psalm-suppress UnresolvableInclude */
require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin()
{
	if (file_exists(WP_PLUGIN_DIR . '/two-factor-auth') && is_link(WP_PLUGIN_DIR . '/wp-two-factor-auth')) {
		unlink(WP_PLUGIN_DIR . '/wp-two-factor-auth');
	}

	symlink(dirname(dirname(__FILE__)), WP_PLUGIN_DIR . '/wp-two-factor-auth');
	wp_register_plugin_realpath(WP_PLUGIN_DIR . '/wp-two-factor-auth/plugin.php');
	require WP_PLUGIN_DIR . '/wp-two-factor-auth/plugin.php';
}

tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start up the WP testing environment.
/** @psalm-suppress UnresolvableInclude */
require_once $_tests_dir . '/includes/bootstrap.php';
