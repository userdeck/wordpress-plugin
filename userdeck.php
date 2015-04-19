<?php
/**
 * Plugin Name: UserDeck
 * Plugin URI: http://wordpress.org/plugins/userdeck
 * Description: Embedded customer support from <a href="http://userdeck.com?utm_source=wordpress&utm_medium=link&utm_campaign=website">UserDeck</a> that embeds into your website.
 * Version: 1.0.2
 * Author: UserDeck
 * Author URI: http://userdeck.com?utm_source=wordpress&utm_medium=link&utm_campaign=website
 */

defined( 'ABSPATH' ) or die();

function userdeck_initialize_plugin() {
	
	if ( file_exists( dirname( __FILE__ ) . '/lib/userdeck.class.php' ) ) {
		require_once( dirname( __FILE__ ) . '/lib/userdeck.class.php' );
		userdeck();
	}

}

// Initialize the plugin.
add_action( 'plugins_loaded', 'userdeck_initialize_plugin' );
