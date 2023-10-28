<?php

use WildWolf\TFA\Utils;
use Yoast\PHPUnitPolyfills\Polyfills\AssertionRenames;

class UtilsTest extends WP_UnitTestCase
{
	public function generatePanicCodeDataProvider()
	{
		return [
			[-1, 0],
			[ 0, 0],
			[ 6, 6]
		];
	}

	/**
	 * @dataProvider generatePanicCodeDataProvider
	 */
	public function testGeneratePanicCode($len, $expectedLen)
	{
		$code = Utils::generatePanicCode($len);
		$this->assertEquals($expectedLen, strlen($code));
		$this->assertMatchesRegularExpression('/^[0-9]*+$/', $code);
	}

	public function randomBase32StringDataProvider()
	{
		return [
			[ -1,   0],
			[  0,   0],
			[  6,   6],
			[100, 100]
		];
	}

	/**
	 * @dataProvider randomBase32StringDataProvider
	 */
	public function testRandomBase32String($len, $expectedLen)
	{
		$s = Utils::randomBase32String($len);
		$this->assertEquals($expectedLen, strlen($s));
		$this->assertMatchesRegularExpression('/^[ABCDEFGHIJKLMNOPQRSTUVWXYZ234567]*+$/', $s);
	}

	public function encryptionDataProvider()
	{
		return [
			['test'],
			[''],
			['1'],
			['12'],
			['123'],
			['1234'],
			['12345'],
			['123456'],
			['1234567'],
			['12345678'],
			['123456789'],
			['123456789A'],
			['123456789AB'],
			['123456789ABC'],
			['123456789ABCD'],
			['123456789ABCDE'],
			['123456789ABCDEF'],
			['0123456789ABCDEF'],
			['0123456789ABCDEF0'],
		];
	}

	/**
	 * @dataProvider encryptionDataProvider
	 */
	public function testEncryption($src)
	{
		$key = 'key';
		$enc = Utils::encrypt($src, $key);
		$dec = Utils::decrypt($enc, $key);
		$this->assertEquals($src, $dec);
	}

	// RFC 6238 test data
	public function otpDataProvider()
	{
		static $seed20 = '12345678901234567890';
		static $seed32 = '12345678901234567890123456789012';
		static $seed64 = '1234567890123456789012345678901234567890123456789012345678901234';

		return [
			[$seed20,          59, 0x00000001, 'sha1',   '94287082'],
			[$seed32,          59, 0x00000001, 'sha256', '46119246'],
			[$seed64,          59, 0x00000001, 'sha512', '90693936'],

			[$seed20,  1111111109, 0x023523EC, 'sha1',   '07081804'],
			[$seed32,  1111111109, 0x023523EC, 'sha256', '68084774'],
			[$seed64,  1111111109, 0x023523EC, 'sha512', '25091201'],

			[$seed20,  1111111111, 0x023523ED, 'sha1',   '14050471'],
			[$seed32,  1111111111, 0x023523ED, 'sha256', '67062674'],
			[$seed64,  1111111111, 0x023523ED, 'sha512', '99943326'],

			[$seed20,  1234567890, 0x0273EF07, 'sha1',   '89005924'],
			[$seed32,  1234567890, 0x0273EF07, 'sha256', '91819424'],
			[$seed64,  1234567890, 0x0273EF07, 'sha512', '93441116'],

			[$seed20,  2000000000, 0x03F940AA, 'sha1',   '69279037'],
			[$seed32,  2000000000, 0x03F940AA, 'sha256', '90698825'],
			[$seed64,  2000000000, 0x03F940AA, 'sha512', '38618901'],

			[$seed20, 20000000000, 0x27BC86AA, 'sha1',   '65353130'],
			[$seed32, 20000000000, 0x27BC86AA, 'sha256', '77737706'],
			[$seed64, 20000000000, 0x27BC86AA, 'sha512', '47863826'],
		];
	}

	/**
	 * @dataProvider otpDataProvider
	 */
	public function testOTP($seed, $time, $step, $algo, $expected)
	{
		$actual = Utils::generateTOTP($seed, 30, 8, $algo, $time);
		$this->assertEquals($expected, $actual);

		$actual = Utils::generateHOTP($seed, $step, 8, $algo);
		$this->assertEquals($expected, $actual);
	}
}
