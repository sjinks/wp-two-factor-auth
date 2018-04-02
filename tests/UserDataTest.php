<?php

use PHPUnit\Framework\TestCase;
use WildWolf\TFA\UserData;

class UserDataTest extends TestCase
{
	public function test2FAEnabled()
	{
		$user_id = 1;
		$data    = new UserData(1);

		$this->assertFalse($data->is2FAEnabled());
		$this->assertFalse($data->is2FAForcefullyEnabled());

		$data->setDeliveryMethod('email');
		$this->assertTrue($data->is2FAEnabled());

		$data->setDeliveryMethod('');
		$this->assertFalse($data->is2FAEnabled());

		$actual = get_user_meta($user_id, 'tfa', true);
		$this->assertEquals('', $actual);

		update_option('tfa', ['role_administrator' => true]);
		$this->assertTrue($data->is2FAEnabled());
		$this->assertTrue($data->is2FAForcefullyEnabled());
		$this->assertEquals('email', $data->getDeliveryMethod());

		$data->setDeliveryMethod('');
		$this->assertTrue($data->is2FAEnabled());
		$this->assertEquals('email', $data->getDeliveryMethod());
	}
}