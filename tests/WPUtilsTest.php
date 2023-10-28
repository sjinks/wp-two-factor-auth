<?php

use WildWolf\TFA\UserData;
use WildWolf\TFA\WPUtils;

class WPUtilsTest extends WP_UnitTestCase
{
	public function setUp(): void
	{
		parent::setUp();
		reset_phpmailer_instance();
	}

	public function testGetUserByLoginOrEmail()
	{
		$login = 'admin';
		$email = 'admin@example.org';

		$user1 = WPUtils::getUserByLoginOrEmail($login);
		$user2 = WPUtils::getUserByLoginOrEmail($email);

		$this->assertEquals($user1, $user2);

		$user = WPUtils::getUserByLoginOrEmail('invalid');
		$this->assertFalse($user);
	}

	public function testPreAuth()
	{
		$login  = 'invalid';
		$result = WPUtils::preAuth($login);
		$this->assertTrue($result);
		$email = tests_retrieve_phpmailer_instance()->get_sent();
		$this->assertFalse($email);
		reset_phpmailer_instance();

		$login  = 'admin';
		$result = WPUtils::preAuth($login);
		$this->assertFalse($result);
		$email = tests_retrieve_phpmailer_instance()->get_sent();
		$this->assertFalse($email);
		reset_phpmailer_instance();

		update_option('tfa', ['role_administrator' => true, 'email_from' => 'no-reply@example.org', 'email_name' => 'OTP']);
		$admin  = WPUtils::getUserByLoginOrEmail($login);

		$result = WPUtils::preAuth('admin');
		$this->assertTrue($result);
		$email = tests_retrieve_phpmailer_instance()->get_sent();
		$this->assertTrue(is_object($email));
		$this->assertCount(1, $email->to);
		$this->assertEquals($admin->user_email, $email->to[0][0]);
		$this->assertEmpty($email->to[0][1]);
		$from = 'From: OTP <no-reply@example.org>'; // PHPMailer will convert "OTP" <no-reply@example.org> to OTP <no-reply@example.org>
		$this->assertTrue(false !== strpos($email->header, $from));
		reset_phpmailer_instance();

		$data = new UserData($admin);
		$data->setDeliveryMethod('third-party-apps');
		$result = WPUtils::preAuth('admin');
		$this->assertTrue($result);
		$email = tests_retrieve_phpmailer_instance()->get_sent();
		$this->assertFalse($email);
	}
}
