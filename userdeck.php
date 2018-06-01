<?php
/**
 * Plugin Name: UserDeck
 * Plugin URI: http://wordpress.org/plugins/userdeck
 * Description: Embedded customer support from <a href="http://userdeck.com?utm_source=wordpress&utm_medium=link&utm_campaign=website">UserDeck</a> that embeds into your website.
 * Version: 1.1.5
 * Author: UserDeck
 * Author URI: http://userdeck.com?utm_source=wordpress&utm_medium=link&utm_campaign=website
 */

defined( 'ABSPATH' ) or die();

class UserDeck {
	
	protected static $instance;
	protected $plugin_path;
	protected $plugin_url;
	protected $admin_notices = array();
	protected $guide_page;
	
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
		
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		
		$this->plugin_path = trailingslashit( dirname( dirname( __FILE__ ) ) );
		$this->plugin_url = trailingslashit( plugins_url( '', dirname( __FILE__ ) ) );
		
		$this->add_actions();
		$this->add_filters();
		
	}
	
	public function add_actions() {
		
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'create_menu_page') );
			add_action( 'admin_menu', array( $this, 'create_tickets_page' ) );
			add_action( 'admin_init', array( $this, 'settings_init') );
			add_action( 'admin_init', array( $this, 'migrate_guides_shortcodes') );
			add_action( 'admin_init', array( $this, 'build_admin_notices') );
			add_action( 'admin_notices', array( $this, 'render_admin_notices') );
		}
		
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ), 99 );
		add_action( 'wp_head', array( $this, 'output_escaped_fragment_meta' ) );
		add_action( 'wp_footer', array( $this, 'output_conversations_overlay_code' ) );
		
	}
	
	public function add_filters() {
		
		if ( is_plugin_active( 'wordpress-seo/wp-seo.php' ) || is_plugin_active( 'wordpress-seo-premium/wp-seo-premium.php' ) ) {
			
			$this->guide_page = $this->get_guide_page();
			
			global $wpseo_sitemaps;
			
			if ( !empty( $this->guide_page ) ) {
				
				if ( $wpseo_sitemaps instanceof WPSEO_Sitemaps && method_exists( $wpseo_sitemaps, 'register_sitemap' ) ) {
					$wpseo_sitemaps->register_sitemap('userdeck', array( $this, 'register_sitemap' ) );
				}
				
				add_filter( 'wpseo_sitemap_index', array( $this, 'register_sitemap_index' ) );
				
			}
			
		}
		
		add_filter( 'the_content', array( $this, 'output_conversations_page' ) );
		add_filter( 'the_content', array( $this, 'output_guides_page' ) );
		
		$plugin = plugin_basename(__FILE__);
		add_filter( "plugin_action_links_$plugin", array( $this, 'add_action_links' ) );
		add_filter( 'plugin_row_meta', array( $this, 'add_plugin_meta_links' ), 10, 4 );
	}
	
	public static function install() {
		
		self::track_event('install');
		
	}
	
	public static function uninstall() {
		
		self::track_event('uninstall');
		
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
	
	public function add_admin_notice($type, $content) {
		
		array_unshift( $this->admin_notices, array(
			'type' => $type,
			'content' => $content,
		) );
		
	}
	
	public function render_admin_notices() {
	
		foreach ($this->admin_notices as $notice) {
			echo '<div class="' . $notice['type'] . '">';
			echo '<p>' . $notice['content'] . '</p>';
			echo '</div>';
		}
		
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
	
	public function has_guide_meta( $post ) {
		
		$guides_key = get_post_meta($post->ID, 'userdeck_guides_key', true);
		
		if (!empty($guides_key)) {
			return $guides_key;
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
		
		$guides_key = $this->has_guide_meta( $post );
		
		if ( empty( $guides_key ) ) {
			return '';
		}
		
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
		
		$guides_key = $this->has_guide_meta( $post );
		
		if ( empty( $guides_key ) ) {
			return;
		}

		$sitemap_url = 'https://userdeck.net/g/' . $guides_key . '/sitemap.xml';

		$request = wp_remote_get( $sitemap_url );

		$sitemap = '';

		if ( wp_remote_retrieve_response_code( $request ) == 200 ) {
			$sitemap = wp_remote_retrieve_body( $request );
		}
		
		$sitemap = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $sitemap);
		$sitemap = preg_replace('/'.preg_quote('<url><loc>https://userdeck.net/g/'.$guides_key.'</loc><lastmod>', '/').'(.*?)'.preg_quote('</lastmod><changefreq>', '/').'(.*?)'.preg_quote('</changefreq><priority>', '/').'(.*?)'.preg_quote('</priority></url>', '/').'/', '', $sitemap);
		$sitemap = str_replace('https://userdeck.net/g/'.$guides_key.'/', rtrim(get_permalink($post->ID), '/').'#!', $sitemap);
		
		if ( $wpseo_sitemaps instanceof WPSEO_Sitemaps && method_exists( $wpseo_sitemaps, 'set_sitemap' ) ) {
			$wpseo_sitemaps->set_sitemap( $sitemap );
		}
		
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
		
			$guides_key = $this->has_guide_meta( $post );
			
			if (!empty($guides_key)) {
				return $this->output_guides_code($guides_key);
			}
			
		}
		
		return $content;
		
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
	
	public function output_escaped_fragment_meta() {

		global $post;
		
		if ( isset( $post ) && is_page() ) {
			if ( $this->has_guide_meta( $post ) )
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
	public function build_admin_notices() {
		
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'userdeck' && isset( $_GET['settings_updated'] ) ) {
			$this->add_admin_notice( 'updated', 'Settings successfully saved.' );
		}
		
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'userdeck' && isset( $_GET['page_added'] ) ) {
			$this->add_admin_notice( 'updated', sprintf( 'Page created. <a href="%s">View page</a>', get_permalink( $_GET['page_id'] ) ) );
		}
		
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'userdeck' && isset( $_GET['page_updated'] ) ) {
			$this->add_admin_notice( 'updated', sprintf( 'Page updated. <a href="%s">View page</a>', get_permalink( $_GET['page_id'] ) ) );
		}

		// show a reminder to users who can update options
		if ( current_user_can( 'manage_options' ) ) {
			$options = $this->get_settings();

			if ( ( !isset( $options['account_key'] ) || !$options['account_key'] ) && ( !isset( $options['guides'] ) || !$options['guides'] ) ) {
				if ( !isset( $_GET['page'] ) || $_GET['page'] != 'userdeck' ) {
					$this->add_admin_notice( 'error', sprintf( '<strong>UserDeck is not setup</strong>. Please <a href="%s">configure the UserDeck settings</a> to use the plugin.', admin_url( 'admin.php?page=userdeck' ) ) );
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
		
		add_menu_page( 'Tickets', 'Tickets', 'read', 'userdeck_tickets', array($this, 'render_tickets_page'), 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz48c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IiB3aWR0aD0iNjNweCIgaGVpZ2h0PSI2M3B4IiB2aWV3Qm94PSIwIDAgNjMgNjMiIGVuYWJsZS1iYWNrZ3JvdW5kPSJuZXcgMCAwIDYzIDYzIiB4bWw6c3BhY2U9InByZXNlcnZlIj48cGF0aCBmaWxsPSIjOTk5OTk5IiBkPSJNMzEsMy45QzE1LjgsMy45LDMuNCwxNi4yLDMuNCwzMS41YzAsMTUuMywxMi40LDI3LjYsMjcuNiwyNy42YzE1LjMsMCwyNy42LTEyLjQsMjcuNi0yNy42QzU4LjcsMTYuMiw0Ni4zLDMuOSwzMSwzLjl6IE00Ni45LDM3LjVjMCwzLjEtMi40LDUuMy01LjEsNS4zaC02LjVMMzIsNDguM2MtMC4zLDAuNC0wLjcsMC44LTEuMSwwLjhjLTAuNCwwLTAuOS0wLjQtMS4xLTAuOGwtMy4zLTUuNGgtNi4xYy0yLjcsMC01LjQtMi4yLTUuNC01LjN2LTEzYzAtMy4xLDIuNy01LjcsNS40LTUuN2gyMS41YzIuNywwLDUuMSwyLjYsNS4xLDUuN1YzNy41eiIvPjwvc3ZnPg==' );
		
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
		
		include( $this->plugin_path . '/userdeck/views/admin-options.php' );
		
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
	
	protected static function track_event( $event ) {
		
		$params = array(
			'event'        => $event,
			'site_url'     => get_site_url(),
			'home_url'     => get_home_url(),
			'version'      => get_bloginfo( 'version' ),
			'site_lang'    => get_bloginfo( 'language' ),
			'admin_email'  => get_option( 'admin_email' ),
			'is_multisite' => is_multisite(),
			'php_version'  => PHP_VERSION,
		);
		
		wp_safe_remote_post( 'https://api.userdeck.com/webhooks/wordpress', array(
			'timeout'   => 25,
			'blocking'  => false,
			'sslverify' => false,
			'body'      => array(
				'payload' => wp_json_encode( $params ),
			),
		) );
		
	}
	
}

function userdeck_init() {
	global $userdeck;
	
	$userdeck = new UserDeck();
}

add_action( 'init', 'userdeck_init', 0 );
register_activation_hook( __FILE__, array( 'UserDeck', 'install' ) );
register_uninstall_hook( __FILE__, array( 'UserDeck', 'uninstall' ) );
