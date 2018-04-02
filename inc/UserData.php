<?php
namespace WildWolf\TFA;

class UserData
{
	/**
	 * @var integer
	 */
	public static $timeWindow       = 30;
	/**
	 * @var integer
	 */
	public static $windowsToCheck   =  2;
	public static $countersToCheck  = 20;
	public static $otpLength        =  6;
	public static $panicCodeLength  =  8;
	public static $panicCodes       = 10;
	/**
	 * @var string
	 */
	public static $defaultHmac      = 'totp';
	/**
	 * @var string
	 */
	public static $defaultHash      = 'sha1';

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
		if (!$this->salt) {
			$this->salt = \openssl_random_pseudo_bytes(64);
		}

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
		if (!$this->secret && !$this->hmac && !$this->method) {
			\delete_user_meta($this->id, 'tfa');
			return;
		}

		$data = [];
		if ($this->secret && $this->hmac) {
			$data['secret']  = $this->secret;
			$data['hmac']    = $this->hmac;
			$data['counter'] = $this->counter;
			$data['panic']   = $this->panic;
			$data['used']    = $this->used;
		}

		$data['method'] = $this->method;
		$data = \array_filter($data);

		$password = $this->password();
		$payload  = \serialize($data);
		$data     = $this->salt . Utils::encrypt($payload, $password);
		\update_user_meta($this->id, 'tfa', \base64_encode($data));
	}

	private function doGeneratePanicCodes()
	{
		$panic = [];
		for ($i=0; $i<self::$panicCodes; ++$i) {
			$panic[] = Utils::generatePanicCode(self::$panicCodeLength);
		}

		$this->panic = $panic;
	}

	public function generatePanicCodes() : array
	{
		$this->doGeneratePanicCodes();
		$this->save();
		return $this->panic;
	}

	public function setPanicCodes(array $v)
	{
		$this->panic = $v;
		$this->save();
	}

	private function doSetHMAC(string $hmac) : bool
	{
		if ($this->hmac !== $hmac) {
			$this->hmac    = $hmac;
			$this->counter = ('hotp' === $hmac) ? \mt_rand(1, \mt_getrandmax()) : 0;
			return true;
		}

		return false;
	}

	public function getHMAC() : string
	{
		return $this->hmac;
	}

	public function setHMAC(string $hmac) : string
	{
		static $allowed = ['hotp' => 1, 'totp' => 1];
		if (!isset($allowed[$hmac])) {
			throw new \InvalidArgumentException();
		}

		if ($this->doSetHMAC($hmac)) {
			$this->save();
		}

		return $this->hmac;
	}

	public function getCounter() : int
	{
		return $this->counter;
	}

	public function setCounter(int $v)
	{
		$this->counter = $v;
		$this->save();
	}

	public function getUsedCodes() : array
	{
		return $this->used;
	}

	public function setUsedCodes(array $v)
	{
		$this->used = $v;
		$this->save();
	}

	public function getDeliveryMethod() : string
	{
		if ($this->is2FAForcefullyEnabled()) {
			return $this->method ?: $this->setDeliveryMethod('email');
		}

		return $this->method;
	}

	private function doSetDeliveryMethod(string $method)
	{
		if ($this->method !== $method) {
			$this->method = $method;
			switch ($method) {
				case 'email':
					$this->doSetHMAC('hotp');
					$this->panic = [];
					$this->used  = [];
					break;

				case 'third-party-apps':
					$this->secret  = '';
					$this->counter = 0;
					break;

				default:
					$this->secret  = '';
					$this->counter = 0;
					$this->hmac    = '';
					$this->panic   = [];
					$this->used    = [];
			}

			return true;
		}

		return false;
	}

	public function setDeliveryMethod(string $method)
	{
		static $allowed = ['email' => 1, 'third-party-apps' => 1, '' => 1];
		if (!isset($allowed[$method])) {
			throw new \InvalidArgumentException();
		}

		if ('' === $method && $this->is2FAForcefullyEnabled()) {
			$method = 'email';
		}

		if ($this->doSetDeliveryMethod($method)) {
			$this->save();
		}

		return $method;
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
				if (!$this->getHMAC()) {
					$this->doSetHMAC(self::$defaultHmac);
				}

				$this->doGeneratePanicCodes();
			}
			else {
				$this->doSetHMAC('hotp');
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

	public function is2FAForcefullyEnabled() : bool
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

	public function is2FAEnabled() : bool
	{
		$dm = $this->getDeliveryMethod();
		return !empty($dm) || $this->is2FAForcefullyEnabled();
	}
}
