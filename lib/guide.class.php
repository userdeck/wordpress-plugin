<?php

defined( 'ABSPATH' ) or die();

if ( !class_exists( 'UserDeck_Guide' ) ) {

	class UserDeck_Guide {

		public function __construct() {

			add_shortcode( 'userdeck_guides', array( $this, 'output_code') );

		}
		
		/**
		 * output the userdeck guides javascript install code
		 * @return null
		 */
		public function output_code() {
			
			// retrieve the options
			$options = UserDeck::instance()->get_settings();
			
			$guides_key = $options['guides_key'];
			
			?>
			
			<a href="http://userdeck.com" data-userdeck-guides="<?php echo $guides_key ?>">Customer Support Software</a>
			<script src="//widgets.userdeck.com/guides.js"></script>
			
			<?php
			
		}
		
		public static function generate_shortcode($guides_key) {
			
			return '[userdeck_guides key="'.$guides_key.'"]';
			
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
