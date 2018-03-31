<?php


use WildWolf\TFA\Admin;
use WildWolf\TFA\UserData;

class AJAXTest extends WP_Ajax_UnitTestCase
{
	protected static $admin_id = 0;

	public static function wpSetUpBeforeClass($factory)
	{
		self::$admin_id = $factory->user->create(['role' => 'administrator']);
	}

	public function setUp()
	{
		parent::setUp();
		wp_set_current_user(self::$admin_id);
		$inst = Admin::instance();
		if (false === has_action('admin_init', [$inst, 'admin_init'])) {
			$inst->init();
		}

		update_option('tfa', ['role_administrator' => true]);
	}

	public function testResetMethod()
	{
		$this->assertTrue(current_user_can('edit_users'));
		$data = new UserData(self::$admin_id);
		$this->assertTrue($data->is2FAEnabled());

		$data->setDeliveryMethod('third-party-apps');

		$_POST = [
			'action' => 'tfa-reset-method',
			'uid'    => self::$admin_id,
			'_ajax_nonce' => wp_create_nonce('tfa-reset_' . self::$admin_id),
		];

		$response = null;
		try {
			$this->_handleAjax($_POST['action']);
		}
		// The handler responds with a string,
		// therefore WPAjaxDieStopException is thrown,
		// not WPAjaxDieContinueException
		catch (WPAjaxDieStopException $e) {
			$response = $e->getMessage();
		}

		$this->assertEquals(1, did_action('wp_ajax_tfa-reset-method'));

		$this->assertEmpty($this->_last_response);
		$this->assertNotNull($response);

		$expected = __('By email', 'two-factor-auth');
		$this->assertEquals($expected, $response);

		$data = new UserData(self::$admin_id);
		$this->assertEquals('email', $data->getDeliveryMethod());
	}

	public function testResetMethodFail()
	{
		$uid = $this->factory()->user->create(['role' => 'subscriber']);
		wp_set_current_user($uid);
		$this->assertFalse(current_user_can('edit_users'));

		$data = new UserData(self::$admin_id);
		$this->assertTrue($data->is2FAEnabled());

		$data->setDeliveryMethod('third-party-apps');

		$_POST = [
			'action' => 'tfa-reset-method',
			'uid'    => self::$admin_id,
			'_ajax_nonce' => wp_create_nonce('tfa-reset_' . self::$admin_id),
		];

		$response = null;
		try {
			$this->_handleAjax($_POST['action']);
		}
		catch (WPAjaxDieStopException $e) {
			$response = $e->getMessage();
		}

		$this->assertEquals(1, did_action('wp_ajax_tfa-reset-method'));

		$this->assertEmpty($this->_last_response);
		$this->assertNull($response);

		$data = new UserData(self::$admin_id);
		$this->assertEquals('third-party-apps', $data->getDeliveryMethod());
	}

	public function testVerifyCode()
	{
		$uid  = wp_get_current_user()->ID;
		$data = new UserData($uid);
		$this->assertTrue($data->is2FAEnabled());

		$data->setDeliveryMethod('third-party-apps');
		$data->setHMAC('totp');
		$code = $data->generateOTP();

		$_POST = [
			'action'      => 'tfa-verify-code',
			'_ajax_nonce' => wp_create_nonce('tfa-verify_' . $uid),
			'code'        => $code,
		];

		$response = null;
		try {
			$this->_handleAjax($_POST['action']);
		}
		catch (WPAjaxDieStopException $e) {
			$response = $e->getMessage();
		}

		$this->assertEquals(1, did_action('wp_ajax_tfa-verify-code'));

		$this->assertEmpty($this->_last_response);
		$this->assertNotNull($response);

		$this->assertStringStartsWith('<strong class="verify-success">', $response);
	}

	public function testVerifyCodeFail()
	{
		$uid  = wp_get_current_user()->ID;
		$data = new UserData($uid);
		$this->assertTrue($data->is2FAEnabled());

		$data->setDeliveryMethod('third-party-apps');
		$_POST = [
			'action'      => 'tfa-verify-code',
			'_ajax_nonce' => wp_create_nonce('tfa-verify_' . $uid),
		];

		$response = null;
		try {
			$this->_handleAjax($_POST['action']);
		}
		catch (WPAjaxDieStopException $e) {
			$response = $e->getMessage();
		}

		$this->assertEquals(1, did_action('wp_ajax_tfa-verify-code'));

		$this->assertEmpty($this->_last_response);
		$this->assertNotNull($response);

		$this->assertStringStartsWith('<strong class="verify-failure">', $response);
	}
}
