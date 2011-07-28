<?php
/**
* Shortcodes Pro Options
*
* @package Shortcodes Pro
* @author Matt Varone
* @since 1.0.9.8
*/

// If uninstall not called from WordPress exit 
if(!defined('WP_UNINSTALL_PLUGIN'))
exit();

global $wpdb;

// Delete options from options table 
delete_option('shortcodespro');

// Delete transients
delete_transient('sp.get.buttons');
delete_transient('sp.get.buttons.main');
delete_transient('sp.get.buttons.edit');	
delete_transient('sp.get.quicktags.main');