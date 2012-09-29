<?php
/**
* Shortcodes Pro Functions
*
* @package		Shortcodes Pro
* @author       Matt Varone
*/


/**
 * Shortcodes Pro Safe Slug
 *
 * @access      public
 * @since       1.1.2
 * @return      string
*/

function mv_shortcodes_pro_safe_slug( $slug = "") {
	$slug = str_replace( '-', '', $slug );
	if ( is_numeric( substr( $slug, 0, 1 ) ) ) {
		$slug = 's'.$slug;
	}
	return esc_js($slug);
}


/**
 * Shortcodes Pro Get Capability
 *
 * @access      public
 * @since       1.1.4
 * @return      string
*/

function mv_shortcodes_pro_get_capability() {
	return apply_filters('mv_shortcodes_pro_capability','manage_options');
}