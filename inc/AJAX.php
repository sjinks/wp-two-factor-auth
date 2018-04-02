<?php
namespace WildWolf\TFA;

final class AJAX
{
	public static function instance()
	{
		static $self = null;

		if (!$self) {
			$self = new self();
		}

		return $self;
	}

	private function __construct()
	{
		$this->admin_init();
	}

	public function admin_init()
	{
		\add_action('wp_ajax_nopriv_tfa-init-otp', [$this, 'tfa_init_otp']);
		\add_action('wp_ajax_tfa-init-otp',        [$this, 'tfa_init_otp']);
		\add_action('wp_ajax_tfa-refresh-code',    [$this, 'tfa_refresh_code']);
		\add_action('wp_ajax_tfa-reset-method',    [$this, 'tfa_reset_method']);
		\add_action('wp_ajax_tfa-verify-code',     [$this, 'tfa_verify_code']);
	}

	/**
	 * @uses $_POST['log']
	 */
	public function tfa_init_otp()
	{
		$log = \sanitize_user($_POST['log'] ?? '');
		$res = WPUtils::preAuth($log);
		\wp_die(\json_encode(['status' => $res]));
	}

	public function tfa_refresh_code()
	{
		$current_user = \wp_get_current_user();
		\check_ajax_referer('tfa-refresh_' . $current_user->ID);

		$data = new UserData($current_user);
		\wp_die($data->generateOTP());
	}

	/**
	 * @uses $_POST['uid']
	 */
	public function tfa_reset_method()
	{
		if (\current_user_can('edit_users')) {
			$uid = (int)($_POST['uid'] ?? -1);
			\check_ajax_referer('tfa-reset_' . $uid);

			$data = new UserData($uid);
			$data->setDeliveryMethod('email');

			\wp_die(Admin::$delivery_type_lut['email']);
		}
	}

	/**
	 * @uses $_POST['code']
	 */
	public function tfa_verify_code()
	{
		$current_user = \wp_get_current_user();
		\check_ajax_referer('tfa-verify_' . $current_user->ID);

		$code   = $_POST['code'] ?? '';
		$result = OTPVerifier::verifyRelaxed(new UserData($current_user), $code);

		if ($result) {
			\wp_die('<strong class="verify-success">' . \__('Success!', 'wwatfa') . '</strong>');
		}
		else {
			\wp_die('<strong class="verify-failure">' . \__('Failure!', 'wwatfa') . '</strong>');
		}
	}
}
