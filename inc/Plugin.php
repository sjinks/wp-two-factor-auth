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
			$self = new self();
		}

		return $self;
	}

	private function __construct()
	{
		\add_action('init', [$this, 'init']);
	}

	public function init()
	{
		$this->base_url = \plugin_dir_url(\dirname(__DIR__) . '/plugin.php');

		\load_plugin_textdomain('two-factor-auth', false, \dirname(\substr(__DIR__, \strlen(\WP_PLUGIN_DIR) + 1)) . '/languages/');
		\register_setting('two-factor-auth', self::OPTIONS_KEY, ['default' => []]);

		if (\is_admin()) {
			Admin::instance();
		}

		\add_action('login_init',   [$this, 'login_init']);
		\add_filter('authenticate', [$this, 'authenticate'], 999, 3);
	}

	public function baseUrl() : string
	{
		return $this->base_url;
	}

	public function login_init()
	{
		\add_action('login_enqueue_scripts', [$this, 'login_enqueue_scripts']);
		\add_action('login_form',            [$this, 'login_form']);
	}

	public function login_enqueue_scripts()
	{
		\wp_enqueue_style('tfa-login', $this->base_url . 'assets/login.min.css', [], '5.0');
		\wp_enqueue_script('tfa-ajax-request', $this->base_url . 'assets/tfa.min.js', [], '5.0', true);
		\wp_localize_script('tfa-ajax-request', 'tfaSettings', ['ajaxurl' => \admin_url('admin-ajax.php')]);
	}

	public function login_form()
	{
		require __DIR__ . '/../views/login.php';
	}

	public function authenticate($user, $username, $password)
	{
		if (\is_wp_error($user)) {
			return $user;
		}

		$u = WPUtils::getUserByLoginOrEmail($username);
		if (false !== $u) {
			$code = $_POST['two_factor_code'] ?? '';
			$data = new UserData($u);
			if ($data->is2FAEnabled()) {
				$ok   = $data->verifyOTP($code);
				if (!$ok) {
					return new \WP_Error('authentication_failed', \__('<strong>ERROR</strong>: The one time password you have entered is incorrect.', 'two-factor-auth'));
				}
			}
		}

		return $user;
	}
}
