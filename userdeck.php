<?php
/**
 * Plugin Name: UserDeck
 * Plugin URI: http://userdeck.com/plugins/wordpress
 * Description: Embedded customer support from <a href="http://userdeck.com">UserDeck</a> that embeds into your website.
 * Version: 1.0.0
 * Author: UserDeck
 * Author URI: http://userdeck.com
 */

defined( 'ABSPATH' ) or die();

class UserDeck {
	
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
		
		add_shortcode( 'userdeck_guides', array( $this, 'output_guides_code') );
		
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

			if ( !isset( $options['guides_key'] ) || !$options['guides_key'] ) {
				if ( !isset( $_GET['page'] ) || $_GET['page'] != 'userdeck' ) {
					?>
						<div class="error" id="userdeck-notice">
							<p>
								<strong>UserDeck is not setup</strong>.
								Please <a href="options-general.php?page=userdeck">configure the UserDeck settings</a> to use the plugin.
							</p>
						</div>
					<?php
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
		
		?>
		
		<div id="userdeck-wrapper" class="wrap">
			<?php screen_icon( 'options-general' ); ?>
			<h2>UserDeck</h2>
			
			<?php if ($show_guides_options): ?>
				<h2>Guides</h2>
				
				<div id="poststuff">
					<div class="postbox-container" style="width:65%;">
						<?php if (current_user_can('publish_pages')) : ?>
							<form method="post" action="options-general.php?page=userdeck">
								<div class="postbox">
									<h3 class="hndle" style="cursor: auto;"><span>Create a Page</span></h3>
									
									<div class="inside">
										<p>Create a new page with the Guides shortcode.</p>
										
										<table class="form-table">
											<tbody>
												<tr valign="top">
													<th scope="row">
														<label for="page-title">Page Title</label>
													</th>
													<td>
														<input name="page_title" type="text" value="" id="page-title" />
													</td>
												</tr>
											</tbody>
										</table>
										
										<p>
											<?php wp_nonce_field('userdeck-page-create'); ?>
											<input type="hidden" name="guides_key" value="<?php echo $guides_key ?>" />
											<input class="button-primary" name="userdeck-page-create" type="submit" value="Create Page" />
										</p>
									</div>
								</div>
							</form>
						<?php endif; ?>
						
						<?php if (current_user_can('edit_pages')) : ?>
							<?php if (count($pages) > 0): ?>
								<form method="post" action="options-general.php?page=userdeck">
									<div class="postbox">
										<h3 class="hndle" style="cursor: auto;"><span>Add to Page</span></h3>
										
										<div class="inside">
											<p>Add the Guides shortcode to an existing page.</p>
											
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
														</td>
													</tr>
												</tbody>
											</table>
											
											<p>
												<?php wp_nonce_field('userdeck-page-add'); ?>
												<input type="hidden" name="guides_key" value="<?php echo $guides_key ?>" />
												<input class="button-primary" name="userdeck-page-add" type="submit" value="Add to Page" />
											</p>
										</div>
									</div>
								</form>
							<?php endif; ?>
						<?php endif; ?>
						
						<div class="postbox">
							<h3 class="hndle" style="cursor: auto;"><span>Copy Shortcode</h3>
							
							<div class="inside">
								<p>Copy the Guides shortcode to any of your pages or posts.</p>
								
								<?php $this->output_guides_shortcode($guides_key) ?>
							</div>
						</div>
					</div>
				</div>
			<?php else: ?>
				<p>
					A UserDeck account is required to use the plugin. <a href="http://userdeck.com?utm_source=wordpress&utm_medium=link&utm_campaign=website" target="_blank">Learn more about UserDeck</a>.
				</p>

				<p>Connect below to login or signup. Don't have an account? You can create a new account for free.</p>
				
				<p>
					<a href="javascript:void(0)" onclick="UserDeck.showConnect()" class="button button-primary button-hero" id="button-connect">Connect to UserDeck</a>
				</p>

				<script type="text/javascript">
					var plugin_settings_nonce = "<?php echo wp_create_nonce('userdeck-options'); ?>";
					var plugin_url = "<?php echo get_admin_url() . add_query_arg( array('page' => 'userdeck'), 'options-general.php' ); ?>";
				</script>
				
				<style type="text/css">
					#iframe-guides { display: none; box-shadow: 0 1px 1px rgba(0,0,0,.04); border: 1px solid #e5e5e5; padding: 2px; background: #fff; }
				</style>
			<?php endif; ?>
		</div>
		
		<?php
		
	}
	
	/**
	 * use the WordPress settings api to initiate the various settings
	 * and if it's a network settings page then validate & update any submitted settings
	 * @return null
	 */
	public function settings_init() {
		
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

function userdeck_init() {
	global $userdeck;
	
	$userdeck = new UserDeck();
}

add_action( 'init', 'userdeck_init', 0 );
