<?php  
if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( !class_exists('RFSfrontEndAjaxLoginFront') ) {

	class RFSfrontEndAjaxLoginFront {

		public function __construct() {

			add_shortcode( 'rfs_login_page', array($this, 'render_login_page_content') );
			add_shortcode( 'rfs_profile_page', array($this, 'render_profile_page_content') );

			add_action( 'wp_enqueue_scripts', array($this, 'load_assets'), 11 );

			add_action( 'login_init', array($this, 'default_login_page_redirect') );
			add_action( 'template_redirect', array($this, 'feal_pages_redirects') );

			if( is_user_logged_in() ) {
				add_action( 'wp_ajax_add_user_bar', array($this, 'add_user_bar') );
				add_action( 'wp_ajax_nopriv_add_user_bar', array($this, 'add_user_bar') );
			}
			add_action( 'wp_ajax_login_profile_actions', array($this, 'login_profile_actions') );
			add_action( 'wp_ajax_nopriv_login_profile_actions', array($this, 'login_profile_actions') );

			add_action( 'after_setup_theme', array($this, 'hide_admin_bar') );
			add_action( 'admin_init',  array($this, 'restrict_admin') );

			add_action( 'template_redirect', array($this, 'reset_password_link_type') );

			add_action( 'wp_logout', array($this, 'clear_user_bar_transient') );
			add_action( 'wp_login', array($this, 'clear_login_fails') );

			add_action( 'user_register',  array( $this, 'register_user_via_admin'), 10, 1);
			add_action( 'personal_options_update', array($this, 'change_password_via_admin') );
			add_action( 'edit_user_profile_update', array($this, 'change_password_via_admin') );
		}

		/**
		 * This function will load plugin styles and js scripts
		 * @param n/a
		 * @return n/a
		*/
		public function load_assets() {
			wp_enqueue_style( 'rfs_front_end_login', plugin_dir_url( __FILE__ ).'dist/css/styles.min.css', array(), '1.0');
			wp_enqueue_script( 'rfs_front_end_login', plugin_dir_url(__FILE__).'dist/js/scripts.min.js',array(),'1.0', true);

			wp_enqueue_script('rfs_feal_ajax', plugin_dir_url( __FILE__ ).'dist/js/ajax.js', array('jquery'),'', true );
			wp_localize_script('rfs_feal_ajax', 'rfsfealajax', 
				array(
					'ajaxurl'					=> admin_url( 'admin-ajax.php' ),
					'loggedin'					=> absint( is_user_logged_in() ),
					'show_bar'					=> absint( !$this->user_has_admin_access() ),
					'feal_nonce' 				=> wp_create_nonce( 'get_feal_nonce' ),
					'messages'		=> array(
						'error'			=> array(
							'error'				=> __('An error occurred. Refresh the page and try again', feal()->text_domain),
							'empty'				=> __('Fill in all fields', feal()->text_domain),
							'wrong'				=> __('Invalid login or password', feal()->text_domain),
							'nonce'				=> __('Authorization error occured', feal()->text_domain),
							'username_exists' 	=> __('Username exists', feal()->text_domain),
							'email_exists' 		=> __('Email exists', feal()->text_domain),
							'email' 			=> __('Invalid address email', feal()->text_domain),
							'invalid_link'		=> __('The link is either expired or invalid', feal()->text_domain),
							'pass_diff'			=> __('Passwords must be the same', feal()->text_domain),
							'pass_short'		=> __('Password must contain at least 8 characters', feal()->text_domain),
							'pass_lower'		=> __('Password must contain at least one lowercase letter', feal()->text_domain),
							'pass_upper'		=> __('Password must contain at least one uppercase letter', feal()->text_domain),
							'pass_number'		=> __('Password must contain at least one number', feal()->text_domain),
							'pass_special'		=> __('Password must contain at least one special character', feal()->text_domain),
							'not_exists'		=> __('The user does not exist', feal()->text_domain)
						),
						'ok'		=> array(
							'user_created' 		=> __('Your account has been created. You will receive an email with an activation link.', feal()->text_domain),
							'reset_sent'		=> __('An email with a password reset link has been sent.', feal()->text_domain),
							'data_saved'		=> __('Changes has been saved', feal()->text_domain),
						),
						'warning'		=> array(
							'password_changed'	=> __('Your password has been changed. You will be logged out in', feal()->text_domain).' <strong class="feal-countdown"></strong> s',
						)
					)
				)
			);
		}

		/**
		 * This function will render html login page on front end
		 * @param n/a
		 * @return html
		*/
		public function render_login_page_content() {
			$file = __DIR__.'/views/login-page.php';
			
			if( file_exists( $file ) ) {
				include_once( $file );
			}
		}

		/**
		 * This function will render html profile page on front end
		 * @param n/a
		 * @return html
		*/
		public function render_profile_page_content() {
			$file = __DIR__.'/views/profile-page.php';
			
			if( file_exists( $file ) ) {
				include_once( $file );
			}
		}

		/**
		 * This function will return logged in user data
		 * @param n/a
		 * @return object
		*/
		public function user_profile_data() {
			if( !is_user_logged_in() ) return false;

			return get_userdata( get_current_user_id() );
		}

		/**
		 * This function will check if the reset link is set and set the link type
		 * @param n/a
		 * @return boolean
		*/
		public function reset_password_link_type() {
			if( isset($_GET['type']) ) {
				feal()->setPasswordType = sanitize_text_field( $_GET['type'] );
			}
		}

		/**
		 * This function will validate password reset link
		 * @param n/a
		 * @return mixed
		*/
		public function check_password_key() {
			if( isset($_GET['key']) && isset($_GET['login']) ) {
				$validate_key = check_password_reset_key( sanitize_text_field( $_GET['key'] ), sanitize_text_field( $_GET['login'] ) );
				
				return is_wp_error( $validate_key ) ? false : $validate_key->ID;
			}
			return false;
		}

		/**
		 * This function will create password reset link
		 * @param $user (object)
		 * @param $type (string)
		 * @return string
		*/
		public function create_reset_password_link($user, $type) {
			$key = get_password_reset_key( $user );

			$link = add_query_arg(
				array(
					'type' 	=> $type,
					'key' 	=> $key,
					'login'	=> $user->data->user_login
				), 
				get_permalink( feal()->login_page ).'#setpassword');

			return $link;
		}

		/**
		 * This function will check whether a user has access to admin panel
		 * @param $user (object) - optional
		 * @return boolean
		*/
		public function user_has_admin_access($user = false) {
			$user 	= $user ? $user : wp_get_current_user();
			$roles 	= $user->roles;

			$hasAccess = array_intersect( feal()->admin_access_options, $roles );

			return !empty( $hasAccess );
		}

		/**
		 * This function will redirect default wordpress login actions to front end
		 * @param n/a
		 * @return n/a
		*/
		function default_login_page_redirect () {
			$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'login';

			if( $action == 'login') {
				wp_safe_redirect( get_permalink( feal()->login_page ) ); exit;
			}

			if ( isset( $_POST['wp-submit'] ) ) {
				$action = 'post-data';
			} else if ( isset( $_GET['reauth'] ) ) {
				$action = 'reauth';
			} 

			if( $action == 'rp' || $action == 'resetpass' || $action == 'lostpassword' ) {
				wp_safe_redirect( get_permalink( feal()->login_page ).'#remind' ); exit;
			}

			if( $action == 'lostpassword' && isset($_GET['error']) && ( $_GET['error'] == 'expiredkey' || $_GET['error'] == 'invalidkey' ) ) {
				wp_safe_redirect( get_permalink( feal()->login_page ).'#remind' ); exit;exit;
			}

			if( $action == 'register') {
				wp_safe_redirect( get_permalink( feal()->login_page ).'#register' ); exit;
			}

			if(
				$action == 'post-data' || // don't mess with POST requests
				$action == 'reauth' || // need to reauthorize
				$action == 'logout' // user is logging out
			) {
				return;
			}

			wp_safe_redirect( get_permalink( feal()->login_page ) ); exit;
		}


		/**
		 * This function will redirect users trying to access login or profile page depending on logged in status
		 * @param 
		 * @return 
		*/
		public function feal_pages_redirects() {
			if( is_page( feal()->login_page ) && is_user_logged_in() ) {

				if( $this->user_has_admin_access() ) {
					$url = admin_url();
				}else {
					$url = get_permalink( feal()->profile_page );
				}
				wp_safe_redirect( $url.'#loggedin' ); exit;
			}

			if( is_page( feal()->profile_page ) && absint(feal()->profile_page) > 0 && !is_user_logged_in() ) {
				wp_safe_redirect( get_permalink( feal()->login_page ) ); exit;
			}
		}

		/**
		 * This function will hide admin bar depending on logged in user role
		 * @param 
		 * @return 
		*/
		public function hide_admin_bar() {
			if( !is_user_logged_in() || current_user_can( 'administrator' ) ) return;

			if( !$this->user_has_admin_access() ) {
				add_filter('show_admin_bar', '__return_false');
			}
		}

		/**
		 * This function will restrict access to admin panel based on user role
		 * @param n/a
		 * @return n/a
		*/
		public function restrict_admin() {
			if( !is_user_logged_in() || current_user_can( 'administrator' ) ) return;
			
			if( !defined('DOING_AJAX') && !$this->user_has_admin_access() ){
				wp_safe_redirect( home_url() ); exit;
			}
		}

		/**
		 * This function will collect failed login attempts
		 * @param n/a
		 * @return n/a
		*/
		private function login_failed_attempt() {
			global $wpdb;

			if( feal()->enable_login_lockdown != 'yes' ) return;

			$wpdb->insert(
				$wpdb->prefix.'feal_login_lockdown_fails',
				array(
					'attempt_time' 	=> current_time('Y-m-d H:i'),
					'ip_address'	=> $_SERVER['REMOTE_ADDR'] 
				),
				array('%s', '%s')
			);

			$this->login_lockdown();
		}

		/**
		 * This function will lock user's ip if the failed login attempts limit is reached
		 * @param n/a
		 * @return n/a
		*/
		private function login_lockdown() {
			global $wpdb;

			$limit 			= feal()->login_lockdown_limit;
			$failsTime 		= feal()->login_lockdown_fails_time;
			$lockdownTime 	= feal()->login_lockdown_time;

			$ip 			= $_SERVER['REMOTE_ADDR'];

			$loginFails 	= $wpdb->get_results( $wpdb->prepare("SELECT attempt_time FROM {$wpdb->prefix}feal_login_lockdown_fails WHERE ip_address = '%s' ORDER BY id DESC LIMIT {$limit}", $ip) );

			if( $loginFails ) {
				$fails = count($loginFails);

				if( $fails < $limit ) return;

				$lastTime 	= strtotime( $loginFails[0]->attempt_time );
				$firstTime 	= strtotime( $loginFails[$fails-1]->attempt_time );

				$interval 	= ($lastTime - $firstTime) / 60; // return interaval in minutes

				if( $interval <= $failsTime ) {

					$now 				= current_time('YmdHi');
					$lockedUntil 		= strtotime( $now. '+'.$lockdownTime.' minutes' );
					$lockedUntilDate 	= date('Y-m-d H:i', $lockedUntil);

					$lockIp = $wpdb->insert(
						$wpdb->prefix.'feal_login_lockdown_locks',
						array(
							'ip_address'	=> $_SERVER['REMOTE_ADDR'],
							'locked_until'	=> $lockedUntilDate
						)
					);

				}
			}
		}

		/**
		 * This function will clear login fails for an ip on user successful login
		 * @param n/a
		 * @return n/a
		*/
		public function clear_login_fails() {
			global $wpdb;
			$ip = $_SERVER['REMOTE_ADDR'];

			$wpdb->delete(
				$wpdb->prefix.'feal_login_lockdown_fails',
				array('ip_address' => $ip),
				array('%s')
			);
		}

		/**
		 * This function will check if the user's ip address is locked
		 * @param n/a
		 * @return boolean
		*/
		private function is_locked() {
			global $wpdb;

			if( feal()->enable_login_lockdown != 'yes' ) return false;

			$ip = $_SERVER['REMOTE_ADDR'];

			$isLocked = $wpdb->get_row( $wpdb->prepare("SELECT TIMESTAMPDIFF(minute, NOW(), locked_until) as timediff FROM {$wpdb->prefix}feal_login_lockdown_locks WHERE ip_address = '%s' ORDER BY id DESC", $ip) );

			return $isLocked->timediff > 0;
		}

		/**
		 * This function will validate user password
		 * @param $password (string)
		 * @param $rPassword (string)
		 * @return json
		*/
		private function validate_password($password, $rPassword) {
			if( $password != $rPassword ) {
				wp_send_json( array('error', 'pass_diff') );
			}
			if( !preg_match('/(?=\S{8,})/', $password) ) {
				wp_send_json( array('error', 'pass_short') );
			}
			if( !preg_match('/(?=\S*[a-z])/', $password) ) {
				wp_send_json( array('error', 'pass_lower') );
			}
			if( !preg_match('/(?=\S*[A-Z])/', $password) ) {
				wp_send_json( array('error', 'pass_upper') );
			}
			if( !preg_match('/(?=\S*[\d])/', $password) ) {
				wp_send_json( array('error', 'pass_number') );
			}
			if( !preg_match('/(?=\S*[\W])/', $password) ) {
				wp_send_json( array('error', 'pass_special') );
			}
		}

		/**
		 * This function will proccess front end forms
		 * @param n/a
		 * @return n/a
		*/
		public function login_profile_actions() {
			global $wpdb;
			$nonce = $_POST['nonce'];

			if( !wp_verify_nonce( $nonce, 'get_feal_nonce' ) ) {
				wp_send_json( array('error', 'nonce') );
			}

			$formData = $_POST['formData'];

			switch( $formData['type'] ) {
				case 'login':

					$login 		= sanitize_text_field( $formData['feal-login'] );
					$password 	= sanitize_text_field( $formData['feal-pass'] );

					if( empty($login) || empty($password) ) {
						wp_send_json( array('error', 'empty') );
					}

					if( $this->is_locked() ) {
						wp_send_json( array('error', 'wrong') );
					}

					if( !username_exists( $login ) ) {
						$this->login_failed_attempt();
						wp_send_json( array('error', 'wrong') );
					}

					$user = get_user_by( 'login', $login );

					if( !wp_check_password( $password, $user->data->user_pass, $user->ID ) ) {
						$this->login_failed_attempt();
						wp_send_json( array('error', 'wrong') );
					}

					$credentials 					= array();
					$credentials['user_login'] 		= $login;
					$credentials['user_password'] 	= $password;
					$signon 						= wp_signon($credentials, false);

					if( is_wp_error( $signon ) ) {
						wp_send_json( array('error', 'error') );
					}

					wp_send_json( array('ok') );

					break;
				case 'register':

					$username 	= sanitize_text_field( $formData['feal-username'] );
					$email 		= sanitize_text_field( $formData['feal-email'] );

					if( empty($username) || empty($email) ) {
						wp_send_json( array('error', 'empty') );
					}

					if( !is_email( $email ) ) {
						wp_send_json( array('error', 'email') );
					}

					if( username_exists( $username ) ) {
						wp_send_json( array('error', 'username_exists') );
					}

					if( email_exists( $email ) ) {
						wp_send_json( array('error', 'email_exists') );
					}

					$wpdb->query('START TRANSACTION');

					$createUser = wp_insert_user( array(
						'user_login'	=>	$username,
						'user_email'	=>	$email,
						'user_pass'		=>	NULL
					) );

					if( is_wp_error( $createUser ) ) {
						$wpdb->query('ROLLBACK');
						wp_send_json( array('error', 'error') );
					}

					$emailService = new RFSfrontEndAjaxLoginEmailService;

					$user 	= get_user_by( 'id', $createUser );

					$link = $this->create_reset_password_link( $user, 'activate' );

					$message = $emailService->feal_email_template('registration', array(
						'styles'	=> $emailService->feal_email_styles(),
						'link'		=> $link,
						'username'	=> $username
					));
					
					$subject 	= esc_html( get_bloginfo('name') ).' - '.__('Registration', feal()->text_domain);
					$send 		= wp_mail($email, $subject, $message);

					if( !$send ) {
						$wpdb->query('ROLLBACK');
						wp_send_json( array('error', 'error') );
					}

					$wpdb->query('COMMIT');
					wp_send_json( array('ok', 'user_created', $message) );

					break;
				case 'remind':

					$username_or_email = sanitize_text_field( $formData['feal-user'] );

					if( empty($username_or_email) ) {
						wp_send_json( array('error', 'empty') );
					}

					$usernameExists = username_exists( $username_or_email );
					$emailExists 	= email_exists( $username_or_email );

					if( !$usernameExists && !$emailExists ) {
						wp_send_json( array('error', 'not_exists') );
					}
					
					$user_id 	= absint( $usernameExists ) > 0 ? $usernameExists : $emailExists;
					$user 		= get_user_by( 'id', $user_id );

					$emailService = new RFSfrontEndAjaxLoginEmailService;

					$link = $this->create_reset_password_link( $user, 'remind' );

					$message = $emailService->feal_email_template('password-reminder', array(
						'styles'	=> $emailService->feal_email_styles(),
						'link'		=> $link,
						'username'	=> $user->data->user_login
					));
					
					$subject 	= esc_html( get_bloginfo('name') ).' - '.__('Password reminder', feal()->text_domain);
					$send 		= wp_mail($user->data->user_email, $subject, $message);

					if( !$send ) {
						wp_send_json( array('error', 'error') );
					}

					wp_send_json( array('ok', 'reset_sent') );

					break;
				case 'setpassword':

					$user_id 	= absint( $formData['feal-set-pass-user'] );
					$password 	= sanitize_text_field( $formData['feal-new-pass'] );
					$rPassword 	= sanitize_text_field( $formData['feal-new-pass-repeat'] );

					if( get_user_by( 'id', $user_id ) == false ) {
						wp_send_json( array('error', 'invalid_link') );
					}

					if( empty($password) || empty($rPassword) ) {
						wp_send_json( array('error', 'empty') );
					}
					$this->validate_password($password, $rPassword);

					$hashedPassword = wp_hash_password( $password );

					$wpdb->query('START TRANSACTION');

					$updateUser = $wpdb->update(
						$wpdb->prefix.'users',
						array(
							'user_pass' 			=> $hashedPassword,
							'user_activation_key' 	=> ''
						),
						array('ID' => $user_id),
						array('%s', '%s'),
						array('%d')
					);

					if( $updateUser === false ) {
						$wpdb->query('ROLLBACK');
						wp_send_json( array('error', 'error') );
					}
					
					$wpdb->query('COMMIT');

					wp_send_json( array('ok', get_permalink( feal()->login_page ).'#passwordchanged') );

					break;
				case 'profile':

					$user_id 		= get_current_user_id();
					$email 			= sanitize_text_field( $formData['feal-profile-email'] );
					$password 		= sanitize_text_field( $formData['feal-profile-new-pass'] );
					$rPassword 		= sanitize_text_field( $formData['feal-profile-new-pass-repeat'] );
					$passChanged 	= false;

					if( !is_email( $email ) ) {
						wp_send_json( array('error', 'email') );
					}

					$userdata 	= get_userdata( $user_id );
					$userEmail 	= $userdata->user_email;

					if( empty($password) && empty($rPassword) && $email == $userEmail ) {
						wp_send_json( array('ok', 'data_saved') );
					}

					$wpdb->query('START TRANSACTION');

					if( !empty($password) || !empty($rPassword) ) {

						$this->validate_password($password, $rPassword);

						$hashedPassword = wp_hash_password( $password );

						$updatePasword = $wpdb->update(
							$wpdb->prefix.'users',
							array('user_pass' => $hashedPassword),
							array('ID' => $user_id),
							array('%s'),
							array('%d')
						);

						if( $updatePasword === false ) {
							$wpdb->query('ROLLBACK');
							wp_send_json( array('error', 'error') );
						}

						$passChanged = true;
					}

					if( $email != $userEmail ) {
						$updateEmail = wp_update_user( array(
							'ID' 			=> $user_id, 
							'user_email' 	=> $email
						) );

						if( is_wp_error( $updateEmail ) ) {
							$wpdb->query('ROLLBACK');
							wp_send_json( array('error', 'error') );
						}
					}

					$wpdb->query('COMMIT');

					wp_send_json( array('ok', 'data_saved', $passChanged ) );

					break;
			}

			wp_send_json( array('error', 'error') );
		}

		/**
		 * This function will render user bar html for logged in users that have no admin panel access
		 * @param n/a
		 * @return html
		*/
		public function add_user_bar() {
			$nonce = $_POST['nonce'];

			if( !wp_verify_nonce( $nonce, 'get_feal_nonce' ) ) {
				wp_send_json( array('error', 'nonce') );
			}

			$userdata = get_userdata( get_current_user_id() );

			$html = get_transient( 'rfs_feal_user_bar' );

			if( $html === false) {
			$html = '
				<div id="rfs-feal-user-bar">
					<div class="rfs-feal-user-bar-inner">
						<div class="rfs-feal-user-bar-col"><p>'.__('Hello', feal()->text_domain).' '.esc_html( $userdata->display_name ).'</p></div>
						<div class="rfs-feal-user-bar-col text-right">
						';

					if( feal()->enableRegistration ) {
						$html .= '
							<p><a class="rfs-feal-icon-profile rfs-feal-icon" href="'.esc_url( get_permalink( feal()->profile_page ) ).'">'.__('My account', feal()->text_domain).'</a></p>
						';
					}

					$html .= '
							<p><a class="rfs-feal-icon-logout rfs-feal-icon" href="'.esc_url( wp_logout_url() ).'">'.__('Logout', feal()->text_domain).'</a></p>
						</div>
					</div>
				</div>';
				set_transient( 'rfs_feal_user_bar', $html, 3600 );
			}

			wp_send_json( array('ok', $html) );
		}

		/**
		 * This function will delete user bar transient (cache)
		 * @param n/a
		 * @return n/a
		*/
		public function clear_user_bar_transient() {
			delete_transient( 'rfs_feal_user_bar' );
		}

		/**
		 * This function will send n account activation email for users registered in admin panel
		 * @param n/a
		 * @return n/a
		*/
		public function register_user_via_admin($user_id) {
			$email		= sanitize_text_field( $_POST['email'] );
			$username	= sanitize_text_field( $_POST['user_login'] );
			$send_email	= $_POST['send_user_notification'];

			if($send_email == 1) {
				$emailService 	= new RFSfrontEndAjaxLoginEmailService;
				$user 			= get_user_by( 'id', $user_id );
				$link 			= $this->create_reset_password_link( $user, 'activate' );

				$message = $emailService->feal_email_template('registration', array(
					'styles'	=> $emailService->feal_email_styles(),
					'link'		=> $link,
					'username'	=> $username
				));
				
				$subject 	= esc_html( get_bloginfo('name') ).' - '.__('Registration', feal()->text_domain);
				$send 		= wp_mail($email, $subject, $message);
			}
		}

		/**
		 * This function will send password reset link when generating new password in admin panel
		 * @param n/a
		 * @return n/a
		*/
		public function change_password_via_admin($user_id) {
			if( !empty($_POST['pass1']) && !empty($_POST['pass2']) ) {

				// stop password from changing
				$_POST['pass-text'] = '';
				$_POST['pass1'] 	= '';
				$_POST['pass2'] 	= '';

				$user 			= get_user_by( 'id', $user_id );
				$emailService 	= new RFSfrontEndAjaxLoginEmailService;
				$link 			= $this->create_reset_password_link( $user, 'remind' );

				$message = $emailService->feal_email_template('password-reminder', array(
					'styles'	=> $emailService->feal_email_styles(),
					'link'		=> $link,
					'username'	=> $user->data->user_login
				));
				
				$subject 	= esc_html( get_bloginfo('name') ).' - '.__('Password reminder', feal()->text_domain);
				$send 		= wp_mail($user->data->user_email, $subject, $message);
			}
		}

	}
	new RFSfrontEndAjaxLoginFront();

}
?>