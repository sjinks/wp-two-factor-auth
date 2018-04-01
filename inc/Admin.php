<?php

namespace WildWolf\TFA;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class Admin
{
	/**
	 * @var string
	 */
	private $user_settings_hook;

	/**
	 * @var array
	 */
	private static $delivery_type_lut = [];

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
		$this->init();

		self::$delivery_type_lut = [
			'email'            => \__('By email', 'wwatfa'),
			'third-party-apps' => \__('Via third party applications', 'wwatfa')
		];
	}

	/**
	 * `init` action handler
	 */
	public function init()
	{
		\load_plugin_textdomain('wwatfa', /** @scrutinizer ignore-type */ false, \plugin_basename(\dirname(\dirname(__FILE__))) . '/lang/');

		\add_action('wp_ajax_nopriv_tfa-init-otp', [$this, 'preAuth']);
		\add_action('admin_init',                  [$this, 'admin_init']);
		\add_action('admin_menu',                  [$this, 'admin_menu']);
	}

	public function preAuth()
	{
		$log = \sanitize_user($_POST['log'] ?? '');
		$res = WPUtils::preAuth($log);
		\wp_die(\json_encode(['status' => $res]));
	}

	public function checkbox_field(array $args)
	{
		$name    = Plugin::OPTIONS_KEY;
		$options = \get_option($name);
		$id      = \esc_attr($args['label_for']);
		$isset   = !empty($options[$id]);
		$checked = \checked($isset, 1, false);
		echo <<< EOT
<input type="checkbox" name="{$name}[{$id}]" id="{$id}" value="1"{$checked}/>
EOT;
	}

	public function input_field(array $args)
	{
		$name    = Plugin::OPTIONS_KEY;
		$options = \get_option($name);
		$id      = \esc_attr($args['label_for']);
		$type    = \esc_attr($args['type'] ?? 'text');
		$value   = \esc_attr($options[$id] ?? '');
		echo <<< EOT
<input type="{$type}" name="{$name}[{$id}]" id="{$id}" value="{$value}"/>
EOT;
	}

	public function admin_init()
	{
		/**
		 * @var WP_Roles $roles
		 */
		$roles = \wp_roles();

		\add_settings_section('tfa-roles', \__('User Roles', 'wwatfa'), [$this, 'tfa_user_roles_callback'], 'two-factor-auth');
		foreach ($roles->role_names as $id => $name) {
			\add_settings_field('role_' . $id, \translate_user_role($name), [$this, 'checkbox_field'], 'two-factor-auth', 'tfa-roles', ['label_for' => 'role_' . $id]);
		}

		\add_settings_section('tfa-email', \__('Email Settings', 'wwatfa'), '__return_null', 'two-factor-auth');
		\add_settings_field('email_from', \__('Email Address', 'wwatfa'), [$this, 'input_field'], 'two-factor-auth', 'tfa-email', ['label_for' => 'email_from', 'type' => 'email']);
		\add_settings_field('email_name', \__('Sender Name', 'wwatfa'),   [$this, 'input_field'], 'two-factor-auth', 'tfa-email', ['label_for' => 'email_name']);

		$plugin = \plugin_basename(dirname(__DIR__) . '/plugin.php');
		\add_filter('plugin_action_links_' . $plugin, [$this, 'plugin_action_links']);

		if (\current_user_can('edit_users')) {
			\add_action('edit_user_profile', [$this, 'edit_user_profile']);
		}

		\add_action('wp_ajax_tfa-init-otp',              [$this, 'preAuth']);
		\add_action('wp_ajax_tfa-refresh-code',          [$this, 'tfa_refresh_code']);
		\add_action('wp_ajax_tfa-reset-method',          [$this, 'tfa_reset_method']);
		\add_action('wp_ajax_tfa-verify-code',           [$this, 'tfa_verify_code']);

		\add_action('admin_post_tfa_save_user_settings', [$this, 'tfa_save_user_settings']);
		\add_action('admin_post_tfa_reset_key',          [$this, 'tfa_reset_key']);
		\add_action('admin_post_tfa_save_user_method',   [$this, 'tfa_save_user_method']);
		\add_action('admin_post_tfa_reset_panic',        [$this, 'tfa_reset_panic']);

		\add_action('admin_enqueue_scripts',             [$this, 'admin_enqueue_scripts'], 10, 1);
	}

	public function admin_menu()
	{
		\add_options_page('Two Factor Auth', 'Two Factor Auth', 'manage_options', 'two-factor-auth', [$this, 'settings_page']);

		$current_user = \wp_get_current_user();
		$data         = new UserData($current_user);
		if ($data->is2FAEnabled()) {
			$base = Plugin::instance()->baseUrl();
			$this->user_settings_hook = \add_menu_page('Two Factor Auth', 'Two Factor Auth', 'read', 'two-factor-auth-user', [$this, 'user_settings_page'], $base . 'assets/icon.png', 72);
		}
	}

	public function settings_page()
	{
		$this->render(__DIR__ . '/../views/admin-settings.php');
	}

	public function admin_enqueue_scripts($hook)
	{
		$base = Plugin::instance()->baseUrl();
		if ('user-edit.php' === $hook) {
			$uid = \filter_input(INPUT_GET, 'user_id', \FILTER_SANITIZE_NUMBER_INT);
			\wp_enqueue_script('tfa-profile', $base . 'assets/profile.min.js', [], '5.0', true);
			\wp_localize_script('tfa-profile', 'tfaSettings', [
				'ajaxurl' => \admin_url('admin-ajax.php'),
				'uid'     => $uid,
				'nonce'   => \wp_create_nonce('tfa-reset_' . $uid),
			]);
		}
		elseif ($this->user_settings_hook === $hook) {
			$user = \wp_get_current_user();

			\wp_enqueue_style('tfa-admin', $base . 'assets/admin.min.css', [], '5.0');
			\wp_enqueue_script('tfa-user-settings', $base . 'assets/user-settings.min.js', [], '5.0', true);
			\wp_localize_script('tfa-user-settings', 'tfaSettings', [
				'ajaxurl'    => \admin_url('admin-ajax.php'),
				'confirm'    => \__('WARNING: If you reset the private key, you will have to update your applications with the new one. Do you want to proceed?', 'wwatfa'),
				'refreshing' => \__('Refreshing&hellip;', 'wwatfa'),
				'nonce'      => \wp_create_nonce('tfa-refresh_' . $user->ID),
				'vnonce'     => \wp_create_nonce('tfa-verify_'  . $user->ID),
			]);
		}
	}

	public function user_settings_page()
	{
		$message = \filter_input(\INPUT_GET, 'message', \FILTER_SANITIZE_NUMBER_INT);
		switch ($message) {
			case 1: \add_settings_error('general', 'settings_updated', \__('Settings saved.'), 'updated'); break;
			case 2: \add_settings_error('general', 'settings_updated', \__('Private key reset.', 'wwatfa'), 'updated'); break;
			case 3: \add_settings_error('general', 'settings_updated', \__('Algorithm changed.', 'wwatfa'), 'updated'); break;
			case 4: \add_settings_error('general', 'settings_updated', \__('Panic codes regenerated.', 'wwatfa'), 'updated'); break;
		}

		$current_user = \wp_get_current_user();
		$data         = new UserData($current_user);

		$privkey      = null;
		$key          = null;
		$otp          = null;
		$panic        = null;
		$algo         = null;
		$counter      = null;
		$qrimg        = null;
		$type         = $data->getDeliveryMethod();
		$type         = isset(self::$delivery_type_lut[$type]) ? $type : \key(self::$delivery_type_lut);

		if ('third-party-apps' === $type) {
			$privkey = $data->getPrivateKey();
			$algo    = $data->getHMAC();
			$otp     = $data->generateOTP();
			$panic   = $data->getPanicCodes();
			$counter = $data->getCounter();

			$domain  = \parse_url(\site_url(), \PHP_URL_HOST);
			$issuer  = \get_bloginfo('name') . ' (' . $domain . ')';
			$login   = \urlencode($current_user->user_login);
			$key     = \Tuupola\Base32Proxy::encode($privkey);
 			$otpauth = "otpauth://{$algo}/{$issuer}:{$login}?secret={$key}&issuer={$issuer}&counter={$counter}";

 			$qropts  = new QROptions(['outputType' => QRCode::OUTPUT_IMAGE_PNG, 'eccLevel' => QRCode::ECC_H, 'imageBase64' => true, 'scale' => 4, 'addQuietzone' => false]);
 			$qrcode  = new QRCode($qropts);
 			$qrimg   = $qrcode->render($otpauth);
		}

		$options = [
			'uid'           => $current_user->ID,
			'delivery_type' => $type,
			'privkey'       => $privkey,
			'privkey32'     => $key,
			'otp'           => $otp,
			'panic'         => $panic,
			'algo'          => $algo,
			'counter'       => $counter,
			'qrimg'         => $qrimg,
		];

		$this->render(__DIR__ . '/../views/user-settings.php', $options);
	}

	public function plugin_action_links(array $links) : array
	{
		$url  = \esc_attr(\admin_url('options-general.php?page=two-factor-auth'));
		$link = '<a href="' . $url . '">' . \__('Settings', 'wwatfa') . '</a>';
		$links['settings'] = $link;
		return $links;
	}

	public function tfa_save_user_settings()
	{
		$current_user = \wp_get_current_user();
		\check_admin_referer('save-tfauser_' . $current_user->ID);

		$tfa  = (array)($_POST['tfa'] ?? []);
		$type = $tfa['delivery'] ?? 'email';
		$data = new UserData($current_user);
		$data->setDeliveryMethod($type);

		\wp_redirect(\admin_url('admin.php?page=two-factor-auth-user&message=1'));
	}

	public function tfa_reset_key()
	{
		$current_user = \wp_get_current_user();
		\check_admin_referer('reset-tfauser_' . $current_user->ID);

		$data = new UserData($current_user);
		$data->resetPrivateKey();

		\wp_redirect(\admin_url('admin.php?page=two-factor-auth-user&message=2'));
	}

	public function tfa_save_user_method()
	{
		$current_user = \wp_get_current_user();
		\check_admin_referer('save-tfamethod_' . $current_user->ID);

		$tfa  = (array)($_POST['tfa'] ?? []);
		$algo = $tfa['method'] ?? 'totp';

		$data = new UserData($current_user);
		$data->setHMAC($algo);

		\wp_redirect(\admin_url('admin.php?page=two-factor-auth-user&message=3'));
	}

	public function tfa_refresh_code()
	{
		$current_user = \wp_get_current_user();
		\check_ajax_referer('tfa-refresh_' . $current_user->ID);

		$data = new UserData($current_user);
		\wp_die($data->generateOTP());
	}

	public function tfa_verify_code()
	{
		$current_user = \wp_get_current_user();
		\check_ajax_referer('tfa-verify_' . $current_user->ID);

		$code   = $_POST['code'] ?? '';
		$data   = new UserData($current_user);
		$result = $data->verifyOTP($code, true);

		if ($result) {
			\wp_die('<strong class="verify-success">' . \__('Success!', 'wwatfa') . '</strong>');
		}
		else {
			\wp_die('<strong class="verify-failure">' . \__('Failure!', 'wwatfa') . '</strong>');
		}
	}

	public function tfa_reset_method()
	{
		if (\current_user_can('edit_users')) {
			$uid = (int)($_POST['uid'] ?? -1);
			\check_ajax_referer('tfa-reset_' . $uid);

			$data = new UserData($uid);
			$data->setDeliveryMethod('email');

			\wp_die(self::$delivery_type_lut['email']);
		}
	}

	public function tfa_reset_panic()
	{
		$current_user = \wp_get_current_user();
		\check_admin_referer('reset-tfapanic_' . $current_user->ID);

		$data  = new UserData($current_user->ID);
		$data->generatePanicCodes();
		\wp_redirect(\admin_url('admin.php?page=two-factor-auth-user&message=4'));
	}

	private function render(string $view, array $options = [])
	{
		require $view;
	}

	public function tfa_user_roles_callback()
	{
		$this->render(__DIR__ . '/../views/admin-settings-roles.php');
	}

	public function edit_user_profile(\WP_User $user)
	{
		if (!\current_user_can('edit_users')) {
			return;
		}

		$options = [];
		$data    = new UserData($user);
		if ($data->is2FAEnabled()) {
			$type = $data->getDeliveryMethod();
			$type = isset(self::$delivery_type_lut[$type]) ? $type : \key(self::$delivery_type_lut);
			$options['enabled']  = true;
			$options['method']   = $type;
			$options['delivery'] = self::$delivery_type_lut[$type];
		}
		else {
			$options['enabled'] = false;
		}

		$this->render(__DIR__ . '/../views/profile.php', $options);
	}
}
