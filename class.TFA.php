<?php


class TFA 
{
private $salt_prefix;
private $pw_prefix;

	public function __construct($base23_encoder, $otp_helper)
	{
		$this->base32_encoder = $base23_encoder;
		$this->otp_helper = $otp_helper;
		$this->time_window_size = 30;
		$this->check_back_time_windows = 2;
		$this->check_forward_counter_window = 20;
		$this->otp_length = 6;
		$this->panic_codes_length = 8;
		$this->salt_prefix = AUTH_SALT;
		$this->pw_prefix = AUTH_KEY;
		$this->default_hmac = 'totp';
	}

	public function generateOTP($user_ID, $key_b64, $length = 6, $counter = false)
	{
		
		$length = $length ? (int)$length : 6;
		
		$key = $this->decryptString($key_b64, $user_ID);
		$alg = $this->getUserAlgorithm($user_ID);
		
		if($alg == 'hotp')
		{
			$db_counter = $this->getUserCounter($user_ID);
			
			$counter = $counter ? $counter : $db_counter;
			$otp_res = $this->otp_helper->generateByCounter($key, $counter);
		}
		else
		{
			//time() is supposed to be UTC
			$time = $counter ? $counter : time();
			$otp_res = $this->otp_helper->generateByTime($key, $this->time_window_size, $time);
		}
		$code = $otp_res->toHotp($length);
		
		return $code;
	}

	public function generateOTPsForLoginCheck($user_ID, $key_b64)
	{
		$key = trim($this->decryptString($key_b64, $user_ID));
		$alg = $this->getUserAlgorithm($user_ID);
		
		if($alg == 'totp')
			$otp_res = $this->otp_helper->generateByTimeWindow($key, $this->time_window_size, -1*$this->check_back_time_windows, 0);
		elseif($alg == 'hotp')
		{
			$counter = $this->getUserCounter($user_ID);
			
			$otp_res = array();
			for($i = 0; $i < $this->check_forward_counter_window; $i++)
				$otp_res[] = $this->otp_helper->generateByCounter($key, ($counter+$i));
		}
		return $otp_res;
	}
	

	public function addPrivateKey($user_ID, $key = false)
	{
		//Generate a private key for the user. 
		//To work with Google Authenticator it has to be 10 bytes = 16 chars in base32
		$code = $key ? $key : strtoupper($this->randString(10));

		//Lets encrypt the key
		$code = $this->encryptString($code, $user_ID);
		
		//Add private key to users meta
		update_user_meta($user_ID, 'tfa_priv_key_64', $code);
		
		$alg = $this->getUserAlgorithm($user_ID);
		
		if($alg == 'hotp')
		{
			//Add some panic codes as well. Take 8 digits from events 1,2,3
			update_user_meta($user_ID, 'tfa_panic_codes_64', array(
										$this->encryptString($this->generateOTP($user_ID, $code, 8, 1), $user_ID), 
										$this->encryptString($this->generateOTP($user_ID, $code, 8, 2), $user_ID), 
										$this->encryptString($this->generateOTP($user_ID, $code, 8, 3), $user_ID)
									));
		}
		else
		{
			//Add some panic codes as well. Take 8 digits from time window 1,2,3
			update_user_meta($user_ID, 'tfa_panic_codes_64', array(
										$this->encryptString($this->generateOTP($user_ID, $code, 8, 1), $user_ID), 
										$this->encryptString($this->generateOTP($user_ID, $code, 8, $this->time_window_size+1), $user_ID), 
										$this->encryptString($this->generateOTP($user_ID, $code, 8, $this->time_window_size*2+1), $user_ID)
									));
		}
		$this->changeUserAlgorithmTo($user_ID, $alg);
		
		return $code;
	}


	public function getPrivateKeyPlain($enc, $user_ID)
	{
		$dec = $this->decryptString($enc, $user_ID);
		return $dec;
	}


	public function getPanicCodesString($arr, $user_ID)
	{
		$panic_str = '';
		
		if(!is_array($arr))
			return '<em>No panic codes left. Sorry.</em>';
		
		foreach($arr as $p_code)
			$panic_str .= $this->decryptString($p_code, $user_ID).', ';
			
		$panic_str = rtrim($panic_str, ', ');
		
		$panic_str = $panic_str ? $panic_str : '<em>No panic codes left. Sorry.</em>';
		return $panic_str;
	}
	
	public function preAuth($params)
	{
		global $wpdb;
		$field = filter_var($params['log'], FILTER_VALIDATE_EMAIL) ? 'user_email' : 'user_login';
		$query = $wpdb->prepare("SELECT ID, user_email from ".$wpdb->users." WHERE ".$field."=%s", $params['log']);
		$user = $wpdb->get_row($query);
		$is_activated_for_user = true;
		
		if($user)
		{
			$tfa_priv_key = get_user_meta($user->ID, 'tfa_priv_key_64', true);
			$is_activated_for_user = $this->isActivatedForUser($user->ID);
			
			if($is_activated_for_user)
			{
				$delivery_type = get_user_meta($user->ID, 'tfa_delivery_type', true);
				
				//Default is email
				if(!$delivery_type || $delivery_type == 'email')
				{
					//No private key yet, generate one.
					//This is safe to do since the code is emailed to the user.
					//Not safe to do if the user has disabled email.
					if(!$tfa_priv_key)
						$tfa_priv_key = $this->addPrivateKey($user->ID);
					
					$code = $this->generateOTP($user->ID, $tfa_priv_key);
					$this->sendOTPEmail($user->user_email, $code);
				}
				return true;
			}
			return false;
		}
		return true;
	}

	
	public function authUserFromLogin($params)
	{
		
		global $wpdb;
		
		if(!$this->isCallerActive($params))
			return true;
		
		$field = filter_var($params['log'], FILTER_VALIDATE_EMAIL) ? 'user_email' : 'user_login';
		$query = $wpdb->prepare("SELECT ID from ".$wpdb->users." WHERE ".$field."=%s", $params['log']);
		$user_ID = $wpdb->get_var($query);
		$user_code = trim(@$params['two_factor_code']);
		
		if(!$user_ID)
			return true;
		
		if(!$this->isActivatedForUser($user_ID))
			return true;
			
		$tfa_priv_key = get_user_meta($user_ID, 'tfa_priv_key_64', true);
		$tfa_last_login = get_user_meta($user_ID, 'tfa_last_login', true);
		$tfa_last_pws_arr = get_user_meta($user_ID, 'tfa_last_pws', true);
		$tfa_last_pws = @$tfa_last_pws_arr ? $tfa_last_pws_arr : array();
		$alg = $this->getUserAlgorithm($user_ID);
		
		$current_time_window = intval(time()/30);
		
		//Give the user 1,5 minutes time span to enter/retrieve the code
		//Or check $this->check_forward_counter_window number of events if hotp
		$codes = $this->generateOTPsForLoginCheck($user_ID, $tfa_priv_key);
	
		//A recently used code was entered.
		//Not ok
		if(in_array($this->hash($user_code, $user_ID), $tfa_last_pws))
			return false;
	
		$match = false;
		foreach($codes as $index => $code)
		{
			if(trim($code->toHotp(6)) == trim($user_code))
			{
				$match = true;
				$found_index = $index;
				break;
			}
		}
		
		//Check panic codes
		if(!$match)
		{
			$panic_codes = get_user_meta($user_ID, 'tfa_panic_codes_64');
			
			if(!@$panic_codes[0])
				return $match;
			
			$panic_codes = current($panic_codes);
			
			$dec = array();
			foreach($panic_codes as $panic_code)
				$dec[] = trim($this->decryptString(trim($panic_code), $user_ID));

			$in_array = array_search($user_code, $dec);
			$match = $in_array !== false;
			
			if($match)//Remove panic code
			{
				array_splice($panic_codes, $in_array, 1);
				update_user_meta($user_ID, 'tfa_panic_codes_64', $panic_codes);
			}
			
		}
		else
		{	
			//Add the used code as well so it cant be used again
			//Keep the two last codes
			$tfa_last_pws[] = $this->hash($user_code, $user_ID);
			$nr_of_old_to_save = $alg == 'hotp' ? $this->check_forward_counter_window : $this->check_back_time_windows;
			
			if(count($tfa_last_pws) > $nr_of_old_to_save)
				array_splice($tfa_last_pws, 0, 1);
				
			update_user_meta($user_ID, 'tfa_last_pws', $tfa_last_pws);
		}
		
		if($match)
		{
			//Save the time window when the last successful login took place
			update_user_meta($user_ID, 'tfa_last_login', $current_time_window);
			
			//Update the counter if HOTP was used
			if($alg == 'hotp')
			{
				$counter = $this->getUserCounter($user_ID);
				
				$enc_new_counter = $this->encryptString($counter+1, $user_ID);
				update_user_meta($user_ID, 'tfa_hotp_counter', $enc_new_counter);
				
				if($found_index > 10)
					update_user_meta($user_ID, 'tfa_hotp_off_sync', 1);
			}
		}
		
		return $match;
		
	}

	public function getUserCounter($user_ID)
	{
		$enc_counter = get_user_meta($user_ID, 'tfa_hotp_counter', true);
		
		if($enc_counter)
			$counter = $this->decryptString(trim($enc_counter), $user_ID);
		else
			return '';
			
		return trim($counter);
	}
	
	public function changeUserAlgorithmTo($user_id, $new_algorithm)
	{
		update_user_meta($user_id, 'tfa_algorithm_type', $new_algorithm);
		delete_user_meta($user_id, 'tfa_hotp_off_sync');
		
		$counter_start = rand(13, 999999999);
		$enc_counter_start = $this->encryptString($counter_start, $user_id);
		
		if($new_algorithm == 'hotp')
			update_user_meta($user_id, 'tfa_hotp_counter', $enc_counter_start);
		else
			delete_user_meta($user_id, 'tfa_hotp_counter');
	}
	
	
	public function changeUserDeliveryTypeTo($user_id, $new_delivery_type)
	{
		$new_delivery_type = trim($new_delivery_type);
		update_user_meta($user_id, 'tfa_delivery_type', $new_delivery_type);
	
		//Always use the site default algorithm for mail users
		if($new_delivery_type== 'email')
			delete_user_meta($user_id, 'tfa_algorithm_type');
	}
	
	
	public function getUserAlgorithm($user_id)
	{
		$setting = get_user_meta($user_id, 'tfa_algorithm_type', true);
		$default_hmac = get_option('tfa_default_hmac');
		$default_hmac = $default_hmac ? $default_hmac : $this->default_hmac;
		
		$setting = $setting === false || !$setting ? $default_hmac : $setting;
		return $setting;
	}
	
	public function isActivatedForUser($user_id)
	{
		$user = new WP_User($user_id);

		foreach($user->roles as $role)
		{
			$db_val = get_option('tfa_'.$role);
			$db_val = $db_val === false || $db_val ? 1 : 0; //Nothing saved or > 0 returns 1;
			
			if($db_val)
				return true;
		}
		
		return false;
		
	}

	public function saveCallerStatus($caller_id, $status)
	{
		if($caller_id == 'xmlrpc')
			set_option('tfa_xmlrpc_on', $status);
	}

	private function isCallerActive($params)
	{

		if(!preg_match('/(\/xmlrpc\.php)$/', trim($params['caller'])))
			return true;

		$saved_data = get_option('tfa_xmlrpc_on');
		
		if($saved_data)
			return true;
		
		return false;
	}

	private function sendOTPEmail($email, $code)
	{
		wp_mail( $email, 'Login Code for '.get_bloginfo('name'), "\n\nEnter this code to log in: ".$code."\n\n\n".site_url(), "Content-Type: text/plain");
	}

	private function encryptString($string, $salt_suffix)
	{
		$key = $this->hashAndBin($this->pw_prefix.$salt_suffix, $this->salt_prefix.$salt_suffix);
		
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		
		$enc = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $string, MCRYPT_MODE_CBC, $iv);
		
		$enc = $iv.$enc;
		$enc_b64 = base64_encode($enc);
		return $enc_b64;
	}
	
	private function decryptString($enc_b64, $salt_suffix)
	{
		$key = $this->hashAndBin($this->pw_prefix.$salt_suffix, $this->salt_prefix.$salt_suffix);
		
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
		$enc_conc = base64_decode($enc_b64);
		
		$iv = substr($enc_conc, 0, $iv_size);
		$enc = substr($enc_conc, $iv_size);
		
		$string = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $enc, MCRYPT_MODE_CBC, $iv);

		return $string;
	}

	private function hashAndBin($pw, $salt)
	{
		$key = $this->hash($pw, $salt);
		$key = pack('H*', $key);
	}

	private function hash($pw, $salt)
	{
		//$hash = hash_pbkdf2('sha256', $pw, $salt, 10);
		//$hash = crypt($pw, '$5$'.$salt.'$');
		$hash = md5($salt.$pw);
		return $hash;
	}

	private function randString($len = 6)
	{
		$chars = '23456789QWERTYUPASDFGHJKLZXCVBNM';
		$chars = str_split($chars);
		shuffle($chars);
		$code = implode('', array_splice($chars, 0, $len));
		
		return $code;
	}
	
	public function setUserHMACTypes()
	{
		//We need this because we dont want to change third party apps users algorithm
		$users = get_users(array('meta_key' => 'tfa_delivery_type', 'meta_value' => 'third-party-apps'));
		if(!empty($users))
		{
			foreach($users as $user)
			{
				$tfa_algorithm_type = get_user_meta($user->ID, 'tfa_algorithm_type', true);
				if($tfa_algorithm_type)
					continue;
				
				update_user_meta($user->ID, 'tfa_algorithm_type', $this->getUserAlgorithm($user->ID));
			}
		}
	}
	
	public function upgrade()
	{
		global $wpdb;
		
		$installed_version = get_option('tfa_version');
		if($installed_version > 3)
			return;
		
		//Private key. Encrypt and remove old field
		$users = get_users(array('meta_key' => 'tfa_priv_key'));
		if(!empty($users))
		{
			foreach($users as $user)
			{
				$tfa_priv_key = get_user_meta($user->ID, 'tfa_priv_key');
				$tfa_priv_key = is_array($tfa_priv_key) ? $tfa_priv_key[0] : $tfa_priv_key;
				
				$enc_key_64 = $this->addPrivateKey($user->ID, $tfa_priv_key);
				delete_user_meta($user->ID, 'tfa_priv_key');
			}
		}
		
		
		//Panic codes. Encrypt and remove old field.
		$users = get_users(array('meta_key' => 'tfa_panic_codes'));
		if(!empty($users))
		{
			foreach($users as $user)
			{
				$tfa_panic_codes = get_user_meta($user->ID, 'tfa_panic_codes');

				$enc_p_codes = array();
				foreach($tfa_panic_codes[0] as $p_code)
				{
					$enc_pc_64 = $this->encryptString($p_code, $user->ID);
					$enc_p_codes[] = $enc_pc_64;
				}
				update_user_meta($user->ID, 'tfa_panic_codes_64', $enc_p_codes);
				delete_user_meta($user->ID, 'tfa_panic_codes');

			}
		}
		
		//Last used. Remove all since hashed with md5.
		$q = "DELETE FROM ".$wpdb->usermeta." WHERE meta_key = 'tfa_last_pws'";
		$wpdb->query($q);
		update_option('tfa_version', '4');
	}

}


?>