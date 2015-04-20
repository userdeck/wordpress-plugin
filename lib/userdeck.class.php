<?php

defined( 'ABSPATH' ) or die();

if ( !class_exists( 'UserDeck' ) ) {

	class UserDeck {

		const VERSION = '1.0.2';

		const SLUG = 'userdeck';

		protected static $instance;

		protected $plugin_path;

		protected $plugin_url;

		protected $admin_alerts = array();

		/**
		 * singleton method
		 */
		public static function instance() {
			if ( !is_a( self::$instance, __CLASS__ ) ) {
				self::$instance = new self();
			}
			
			return self::$instance;
		}
		
		/**
		 * class constructor
		 * register the activation and de-activation hooks and hook into a bunch of actions
		 */
		public function __construct() {
			$this->plugin_path = trailingslashit( dirname( dirname( __FILE__ ) ) );
			$this->plugin_url = trailingslashit( plugins_url( '', dirname( __FILE__ ) ) );
			
			register_activation_hook( __FILE__, array( $this, 'install' ) );
			register_deactivation_hook( __FILE__, array( $this, 'uninstall' ) );

			$this->load_guide();
			$this->add_actions();
			$this->add_filters();
		}

		public function load_guide()
		{
			require_once( $this->plugin_path . 'lib/guide.class.php' );
			$guide = new UserDeck_Guide();
		}

		public function add_actions()
		{
			if ( is_admin() ) {
				add_action( 'admin_menu', array( $this, 'create_admin_menu_items') );
				add_action( 'admin_init', array( $this, 'settings_init') );
				add_action( 'admin_init', array( $this, 'build_admin_alerts') );
				add_action( 'admin_notices', array( $this, 'render_admin_alerts') );
			}
		}

		public function add_filters()
		{
			$plugin = plugin_basename(__FILE__);
			add_filter("plugin_action_links_$plugin", array($this, 'add_action_links'));
		}
		
		public function install() {}
		
		public function uninstall() {
			
			delete_option('userdeck');
			
		}

		public function add_admin_alert($type, $message) {

			array_unshift($this->admin_alerts, array(
				'type' => $type,
				'message' => $message,
			));
			
		}

		public function render_admin_alerts() {

			foreach ($this->admin_alerts as $alert) {
				echo '<div class="' . $alert['type'] . '">';
				echo '<p>' . $alert['message'] . '</p>';
				echo '</div>';
			}

		}
		
		/**
		 * retrieve the userdeck options
		 * @return array 'userdeck' options
		 */
		public function get_settings() {
			
			return get_option( 'userdeck', array('guides_key' => null) );
			
		}

		/**
		 * update the userdeck options in the database
		 * @param  array $options new options settings to save
		 * @return null
		 */
		public function update_settings( $options ) {

			update_option( 'userdeck', $options );

		}
		
		/**
		 * show a 'settings saved' notice
		 * and a friendly reminder if the guide key hasn't been entered
		 * @return null
		 */
		public function build_admin_alerts() {
			
			if ( isset( $_GET['page'] ) && $_GET['page'] == 'userdeck' ) {

				if ( isset( $_GET['page_added'] ) ) {

					$message = sprintf( 'Page created. <a href="%s">View page</a>', get_permalink( $_GET['page_id'] ) );

					$this->add_admin_alert( 'updated', $message );
					
				}
				
				if ( isset( $_GET['page_updated'] ) ) {
					
					$message = sprintf( 'Page updated. <a href="%s">View page</a>', get_permalink( $_GET['page_id'] ) );

					$this->add_admin_alert( 'updated', $message );
					
				}
				
			}
			else {

				// show a reminder to users who can update options
				if ( current_user_can( 'manage_options' ) ) {

					$options = $this->get_settings();

					if ( !isset( $options['guides_key'] ) || !$options['guides_key'] ) {
						$message = '<strong>UserDeck is not setup</strong>. Please <a href="options-general.php?page=userdeck">configure the UserDeck settings</a> to use the plugin.';
						
						$this->add_admin_alert('error', $message);
					}
					
				}

			}

		}
		
		/**
		 * create the relevant type of options page
		 * @return null
		 */
		public function create_admin_menu_items() {
			
			add_options_page('UserDeck', 'UserDeck', 'manage_options', self::SLUG, array($this, 'render_options_page'));
			
		}
		
		/**
		 * output the options page
		 * @return null
		 */
		public function render_options_page() {
			
			$options = $this->get_settings();
			
			$page_list = get_pages(array('post_type' => 'page'));
			$pages = array();
			
			foreach ($page_list as $page) {
				$pages[$page->ID] = $page->post_title;
			}

			$guides_key = $options['guides_key'];
			
			$show_guides_options = false;
			
			if ($guides_key) {
				$show_guides_options = true;
			}

			$guides_shortcode = UserDeck_Guide::generate_shortcode($guides_key);
			
			include( $this->plugin_path . 'views/admin-options.php' );
			
		}
		
		/**
		 * use the WordPress settings api to initiate the various settings
		 * and if it's a network settings page then validate & update any submitted settings
		 * @return null
		 */
		public function settings_init() {

			if ( !isset( $_GET['page'] ) || $_GET['page'] != 'userdeck' ) {
				return;
			}
			
			wp_enqueue_script( 'userdeck', $this->plugin_url . 'userdeck.js', array('jquery') );
			
			register_setting( 'userdeck', 'userdeck', array( $this, 'validate_settings' ) );
			
			if ( isset( $_POST['userdeck-submit'] ) ) {
				if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'userdeck-options' ) ) {
					$options = $this->validate_settings( array('guides_key' => $_POST['guides_key']) );
					$this->update_settings( $options );
					exit;
				}
			}
			
			if (current_user_can('publish_pages')) {
				if ( isset( $_POST['userdeck-page-create'] ) ) {
					if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'userdeck-page-create' ) ) {
						$page_title = wp_kses( trim( $_POST['page_title'] ), array() );
						
						$options = $this->get_settings();
			
						$guides_key = $options['guides_key'];
						
						if (!empty($page_title) && !empty($guides_key)) {
							$page_id = UserDeck_Guide::create_page($page_title, $guides_key);
							
							wp_redirect( add_query_arg( array('page' => 'userdeck', 'page_added' => 1, 'page_id' => $page_id), 'options-general.php' ) );
							exit;
						}
					}
				}
			}
			
			if (current_user_can('edit_pages')) {
				if ( isset( $_POST['userdeck-page-add'] ) ) {
					if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'userdeck-page-add' ) ) {
						$page_id = absint( $_POST['page_id'] );
						
						$options = $this->get_settings();
			
						$guides_key = $options['guides_key'];
						
						if (!empty($page_id) && !empty($guides_key)) {
							$page_id = UserDeck_Guide::update_page($page_id, $guides_key);
							
							wp_redirect( add_query_arg( array('page' => 'userdeck', 'page_updated' => 1, 'page_id' => $page_id), 'options-general.php' ) );
							exit;
						}
					}
				}
			}
			
		}
		
		/**
		 * make sure that no dodgy stuff is trying to sneak through
		 * @param  array $input options to validate
		 * @return array        validated options
		 */
		public function validate_settings( $input ) {

			$input['guides_key'] = wp_kses( trim( $input['guides_key'] ), array() );

			return $input;

		}
		
		public function add_action_links( $links ) {
			
			$settings_link = '<a href="options-general.php?page=userdeck">Settings</a>';
			
			array_unshift( $links, $settings_link );
			
			return $links;
			
		}
		
	}

	function userdeck() {

		return UserDeck::instance();

	}
	
}
