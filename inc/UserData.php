<?php
namespace WildWolf\TFA;

use WildWolf\OTP;


class UserData
{
	/**
	 * @var integer
	 */
	private static $timeWindow       = 30;
	/**
	 * @var integer
	 */
	private static $windowsToCheck   =  2;
	private static $countersToCheck  = 20;
	private static $otpLength        =  6;
	private static $panicCodeLength  =  8;
	private static $panicCodes       = 10;
	/**
	 * @var string
	 */
	private static $defaultHmac      = 'totp';
	/**
	 * @var string
	 */
	private static $defaultHash      = 'sha1';

	/**
	 * @var string
	 */
	private $salt;

	/**
	 * @var int
	 */
	private $id;

	/**
	 * @var string
	 */
	private $secret = '';

	/**
	 * @var int
	 */
	private $counter = 0;

	/**
	 * @var string
	 */
	private $hmac = '';

	/**
	 * @var array
	 */
	private $panic = [];

	/**
	 * @var array
	 */
	private $used = [];

	/**
	 * @var string
	 */
	private $method = '';

	/**
	 * @param int|\WP_User $user
	 */
	public function __construct($user)
	{
		if ($user instanceof \WP_User) {
			$user = $user->ID;
		}

		$this->id = (int)$user;
		$data     = (string)\get_user_meta($user, 'tfa', true);
		$this->decodeData($data);
	}

	private function password() : string
	{
		return \hash_pbkdf2(
			'sha256',
			\hash('sha256', \AUTH_KEY . $this->id, true),
			$this->salt,
			1000,
			32,
			true
		);
	}

	private function decodeData(string $data)
	{
		$data = \base64_decode($data);
		if (\strlen($data) < 64) {
			$this->salt = \openssl_random_pseudo_bytes(64);
			return;
		}

		$this->salt = \substr($data, 0, 64);
		$payload    = \substr($data, 64);
		$password   = $this->password();

		$payload    = Utils::decrypt($payload, $password);
		$data       = \unserialize($payload);

		$this->secret  = $data['secret']  ?? '';
		$this->counter = $data['counter'] ?? 0;
		$this->hmac    = $data['hmac']    ?? '';
		$this->panic   = $data['panic']   ?? [];
		$this->used    = $data['used']    ?? [];
		$this->method  = $data['method']  ?? '';
	}

	private function save()
	{
		$data = [];
		if ($this->secret && $this->hmac) {
			$data['secret'] = $this->secret;
			$data['hmac']   = $this->hmac;

			if ($this->counter) {
				$data['counter'] = $this->counter;
			}

			if ($this->panic) {
				$data['panic'] = $this->panic;
			}

			if ($this->used) {
				$data['used'] = $this->used;
			}
		}

		if ($this->method) {
			$data['method'] = $this->method;
		}

		$password = $this->password();
		$payload  = \serialize($data);
		$data     = $this->salt . Utils::encrypt($payload, $password);
		\update_user_meta($this->id, 'tfa', \base64_encode($data));
	}

	private function doGeneratePanicCodes(bool $save)
	{
		$panic = [];
		for ($i=0; $i<self::$panicCodes; ++$i) {
			$panic[] = Utils::generatePanicCode(self::$panicCodeLength);
		}

		$this->panic = $panic;
		if ($save) {
			$this->save();
		}
	}

	public function generatePanicCodes() : array
	{
		$this->doGeneratePanicCodes(true);
		return $this->panic;
	}

	private function doSetHMAC(string $hmac, bool $save)
	{
		if ($this->hmac !== $hmac) {
			$this->hmac    = $hmac;
			$this->counter = ('hotp' === $hmac) ? \mt_rand(1, \mt_getrandmax()) : 0;

			if ($save) {
				$this->save();
			}
		}
	}

	public function getHMAC() : string
	{
		return $this->hmac;
	}

	public function setHMAC(string $hmac) : string
	{
		if ($hmac !== 'hotp' && $hmac !== 'totp') {
			throw new \InvalidArgumentException();
		}

		$this->doSetHMAC($hmac, true);
		return $this->hmac;
	}

	public function getCounter() : int
	{
		return $this->counter;
	}

	public function getDeliveryMethod() : string
	{
		return $this->method ?: 'email';
	}

	public function setDeliveryMethod(string $method)
	{
		if ($method !== 'email' && $method !== 'third-party-apps') {
			throw new \InvalidArgumentException();
		}

		if ($this->method !== $method) {
			$this->method = $method;
			if ('email' === $method) {
				$this->doSetHMAC('hotp', false);
				$this->panic = [];
				$this->used  = [];
			}
			else {
				$this->secret  = '';
				$this->counter = 0;
			}

			$this->save();
		}
	}

	public function getPrivateKey() : string
	{
		if (!$this->secret) {
			/* RFC 4226, Section 4:
			 * R6 - The algorithm MUST use a strong shared secret. The length of
			 * the shared secret MUST be at least 128 bits. This document
			 * RECOMMENDs a shared secret length of 160 bits.
			 */

			$this->secret = Utils::randomBase32String(32);
			if ('email' !== $this->method) {
				$this->doSetHMAC(self::$defaultHmac, false);
				$this->doGeneratePanicCodes(false);
			}
			else {
				$this->doSetHMAC('hotp', false);
				$this->panic = [];
			}

			$this->used = [];
			$this->save();
		}

		return $this->secret;
	}

	public function resetPrivateKey()
	{
		$this->secret = '';
		return $this->getPrivateKey();
	}

	public function getPanicCodes() : array
	{
		return $this->panic;
	}

	public function generateOTP() : string
	{
		$secret = $this->getPrivateKey();

		if ('hotp' === $this->getHMAC()) {
			return Utils::generateHOTP($secret, $this->counter, self::$otpLength, self::$defaultHash);
		}

		return Utils::generateTOTP($secret, self::$timeWindow, self::$otpLength, self::$defaultHash);
	}

	private function verifyEmailOTP(string $code) : bool
	{
		$otp = Utils::generateHOTP($this->getPrivateKey(), $this->getCounter(), self::$otpLength, self::$defaultHash);
		if ($code === $otp) {
			// https://tools.ietf.org/html/rfc4226#section-7.2
			// If the value received by the authentication server matches the value calculated by the client,
			// then the HOTP value is validated. In this case, the server increments the counter value by one.
			++$this->counter;
			$this->save();
			return true;
		}

		return false;
	}

	private function verifyHOTP(string $code) : bool
	{
		$codes = OTP::generateMultipleByCounter($this->getPrivateKey(), $this->getCounter(), self::$countersToCheck, self::$defaultHash);
		foreach ($codes as $idx => $c) {
			if (OTP::asOTP($c, self::$otpLength) === $code) {
				$this->counter += $idx + 1;
				$this->save();
				return true;
			}
		}

		return false;
	}

	private function verifyTOTP(string $code, bool $relaxed) : bool
	{
		$codes = OTP::generateByTimeWindow($this->getPrivateKey(), self::$timeWindow, -self::$windowsToCheck, 0, null, self::$defaultHash);
		foreach ($codes as $idx => $c) {
			if (OTP::asOTP($c, self::$otpLength) === $code) {
				if (!$relaxed) {
					$data['used'] = \array_slice($codes, 0, $idx + 1);
					$this->save();
				}

				return true;
			}
		}

		return false;
	}

	private static function checkPanicCode(string $code) : bool
	{
		if (\strlen($code) === self::$panicCodeLength) {
			$idx = \array_search($code, $this->panic);
			if (false !== $idx) {
				\array_splice($this->panic, $idx, 1);
				$this->save();
				return true;
			}
		}

		return false;
	}

	public function verifyOTP(string $code, bool $relaxed = false) : bool
	{
		if ('email' === $this->getDeliveryMethod()) {
			\assert(!$relaxed);
			return $this->verifyEmailOTP($code);
		}

		// Check the code only if its length matches
		if (\strlen($code) === self::$otpLength) {
			// Disallow recently entered codes
			if (!$relaxed && \in_array($hashed, $this->used)) {
				return false;
			}

			if ('hotp' === $this->getHMAC()) {
				$result = $this->verifyHOTP($code);
			}
			else {
				$result = $this->verifyTOTP($code, $relaxed);
			}

			if ($result) {
				return true;
			}
		}

		return $relaxed ? false : $this->checkPanicCode($code);
	}

	public function is2FAEnabled()
	{
		$user = new \WP_User($this->id);
		$opts = \get_option(Plugin::OPTIONS_KEY);

		foreach ($user->roles as $role) {
			if (!empty($opts['role_' . $role])) {
				return true;
			}
		}

		return false;
	}
}