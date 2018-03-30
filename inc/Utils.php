<?php
namespace WildWolf\TFA;

use WildWolf\OTP;

/**
 * Framework/CMS-agnostic helper functions
 */
abstract class Utils
{
	public static function generateHOTP(string $key, int $counter, int $len, string $algo = 'sha1') : string
	{
		$code = OTP::generateByCounter($key, $counter, $algo);
		return OTP::asOTP($code, $len);
	}

	public static function generateTOTP(string $key, int $window, int $len, string $algo = 'sha1') : string
	{
		$code = OTP::generateByTime($key, $window, \time(), $algo);
		return OTP::asOTP($code, $len);
	}

	public static function encrypt(string $s, string $key) : string
	{
		$iv = \openssl_random_pseudo_bytes(\openssl_cipher_iv_length('aes-256-cbc'));
		return $iv . \openssl_encrypt($s, 'aes-256-cbc', $key, \OPENSSL_RAW_DATA, $iv);
	}

	public static function decrypt(string $s, string $key) : string
	{
		$len = \openssl_cipher_iv_length('aes-256-cbc');
		$iv  = \substr($s, 0, $len);
		$enc = \substr($s, $len);
		return \openssl_decrypt($enc, 'aes-256-cbc', $key, \OPENSSL_RAW_DATA, $iv);
	}

	public static function randomBase32String(int $len) : string
	{
		static $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
		$result = '';

		while ($len > 0) {
			$result .= $chars[\mt_rand(0, 31)];
			--$len;
		}

		return $result;
	}

	public static function generatePanicCode(int $len) : string
	{
		$code = '';
		for ($i=0; $i<$len; ++$i) {
			$code .= \mt_rand(0, 9);
		}

		return $code;
	}
}
