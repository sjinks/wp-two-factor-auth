<?php

use WildWolf\TFA\UserData;
use WildWolf\TFA\Utils;

class UserDataTest extends WP_UnitTestCase
{
	private static $admin_id;

	public static function wpSetUpBeforeClass($factory)
	{
		self::$admin_id = $factory->user->create(['role' => 'administrator']);
		grant_super_admin(self::$admin_id);
	}

	public function test2FAEnabled()
	{
		$user_id = self::$admin_id;
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

	public function testSetDeliveryMethodException()
	{
		$this->expectException(InvalidArgumentException::class);
		$data = new UserData(self::$admin_id);
		$data->setDeliveryMethod('invalid');
	}

	public function testSetHMACMethodException()
	{
		$this->expectException(InvalidArgumentException::class);
		$data = new UserData(self::$admin_id);
		$data->setHMAC('invalid');
	}

	public function testResetPrivateKey()
	{
		$data = new UserData(self::$admin_id);
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
		$data  = new UserData(self::$admin_id);
		$codes = $data->generatePanicCodes();
		$this->assertNotEmpty($codes);
		$this->assertCount(UserData::$panicCodes, $codes);

		foreach ($codes as $code) {
			$this->assertEquals(UserData::$panicCodeLength, strlen($code));
		}
	}

	public function testGenerateOTP_1()
	{
		$data = new UserData(self::$admin_id);
		$data->setDeliveryMethod('email');
		$this->assertEquals('hotp',  $data->getHMAC());

		$expected = $data->generateOTP();
		$data     = new UserData(self::$admin_id);
		$counter  = $data->getCounter();
		$actual   = Utils::generateHOTP($data->getPrivateKey(), $counter, UserData::$otpLength, UserData::$defaultHash);

		$this->assertEquals($expected, $actual);
	}

	public function testGenerateOTP_2()
	{
		$data = new UserData(self::$admin_id);
		update_option('tfa', ['role_administrator' => true]);

		$this->assertTrue($data->is2FAEnabled());
		$this->assertEquals('email', $data->getDeliveryMethod());
		$this->assertEquals('hotp',  $data->getHMAC());

		$expected = $data->generateOTP();
		$data     = new UserData(self::$admin_id);

		$this->assertEquals('email', $data->getDeliveryMethod());
		$this->assertEquals('hotp',  $data->getHMAC());

		$counter  = $data->getCounter();
		$actual   = Utils::generateHOTP($data->getPrivateKey(), $counter, UserData::$otpLength, UserData::$defaultHash);

		$this->assertEquals($expected, $actual);
	}
}
