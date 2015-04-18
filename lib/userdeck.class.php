<?php

defined( 'ABSPATH' ) or die();

if ( !class_exists( 'UserDeck' ) ) {

	class UserDeck {

		protected static $instance;

		protected $plugin_path;

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
			
			register_activation_hook( __FILE__, array( $this, 'install' ) );
			register_deactivation_hook( __FILE__, array( $this, 'uninstall' ) );
			
			$this->add_actions();
			$this->add_filters();
			$this->add_shortcodes();
		}

		public function add_actions()
		{
			if ( is_admin() ) {
				add_action( 'admin_menu', array( $this, 'create_options_page') );
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

		public function add_shortcodes()
		{
			add_shortcode( 'userdeck_guides', array( $this, 'output_guides_code') );
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
		 * output the userdeck guides javascript install code
		 * @return null
		 */
		public function output_guides_code() {
			
			// retrieve the options
			$options = $this->get_settings();
			
			$guides_key = $options['guides_key'];
			
			?>
			
			<a href="http://userdeck.com" data-userdeck-guides="<?php echo $guides_key ?>">Customer Support Software</a>
			<script src="//widgets.userdeck.com/guides.js"></script>
			
			<?php
			
		}
		
		public function generate_guides_shortcode($guides_key) {
			
			return '[userdeck_guides key="'.$guides_key.'"]';
			
		}
		
		public function output_guides_shortcode($guides_key) {
			
			?>
			<input type="text" onfocus="this.select()" readonly="readonly" value='<?php echo $this->generate_guides_shortcode($guides_key) ?>' class="code" style="width: 350px;" />
			<?php
			
		}
		
		/**
		 * show a 'settings saved' notice
		 * and a friendly reminder if the guide key hasn't been entered
		 * @return null
		 */
		public function build_admin_alerts() {
			
			if ( isset( $_GET['page'] ) && $_GET['page'] == 'userdeck' && isset( $_GET['page_added'] ) ) {

				$message = sprintf( 'Page created. <a href="%s">View page</a>', get_permalink( $_GET['page_id'] ) );

				$this->add_admin_alert( 'updated', $message );
				
			}
			
			if ( isset( $_GET['page'] ) && $_GET['page'] == 'userdeck' && isset( $_GET['page_updated'] ) ) {
				
				$message = sprintf( 'Page updated. <a href="%s">View page</a>', get_permalink( $_GET['page_id'] ) );

				$this->add_admin_alert( 'updated', $message );
				
			}

			// show a reminder to users who can update options

			if ( current_user_can( 'manage_options' ) ) {

				$options = $this->get_settings();

				if ( !isset( $options['guides_key'] ) || !$options['guides_key'] ) {
					if ( !isset( $_GET['page'] ) || $_GET['page'] != 'userdeck' ) {
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
		public function create_options_page() {
			
			add_options_page('UserDeck Settings', 'UserDeck', 'manage_options', 'userdeck', array($this, 'render_options_page'));
			
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
			
			wp_enqueue_script( 'userdeck', plugins_url( '/userdeck.js' , __FILE__ ), array('jquery') );
			
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
							$page_id = wp_insert_post( array(
								'post_title'     => $page_title,
								'post_content'   => $this->generate_guides_shortcode($guides_key),
								'post_status'    => 'publish',
								'post_author'    => get_current_user_id(),
								'post_type'      => 'page',
								'comment_status' => 'closed',
							) );
							
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
							$page = get_post($page_id);
							$page_content = $page->post_content;
							$page_content .= "\n" . $this->generate_guides_shortcode($guides_key);
							
							$page_id = wp_update_post( array(
								'ID'           => $page_id,
								'post_content' => $page_content,
							) );
							
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
	
}
