<?php
/**
 * Plugin Name: UserDeck
 * Plugin URI: http://userdeck.com/plugins/wordpress
 * Description: WordPress plugin for <a href="http://userdeck.com">UserDeck</a>.
 * Version: 0.0.1
 * Author: UserDeck
 * Author URI: http://userdeck.com
 */

defined( 'ABSPATH' ) or die();

class UserDeck {
	
	private static $instance = null;

	public static function get_instance() {

		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;

	}
	
	/**
	 * class constructor
	 * register the activation and de-activation hooks and hook into a bunch of actions
	 */
	public function __construct() {
		
		register_activation_hook( __FILE__, array( $this, 'install' ) );
		register_deactivation_hook( __FILE__, array( $this, 'uninstall' ) );
		
		add_action( 'wp_footer', array( $this, 'output_feedback_code' ) );
		add_action( 'admin_menu', array( $this, 'create_options_page') );
		add_action( 'admin_init', array( $this, 'settings_init') );
		add_action( 'admin_notices', array( $this, 'admin_notice') );
		
		add_shortcode( 'userdeck', array( $this, 'parse_shortcode') );
		
		$plugin = plugin_basename(__FILE__);
		add_filter("plugin_action_links_$plugin", array($this, 'add_action_links'));
		
	}
	
	public function install() {}
	
	public function uninstall() {
		
		delete_option('userdeck');
		
	}
	
	/**
	 * retrieve the userdeck options
	 * @return array 'userdeck' options
	 */
	public function get_settings() {
		
		return get_option( 'userdeck', array('helpdesk_id' => null) );
		
	}

	/**
	 * update the userdeck options in the database
	 * @param  array $options new options settings to save
	 * @return null
	 */
	public function update_settings( $options ) {

		update_option( 'userdeck', $options );

	}
	
	public function parse_shortcode( $attrs ) {
		
		if (!isset($attrs['widget'])) {
			?>
			<strong>Error:</strong> Missing widget attribute in shortcode for UserDeck widget.
			<?php
			return;
		}
		
		$widget = $attrs['widget'];
		
		if ($widget == 'kb') {
			$this->output_knowledgebase_code();
		}
	}
	
	
	/**
	 * output the userdeck feedback javascript install code
	 * @return null
	 */
	public function output_feedback_code() {
		
		// retrieve the options
		$options = $this->get_settings();
		
		$helpdesk_id = $options['helpdesk_id'];
		
		?>

		<script type="text/javascript">
			(function() {
				var f = document.createElement('script'); f.type = 'text/javascript'; f.async = true; f.id = 'feedbackapp';
				f.src = 'http://userdeck.com/assets/bundles/helpdesk/feedback.js?id=<?php echo $helpdesk_id ?>';
				var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(f, s);
			})();
		</script>
		
		<?php
		
	}
	
	/**
	 * output the userdeck knowledge base javascript install code
	 * @return null
	 */
	public function output_knowledgebase_code() {
		
		// retrieve the options
		$options = $this->get_settings();
		
		$helpdesk_id = $options['helpdesk_id'];
		
		?>
		
		<div id="kbapp"></div>
		<script type="text/javascript">
			var KBAppOptions = {id : "<?php echo $helpdesk_id ?>"};
			document.write('\x3Cscript type="text/javascript" src="'
			+ 'http://userdeck.com/assets/bundles/helpdesk/kb.js'
			+ '">\x3C/script>');
		</script>
		
		<?php
		
	}
	
	/**
	 * show a 'settings saved' notice
	 * and a friendly reminder if the app ID or secret key haven't been entered
	 * @return null
	 */
	public function admin_notice() {

		// show a reminder to users who can update options

		if ( ! current_user_can( 'manage_options' ) )
			return;

		$options = $this->get_settings();

		if ( !isset( $options['helpdesk_id'] ) or !$options['helpdesk_id'] ) {
			echo '<div class="error" id="userdeck-notice"><p><strong>UserDeck needs some attention</strong>. ';
			if ( isset( $_GET['page'] ) and 'userdeck' == $_GET['page'] ) {
				echo 'Please enter your UserDeck helpdesk ID';
			} else {
				echo 'Please <a href="options-general.php?page=userdeck">configure the UserDeck settings</a>';
			}
			echo ' to using the plugin.</p></div>' . "\n";
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
		
		?>
		
		<div class="wrap">
		
			<?php screen_icon( 'options-general' ); ?>
			<h2>UserDeck Settings</h2>
			
			<p>An account at <a href="http://userdeck.com?utm_source=wordpress&utm_medium=link&utm_campaign=website" target="_blank">UserDeck</a> is required to use the plugin.
			You can <a href="http://userdeck.com?utm_source=wordpress&utm_medium=link&utm_campaign=website" target="_blank">create a new account</a> for free if you don't have one.</p>
			
			<div class="postbox-container" style="width:65%;">

				<form method="post" action="options.php">

					<?php settings_fields( 'userdeck' ); ?>

					<table class="form-table">
						<tbody>

							<tr valign="top">
								<th scope="row">HelpDesk ID</th>
								<td>
									<input name="userdeck[helpdesk_id]" type="text" value="<?php echo esc_attr( $options['helpdesk_id'] ); ?>">
								</td>
							</tr>

						</tbody>
						
					</table>
					
					<p class="submit">
						<input class="button-primary" name="userdeck-submit" type="submit" value="Save Settings">
					</p>
					
				</form>
				
			</div>
			
		</div>
		
		<?php
		
	}
	
	/**
	 * use the WordPress settings api to initiate the various settings
	 * and if it's a network settings page then validate & update any submitted settings
	 * @return null
	 */
	public function settings_init() {
		
		register_setting( 'userdeck', 'userdeck', array( $this, 'validate_settings' ) );
		if ( isset( $_REQUEST['_wpnonce'] ) and wp_verify_nonce( $_REQUEST['_wpnonce'], 'userdeck-options' ) ) {

			if ( isset( $_POST['userdeck-submit'] ) ) {
				$options = $this->validate_settings( $_POST['userdeck'] );
				$this->update_settings( $options );
				wp_redirect( add_query_arg( array('page' => 'userdeck', 'updated' => true), 'options-general.php' ) );
				exit;
			}

		}
		
	}
	
	/**
	 * make sure that no dodgy stuff is trying to sneak through
	 * @param  array $input options to validate
	 * @return array        validated options
	 */
	public function validate_settings( $input ) {

		$input['helpdesk_id'] = wp_kses( trim( $input['helpdesk_id'] ), array() );

		return $input;

	}
	
	public function add_action_links( $links ) {
		
		$settings_link = '<a href="options-general.php?page=userdeck">Settings</a>';
		
		array_unshift( $links, $settings_link );
		
		return $links;
		
	}
	
} // class

UserDeck::get_instance();
