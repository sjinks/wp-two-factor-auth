<?php
namespace WildWolf\TFA;

use WildWolf\OTP;

abstract class OTPVerifier
{
	public static function verifyRelaxed(UserData $data, string $code) : bool
	{
		\assert('email' !== $data->getDeliveryMethod());

		if (\strlen($code) === UserData::$otpLength) {
			return self::doVerifyCode($data, $code, true);
		}

		return false;
	}

	public static function verify(UserData $data, string $code) : bool
	{
		if ('email' === $data->getDeliveryMethod()) {
			return self::verifyEmailOTP($data, $code);
		}

		// Check the code only if its length matches
		if (\strlen($code) === UserData::$otpLength) {
			// Disallow recently entered codes
			if (self::isCodeUsed($data, $code)) {
				return false;
			}

			if (self::doVerifyCode($data, $code, false)) {
				return true;
			}
		}

		return self::checkPanicCode($data, $code);
	}

	private static function doVerifyCode(UserData $data, string $code, bool $relaxed) : bool
	{
		return ('hotp' === $data->getHMAC())
			? self::verifyHOTP($data, $code)
			: self::verifyTOTP($data, $code, $relaxed)
		;
	}

	private static function verifyHOTP(UserData $data, string $code) : bool
	{
		$counter = $data->getCounter();
		$codes   = OTP::generateMultipleByCounter($data->getPrivateKey(), $counter, UserData::$countersToCheck, UserData::$defaultHash);
		foreach ($codes as $idx => $c) {
			if (OTP::asOTP($c, UserData::$otpLength) === $code) {
				$data->setCounter($counter + $idx + 1);
				return true;
			}
		}

		return false;
	}

	private static function verifyTOTP(UserData $data, string $code, bool $relaxed) : bool
	{
		$codes = OTP::generateByTimeWindow($data->getPrivateKey(), UserData::$timeWindow, -UserData::$windowsToCheck, 0, null, UserData::$defaultHash);
		foreach ($codes as $idx => $c) {
			if (OTP::asOTP($c, UserData::$otpLength) === $code) {
				if (!$relaxed) {
					$data->setUsedCodes(\array_slice($codes, 0, $idx + 1));
				}

				return true;
			}
		}

		return false;
	}

	private static function verifyEmailOTP(UserData $data, string $code) : bool
	{
		$counter = $data->getCounter();
		$otp     = Utils::generateHOTP($data->getPrivateKey(), $counter, UserData::$otpLength, UserData::$defaultHash);
		if ($code === $otp) {
			// https://tools.ietf.org/html/rfc4226#section-7.2
			// If the value received by the authentication server matches the value calculated by the client,
			// then the HOTP value is validated. In this case, the server increments the counter value by one.
			$data->setCounter($counter + 1);
			return true;
		}

		return false;
	}

	private static function isCodeUsed(UserData $data, string $code) : bool
	{
		$used = $data->getUsedCodes();
		foreach ($used as $c) {
			if (OTP::asOTP($c, UserData::$otpLength) === $code) {
				return true;
			}
		}

		return false;
	}

	private static function checkPanicCode(UserData $data, string $code) : bool
	{
		if (\strlen($code) === UserData::$panicCodeLength) {
			$codes = $data->getPanicCodes();
			$idx   = \array_search($code, $codes);
			if (false !== $idx) {
				\array_splice($codes, (int)$idx, 1);
				$data->setPanicCodes($codes);
				return true;
			}
		}

		return false;
	}
}
