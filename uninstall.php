<?php
defined('WP_UNINSTALL_PLUGIN') || die();

global $wpdb;
delete_option('tfa');
$wpdb->delete($wpdb->usermeta, ['meta_key' => 'tfa']);
