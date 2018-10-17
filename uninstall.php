<?php
defined('WP_UNINSTALL_PLUGIN') || die();

delete_option('tfa');
delete_metadata('user', 0, 'tfa', '', true);
