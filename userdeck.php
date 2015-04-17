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

if ( file_exists( dirname( __FILE__ ) . '/lib/userdeck.class.php' ) ) {
	require_once( dirname( __FILE__ ) . '/lib/userdeck.class.php' );
}

function userdeck_init() {
	global $userdeck;
	
	$userdeck = new UserDeck();
}

add_action( 'init', 'userdeck_init', 0 );
