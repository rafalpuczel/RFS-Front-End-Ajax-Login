<?php  
if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( !class_exists('RFSfrontEndAjaxLoginSettings') ) {

	class RFSfrontEndAjaxLoginSettings {

		public $active_tab;

		public function __construct() {

			add_action( 'admin_enqueue_scripts', array($this, 'load_assets'), 11 );
			
			add_action( 'admin_menu', array($this, 'create_settings_page'), 10 );

			add_action( 'admin_init', array($this, 'create_plugin_settings') );
		}


		/**
		 * This function will create admin menu page for a plugin settings
		 * @param n/a
		 * @return n/a
		*/
		public function create_settings_page() {
			global $settings_page;

			$settings_page = add_menu_page(
				'RFS Front End Ajax Login', 
				'RFS Front End Ajax Login', 
				'manage_options', 
				'rfs-feal-settings', 
				array($this, 'settings_page'),
				'dashicons-lock'
			);

		}

		public function load_assets($hook) {
			global $settings_page;
			if( $hook == $settings_page ) {
				wp_enqueue_style( 'rfs_front_end_login_settings', plugin_dir_url( __FILE__ ).'dist/css/styles.min.css', array(), '1.0');
				wp_enqueue_script( 'rfs_front_end_login_settings', plugin_dir_url(__FILE__).'dist/js/scripts.min.js',array(),'1.0', true);
			}
		}

		/**
		 * This function will render settings page html
		 * @param n/a
		 * @return n/a
		*/
		public function settings_page() {
			$file = __DIR__.'/views/settings-page.php';
			
			if( file_exists( $file ) ) {
				include_once( $file );
			}
		}

		public function create_plugin_settings() {
			
			// General
			add_settings_section(
				'general_section', 
				'', 
				array($this, 'general_settings_content'), 
				'rfs-feal-settings' 
			);

			add_settings_field( 
				'rfs_feal_admin_access', 
				__('Admin access for roles:', feal()->text_domain), 
				array($this, 'display_admin_access_field'), 
				'rfs-feal-settings', 
				'general_section'
			);

			register_setting( 
				'rfs-feal-options', 
				'rfs_feal_admin_access',
				array($this, 'rfs_feal_admin_access_sanitize')
			);

			// Login Lockdown
			add_settings_section( 
				'login_lockdown_section', 
				'', 
				array($this, 'login_lockdown_settings_content'), 
				'rfs-feal-settings' 
			);

			add_settings_field( 
				'rfs_feal_enable_login_lockdown', 
				'<h4>'.__('Enable Login lockdown', feal()->text_domain).'</h4>', 
				array($this, 'display_login_lockdown_field'), 
				'rfs-feal-settings', 
				'login_lockdown_section',
				array(
					'yes' 	=> __('Yes', feal()->text_domain),
					'no' 	=> __('No', feal()->text_domain),
				)
			);

			add_settings_field( 
				'rfs_feal_login_lockdown_limit', 
				'<h4>'.__('Max login fails', feal()->text_domain).'<br><small>'.__('Number of login fails before locking the login functionality.', feal()->text_domain).'</small></h4>', 
				array($this, 'display_login_lockdown_limit_field'), 
				'rfs-feal-settings', 
				'login_lockdown_section'
			);

			add_settings_field( 
				'rfs_feal_login_lockdown_fails_time', 
				'<h4>'.__('Login fails time period (minutes)', feal()->text_domain).'<br><small>'.__('Amount of time during which the login fails are counted.', feal()->text_domain).'</small></h4>', 
				array($this, 'display_login_lockdown_fails_time_field'), 
				'rfs-feal-settings', 
				'login_lockdown_section'
			);

			add_settings_field( 
				'rfs_feal_login_lockdown_time', 
				'<h4>'.__('Login lockdown time length (minutes)', feal()->text_domain).'<br><small>'.__('Determines how the login functionality will be locked.', feal()->text_domain).'</small></h4>', 
				array($this, 'display_login_lockdown_time_field'), 
				'rfs-feal-settings', 
				'login_lockdown_section'
			);

			register_setting( 
				'rfs-feal-options', 
				'rfs_feal_enable_login_lockdown'
			);
			register_setting( 
				'rfs-feal-options', 
				'rfs_feal_login_lockdown_limit',
				'intval'
			);
			register_setting( 
				'rfs-feal-options', 
				'rfs_feal_login_lockdown_fails_time',
				'intval'
			);
			register_setting( 
				'rfs-feal-options', 
				'rfs_feal_login_lockdown_time',
				'intval'
			);
				
		}

		public function general_settings_content() {}

		public function login_lockdown_settings_content() {}

		public function display_admin_access_field() {
			$file = __DIR__.'/views/fields/admin-access.php';
			
			if( file_exists( $file ) ) {
				include_once( $file );
			}
		}

		public function display_login_lockdown_field($args) {
			if( !is_array($args) ) return;

			$html = '';

			foreach($args as $value => $label) {
				$html .= '<label><input name="rfs_feal_enable_login_lockdown" value="'.$value.'" class="tog" type="radio" '.checked( $value, feal()->enable_login_lockdown, false ).'>'.$label.'</label> ';
			}
			
			echo $html;
		}

		public function display_login_lockdown_limit_field() {
			echo '<input type="number" name="rfs_feal_login_lockdown_limit" value="'.absint( feal()->login_lockdown_limit ).'" min="1" step="1">';
		}

		public function display_login_lockdown_fails_time_field() {
			echo '<input type="number" name="rfs_feal_login_lockdown_fails_time" value="'.absint( feal()->login_lockdown_fails_time ).'" min="1" step="1">';
		}

		public function display_login_lockdown_time_field() {
			echo '<input type="number" name="rfs_feal_login_lockdown_time" value="'.absint( feal()->login_lockdown_time ).'" min="1" step="1">';
		}

		public function rfs_feal_admin_access_sanitize($input) {
			if( empty($input) ) {
				$input = array();
			}

			$input[] = 'administrator';
			
			return $input;
		}

	}
	new RFSfrontEndAjaxLoginSettings();

}
?>