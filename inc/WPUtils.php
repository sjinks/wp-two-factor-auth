<?php

namespace WildWolf\TFA;

class WPUtils
{
	private static function sendOTPEmail(\WP_User $user, string $code)
	{
		$opts    = \get_option(Plugin::OPTIONS_KEY);
		$from    = $opts['email_from'] ?? '';
		$name    = $opts['email_name'] ?? '';
		$headers = [];

		if ($from && \filter_var($from, \FILTER_VALIDATE_EMAIL)) {
			$h = 'From: ';
			if ($name) {
				$h .= '"' . $name . '" ';
			}

			$h        .= '<' . $from . ">\r\n";
			$headers[] = $h;
		}

		$headers[] = "Content-Type: text/plain\r\n";
		$subject   = \sprintf(\__('One Time Password for %s', 'wwatfa'), \get_bloginfo('name'));
		$body      = \sprintf(\__("Enter this OTP to log in: %s\n\n%s\n", 'wwatfa'), $code, \site_url());

		$subject   = \apply_filters('tfa_otp_email_subject', $subject, $user, $code);
		$body      = \apply_filters('tfa_otp_email_body',    $body, $user, $code);

		\wp_mail($user->user_email, $subject, $body, $headers);
	}

	/**
	 * @param string $s
	 * @return \WP_User|false
	 */
	public static function getUserByLoginOrEmail(string $s)
	{
		$user = \get_user_by('login', $s);
		if (!$user && \strpos($s, '@')) {
			$user = \get_user_by('email', $s);
		}

		return $user;
	}

	public static function preAuth(string $value) : bool
	{
		$user = self::getUserByLoginOrEmail($value);

		if (false !== $user) {
			$data = new UserData($user);
			if ($data->is2FAEnabled()) {
				$method = $data->getDeliveryMethod();
				if ('email' === $method) {
					$code = $data->generateOTP();
					self::sendOTPEmail($user, $code);
				}

				return true;
			}

			return false;
		}

		// If this user does not exist, show the OTP field anyway
		return true;
	}
}
