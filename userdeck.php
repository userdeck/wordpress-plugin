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
		
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'create_options_page') );
			add_action( 'admin_init', array( $this, 'settings_init') );
			add_action( 'admin_notices', array( $this, 'admin_notice') );
		}
		
		add_action( 'wp_footer', array( $this, 'output_feedback_code' ) );
		
		add_shortcode( 'userdeck_kb', array( $this, 'output_kb_code') );
		
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
	public function output_kb_code() {
		
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
	
	public function generate_kb_shortcode() {
		
		// retrieve the options
		$options = $this->get_settings();
		
		$helpdesk_id = $options['helpdesk_id'];
		
		return '[userdeck_kb]';
		
	}
	
	public function output_kb_shortcode() {
		
		?>
		<input type="text" onfocus="this.select()" readonly="readonly" value="<?php echo $this->generate_kb_shortcode() ?>" class="code" style="width: 150px;" />
		<?php
		
	}
	
	/**
	 * show a 'settings saved' notice
	 * and a friendly reminder if the app ID or secret key haven't been entered
	 * @return null
	 */
	public function admin_notice() {
		
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'userdeck' && isset( $_GET['page_added'] ) ) {
			
			?>
			<div class="updated">
				<p>Page created. <a href="<?php echo get_permalink($_GET['page_id']) ?>">View page</a></p>
			</div>
			<?php
			
		}
		
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'userdeck' && isset( $_GET['page_updated'] ) ) {
			
			?>
			<div class="updated">
				<p>Page updated. <a href="<?php echo get_permalink($_GET['page_id']) ?>">View page</a></p>
			</div>
			<?php
			
		}

		// show a reminder to users who can update options

		if ( current_user_can( 'manage_options' ) ) {

			$options = $this->get_settings();

			if ( !isset( $options['helpdesk_id'] ) || !$options['helpdesk_id'] ) {
				echo '<div class="error" id="userdeck-notice"><p><strong>UserDeck is not setup</strong>. ';
				if ( isset( $_GET['page'] ) && $_GET['page'] == 'userdeck' ) {
					echo 'Please enter your UserDeck helpdesk ID';
				} else {
					echo 'Please <a href="options-general.php?page=userdeck">configure the UserDeck settings</a>';
				}
				echo ' to use the plugin.</p></div>' . "\n";
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
								<th scope="row">
									<label for="userdesk-helpdesk-id">HelpDesk ID</label>
								</th>
								<td>
									<input name="userdeck[helpdesk_id]" type="text" value="<?php echo esc_attr( $options['helpdesk_id'] ); ?>" id="userdesk-helpdesk-id" />
								</td>
							</tr>

						</tbody>
						
					</table>
					
					<p class="submit">
						<input class="button-primary" name="userdeck-submit" type="submit" value="Save Settings" />
					</p>
					
				</form>
				
				<h2>Knowledge Base</h2>
				
				<?php if (current_user_can('publish_pages')) : ?>
					<h3>Create a Page</h3>
					
					<p>Create a new page with the knowledge base shortcode.</p>
					
					<form method="post" action="options-general.php?page=userdeck">
						
						<?php wp_nonce_field('userdeck-page-create'); ?>

						<table class="form-table">
							<tbody>

								<tr valign="top">
									<th scope="row">
										<label for="page-title">Page Title</label>
									</th>
									<td>
										<input name="page_title" type="text" value="" id="page-title" />
										<input class="button-secondary" name="userdeck-page-create" type="submit" value="Create Page" />
									</td>
								</tr>

							</tbody>
							
						</table>
						
					</form>
				<?php endif; ?>
				
				<?php if (current_user_can('edit_pages')) : ?>
					<?php if (count($pages) > 0): ?>
						<h3>Add to Page</h3>
						
						<p>Add the knowledge base shortcode to an existing page.</p>
						
						<form method="post" action="options-general.php?page=userdeck">
							
							<?php wp_nonce_field('userdeck-page-add'); ?>

							<table class="form-table">
								<tbody>

									<tr valign="top">
										<th scope="row">
											<label for="page-id">Page Title</label>
										</th>
										<td>
											<select name="page_id" id="page-id">
												<?php foreach ($pages as $id => $title): ?>
													<option value="<?php echo $id ?>"><?php echo $title ?></option>
												<?php endforeach; ?>
											</select>
											<input class="button-secondary" name="userdeck-page-add" type="submit" value="Add to Page" />
										</td>
									</tr>

								</tbody>
								
							</table>
							
						</form>
					<?php endif; ?>
				<?php endif; ?>
				
				<h3>Copy Shortcode</h3>
				
				<p>Copy the knowledge base shortcode to any of your pages or posts.</p>
				
				<?php $this->output_kb_shortcode() ?>
				
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
		
		if ( isset( $_POST['userdeck-submit'] ) ) {
			if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'userdeck-options' ) ) {
				$options = $this->validate_settings( $_POST['userdeck'] );
				$this->update_settings( $options );
				wp_redirect( add_query_arg( array('page' => 'userdeck', 'updated' => 1), 'options-general.php' ) );
				exit;
			}
		}
		
		if (current_user_can('publish_pages')) {
			if ( isset( $_POST['userdeck-page-create'] ) ) {
				if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'userdeck-page-create' ) ) {
					$page_title = wp_kses( trim( $_POST['page_title'] ), array() );
					
					if (!empty($page_title)) {
						$page_id = wp_insert_post( array(
							'post_title'     => $page_title,
							'post_content'   => $this->generate_kb_shortcode(),
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
					
					if (!empty($page_id)) {
						$page = get_post($page_id);
						$page_content = $page->post_content;
						$page_content .= "\n" . $this->generate_kb_shortcode();
						
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

		$input['helpdesk_id'] = wp_kses( trim( $input['helpdesk_id'] ), array() );

		return $input;

	}
	
	public function add_action_links( $links ) {
		
		$settings_link = '<a href="options-general.php?page=userdeck">Settings</a>';
		
		array_unshift( $links, $settings_link );
		
		return $links;
		
	}
	
} // class

add_action( 'init', array( 'UserDeck', 'get_instance' ) );
