<?php
/*
Plugin Name: RFS Front End AJAX Login
Plugin URI: 
Description: Front End Login and Registration
Version: 1.0.0
Author: Rafal Puczel
Author URI: http://www.rfscreations.pl/
Copyright: Rafal Puczel
Text Domain: rfs-front-end-ajax-login
Domain Path: /lang
*/

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


// Disable default user accounts related email notifications

if( !function_exists('wp_new_user_notification') ) {
	function wp_new_user_notification() {}
}
add_filter( 'send_password_change_email', '__return_false' );
add_filter( 'send_email_change_email', '__return_false' );


if( !class_exists('RFSfrontEndAjaxLogin') ) {

	function create_front_end_pages() {
		$login_page_id 		= get_option( 'rfs_frontend_login_page' );
		$profile_page_id 	= get_option( 'rfs_frontend_profile_page' );
		$enableRegistration = get_option( 'users_can_register' );

		$args1 = array(
			'post_status'	=> 'publish',
			'post_type'		=> 'page',
			'post_title'	=> 'Login Page',
			'post_content'	=> '[rfs_login_page]'
		);

		$args2 = array(
			'post_status'	=> 'publish',
			'post_type'		=> 'page',
			'post_title'	=> 'Profile Page',
			'post_content'	=> '[rfs_profile_page]'
		);

		if( !$login_page_id || get_post_status( $login_page_id ) != 'publish' ) {
			$loginPage = wp_insert_post( $args1 );

			if( $loginPage > 0 ) {
				update_option( 'rfs_frontend_login_page', $loginPage );
			}
		}

		if( $enableRegistration ) {
			if( !$profile_page_id || get_post_status( $profile_page_id ) != 'publish' ) {
				$profilePage = wp_insert_post( $args2 );

				if( $profilePage > 0 ) {
					update_option( 'rfs_frontend_profile_page', $profilePage );
				}
			}
		}
	}
	register_activation_hook( __FILE__, 'create_front_end_pages' );

	class RFSfrontEndAjaxLogin {

		// vars
		public $login_page;
		public $profile_page;

		public $enableRegistration;
		public $setPasswordType;
		public $text_domain;

		// plugin options
		public $admin_access_options;
		public $enable_login_lockdown;
		public $login_lockdown_limit;
		public $login_lockdown_fails_time;
		public $login_lockdown_time;

		/**
		 * A constructor function
		 * @param n/a
		 * @return n/a
		*/
		public function __construct() {
			$this->text_domain 			= 'rfs-front-end-ajax-login';
			$this->enableRegistration 	= get_option( 'users_can_register' );
			$this->login_page 			= get_option( 'rfs_frontend_login_page' );
			$this->profile_page 		= get_option( 'rfs_frontend_profile_page' );

			$this->plugin_options();

			add_action( 'plugins_loaded', array($this, 'rfs_load_textdomain') );
			add_action( 'add_meta_boxes', array($this, 'set_pages_meta_boxes'), 10, 2 );

			add_action( 'update_option_users_can_register', array($this, 'toggle_profile_page_on_users_can_register_setting'), 10, 3 );
			
			if( !$this->login_page || get_post_status( $this->login_page ) != 'publish' ) {
				add_action( 'admin_notices', array($this, 'no_login_page_found') );
				return false;
			}

			if( !$this->enableRegistration ) {
				add_action( 'admin_notices', array($this, 'registration_functionality_notice') );
				return false;
			}

			if( ( !$this->profile_page || get_post_status( $this->profile_page ) != 'publish' ) && $this->enableRegistration ) {
				add_action( 'admin_notices', array($this, 'no_profile_page_found') );
				return false;
			}

			add_action( 'plugins_loaded', array($this, 'create_db_tables') );

			add_filter( 'display_post_states', array($this, 'set_feal_pages_states') );
			add_action( 'admin_head', array($this, 'style_feal_pages') );
			add_filter( 'edit_form_after_title', array($this, 'feal_pages_info') );
			add_action( 'admin_init', array($this, 'set_new_feal_page') );

			add_action( 'plugins_loaded', array($this, 'plugin_front_end') );
			add_action( 'plugins_loaded', array($this, 'plugin_settings') );
			add_action( 'plugins_loaded', array($this, 'plugin_email_service') );
		}

		/**
		 * This function will get plugin settings options
		 * @param n/a
		 * @return n/a
		*/
		public function plugin_options() {
			$this->admin_access_options 		= get_option( 'rfs_feal_admin_access' );
			$this->enable_login_lockdown 		= get_option( 'rfs_feal_enable_login_lockdown' );
			$this->login_lockdown_limit 		= get_option( 'rfs_feal_login_lockdown_limit' );
			$this->login_lockdown_fails_time 	= get_option( 'rfs_feal_login_lockdown_fails_time' );
			$this->login_lockdown_time 			= get_option( 'rfs_feal_login_lockdown_time' );

			if( $this->enable_login_lockdown == false ) {
				add_option( 'rfs_feal_enable_login_lockdown', 'no' );
			}
			if( absint( $this->login_lockdown_limit ) < 1 ) {
				add_option( 'rfs_feal_login_lockdown_limit', 3 );
			}
			if( absint( $this->login_lockdown_fails_time ) < 1 ) {
				add_option( 'rfs_feal_login_lockdown_fails_time', 5 );
			}
			if( absint( $this->login_lockdown_time ) < 1 ) {
				add_option( 'rfs_feal_login_lockdown_time', 30 );
			}
		}

		/**
		 * This function will create plugin database tables
		 * @param n/a
		 * @return n/a
		*/
		public function create_db_tables() {
			global $wpdb;

			$table_name 		= $wpdb->prefix . "feal_login_lockdown_fails"; 
			$table2_name 		= $wpdb->prefix . "feal_login_lockdown_locks"; 
			$charset_collate 	= $wpdb->get_charset_collate();

			$sql = "CREATE TABLE $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			attempt_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			ip_address varchar(100) DEFAULT '' NOT NULL,
			PRIMARY KEY  (id)
			) $charset_collate;";

			$sql2 = "CREATE TABLE $table2_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			ip_address varchar(100) DEFAULT '' NOT NULL,
			locked_until datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY  (id)
			) $charset_collate;";

			if( $this->enable_login_lockdown == 'yes' ) {
				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				dbDelta( array($sql, $sql2) );
			}
		}

		
		/**
		 * This is a helper function to include files
		 * @param $filename
		 * @return n/a
		*/
		public function include_file($filename, $folder = false) {
			$folder = $folder ? $folder.'/' : '';
			$file 	= __DIR__.'/'.$folder.$filename.'.php';

			if( file_exists( $file ) ) {
				include_once( $file );
			}
		}

		/**
		 * This function will enable plugin translations
		 * @param n/a
		 * @return n/a
		*/
		function rfs_load_textdomain() {
			load_plugin_textdomain( 'rfs-front-end-ajax-login', false, dirname( plugin_basename(__FILE__) ) . '/lang/' );
		}

		/**
		 * This function will create or remove profile page depending on users can register setting in wp general options
		 * @param n/a
		 * @return n/a
		*/
		public function toggle_profile_page_on_users_can_register_setting( $old_value, $value ) {
			if( $old_value != $value && $value == 1 ) {

				create_front_end_pages();

			}elseif( $old_value != $value && $value == 0 ) {
				if( $this->profile_page ) {
					
					$unset = wp_update_post( array(
						'ID'			=> $this->profile_page,
						'post_content'	=> '',
					) );
					wp_delete_post( $this->profile_page, true );
					delete_option( 'rfs_frontend_profile_page' );
				}
			}
		}

		/**
		 * This function will display error message and disable front end login if there is no login page set
		 * @param n/a
		 * @return n/a
		*/
		public function no_login_page_found() {
			$class 		= 'notice notice-error';
			$message 	= __( 'RFS Front End AJAX Login - Login page not found. Set login page to activate plugin functionalities.', $this->text_domain );

			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 
		}

		/**
		 * This function will display error message and disable front end login if there is no profile page set and registration is enabled
		 * @param n/a
		 * @return n/a
		*/
		public function no_profile_page_found() {
			$class 		= 'notice notice-error';
			$message 	= __( 'RFS Front End AJAX Login - Profile page not found. Set profile page to activate plugin functionalities.', $this->text_domain );

			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 
		}

		public function registration_functionality_notice() {
			$class 		= 'notice notice-warning is-dismissible';
			$message 	= __( 'RFS Front End AJAX Login - In order to enable front end registration and profile page, turn on the user registration setting in', $this->text_domain ).' <a href="'.admin_url( 'options-general.php' ).'">'.__('General Settings', $this->text_domain).'</a>';

			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), wp_kses($message, array( 'a'=>array( 'href'=>array() ) )) ); 
		}

		/**
		 * This function will add meta box to pages other than current login page, profile page, posts page or frontend page
		 * @param $post_type, $post
		 * @return n/a
		*/
		public function set_pages_meta_boxes($post_type, $post) {
			if( 
				$post->post_status != 'publish' || 
				$post->ID == get_option( 'page_for_posts' ) ||
				$post->ID == get_option( 'page_on_front' ) ||
				$post->ID == $this->login_page ||
				$post->ID == $this->profile_page
			) return;

			add_meta_box( 'rfs_frontend_login_page_set', __( 'Set as login page', $this->text_domain ), array($this, 'rfs_frontend_pages_meta_box'), 'page', 'side', 'high' );

			if( $this->enableRegistration ) {
				add_meta_box( 'rfs_frontend_profile_page_set', __( 'Set as profile page', $this->text_domain ), array($this, 'rfs_frontend_pages_meta_box'), 'page', 'side', 'high' );
			}
		}

		/**
		 * This function will add content to meta boxes - action btns to set a page as login or profile page
		 * @param $post
		 * @return html
		*/
		public function rfs_frontend_pages_meta_box($post, $args) {
			$nonce1 = wp_create_nonce( 'set_login_page_nonce' );
			$nonce2 = wp_create_nonce( 'set_profile_page_nonce' );
			
			$box_id = $args['id'];

			switch( $box_id ) {
				case 'rfs_frontend_login_page_set':

					printf( 
						'<div style="padding:20px 0 10px; text-align:center;">
							<a class="page-title-action" href="?post=%s&action=%s&set_login_page=%d&_wpnonce=%s">'.__( 'Set', $this->text_domain ).'</a>
						</div>', 
						esc_attr( $_GET['post'] ), 
						'edit', 
						absint( $post->ID ), 
						$nonce1,
						'Set'
					);

					break;
				case 'rfs_frontend_profile_page_set':

					printf( 
						'<div style="padding:20px 0 10px; text-align:center;">
							<a class="page-title-action" href="?post=%s&action=%s&set_profile_page=%d&_wpnonce=%s">'.__( 'Set', $this->text_domain ).'</a>
						</div>', 
						esc_attr( $_GET['post'] ), 
						'edit', 
						absint( $post->ID ), 
						$nonce2,
						'Set'
					);

					break;
			}
		}

		/**
		 * This function will display login and profile page states text in pages list table
		 * @param $states
		 * @return string
		*/
		public function set_feal_pages_states($states) {
			global $post;

			if( get_post_type($post->ID) == 'page' && $post->ID == $this->login_page ) {
				$states[] = __( 'Login page', $this->text_domain );
			}

			if( get_post_type($post->ID) == 'page' && $post->ID == $this->profile_page ) {
				$states[] = __( 'Profile page', $this->text_domain );
			}

			return $states;
		}

		/**
		 * This function will hide login and profile pages unnecessary meta boxes
		 * @param n/a
		 * @return n/a
		*/
		public function style_feal_pages() {
			global $post;
			global $post_type;
			global $pagenow;
			
			if( $post_type == 'page' && $pagenow == 'post.php') {
				if( $post->ID == $this->login_page || $post->ID == $this->profile_page ) {
					echo '<style>#postdivrich, #postimagediv, #screen-meta label[for=postimagediv-hide], #commentstatusdiv, #screen-meta label[for=commentstatusdiv-hide], #commentsdiv, #screen-meta label[for=commentsdiv-hide], #authordiv, #screen-meta label[for=authordiv-hide], #postcustom, #screen-meta label[for=postcustom-hide], #pageparentdiv, #screen-meta label[for=pageparentdiv-hide], #revisionsdiv, #screen-meta label[for=revisionsdiv-hide] { display: none; }</style>
					';
				}
			}
		}

		/**
		 * This function will display info in login page edit screen
		 * @param $post
		 * @return html
		*/
		public function feal_pages_info($post) {
			global $pagenow;
			global $post_type;

			if( $post_type == 'page' && $pagenow == 'post.php') {
				if( $post->ID == $this->login_page || $post->ID == $this->profile_page ) {
					$class 		= 'notice notice-warning inline';
					$message 	= $post->ID == $this->login_page ? __( 'This page is used as a login page.', $this->text_domain ) : __( 'This page is used as a profile page.', $this->text_domain );

					printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 
				}
			}
		}

		/**
		 * This function will set a new page as a login or profile page 
		 * @param n/a
		 * @return n/a
		*/
		public function set_new_feal_page() {
			global $pagenow;

			if( $pagenow != 'post.php' ) return;
			
			if( isset($_GET['post']) && isset($_GET['_wpnonce']) ) {

				if( isset($_GET['set_login_page']) || isset($_GET['set_profile_page']) ) {

					$_post_id 	= absint( $_GET['post'] );
					$nonce 		= sanitize_text_field( $_GET['_wpnonce'] );

					if( isset($_GET['set_login_page']) ) {

						$_lp_id = absint( $_GET['set_login_page'] );

						if( !wp_verify_nonce( $nonce, 'set_login_page_nonce' ) ) {
							return;
						}

						$current_page_id 	= $this->login_page;
						$content 			= '[rfs_login_page]';
						$option 			= 'rfs_frontend_login_page';

					}elseif( isset($_GET['set_profile_page']) ) {

						$_lp_id = absint( $_GET['set_profile_page'] );

						if( !wp_verify_nonce( $nonce, 'set_profile_page_nonce' ) ) {
							return;
						}

						$current_page_id 	= $this->profile_page;
						$content 			= '[rfs_profile_page]';
						$option 			= 'rfs_frontend_profile_page';

					}

					if( $_post_id != $_lp_id ) {
						return;
					}

					// unset current login page
					$unset = wp_update_post( array(
						'ID'			=> $current_page_id,
						'post_content'	=> '',
					) );

					if( $unset ) {
						// set new page as login page
						$set = wp_update_post( array(
							'ID'			=> $_lp_id,
							'post_content'	=> $content,
						) );
						update_option( $option, $_lp_id );
						update_post_meta( $_lp_id, '_wp_page_template', 'default' );

						wp_safe_redirect( admin_url($pagenow.'?post='.$_lp_id.'&action=edit') ); exit;
					}

				}

			}
		}

		/**
		 * This function will initialize Front End functionalities of the plugin
		 * @param n/a
		 * @return n/a
		*/
		public function plugin_front_end() {
			$this->include_file('front-end-login', 'front-end');
		}

		/**
		 * This function will create Admin Page of the plugin
		 * @param n/a
		 * @return n/a
		*/
		public function plugin_settings() {
			$this->include_file('front-end-login-settings', 'admin');
		}

		/**
		 * This function will This function will initialize plugin email service
		 * @param n/a
		 * @return n/a
		*/
		public function plugin_email_service() {
			$this->include_file('email', 'email');
		}

	}

	/**
	 * This function will return main class instance to use everywhere in the plugin
	 * @param n/a
	 * @return object
	*/

	function feal() {

		global $feal;
		
		if( !isset($feal) ) {
		
			$feal = new RFSfrontEndAjaxLogin();
			
		}

		return $feal;
		
	}

	feal();

}