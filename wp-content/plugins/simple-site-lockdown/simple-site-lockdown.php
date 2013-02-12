<?php 
/*
Plugin Name: Simple Site Lockdown
Plugin URI: http://philipjohn.co.uk/category/plugins/simple-site-lockdown/
Description: Provides a really simple mechanism for locking down a site so that it's private to all but logged in admin users.
Version: 1.1
Author: Philip John
Author URI: http://philipjohn.co.uk
License: WTFPL
*/

// Initial sanity check
if (! defined('ABSPATH'))
	die('Please do not directly access this file');

/*
 * Localise the plugin
 */
load_plugin_textdomain('simple-site-lockdown');

/**
 * Plugin class
 */
class Simple_Site_Lockdown {

	/*
	 * Start your engines!
	 */
	function __construct(){
		add_action('send_headers', array(&$this, 'send_headers'));
	}
	
	/*
	 * Hook into send_headers
	 */
	function send_headers($wp){
		if (!is_user_logged_in()){
			auth_redirect();
		}
	}
}

$simple_site_lockdown = new Simple_Site_Lockdown();

?>