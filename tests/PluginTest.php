<?php

use WildWolf\TFA\Plugin;
use WildWolf\TFA\UserData;
use WildWolf\TFA\Utils;

class PluginTest extends WP_UnitTestCase
{
	public function testInstance()
	{
		$i1 = Plugin::instance();
		$i2 = Plugin::instance();
		$this->assertSame($i1, $i2);
	}

	public function testInit()
	{
		$inst = Plugin::instance();
		$this->assertEquals(10,  has_action('login_form_login', [$inst, 'login_form_login']));
		$this->assertEquals(999, has_filter('authenticate',     [$inst, 'authenticate']));

		$settings = get_registered_settings();
		$this->assertArrayHasKey(Plugin::OPTIONS_KEY, $settings);

		$this->assertTrue(is_textdomain_loaded('wwtfa'));
	}

	public function testLoginInit()
	{
		$inst = Plugin::instance();
		remove_action('login_init', 'send_frame_options_header', 10);
		remove_action('login_init', 'wp_admin_headers', 10);
		do_action('login_form_login');
		$this->assertEquals(10, has_action('login_enqueue_scripts', [$inst, 'login_enqueue_scripts']));
		$this->assertEquals(10, has_action('login_form',            [$inst, 'login_form']));
	}

	public function testLoginEnqueueScripts()
	{
		remove_action('login_init', 'send_frame_options_header', 10);
		remove_action('login_init', 'wp_admin_headers', 10);
		do_action('login_form_login');
		do_action('login_enqueue_scripts');
		$this->assertTrue(wp_scripts()->query('tfa-ajax-request', 'enqueued'));
	}

	public function testLoginForm()
	{
		remove_action('login_init', 'send_frame_options_header', 10);
		remove_action('login_init', 'wp_admin_headers', 10);
		do_action('login_form_login');
		ob_start();
		do_action('login_form');
		$s = ob_get_clean();

		$needle = '<input type="text" name="two_factor_code" id="two_factor_auth"';
		$this->assertStringContainsString($needle, $s);
	}

	public function testBaseUrl()
	{
		$actual = Plugin::instance()->baseUrl();
		$prefix = 'http://' . WP_TESTS_DOMAIN . '/wp-content/plugins/';
		$suffix = '/wp-two-factor-auth/';
		$this->assertEquals($prefix, substr($actual, 0, strlen($prefix)));
		$this->assertEquals($suffix, substr($actual, -strlen($suffix)));
	}

	public function testAuthenticate()
	{
		unset($_POST['two_factor_code']);

		$result = apply_filters('authenticate', new WP_Error(), 'user', 'pass');
		$this->assertWPError($result);

		// Whether it is possible to authenticate with 2FA disabled
		$result = apply_filters('authenticate', null, 'admin', 'password');
		$this->assertInstanceOf(\WP_User::class, $result);

		// It should be impossible to authenticate without the code
		update_option('tfa', ['role_administrator' => true]);
		$result = apply_filters('authenticate', null, 'admin', 'password');
		$this->assertWPError($result);
		$this->assertSame(['authentication_failed'], $result->get_error_codes());

		// It should be possible to authenticate with the code
		$data = new UserData(1);
		$data->setDeliveryMethod('third-party-apps');
		$data->setHMAC('totp');
		$_POST['two_factor_code'] = $data->generateOTP();
		$result = apply_filters('authenticate', null, 'admin', 'password');
		$this->assertInstanceOf(\WP_User::class, $result);

		// But it should be impossible to use the same code twice
		$data = new UserData(1);
		$result = apply_filters('authenticate', null, 'admin', 'password');
		$this->assertWPError($result);
		$this->assertSame(['authentication_failed'], $result->get_error_codes());

		// It should be possible to use a panic code
		$panic = $data->getPanicCodes();
		$this->assertNotEmpty($panic);
		$_POST['two_factor_code'] = $panic[0];
		$result = apply_filters('authenticate', null, 'admin', 'password');
		$this->assertInstanceOf(\WP_User::class, $result);

		// but the same panic code cannot be reused
		$result = apply_filters('authenticate', null, 'admin', 'password');
		$this->assertWPError($result);
		$this->assertSame(['authentication_failed'], $result->get_error_codes());

		// and a wrong code won't be accepted
		$_POST['two_factor_code'] = 'AAAAAA';
		$result = apply_filters('authenticate', null, 'admin', 'password');
		$this->assertWPError($result);
		$this->assertSame(['authentication_failed'], $result->get_error_codes());

		// Authenticate using HOTP; must be able to resynchronize
		$data = new UserData(1);
		$data->setHMAC('hotp');
		$key = $data->getPrivateKey();
		$ctr = $data->getCounter();
		$_POST['two_factor_code'] = Utils::generateHOTP($key, $ctr + 5, 6);
		$result = apply_filters('authenticate', null, 'admin', 'password');
		$this->assertInstanceOf(\WP_User::class, $result);
		$data = new UserData(1);
		$new  = $data->getCounter();
		$this->assertEquals($ctr + 6, $new);

		// Should not be able to use previous codes
		$_POST['two_factor_code'] = Utils::generateHOTP($key, $ctr + 4, 6);
		$result = apply_filters('authenticate', null, 'admin', 'password');
		$this->assertWPError($result);
		$this->assertSame(['authentication_failed'], $result->get_error_codes());
	}
}
