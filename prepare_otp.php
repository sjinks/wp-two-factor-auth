<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/wp-load.php');
include 'two-factor-login.php';

tfaPrepareTwoFactorAuth(array('log' => $_GET['user']), false);
?>