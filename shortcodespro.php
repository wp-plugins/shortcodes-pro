<?php
/*
Plugin Name: Shortcodes Pro 
Version: 1.0.9.9
Plugin URI: http://www.mattvarone.com/featured-content/shortcodes-pro/
Description: Shortcodes Pro gives you complete control of your shortcodes and rich editor buttons.
Author: Matt Varone
Author URI: http://www.mattvarone.com/

Copyright 2011  (email: contact@mattvarone.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/**
* Shortcodes Pro Initialize
*
* @package Shortcodes Pro
* @author Matt Varone
*/
		
/*
|--------------------------------------------------------------------------
| SHORTCODES PRO CONSTANTS
|--------------------------------------------------------------------------
*/

define('MV_SHORTCODES_PRO_BASENAME', plugin_basename( __FILE__ ));
define('MV_SHORTCODES_PRO_URL', plugins_url('',__FILE__));
define('MV_SHORTCODES_PRO_PATH', plugin_dir_path(__FILE__));
define('MV_SHORTCODES_PRO_VERSION', '1.0.9.9');
define('MV_SHORTCODES_PRO_FOLDER', '/'.basename(dirname(__FILE__)));
define('MV_SHORTCODES_PRO_TRANSIENT_EXPIRE',60*60*24*30);
define('MV_SHORTCODES_PRO_DOMAIN', 'shortcodes-pro');
/*
|--------------------------------------------------------------------------
| SHORTCODES PRO LANGUAGE INTERNALIZATION
|--------------------------------------------------------------------------
*/

load_plugin_textdomain( MV_SHORTCODES_PRO_DOMAIN, false, MV_SHORTCODES_PRO_FOLDER.'/lan' );

/*
|--------------------------------------------------------------------------
| SHORTCODES PRO INCLUDES
|--------------------------------------------------------------------------
*/

if (is_admin())
require_once(MV_SHORTCODES_PRO_PATH.'inc/shortcodespro-options.php');

// require Shortcodes Pro classes
require_once(MV_SHORTCODES_PRO_PATH.'inc/class-shortcodespro-base.php');
require_once(MV_SHORTCODES_PRO_PATH.'inc/class-shortcodespro-type.php');
require_once(MV_SHORTCODES_PRO_PATH.'inc/class-shortcodespro-main.php');

/*
|--------------------------------------------------------------------------
| SHORTCODES PRO ON ACTIVATION
|--------------------------------------------------------------------------
*/

if ( !function_exists('mv_shortcodes_pro_activation') )
{
	
	/** 
	* Shortcodes Pro Activation
	*
	* @package Shortcodes Pro
	* @since 1.0.7
	*
	*/
	
	function mv_shortcodes_pro_activation()
	{
		// check compatibility
		if ( version_compare( get_bloginfo('version' ),'3.2') >= 0 )
		deactivate_plugins( basename( __FILE__ ) );
		
		// refresh cache
		delete_transient('sp.get.buttons');
		delete_transient('sp.get.buttons.main');
		delete_transient('sp.get.buttons.edit');	
		delete_transient('sp.get.quicktags.main');	
	}
	
	register_activation_hook( __FILE__, 'mv_shortcodes_pro_activation' );
}

/*
|--------------------------------------------------------------------------
| ADD THUMBNAILS SUPPORT
|--------------------------------------------------------------------------
*/
	
if ( !function_exists('mv_shortcodes_pro_thumbnail_support') )
{
	/** 
	* Thumbnail Support
	*
	* @package Shortcodes Pro
	* @since 1.0.4
	*
	*/
	
	function mv_shortcodes_pro_thumbnail_support()
	{
		if (function_exists('add_theme_support'))
		add_theme_support('post-thumbnails');
	}
}

add_action( 'after_setup_theme', 'mv_shortcodes_pro_thumbnail_support', 9999 );

/*
|--------------------------------------------------------------------------
| SHORTCODES PRO MAIN CLASS INITIALIZE
|--------------------------------------------------------------------------
*/

if (!function_exists('mv_shortcodes_pro_init'))
{
	
	/** 
	* Initializes the main Shortcodes Pro class.
	*
	* @package Shortcodes Pro
	* @since 1.0
	*
	*/
	
	function mv_shortcodes_pro_init()
	{
		new MV_Shortcodes_Pro();
	}
	
	add_action('init', 'mv_shortcodes_pro_init');
}