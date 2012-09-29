<?php
/**
* Shortcodes Pro Options
*
* @package		Shortcodes Pro
* @author       Matt Varone
*/

add_action( 'admin_menu', 'mv_shortcodes_pro_create_options_page' );
add_action( 'admin_init', 'mv_shortcodes_pro_register_and_build_fields' );
add_action( 'wp_ajax_shortcodespro_sort', 'mv_shortcodes_pro_save_order' );

if ( ! function_exists( 'mv_shortcodes_pro_create_options_page' ) ) {

	/**
	* Create Options Page
	*
	* @package		Shortcodes Pro
	* @subpackage	Options
	* @since		1.0
	*
	*/

	function mv_shortcodes_pro_create_options_page() {

		// Get options
		$options = get_option( 'shortcodespro' );

		// Check hide-ui option
		if ( ! isset( $options['hide-ui'] ) OR $options['hide-ui'] != "yes" )  {
			add_submenu_page( 'edit.php?post_type=shortcodepro', 'Sort Buttons', __( 'Sort Buttons', 'shortcodes-pro' ), mv_shortcodes_pro_get_capability(), basename( __FILE__ ), 'mv_shortcodes_pro_sort_shortcodes' );
		}

		// Create Options Page
		add_options_page( 'Shortcodes Pro Options', 'Shortcodes Pro', mv_shortcodes_pro_get_capability(), __FILE__, 'mv_shortcodes_pro_options_page' );
	}
}

/*
|--------------------------------------------------------------------------
| SHORTCODES PRO SORT SHORTCODES PAGE
|--------------------------------------------------------------------------
*/

if ( ! function_exists( 'mv_shortcodes_pro_sort_shortcodes' ) ) {

	/**
	* Sort Shortcodes
	*
	* Generates the sort page layout
	*
	* @package		Shortcodes Pro
	* @subpackage	Options
	* @since		1.0
	*
	*/

	function mv_shortcodes_pro_sort_shortcodes() {
	?>

	<div class="wrap">
		<div id="icon-options-general" class="icon32"><br /></div>
		<h2>Sort Shortcodes Buttons</h2>

		<p><?php _e( 'Drag and drop the buttons to sort their display order.', 'shortcodes-pro' ); ?></p>

		<p><?php _e( 'Use the separator bellow to add a space between your buttons. <span class="note">( Double click separators to remove them )</span>.', 'shortcodes-pro' ); ?></p>

		<ul id="shortcodes-separator">
			<li id="separator" class="sortitem sep"><?php _e( 'Separator', 'shortcodes-pro' ); ?></li>
		</ul>

		<h3 id="h3-row1"><span><?php _e( 'Row 1', 'shortcodes-pro' ); ?></span> <img src="<?php echo admin_url('images/loading.gif'); ?>" id="loading-row1" class="loading" /></h3>

		<ul id="shortcodes-target-row1" class="target-row" data="row1">
			<?php

			$shortcode = mv_shortcodes_pro_get_buttons( 'row-1' );
			mv_shortcodes_pro_do_buttons( $shortcode );

			?>

		</ul>

		<h3 id="h3-row2"><span><?php _e( 'Row 2', 'shortcodes-pro' ); ?></span> <img src="<?php echo admin_url('images/loading.gif'); ?>" id="loading-row2" class="loading" /></h3>

		<ul id="shortcodes-target-row2" class="target-row" data="row2">
			<?php

			$shortcode = mv_shortcodes_pro_get_buttons( 'row-2' );
			mv_shortcodes_pro_do_buttons( $shortcode );

			?>
		</ul>

		<h3 id="h3-row3"><span><?php _e( 'Row 3', 'shortcodes-pro' ); ?></span> <img src="<?php echo admin_url('images/loading.gif'); ?>" id="loading-row3" class="loading" /></h3>

		<ul id="shortcodes-target-row3" class="target-row" data="row3">
			<?php

			$shortcode = mv_shortcodes_pro_get_buttons( 'row-3' );
			mv_shortcodes_pro_do_buttons( $shortcode );

			?>
		</ul>

		<h3 id="h3-row4"><span><?php _e( 'Row 4', 'shortcodes-pro' ); ?></span> <img src="<?php echo admin_url('images/loading.gif'); ?>" id="loading-row4" class="loading" /></h3>

		<ul id="shortcodes-target-row4" class="target-row" data="row4">
			<?php

			$shortcode = mv_shortcodes_pro_get_buttons( 'row-4' );
			mv_shortcodes_pro_do_buttons( $shortcode );

			?>
		</ul>

		</div>
	<?php
	}
}

if ( ! function_exists( 'mv_shortcodes_pro_sort_styles' ) ) {

	/**
	* Shortcode Sort page enqueue style
	*
	* @package		Shortcodes Pro
	* @subpackage	Options
	* @since		1.0
	*
	*/

	function mv_shortcodes_pro_sort_styles() {

		global $pagenow;

		$pages = array( 'edit.php' );
		if ( in_array( $pagenow, $pages ) )
			wp_enqueue_style( 'shortcodespro_sort', MV_SHORTCODES_PRO_URL . '/css/shortcodespro-sort.css' );
	}

	add_action( 'admin_enqueue_scripts', 'mv_shortcodes_pro_sort_styles' );
}

if ( ! function_exists( 'mv_shortcodes_pro_sort_scripts' ) ) {

	/**
	* Sort Scripts
	*
	* Sort page JS scripts.
	*
	* @package		Shortcodes Pro
	* @subpackage	Options
	* @since		1.0
	*
	*/

	function mv_shortcodes_pro_sort_scripts() {

		if ( mv_shorcodes_pro_is_page_sort() ) {
			wp_enqueue_script( 'shortcodespro-sort', MV_SHORTCODES_PRO_URL . '/js/shortcodespro-sort.js',array('jquery-ui-sortable','jquery-ui-droppable','jquery-ui-draggable'),  MV_SHORTCODES_PRO_VERSION, true );

			$params = array(
				'in_error' =>  __( 'There was an error saving the updates: ', 'shortcodes-pro' )
			);

			wp_localize_script('shortcodespro-sort', 'mv_shortcodespro_sort_js_params', $params );
		}
	}

	add_action( 'admin_enqueue_scripts', 'mv_shortcodes_pro_sort_scripts' );
}


if ( ! function_exists( 'mv_dynamic_to_top_is_page_sort' ) ) {

	/**
	 * Is page sort?
	 *
	 * @package		Shortcodes Pro
	 * @subpackage	Options
	 * @since		1.0
	*/

	function mv_shorcodes_pro_is_page_sort()
	{
		global $pagenow;

		if ( function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();

			if ( isset( $screen->base ) && $screen->base == 'shortcodepro_page_shortcodespro-options' )
				return true;
			else
				return false;
		}
		else {
			$pages = array( 'edit.php' );

			if ( in_array( $pagenow, $pages ) && isset( $_GET['page'] ) && $_GET['page'] == 'shortcodespro-options.php' )
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'mv_shortcodes_pro_save_order' ) ) {

	/**
	* Save Order
	*
	* Save the new buttons order.
	*
	* @package		Shortcodes Pro
	* @subpackage	Options
	* @since		1.0
	*
	*/

	function mv_shortcodes_pro_save_order() {
		global $wpdb;

		if ( ! isset( $_POST['order'] ) )
		return;

		$order = explode( ',', $_POST['order'] );
		$counter = 0;
		$last  = 0;

		$shortcode_row = ( isset( $_POST['row'] ) ) ? str_replace( 'row', 'row-', esc_attr( $_POST['row'] ) ) : 'row-1';

		foreach ( $order as $shortcode_id )  {

			$shortcode_id = esc_attr( $shortcode_id );

			if  ( $shortcode_id == 'separator' || $shortcode_id === '' )
			{
				if ( isset( $last ) && $last != "separator" && $last > 0 )
				{
					update_post_meta( $last, 'sep_after', 'yes' );
					$last = 0;
				}
			}
			else {
				$last = $shortcode_id;
				$wpdb->update( $wpdb->posts, array( 'menu_order' => $counter ), array( 'ID' => $shortcode_id ) );
				update_post_meta( $shortcode_id, 'row', $shortcode_row );
				update_post_meta( $shortcode_id, 'sep_after', 'no' );
				$counter++;
			}
		}

		delete_transient( 'sp.get.buttons' );
		delete_transient( 'sp.get.buttons.main' );
		delete_transient( 'sp.get.buttons.edit' );
		delete_transient( 'sp.get.quicktags.main' );
		delete_transient( 'sp.get.quicktags.main.33' );

		die( 1 );
	}
}

if ( ! function_exists( 'mv_shortcodes_pro_get_buttons' ) ) {

	/**
	* Get Buttons
	*
	* Get shortcodes/TinyMCE buttons.
	*
	* @package		Shortcodes Pro
	* @subpackage	Options
	* @since		1.0
	*
	*/

	function mv_shortcodes_pro_get_buttons( $row ) {

		$args = array(
			'post_type' => 'shortcodepro',
			'post_status' => 'publish',
			'meta_query' => array(
				array(
					'key' => 'button',
					'value' => 'on',
				 ),
				array(
					'key' => 'row',
					'value' => $row
				 )
			 ),
			'orderby' => 'menu_order',
			'numberposts' => -1,
			'order'	=> 'ASC',
		 );


		return new WP_Query( $args );

	}
}

if ( ! function_exists( 'mv_shortcodes_pro_do_buttons' ) ) {

	/**
	* Do Button
	*
	* Print a shortcode button.
	*
	* @package		Shortcodes Pro
	* @subpackage	Options
	* @since		1.0
	*
	*/

	function mv_shortcodes_pro_do_buttons( $shortcode ) {

		while ( $shortcode->have_posts() ) : $shortcode->the_post();

		$sep_after = get_post_meta( $shortcode->post->ID, 'sep_after', true );

		?>

		<li id="<?php the_ID(); ?>" class="sortitem">

		<?php

		$img = "";


		if ( function_exists( 'has_post_thumbnail' ) ) {
			if ( has_post_thumbnail() ) {
				$image_url = wp_get_attachment_image_src( get_post_thumbnail_id() );

				if ( isset( $image_url ) )
				$img = '<img src="'.esc_url($image_url[0]).'" alt="'.esc_attr(get_the_title()).'" width="20" height="20" />';
			}
		}

		echo '<div class="sortbutton">'.$img.'</div>';

		echo mv_shortcodes_pro_shorten_string( $shortcode->post->post_title, 6 ); ?><br/>

		</li>

		<?php

		if ( isset( $sep_after ) && $sep_after == 'yes' ) {
			?>
			<li id="separator" class="sortitem separator"><?php _e( 'Separator', 'shortcodes-pro' ); ?></li>
			<?php
		}

		endwhile;
	}
}

if ( ! function_exists( 'mv_shortcodes_pro_shorten_string' ) ) {

	/**
	* Shorten String Helper
	*
	* Returns a shorter version of a string.
	*
	* @package		Shortcodes Pro
	* @subpackage	Options
	* @since		1.0
	*
	*/

	function mv_shortcodes_pro_shorten_string( $text = NULL, $n = 20, $tail = '...' ) {
		if ( strlen( $text ) > $n ) {
			return substr( $text , 0, $n ) . $tail;
		} else {
			return $text;
		}
	}
}

/*
|--------------------------------------------------------------------------
| SHORTCODES PRO OPTIONS PAGE
|--------------------------------------------------------------------------
*/

if ( ! function_exists( 'mv_shortcodes_pro_register_and_build_fields' ) ) {

	/**
	* Register and Build Fields
	*
	* Register fields and sections.
	*
	* @package		Shortcodes Pro
	* @subpackage	Options
	* @since		1.0
	*
	*/

	function mv_shortcodes_pro_register_and_build_fields() {
		register_setting( 'shortcodespro', 'shortcodespro' );

		add_settings_section( 'settings_section', __( 'Settings', 'shortcodes-pro' ), '__return_true', __FILE__ );
		add_settings_field( 'hide_ui', __( 'Hide menu:', 'shortcodes-pro' ), 'mv_shortcodes_pro_hide_ui', __FILE__, 'settings_section' );
	}
}

if ( ! function_exists( 'mv_shortcodes_pro_options_page' ) ) {

	/**
	* Options Page
	*
	* Options page layout.
	*
	* @package		Shortcodes Pro
	* @subpackage	Options
	* @since		1.0
	*
	*/

	function mv_shortcodes_pro_options_page() {

	?>
	<div class="wrap">
		<?php screen_icon(); ?>
		<h2><?php _e( 'Shortcodes Pro Options', 'shortcodes-pro' ); ?></h2>

		<form method="post" action="options.php" enctype="multipart/form-data">
			<?php settings_fields( 'shortcodespro' ); ?>
			<?php do_settings_sections( __FILE__ ); ?>

			   <p class="submit">
				  <input name="Submit" type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'shortcodes-pro' ); ?>" />
			   </p>

				<p><?php  _e( '<strong>Remember</strong>: <em>You can use WordPress built in <a href="import.php" title="Import">Import</a>/<a href="export.php" title="Export">Export</a> feature to backup and migrate your custom shortcodes</em>.', 'shortcodes-pro' ); ?></p>

				<p><small><?php _e( 'Brought to you by ', 'shortcodes-pro' ); ?> <a href="http://mattvarone.com" title="Matt Varone" target="_blank">Matt Varone</a> | <a href="http://www.mattvarone.com/donate" target="_blank"><strong><?php _e( 'Donate', 'shortcodes-pro' ) ?></strong></a> &hearts;.</small></p>
		</form>
	</div>
	<?php
	}

}

if ( ! function_exists( 'mv_shortcodes_pro_hide_ui' ) ) {

	/**
	* Hide UI
	*
	* Generate the option: Hide UI.
	*
	* @package		Shortcodes Pro
	* @subpackage	Options
	* @since		1.0
	*
	*/

	function mv_shortcodes_pro_hide_ui() {
		echo mv_shortcodes_pro_do_checkbox( 'hide-ui', __( 'Yes', 'shortcodes-pro' ), 'yes', __( 'Check to hide the &ldquo;Shortcodes&rdquo; menu.', 'shortcodes-pro' ) );
	}
}

if ( ! function_exists( 'mv_shortcodes_pro_do_checkbox' ) ) {

	/**
	* Do Checkbox
	*
	* Generate a checkbox.
	*
	* @package		Shortcodes Pro
	* @subpackage	Options
	* @since		1.0
	*
	*/

	function mv_shortcodes_pro_do_checkbox( $meta, $label="Yes", $value='yes', $desc="" ) {

		$options = get_option( 'shortcodespro' );

		if ( isset( $options[$meta] ) && $options[$meta] == $value )
			$c = 'checked="checked"';
		else
			$c = "";

		if ( $desc != "" )
		$desc = '<div class="desc">'.$desc.'</div>';

	   return '<input type="checkbox" name="shortcodespro['.$meta.']" value="'.$value.'" '.$c.' /> '.$label.$desc;
	}
}