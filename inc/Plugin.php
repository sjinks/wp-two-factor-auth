<?php
namespace WildWolf\TFA;

class Plugin
{
	const OPTIONS_KEY = 'tfa';

	/**
	 * @var string
	 */
	private $base_url;

	public static function instance()
	{
		static $self = null;

		if (!$self) {
			// @codeCoverageIgnoreStart
			$self = new self();
			// @codeCoverageIgnoreEnd
		}

		return $self;
	}

	// @codeCoverageIgnoreStart
	private function __construct()
	{
		\add_action('init', [$this, 'init']);
	}

	public function init()
	{
		$this->base_url = \plugin_dir_url(\dirname(__DIR__) . '/plugin.php');

		\load_plugin_textdomain('wwtfa', false, \plugin_basename(\dirname(__DIR__)) . '/lang/');
		\register_setting('two-factor-auth', self::OPTIONS_KEY, ['default' => []]);

		if (\is_admin()) {
			Admin::instance();
		}

		\add_action('login_form_login', [$this, 'login_form_login']);
		\add_filter('authenticate',     [$this, 'authenticate'], 999, 3);
	}
	// @codeCoverageIgnoreEnd

	public function baseUrl() : string
	{
		return $this->base_url;
	}

	public function login_form_login()
	{
		\add_action('login_enqueue_scripts', [$this, 'login_enqueue_scripts']);
		\add_action('login_form',            [$this, 'login_form']);
	}

	public function login_enqueue_scripts()
	{
		static $style = 'input#two_factor_auth{margin-bottom:2px}#tfa-block>span{font-size:small;display:inline-block;margin-bottom:16px;}';
		\wp_add_inline_style('login', $style);
		\wp_enqueue_script('tfa-ajax-request', $this->base_url . 'assets/tfa.min.js', [], '5.1.3', true);
		\wp_localize_script('tfa-ajax-request', 'tfaSettings', ['ajaxurl' => \admin_url('admin-ajax.php')]);
	}

	public function login_form()
	{
		require __DIR__ . '/../views/login.php';
	}

	public function authenticate($user, $username)
	{
		if (\is_wp_error($user) || WPUtils::isApiRequest()) {
			return $user;
		}

		$u = WPUtils::getUserByLoginOrEmail($username);
		if (false !== $u) {
			$code = $_POST['two_factor_code'] ?? '';
			$data = new UserData($u);
			if ($data->is2FAEnabled() && !OTPVerifier::verify($data, $code)) {
				return new \WP_Error('authentication_failed', \__('<strong>ERROR</strong>: The one time password you have entered is incorrect.', 'wwtfa'));
			}
		}

		return $user;
	}
}
