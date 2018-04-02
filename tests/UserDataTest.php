<?php

use PHPUnit\Framework\TestCase;
use WildWolf\TFA\UserData;

class UserDataTest extends TestCase
{
	public function test2FAEnabled()
	{
		$user_id = 1;
		$data    = new UserData($user_id);

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

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testSetDeliveryMethodException()
	{
		$data = new UserData(1);
		$data->setDeliveryMethod('invalid');
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testSetHMACMethodException()
	{
		$data = new UserData(1);
		$data->setHMAC('invalid');
	}

	public function testResetPrivateKey()
	{
		$data = new UserData(1);
		$data->setDeliveryMethod('email');

		$pk1 = $data->getPrivateKey();
		$this->assertEquals('hotp', $data->getHMAC());
		$this->assertEmpty($data->getPanicCodes());
		$this->assertEmpty($data->getUsedCodes());

		$data->resetPrivateKey();
		$pk2 = $data->getPrivateKey();

		$this->assertNotEquals($pk1, $pk2);
	}

	public function testGeneratePanicCodes()
	{
		$data  = new UserData(1);
		$codes = $data->generatePanicCodes();
		$this->assertNotEmpty($codes);
		$this->assertCount(UserData::$panicCodes, $codes);

		foreach ($codes as $code) {
			$this->assertEquals(UserData::$panicCodeLength, strlen($code));
		}
	}
}
