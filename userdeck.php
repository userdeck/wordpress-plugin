<?php
/**
 * Plugin Name: UserDeck
 * Plugin URI: http://wordpress.org/plugins/userdeck
 * Description: Embedded customer support from <a href="http://userdeck.com?utm_source=wordpress&utm_medium=link&utm_campaign=website">UserDeck</a> that embeds into your website.
 * Version: 1.1.2
 * Author: UserDeck
 * Author URI: http://userdeck.com?utm_source=wordpress&utm_medium=link&utm_campaign=website
 */

defined( 'ABSPATH' ) or die();

class UserDeck {
	
	protected $guide_page;
	
	/**
	 * class constructor
	 * register the activation and de-activation hooks and hook into a bunch of actions
	 */
	public function __construct() {
		
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		
		if ( is_plugin_active( 'wordpress-seo/wp-seo.php' ) || is_plugin_active( 'wordpress-seo-premium/wp-seo-premium.php' ) ) {
		
			$this->guide_page = $this->get_guide_page();
		
			global $wpseo_sitemaps;
			$wpseo_sitemaps->register_sitemap('userdeck', array( $this, 'register_sitemap' ) );
			
			add_filter( 'wpseo_sitemap_index', array( $this, 'register_sitemap_index' ) );
			
		}
		
		register_activation_hook( __FILE__, array( $this, 'install' ) );
		register_deactivation_hook( __FILE__, array( $this, 'uninstall' ) );
		
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'create_menu_page') );
			add_action( 'admin_menu', array( $this, 'create_tickets_page' ) );
			add_action( 'admin_init', array( $this, 'settings_init') );
			add_action( 'admin_init', array( $this, 'migrate_guides_shortcodes') );
			add_action( 'admin_notices', array( $this, 'admin_notice') );
		}
		
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ), 99 );

		add_action( 'wp_head', array( $this, 'output_escaped_fragment_meta' ) );
		
		add_action( 'wp_footer', array( $this, 'output_conversations_overlay_code' ) );
		
		add_filter( 'the_content', array( $this, 'output_conversations_page' ) );
		add_filter( 'the_content', array( $this, 'output_guides_page' ) );
		
		add_shortcode( 'userdeck_guides', array( $this, 'output_guides_shortcode') );
		
		$plugin = plugin_basename(__FILE__);
		add_filter("plugin_action_links_$plugin", array($this, 'add_action_links'));
		
		add_filter( 'plugin_row_meta', array( $this, 'add_plugin_meta_links' ), 10, 4 );
		
	}
	
	public function install() {}
	
	public function uninstall() {
		
		delete_option('userdeck');
		
	}
	
	public function migrate_guides_shortcodes() {
		
		$options = $this->get_settings();
		
		if ( isset( $options['migrate_guides_shortcodes'] ) && $options['migrate_guides_shortcodes'] == 1 ) {
			return;
		}
		
		$pages = get_pages(array('post_type' => 'page'));
		
		foreach ($pages as $page) {
			if ( has_shortcode( $page->post_content, 'userdeck_guides' ) ) {
				
				$page_content = strip_shortcodes($page->post_content);
				
				$guides_key = $options['guides_key'];
				
				update_post_meta( $page->ID, 'userdeck_guides_key', $guides_key );
				
				wp_update_post( array(
					'ID'           => $page->ID,
					'post_content' => $page_content,
				) );
				
			}
		}
		
		$this->update_settings(array('migrate_guides_shortcodes' => 1));
		
	}
	
	/**
	 * retrieve the userdeck options
	 * @return array 'userdeck' options
	 */
	public function get_settings() {
		
		$defaults = array(
			'account_key' => null,
			'mailboxes' => null,
			'guides' => null,
			'ticket_portal' => 0,
			'overlay_widget' => 0,
			'mailbox_id' => null,
		);
		
		$options = get_option( 'userdeck', $defaults );
		
		$options = wp_parse_args( $options, $defaults );
		
		return $options;
		
	}

	/**
	 * update the userdeck options in the database
	 * @param  array $options new options settings to save
	 * @return null
	 */
	public function update_settings( $options ) {
		
		$options = wp_parse_args($options, $this->get_settings());

		update_option( 'userdeck', $options );

	}
	
	public function has_guide_meta() {
		
		global $post;
		
		$guides_key = get_post_meta($post->ID, 'userdeck_guides_key', true);
		
		if (!empty($guides_key)) {
			return true;
		}
		
		return false;
		
	}
	
	public function admin_bar_menu()
	{
		
		$options = $this->get_settings();
		
		if ( $options['ticket_portal'] != 1 ) {
			return;
		}
		
		global $wp_admin_bar;
		
		$wp_admin_bar->add_menu(array(
			'title' => 'Tickets',
			'href'  => admin_url( 'admin.php?page=userdeck_tickets' ),
			'id'    => 'userdeck_admin_bar_menu',
		));
		
	}
	
	public function get_guide_page() {
		
		$posts = get_posts(array(
			'post_type' => 'page',
			'meta_key' => 'userdeck_guides_key',
			'posts_per_page' => 1,
		));
		
		if (!empty($posts)) {
			return $posts[0];
		}
		
		return null;
		
	}
	
	public function register_sitemap_index( $xml ) {
		
		global $wpseo_sitemaps;
		
		$post = $this->guide_page;
		
		$guides_key = get_post_meta($post->ID, 'userdeck_guides_key', true);

		$sitemap_url = 'https://userdeck.net/g/' . $guides_key . '/sitemap.xml';

		$request = wp_remote_get( $sitemap_url );

		$sitemap = '';

		if ( wp_remote_retrieve_response_code( $request ) == 200 ) {
			$sitemap = wp_remote_retrieve_body( $request );
		}
		
		preg_match('/'.preg_quote('<url><loc>https://userdeck.net/g/'.$guides_key.'</loc><lastmod>', '/').'(.*?)'.preg_quote('</lastmod><changefreq>', '/').'(.*?)'.preg_quote('</changefreq><priority>', '/').'(.*?)'.preg_quote('</priority></url>', '/').'/', $sitemap, $matches);
		
		$xml .= '<sitemap>
				<loc>' . wpseo_xml_sitemaps_base_url('userdeck-sitemap.xml' ) . '</loc>
				<lastmod>'.$matches[1].'</lastmod>
				</sitemap>';
		
		return $xml;
		
	}
	
	public function register_sitemap() {
		
		global $wpseo_sitemaps;
		
		$post = $this->guide_page;
		
		$guides_key = get_post_meta($post->ID, 'userdeck_guides_key', true);

		$sitemap_url = 'https://userdeck.net/g/' . $guides_key . '/sitemap.xml';

		$request = wp_remote_get( $sitemap_url );

		$sitemap = '';

		if ( wp_remote_retrieve_response_code( $request ) == 200 ) {
			$sitemap = wp_remote_retrieve_body( $request );
		}
		
		$sitemap = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $sitemap);
		$sitemap = preg_replace('/'.preg_quote('<url><loc>https://userdeck.net/g/'.$guides_key.'</loc><lastmod>', '/').'(.*?)'.preg_quote('</lastmod><changefreq>', '/').'(.*?)'.preg_quote('</changefreq><priority>', '/').'(.*?)'.preg_quote('</priority></url>', '/').'/', '', $sitemap);
		$sitemap = str_replace('https://userdeck.net/g/'.$guides_key.'/', rtrim(get_permalink($post->ID), '/').'#!', $sitemap);
		
		$wpseo_sitemaps->set_sitemap( $sitemap );
		
	}
	
	public function output_conversations_page( $content, $hide_list = true ) {
		
		global $post;
		
		if ( isset( $post ) && is_page() ) {
		
			$account_key = get_post_meta($post->ID, 'userdeck_account_key', true);
			$mailbox_id = get_post_meta($post->ID, 'userdeck_mailbox_id', true);
			
			if (!empty($account_key)) {
				return $this->output_conversations_inline_code($account_key, $mailbox_id, $hide_list);
			}
			
		}
		
		return $content;
		
	}
	
	/**
	 * output the userdeck conversations overlay javascript install code
	 * @return null
	 */
	public function output_conversations_overlay_code($hide_list = false) {
		
		$options = $this->get_settings();
		
		if ( $options['overlay_widget'] != 1 ) {
			return;
		}
		
		$account_key = $options['account_key'];
		$mailbox_id = $options['mailbox_id'];
		
		?>
			<script>
			userdeck_settings = {
				<?php
				if ( is_user_logged_in() ) :
					$current_user = wp_get_current_user();
					?>
					customer_email: '<?php echo $current_user->user_email ?>',
					customer_name: '<?php echo $current_user->user_firstname . ' ' . $current_user->user_lastname ?>',
					customer_external_id: '<?php echo $current_user->ID ?>',
					<?php
				endif;
				?>
				<?php
				if ( !empty($mailbox_id) ) :
				?>
				mailbox_id: '<?php echo $mailbox_id ?>',
				<?php
				endif;
				?>
				conversations_overlay: {"key":"<?php echo $account_key ?>","settings":{
					<?php if ($hide_list): ?>
					"hide_conversation_list":true
					<?php endif; ?>
				}}
			};

			(function(s,o,g,a,m){a=s.createElement(o),m=s.getElementsByTagName(o)[0];
			a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
			})(document,'script','//widgets.userdeck.com/conversations.js');
			</script>
			<noscript><a href="http://userdeck.com">Customer Support Software</a></noscript>
		<?php
		
	}
	
	/**
	 * output the userdeck conversations inline javascript install code
	 * @return null
	 */
	public function output_conversations_inline_code($account_key, $mailbox_id = null, $hide_list = true) {
		
		ob_start();
		?>
			<div id="ud-contact"></div>
			<script src="//widgets.userdeck.com/conversations.js"></script>
			<script>
			userdeck_settings = {
				<?php
				if ( is_user_logged_in() ) :
					$current_user = wp_get_current_user();
					?>
					customer_email: '<?php echo $current_user->user_email ?>',
					customer_name: '<?php echo $current_user->user_firstname . ' ' . $current_user->user_lastname ?>',
					customer_external_id: '<?php echo $current_user->ID ?>',
					<?php
				endif;
				?>
				<?php
				if ( !empty($mailbox_id) ) :
				?>
				mailbox_id: '<?php echo $mailbox_id ?>'
				<?php
				endif;
				?>
			};

			UserDeck.factory("conversations", {"key":"<?php echo $account_key ?>","settings":{
				<?php if ($hide_list): ?>
				"hide_conversation_list":true
				<?php endif; ?>
			},"el":"#ud-contact"});
			</script>
			<noscript><a href="http://userdeck.com">Customer Support Software</a></noscript>
		<?php
		$content = ob_get_clean();
		
		return $content;
		
	}
	
	public function output_guides_page( $content ) {
		
		global $post;
		
		if ( isset( $post ) && is_page() ) {
		
			$guides_key = get_post_meta($post->ID, 'userdeck_guides_key', true);
			
			if (!empty($guides_key)) {
				return $this->output_guides_code($guides_key);
			}
			
		}
		
		return $content;
		
	}
	
	public function output_guides_shortcode() {
		
		// retrieve the options
		$options = $this->get_settings();
		
		$guides_key = $options['guides_key'];
		
		return $this->output_guides_code($guides_key);
		
	}
	
	/**
	 * output the userdeck guides javascript install code
	 * @return null
	 */
	public function output_guides_code($guides_key) {
		
		$content = '';

		if (isset( $_GET['_escaped_fragment_'] )) {

			$path = '';

			if ( $_GET['_escaped_fragment_'] ) {
				$path = $_GET['_escaped_fragment_'][0] == '/' ? substr( $_GET['_escaped_fragment_'], 1 ) : $_GET['_escaped_fragment_'];
			}

			$base_uri = 'https://userdeck.net/g/' . $guides_key . '/';

			if ( $path == '' ) {
				$base_uri = untrailingslashit( $base_uri );
			}

			$request = wp_remote_get( $base_uri . $path );

			$content = '';

			if ( wp_remote_retrieve_response_code( $request ) == 200 ) {
				$content = wp_remote_retrieve_body( $request );
			}

			preg_match('/\<body\>(.*?)\<\/body\>/is', $content, $body);
			$body = $body[1];
			
			$content = strstr($body, '<div class="content">');
			
			$content = str_replace('/g/'.$guides_key.'/', get_permalink().'#!', $content);

		}
		else {
			
			$content = sprintf('<a href="http://userdeck.com" data-userdeck-guides="%s">Customer Support Software</a>', $guides_key);
			$content .= '<script src="//widgets.userdeck.com/guides.js"></script>';
			
		}
		
		return $content;
		
	}
	
	public function generate_guides_shortcode($guides_key) {
		
		return '[userdeck_guides key="'.$guides_key.'"]';
		
	}
	
	public function output_escaped_fragment_meta() {

		global $post;
		
		if ( isset( $post ) && is_page() ) {
			if ($this->has_guide_meta() || has_shortcode( $post->post_content, 'userdeck_guides' ))
			?>
			<meta name="fragment" content="!">
			<?php
		}

	}
	
	/**
	 * show a 'settings saved' notice
	 * and a friendly reminder if the app ID or secret key haven't been entered
	 * @return null
	 */
	public function admin_notice() {
		
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'userdeck' && isset( $_GET['settings_updated'] ) ) {
			
			?>
			<div class="updated">
				<p>Settings successfully saved.</p>
			</div>
			<?php
			
		}
		
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

			if ( ( !isset( $options['account_key'] ) || !$options['account_key'] ) && ( !isset( $options['guides'] ) || !$options['guides'] ) ) {
				if ( !isset( $_GET['page'] ) || $_GET['page'] != 'userdeck' ) {
					?>
						<div class="error" id="userdeck-notice">
							<p>
								<strong>UserDeck is not setup</strong>.
								Please <a href="<?php echo admin_url('admin.php?page=userdeck') ?>">configure the UserDeck settings</a> to use the plugin.
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
	public function create_menu_page() {
		
		add_menu_page('UserDeck', 'UserDeck', 'manage_options', 'userdeck', array($this, 'render_options_page'), 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz48c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IiB3aWR0aD0iNjNweCIgaGVpZ2h0PSI2M3B4IiB2aWV3Qm94PSIwIDAgNjMgNjMiIGVuYWJsZS1iYWNrZ3JvdW5kPSJuZXcgMCAwIDYzIDYzIiB4bWw6c3BhY2U9InByZXNlcnZlIj48cGF0aCBmaWxsPSIjOTk5OTk5IiBkPSJNNTIuNSwzSDEwLjVDNS45LDMsMiw3LjMsMiwxMS45djI4LjdjMCw0LjYsMy45LDguMyw4LjUsOC4zaDIyLjFsMTIuOCwxMC4yYzAuMywwLjIsMC43LDAuNCwxLDAuNGMwLjIsMCwwLjQtMC4xLDAuNy0wLjJjMC42LTAuMywwLjktMC45LDAuOS0xLjVWNDloNC41YzQuNiwwLDguNS0zLjgsOC41LTguM1YxMS45QzYxLDcuMyw1Ny4xLDMsNTIuNSwzeiBNMjIuNCwzMi4xbC0yLjIsMi4yYy0wLjMsMC4zLTAuOCwwLjMtMS4xLDBMMTEsMjYuMWMtMC4zLTAuMy0wLjMtMC44LDAtMS4xbDguMi04LjJjMC4zLTAuMywwLjgtMC4zLDEuMSwwbDIuMiwyLjJjMC4zLDAuMywwLjMsMC44LDAsMS4xTDE3LjUsMjVjLTAuMywwLjMtMC4zLDAuOCwwLDEuMWw0LjksNC45QzIyLjcsMzEuNCwyMi43LDMxLjgsMjIuNCwzMi4xeiBNMzcuOCwxNC40bC03LjQsMjQuOWMtMC4xLDAuNC0wLjUsMC42LTAuOSwwLjVMMjYuNiwzOWMtMC40LTAuMS0wLjYtMC41LTAuNS0wLjlsNy40LTI0LjljMC4xLTAuNCwwLjUtMC42LDAuOS0wLjVsMi45LDAuOUMzNy43LDEzLjUsMzcuOSwxNCwzNy44LDE0LjR6IE01Mi4zLDI2LjFsLTguMiw4LjJjLTAuMywwLjMtMC44LDAuMy0xLjEsMGwtMi4yLTIuMmMtMC4zLTAuMy0wLjMtMC44LDAtMS4xbDQuOS00LjljMC4zLTAuMywwLjMtMC44LDAtMS4xbC00LjktNC45Yy0wLjMtMC4zLTAuMy0wLjgsMC0xLjFsMi4yLTIuMmMwLjMtMC4zLDAuOC0wLjMsMS4xLDBsOC4yLDguMkM1Mi42LDI1LjMsNTIuNiwyNS44LDUyLjMsMjYuMXoiLz48L3N2Zz4=');
		
	}
	
	public function create_tickets_page() {
		
		$options = $this->get_settings();
		
		if ( $options['ticket_portal'] != 1 ) {
			return;
		}
		
		add_object_page( 'Tickets', 'Tickets', 'read', 'userdeck_tickets', array($this, 'render_tickets_page'), 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz48c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IiB3aWR0aD0iNjNweCIgaGVpZ2h0PSI2M3B4IiB2aWV3Qm94PSIwIDAgNjMgNjMiIGVuYWJsZS1iYWNrZ3JvdW5kPSJuZXcgMCAwIDYzIDYzIiB4bWw6c3BhY2U9InByZXNlcnZlIj48cGF0aCBmaWxsPSIjOTk5OTk5IiBkPSJNMzEsMy45QzE1LjgsMy45LDMuNCwxNi4yLDMuNCwzMS41YzAsMTUuMywxMi40LDI3LjYsMjcuNiwyNy42YzE1LjMsMCwyNy42LTEyLjQsMjcuNi0yNy42QzU4LjcsMTYuMiw0Ni4zLDMuOSwzMSwzLjl6IE00Ni45LDM3LjVjMCwzLjEtMi40LDUuMy01LjEsNS4zaC02LjVMMzIsNDguM2MtMC4zLDAuNC0wLjcsMC44LTEuMSwwLjhjLTAuNCwwLTAuOS0wLjQtMS4xLTAuOGwtMy4zLTUuNGgtNi4xYy0yLjcsMC01LjQtMi4yLTUuNC01LjN2LTEzYzAtMy4xLDIuNy01LjcsNS40LTUuN2gyMS41YzIuNywwLDUuMSwyLjYsNS4xLDUuN1YzNy41eiIvPjwvc3ZnPg==' );
		
	}
	
	public function render_tickets_page() {
		
		$options = $this->get_settings();
		
		$account_key = $options['account_key'];
		$mailbox_id = $options['mailbox_id'];
		?>
		
		<div class="wrap">
			<h2>Tickets</h2>
			
			<div id="poststuff">
				<div class="postbox-container" style="width:65%;">
				
					<?php echo $this->output_conversations_inline_code($account_key, $mailbox_id, false); ?>
					
				</div>
			</div>
		</div>
		<?php
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

		$account_key = $options['account_key'];
		$mailboxes = $options['mailboxes'];
		$guides = $options['guides'];
		$ticket_portal = $options['ticket_portal'];
		$overlay_widget = $options['overlay_widget'];
		$mailbox_id = $options['mailbox_id'];
		
		$show_options = false;
		$show_conversations_options = false;
		$show_guides_options = false;
		
		if ($account_key || $guides) {
			$show_options = true;
		}
		
		if ($account_key && $mailboxes) {
			$show_conversations_options = true;
		}
		
		if ($guides) {
			$show_guides_options = true;
		}

		if ( isset( $_GET['tab'] ) ) {
			$tab = $_GET['tab'];
		}
		else {
			$tab = 'conversations';
		}
		?>
		
		<div id="userdeck-wrapper" class="wrap">
			<h2>UserDeck</h2>

			<p><a href="http://userdeck.com?utm_source=wordpress&utm_medium=link&utm_campaign=website" target="_blank">UserDeck</a> provides customer support software that embeds into your WordPress website.</p>
			
			<?php if ($show_options): ?>
				<h2 class="nav-tab-wrapper" id="userdeck-tabs">
					<a href="<?php echo admin_url('admin.php?page=userdeck&tab=conversations') ?>" id="conversations-tab" class="nav-tab <?php if ($tab == 'conversations'): ?>nav-tab-active<?php endif; ?>">Conversations</a>
					<a href="<?php echo admin_url('admin.php?page=userdeck&tab=guides') ?>" id="guides-tab" class="nav-tab <?php if ($tab == 'guides'): ?>nav-tab-active<?php endif; ?>">Guides</a>
				</h2>
				
				<?php if ($tab == 'conversations'): ?>
					<?php if ($show_conversations_options): ?>
						<p>Conversations is ticketing system that embeds either inline on any page of your WordPress site as a contact form or an overlay widget.</p>
						<p>You can also allow your users to manage tickets from the WordPress control panel as a ticket portal.</p>
						
						<div id="poststuff">
							<div class="postbox-container" style="width:65%;">
								<form method="post" action="<?php echo admin_url('admin.php?page=userdeck') ?>">
									<div class="postbox">
										<h3 class="hndle" style="cursor: auto;"><span>Global Settings</span></h3>
										
										<div class="inside">
											<table class="form-table">
												<tbody>
													<tr valign="top">
														<th scope="row">
															Ticket Portal
														</th>
														<td>
															<input name="ticket_portal" type="checkbox" value="on" id="ticket-portal" class="checkbox double"<?php if ($ticket_portal == 1): ?> checked<?php endif; ?> />
															<label for="ticket-portal">Enable Ticket Portal</label>
															<br class="clear">
															<p class="description">Enable to allow your WordPress users to manage tickets if logged in from the control panel menu.</p>
														</td>
													</tr>
													<tr valign="top">
														<th scope="row">
															Overlay Widget
														</th>
														<td>
															<input name="overlay_widget" type="checkbox" value="on" id="overlay-widget" class="checkbox double"<?php if ($overlay_widget == 1): ?> checked<?php endif; ?> />
															<label for="overlay-widget">Enable Overlay Widget</label>
															<br class="clear">
															<p class="description">Enable to show an overlay widget which lets website visitors contact you on any page of your WordPress site and manage conversations.</p>
														</td>
													</tr>
													<tr valign="top">
														<th scope="row">
															<label for="global-mailbox-name">Mailbox</label>
														</th>
														<td>
															<select name="mailbox_id" id="global-mailbox-name">
																<option value=""></option>
																<?php foreach ($mailboxes as $mailbox): ?>
																	<option value="<?php echo $mailbox['id'] ?>"<?php if ($mailbox_id == $mailbox['id']): ?> selected<?php endif; ?>><?php echo $mailbox['name'] ?></option>
																<?php endforeach; ?>
															</select>
															<br class="clear">
															<p class="description">The mailbox to use for ticket portal and overlay widgets.</p>
														</td>
													</tr>
												</tbody>
											</table>
											
											<p>
												<?php wp_nonce_field('userdeck-page-settings'); ?>
												<input class="button-primary" name="userdeck-page-settings" type="submit" value="Save Changes" />
											</p>
										</div>
									</div>
								</form>
								
								<?php if (current_user_can('publish_pages')) : ?>
									<form method="post" action="<?php echo admin_url('admin.php?page=userdeck') ?>">
										<div class="postbox">
											<h3 class="hndle" style="cursor: auto;"><span>Create a Contact Form Page</span></h3>
											
											<div class="inside">
												<p>Create a new page with the Conversations inline widget as a contact form.</p>
												
												<table class="form-table">
													<tbody>
														<tr valign="top">
															<th scope="row">
																<label for="conversations-mailbox-name-create">Mailbox</label>
															</th>
															<td>
																<select name="mailbox_id" id="conversations-mailbox-name-create">
																	<option value=""></option>
																	<?php foreach ($mailboxes as $mailbox): ?>
																		<option value="<?php echo $mailbox['id'] ?>"><?php echo $mailbox['name'] ?></option>
																	<?php endforeach; ?>
																</select>
																<br class="clear">
																<p class="description">The mailbox to use for the contact form page.</p>
															</td>
														</tr>
														<tr valign="top">
															<th scope="row">
																<label for="conversations-page-title">Page Title</label>
															</th>
															<td>
																<input name="page_title" type="text" value="" placeholder="Contact" id="conversations-page-title" />
																<br class="clear">
																<p class="description">The title of the new contact form page to create.</p>
															</td>
														</tr>
													</tbody>
												</table>
												
												<p>
													<?php wp_nonce_field('userdeck-page-conversations-create'); ?>
													<input type="hidden" name="account_key" value="<?php echo $account_key ?>" />
													<input class="button-primary" name="userdeck-page-conversations-create" type="submit" value="Create Page" />
												</p>
											</div>
										</div>
									</form>
								<?php endif; ?>
								
								<?php if (current_user_can('edit_pages')) : ?>
									<?php if (count($pages) > 0): ?>
										<form method="post" action="<?php echo admin_url('admin.php?page=userdeck') ?>">
											<div class="postbox">
												<h3 class="hndle" style="cursor: auto;"><span>Add Contact Form to Page</span></h3>
												
												<div class="inside">
													<p>Add the Conversations inline widget as a contact form to an existing page.</p>
													
													<table class="form-table">
														<tbody>
															<tr valign="top">
																<th scope="row">
																	<label for="conversations-mailbox-name-add">Mailbox</label>
																</th>
																<td>
																	<select name="mailbox_id" id="conversations-mailbox-name-add">
																		<option value=""></option>
																		<?php foreach ($mailboxes as $mailbox): ?>
																			<option value="<?php echo $mailbox['id'] ?>"><?php echo $mailbox['name'] ?></option>
																		<?php endforeach; ?>
																	</select>
																	<br class="clear">
																	<p class="description">The mailbox to use for the contact form page.</p>
																</td>
															</tr>
															<tr valign="top">
																<th scope="row">
																	<label for="conversations-page-id">Page</label>
																</th>
																<td>
																	<select name="page_id" id="conversations-page-id">
																		<?php foreach ($pages as $id => $title): ?>
																			<option value="<?php echo $id ?>"><?php echo $title ?></option>
																		<?php endforeach; ?>
																	</select>
																	<br class="clear">
																	<p class="description">The title of the existing page to update with a contact form.</p>
																</td>
															</tr>
														</tbody>
													</table>
													
													<p>
														<?php wp_nonce_field('userdeck-page-conversations-add'); ?>
														<input type="hidden" name="account_key" value="<?php echo $account_key ?>" />
														<input class="button-primary" name="userdeck-page-conversations-add" type="submit" value="Add to Page" />
													</p>
												</div>
											</div>
										</form>
									<?php endif; ?>
								<?php endif; ?>
							</div>
						</div>
					<?php else: ?>
						<div id="button-connect">
							<h3>Enable Conversations</h3>
							<p>Your account does not have the Conversations enabled. You can enable it below to start a free trial.</p>
							<a href="javascript:void(0)" onclick="UserDeck.showConnect('login', 'conversations')" class="button button-primary button-hero">Enable Conversations</a>
						</div>

						<div id="connect-frame"></div>
				
						<div id="feature-wrapper">
							<h2>Converations Features</h2>

							<ul>
								<li>
									A ticketing system to allow your customers to contact you through email and embedded widgets.
								</li>
								<li>
									Inline widget can be used as a contact form or a ticket portal to let users manage tickets from inside the WordPress control panel.
								</li>
								<li>
									Integrates with WordPress for authenticated sessions to track user name and email on tickets.
								</li>
							</ul>

							<p>
								<a href="http://userdeck.com/conversations?utm_source=wordpress&utm_medium=link&utm_campaign=website" target="_blank">Learn more about Conversations</a>
							</p>
						</div>

						<script type="text/javascript">
							var plugin_settings_nonce = "<?php echo wp_create_nonce('userdeck-options'); ?>";
							var plugin_url = "<?php echo get_admin_url() . add_query_arg( array('page' => 'userdeck'), 'admin.php' ); ?>";
						</script>
						
						<style type="text/css">
							#button-connect { margin: 40px 0; }
							#iframe-guides { display: none; box-shadow: 0 1px 1px rgba(0,0,0,.04); border: 1px solid #e5e5e5; padding: 2px; background: #fff; }
							#feature-wrapper ul { list-style-type: disc; padding-left: 20px; }
						</style>
					<?php endif; ?>
				<?php elseif ($tab == 'guides'): ?>
					<p>Guides is a knowledge base widget that embeds inline into any page of your WordPress pages and inherits the styling and blends in.</p>
					
					<?php if ($show_guides_options): ?>
						<div id="poststuff">
							<div class="postbox-container" style="width:65%;">
								<?php if (current_user_can('publish_pages')) : ?>
									<form method="post" action="<?php echo admin_url('admin.php?page=userdeck') ?>">
										<div class="postbox">
											<h3 class="hndle" style="cursor: auto;"><span>Create a Knowledge Base Page</span></h3>
											
											<div class="inside">
												<p>Create a new page with the Guides knowledge base inline widget.</p>
												
												<table class="form-table">
													<tbody>
														<tr valign="top">
															<th scope="row">
																<label for="guides-name-create">Guide</label>
															</th>
															<td>
																<select name="guides_key" id="guides-name-create">
																	<?php foreach ($guides as $guide): ?>
																		<option value="<?php echo $guide['key'] ?>"><?php echo $guide['name'] ?></option>
																	<?php endforeach; ?>
																</select>
																<br class="clear">
																<p class="description">The guide to use for the knowledge base page.</p>
															</td>
														</tr>
														<tr valign="top">
															<th scope="row">
																<label for="guides-page-title">Page Title</label>
															</th>
															<td>
																<input name="page_title" type="text" value="" placeholder="Support" id="guides-page-title" />
																<br class="clear">
																<p class="description">The title of the new knowledge base page to create.</p>
															</td>
														</tr>
													</tbody>
												</table>
												
												<p>
													<?php wp_nonce_field('userdeck-page-guides-create'); ?>
													<input class="button-primary" name="userdeck-page-guides-create" type="submit" value="Create Page" />
												</p>
											</div>
										</div>
									</form>
								<?php endif; ?>
								
								<?php if (current_user_can('edit_pages')) : ?>
									<?php if (count($pages) > 0): ?>
										<form method="post" action="<?php echo admin_url('admin.php?page=userdeck') ?>">
											<div class="postbox">
												<h3 class="hndle" style="cursor: auto;"><span>Add Knowledge Base to Page</span></h3>
												
												<div class="inside">
													<p>Add the Guides knowledge base inline widget to an existing page.</p>
													
													<table class="form-table">
														<tbody>
															<tr valign="top">
																<th scope="row">
																	<label for="guides-name-add">Guide</label>
																</th>
																<td>
																	<select name="guides_key" id="guides-name-add">
																		<?php foreach ($guides as $guide): ?>
																			<option value="<?php echo $guide['key'] ?>"><?php echo $guide['name'] ?></option>
																		<?php endforeach; ?>
																	</select>
																	<br class="clear">
																	<p class="description">The guide to use for the knowledge base page.</p>
																</td>
															</tr>
															<tr valign="top">
																<th scope="row">
																	<label for="guides-page-id">Page</label>
																</th>
																<td>
																	<select name="page_id" id="guides-page-id">
																		<?php foreach ($pages as $id => $title): ?>
																			<option value="<?php echo $id ?>"><?php echo $title ?></option>
																		<?php endforeach; ?>
																	</select>
																	<br class="clear">
																	<p class="description">The title of the existing page to update with a knowledge base.</p>
																</td>
															</tr>
														</tbody>
													</table>
													
													<p>
														<?php wp_nonce_field('userdeck-page-guides-add'); ?>
														<input class="button-primary" name="userdeck-page-guides-add" type="submit" value="Add to Page" />
													</p>
												</div>
											</div>
										</form>
									<?php endif; ?>
								<?php endif; ?>
							</div>
						</div>
					<?php else: ?>
						<div id="button-connect">
							<h3>Enable Guides</h3>
							<p>Your account does not have the Guides enabled. You can enable it below to start a free trial.</p>
							<a href="javascript:void(0)" onclick="UserDeck.showConnect('login', 'guides')" class="button button-primary button-hero">Enable Guides</a>
						</div>

						<div id="connect-frame"></div>
				
						<div id="feature-wrapper">
							<h2>Converations Features</h2>

							<ul>
								<li>
									A ticketing system to allow your customers to contact you through email and embedded widgets.
								</li>
								<li>
									Inline widget can be used as a contact form or a ticket portal to let users manage tickets from inside the WordPress control panel.
								</li>
								<li>
									Integrates with WordPress for authenticated sessions to track user name and email on tickets.
								</li>
							</ul>

							<p>
								<a href="http://userdeck.com/guides?utm_source=wordpress&utm_medium=link&utm_campaign=website" target="_blank">Learn more about Guides</a>
							</p>
						</div>

						<script type="text/javascript">
							var plugin_settings_nonce = "<?php echo wp_create_nonce('userdeck-options'); ?>";
							var plugin_url = "<?php echo get_admin_url() . add_query_arg( array('page' => 'userdeck'), 'admin.php' ); ?>";
						</script>
						
						<style type="text/css">
							#button-connect { margin: 40px 0; }
							#iframe-guides { display: none; box-shadow: 0 1px 1px rgba(0,0,0,.04); border: 1px solid #e5e5e5; padding: 2px; background: #fff; }
							#feature-wrapper ul { list-style-type: disc; padding-left: 20px; }
						</style>
					<?php endif; ?>
				<?php endif; ?>
			<?php else: ?>
				<p>
					An account is required to use the plugin. Don't have an account? You can create one for free.
				</p>
				
				<div id="button-connect">
					<h3>Connect to UserDeck</h3>
					<a href="javascript:void(0)" onclick="UserDeck.showConnect('login')" class="button button-primary button-hero">Login</a>
					<span style="margin: 0 10px; font-size: 16px; line-height: 42px;">or</span>
					<a href="javascript:void(0)" onclick="UserDeck.showConnect('signup')" class="button button-primary button-hero">Signup</a>
				</div>

				<div id="connect-frame"></div>
		
				<div id="feature-wrapper">
					<h2>Features</h2>
					
					<h3>Converations</h3>

					<ul>
						<li>
							A ticketing system to allow your customers to contact you through email and embedded widgets.
						</li>
						<li>
							Inline widget can be used as a contact form or a ticket portal to let users manage tickets from inside the WordPress control panel.
						</li>
						<li>
							Integrates with WordPress for authenticated sessions to track user name and email on tickets.
						</li>
					</ul>

					<p>
						<a href="http://userdeck.com/conversations?utm_source=wordpress&utm_medium=link&utm_campaign=website" target="_blank">Learn more about Conversations</a>
					</p>
					
					<br>

					<h3>Guides</h3>

					<ul>
						<li>
							A knowledge base widget that embeds inline to any page of your WordPress website.
						</li>
						<li>
							It inherits your theme's design and blends right in.
						</li>
						<li>
							You can embed a collection, category, or a single article instead of an entire knowledge base.
						</li>
						<li>
							Your users will save time by finding answers to common questions through self service.
						</li>
					</ul>

					<p>
						<a href="http://userdeck.com/guides?utm_source=wordpress&utm_medium=link&utm_campaign=website" target="_blank">Learn more about Guides</a>
					</p>
				</div>

				<script type="text/javascript">
					var plugin_settings_nonce = "<?php echo wp_create_nonce('userdeck-options'); ?>";
					var plugin_url = "<?php echo get_admin_url() . add_query_arg( array('page' => 'userdeck'), 'admin.php' ); ?>";
				</script>
				
				<style type="text/css">
					#button-connect { margin: 40px 0; }
					#iframe-guides { display: none; box-shadow: 0 1px 1px rgba(0,0,0,.04); border: 1px solid #e5e5e5; padding: 2px; background: #fff; }
					#feature-wrapper ul { list-style-type: disc; padding-left: 20px; }
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
		
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'userdeck' ) {

			wp_enqueue_script( 'userdeck', plugins_url( '/userdeck.js' , __FILE__ ), array('jquery') );
			
			register_setting( 'userdeck', 'userdeck', array( $this, 'validate_settings' ) );
			
			if ( isset( $_POST['userdeck-submit'] ) ) {
				if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'userdeck-options' ) ) {
					$options = array();
					
					if ( isset( $_POST['account_key'] ) ) {
						$options['account_key'] = $_POST['account_key'];
					}
					
					if ( isset( $_POST['mailboxes'] ) ) {
						$options['mailboxes'] = $_POST['mailboxes'];
					}
					
					if ( isset( $_POST['guides'] ) ) {
						$options['guides'] = $_POST['guides'];
					}
					
					$options = $this->validate_settings( $options );
					$this->update_settings( $options );
					exit;
				}
			}
			
			if ( isset( $_POST['userdeck-page-settings'] ) ) {
				if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'userdeck-page-settings' ) ) {
					$options = array();
					
					if ( isset( $_POST['ticket_portal'] ) && $_POST['ticket_portal'] == 'on' ) {
						$ticket_portal = 1;
					}
					else {
						$ticket_portal = 0;
					}
					
					if ( isset( $_POST['overlay_widget'] ) && $_POST['overlay_widget'] == 'on' ) {
						$overlay_widget = 1;
					}
					else {
						$overlay_widget = 0;
					}
					
					$options['ticket_portal'] = $ticket_portal;
					$options['overlay_widget'] = $overlay_widget;
					$options['mailbox_id'] = $_POST['mailbox_id'];
					
					$this->update_settings($options);
					
					wp_redirect( add_query_arg( array('page' => 'userdeck', 'settings_updated' => 1), 'admin.php' ) );
					exit;
				}
			}
			
			if (current_user_can('publish_pages')) {
				if ( isset( $_POST['userdeck-page-conversations-create'] ) ) {
					if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'userdeck-page-conversations-create' ) ) {
						$page_title = wp_kses( trim( $_POST['page_title'] ), array() );
						$account_key = $_POST['account_key'];
						$mailbox_id = $_POST['mailbox_id'];
						
						if (!empty($page_title) && !empty($account_key)) {
							$page_id = wp_insert_post( array(
								'post_title'     => $page_title,
								'post_status'    => 'publish',
								'post_author'    => get_current_user_id(),
								'post_type'      => 'page',
								'comment_status' => 'closed',
							) );
							
							update_post_meta( $page_id, 'userdeck_account_key', $account_key );
							
							if (!empty($mailbox_id)) {
								update_post_meta( $page_id, 'userdeck_mailbox_id', $mailbox_id );
							}
							
							wp_redirect( add_query_arg( array('page' => 'userdeck', 'page_added' => 1, 'page_id' => $page_id, 'tab' => 'conversations'), 'admin.php' ) );
							exit;
						}
					}
				}
				elseif ( isset( $_POST['userdeck-page-guides-create'] ) ) {
					if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'userdeck-page-guides-create' ) ) {
						$page_title = wp_kses( trim( $_POST['page_title'] ), array() );
						$guides_key = $_POST['guides_key'];
						
						if (!empty($page_title) && !empty($guides_key)) {
							$page_id = wp_insert_post( array(
								'post_title'     => $page_title,
								'post_status'    => 'publish',
								'post_author'    => get_current_user_id(),
								'post_type'      => 'page',
								'comment_status' => 'closed',
							) );
							
							update_post_meta( $page_id, 'userdeck_guides_key', $guides_key );
							
							wp_redirect( add_query_arg( array('page' => 'userdeck', 'page_added' => 1, 'page_id' => $page_id, 'tab' => 'guides'), 'admin.php' ) );
							exit;
						}
					}
				}
			}
			
			if (current_user_can('edit_pages')) {
				if ( isset( $_POST['userdeck-page-conversations-add'] ) ) {
					if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'userdeck-page-conversations-add' ) ) {
						$page_id = absint( $_POST['page_id'] );
						$account_key = $_POST['account_key'];
						$mailbox_id = $_POST['mailbox_id'];
						
						if (!empty($page_id) && !empty($account_key)) {
							update_post_meta( $page_id, 'userdeck_account_key', $account_key );
							
							if (!empty($mailbox_id)) {
								update_post_meta( $page_id, 'userdeck_mailbox_id', $mailbox_id );
							}
							
							wp_redirect( add_query_arg( array('page' => 'userdeck', 'page_updated' => 1, 'page_id' => $page_id, 'tab' => 'conversations'), 'admin.php' ) );
							exit;
						}
					}
				}
				elseif ( isset( $_POST['userdeck-page-guides-add'] ) ) {
					if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'userdeck-page-guides-add' ) ) {
						$page_id = absint( $_POST['page_id'] );
						$guides_key = $_POST['guides_key'];
						
						if (!empty($page_id) && !empty($guides_key)) {
							update_post_meta( $page_id, 'userdeck_guides_key', $guides_key );
							
							wp_redirect( add_query_arg( array('page' => 'userdeck', 'page_updated' => 1, 'page_id' => $page_id, 'tab' => 'guides'), 'admin.php' ) );
							exit;
						}
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

		if ( isset( $input['account_key'] ) ) {
			$input['account_key'] = wp_kses( trim( $input['account_key'] ), array() );
		}
		
		if ( isset( $input['guides_key'] ) ) {
			$input['guides_key'] = wp_kses( trim( $input['guides_key'] ), array() );
		}

		return $input;

	}
	
	public function add_action_links( $links ) {
		
		$settings_link = '<a href="'.admin_url('admin.php?page=userdeck').'">Settings</a>';
		
		array_unshift( $links, $settings_link );
		
		return $links;
		
	}
	
	public function add_plugin_meta_links( $links, $plugin_file ) {
		
		$plugin = plugin_basename(__FILE__);
			
		if ( $plugin == $plugin_file ) {
			$support_link = '<a href="http://userdeck.com/support" target="_blank">Support</a>';
			
			array_push( $links, $support_link );
		}
		
		return $links;
		
	}
	
}

function userdeck_init() {
	global $userdeck;
	
	$userdeck = new UserDeck();
}

add_action( 'init', 'userdeck_init', 0 );
