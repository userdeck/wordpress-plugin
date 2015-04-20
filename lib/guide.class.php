<?php

defined( 'ABSPATH' ) or die();

if ( !class_exists( 'UserDeck_Guide' ) ) {

	class UserDeck_Guide {

		const PROXY_URL = 'https://userdeck.net/g/';

		public function __construct() {

			$this->add_actions();
			$this->add_shortcode();

		}

		public function add_actions() {

			add_action( 'wp_head', array( $this, 'render_escaped_fragment_meta') );

		}

		public function add_shortcode() {

			add_shortcode( 'userdeck_guides', array( $this, 'render_code') );

		}

		public function render_escaped_fragment_meta()
		{

			global $post;
			
			if ( isset( $post ) && is_singular() && UserDeck_Guide::page_has_shortcode( $post->post_content ) ) {
				echo '<meta name="fragment" content="!">';
			}

		}

		public function escaped_fragment_request()
		{

			return isset( $_GET['_escaped_fragment_'] );

		}

		public function escaped_fragment_path()
		{

			$path = '';

			if ( $_GET['_escaped_fragment_'] ) {
				$path = $_GET['_escaped_fragment_'][0] == '/' ? substr( $_GET['_escaped_fragment_'], 1 ) : $_GET['_escaped_fragment_'];
			}

			return $path;

		}

		public function fetch_content($guides_key)
		{

			global $wp_version;

			$path = $this->escaped_fragment_path();

			$base_uri = static::PROXY_URL . $guides_key . '/';

			if ( $path == '' ) {
				$base_uri = untrailingslashit( $base_uri );
			}

			$request = wp_remote_get( $base_uri . $path, array(
				'timeout' => 6,
				'user-agent' => 'WordPress/' . $wp_version . '; UserDeck Plugin/' . UserDeck::VERSION .'; ' . home_url(),
			) );

			$content = '';

			if ( wp_remote_retrieve_response_code( $request ) == 200 ) {
				$content = wp_remote_retrieve_body( $request );
			}

			return $this->parse_content($content, $guides_key);
			
		}

		public function parse_content($content, $guides_key)
		{

			preg_match('/\<body\>(.*?)\<\/body\>/is', $content, $body);
			$body = $body[1];
			
			$content = strstr($body, '<div class="content">');
			
			$content = str_replace('/g/'.$guides_key.'/', get_permalink().'#!', $content);

			return $content;

		}
		
		/**
		 * output the userdeck guides javascript install code
		 * @return null
		 */
		public function render_code() {
			
			// retrieve the options
			$options = UserDeck::instance()->get_settings();
			
			$guides_key = $options['guides_key'];

			if ($this->escaped_fragment_request()) {
				echo $this->fetch_content($guides_key);
			}
			else {
				echo sprintf('<a href="http://userdeck.com" data-userdeck-guides="%s">Customer Support Software</a>', $guides_key);
				echo '<script src="//widgets.userdeck.com/guides.js"></script>';
			}

		}
		
		public static function generate_shortcode($guides_key) {
			
			return '[userdeck_guides key="'.$guides_key.'"]';
			
		}

		public static function page_has_shortcode($page_content) {
			
			return has_shortcode($page_content, 'userdeck_guides');

		}

		public static function create_page($page_title, $guides_key) {

			$page_id = wp_insert_post( array(
				'post_title'     => $page_title,
				'post_content'   => UserDeck_Guide::generate_shortcode($guides_key),
				'post_status'    => 'publish',
				'post_author'    => get_current_user_id(),
				'post_type'      => 'page',
				'comment_status' => 'closed',
			) );

			return $page_id;

		}

		public static function update_page($page_id, $guides_key) {

			$page = get_post($page_id);
			$page_content = $page->post_content;
			$page_content .= "\n" . UserDeck_Guide::generate_shortcode($guides_key);
			
			$page_id = wp_update_post( array(
				'ID'           => $page_id,
				'post_content' => $page_content,
			) );

			return $page_id;
			
		}

	}
	
}
