<?php
/**
* Shortcodes Pro Shortcodes Class
*
* @package		Shortcodes Pro
* @author       Matt Varone
*/

/*
|--------------------------------------------------------------------------
| SHORTCODES PRO / SHORTCODE CLASS
|--------------------------------------------------------------------------
*/

if ( ! class_exists( 'MV_Shortcodes_Pro_shortcodes' ) )
{

	class MV_Shortcodes_Pro_shortcodes extends MV_ShortcodesPro_Base
	{

		/**
		* Construct
		*
		* @package		Shortcodes Pro
		* @subpackage	Shortcodes Class
		* @since		1.0
		*/

		function __construct()
		{
			add_action( 'init', array( &$this, 'init' ) );
		}

		/**
		* Init
		*
		* @package		Shortcodes Pro
		* @subpackage	Shortcodes Class
		* @since		1.1.0
		*/

		function init() {

			// define cols
			$this->c = 0;

			// register post type
			$this->_register_post_type();

			if ( is_admin() )
				$this->_custom_define_metaboxes(); // define metaboxes
			else {
					$this->_register_shortcode(); // registers main shortcode

					// Set the_content filters
					remove_filter( 'the_content', 'do_shortcode', 11 );
					add_filter( 'the_content', array( &$this, 'sp_do_shortcode' ), 11 );
					add_filter( 'the_content', 'do_shortcode', 12 );
					add_filter( 'the_content', array( &$this, 'remove_empty_elements' ) );
			}

			// add ajax actions for thickbox
			add_action( 'wp_ajax_scpaddattribute', array( &$this, 'ajax_add_attribute' ) );
			add_action( 'wp_ajax_scpeditattribute', array( &$this, 'ajax_edit_attribute' ) );
		}


		/**
		* Register Post Type
		*
		* Register the shortcodes post type.
		*
		* @package		Shortcodes Pro
		* @subpackage	Shortcodes Class
		* @since		1.0
		*
		*/

		function _register_post_type()
		{

			// check hide ui option
			$options = get_option( 'shortcodespro' );

			if ( isset( $options['hide-ui'] ) && $options['hide-ui'] == "yes" )
				$show_ui = false;
			else
				$show_ui = true;

			// register the post type
			register_post_type( $this->post_type_id,
			array(
				'labels' => array(
								'name' =>  __( 'Shortcodes', 'shortcodes-pro' ),
	        					'singular_name' => __( 'Shortcode', 'shortcodes-pro' ),
								'add_new' => __( 'Add New Shortcode', 'shortcodes-pro' ),
								'add_new_item' => __( 'Add New Shortcode', 'shortcodes-pro' ),
								'edit_item' => __( 'Edit Shortcode', 'shortcodes-pro' ),
								'new_item' => __( 'New Shortcode', 'shortcodes-pro' ),
								'view_item' => __( 'View Shortcodes', 'shortcodes-pro' ),
								'search_items' => __( 'Search Shortcodes', 'shortcodes-pro' ),
								'not_found' =>  __( 'No Shortcode Found', 'shortcodes-pro' ),
								'not_found_in_trash' => __( 'No Shortcodes Found In Trash', 'shortcodes-pro' ),
								'parent_item_colon' => ''
								 ),
				'public' => false,
				'hierarchial' => false,
				'_builtin' => false,
				'_edit_link' => 'post.php?post=%d',
				'publicly_queryable' => false,
				'exclude_from_search' => true,
				'query_var' => true,
				'show_ui' => $show_ui,
				'capability_type' => 'post',
				'can_export' => true,
				'menu_position' => 200,
				'rewrite' => false,
				'show_in_nav_menus' => true,
				'supports'=> array( 'thumbnail' ),
	    		 )
	 		 );

			if ( is_admin() )
			{
				// remove autosave
				add_filter( 'admin_init', array( &$this, 'remove_autosave' ) );

				// remove quick edit actions
				add_filter( 'post_row_actions',  array( &$this, '_custom_remove_action' ), 10, 2 );

				// manage the custom columns
				add_filter( 'manage_'.$this->post_type_id.'_posts_columns', array( &$this, '_custom_columns' ) );
				add_action( 'manage_posts_custom_column', array( &$this, '_columns_content' ) );
				add_filter( 'manage_edit-'.$this->post_type_id.'_sortable_columns', array( &$this, 'column_register_sortable' ) );
				add_filter( 'request', array( &$this, 'column_orderby' ) );

				// do all the metaboxes
				add_action( 'admin_menu', array( &$this, '_custom_metaboxes' ) );
				add_action( 'do_meta_boxes', array( &$this, '_custom_featured_box' ) );

				// run this when saving the posts
				add_action( 'save_post', array( &$this, 'custom_save_metaboxes' ), 1, 2 );

				// Status messages
				add_filter( 'post_updated_messages', array( &$this, '_custom_update_messages' ) );

				// print custom scripts and styles
				add_action( "admin_enqueue_scripts", array( &$this, '_custom_post_scripts' ) );
				add_action( "admin_enqueue_styles", array( &$this, '_custom_post_styles' ) );

				// delete cache on saves/updates
				add_action( 'save_post', array( &$this, 'delete_cache' ) );

				// add contextual help
				add_action( 'contextual_help', array( &$this, '_custom_help_content' ), 10, 3 );
			}

			// Define the attribute fields
			$this->attribute_fields = array( 'order', 'slug', 'label', 'desc', 'type', 'value', 'options' );

			// free willy!
		}


		/**
		* Custom Meta-boxes
		*
		* Remove built-in meta-boxes and launch add meta-boxes.
		*
		* @package		Shortcodes Pro
		* @subpackage	Shortcodes Class
		* @since		1.0
		*
		*/

		function _custom_metaboxes()
		{
			// remove slug box
			remove_meta_box( 'slugdiv', $this->post_type_id, 'normal' );
			// remove submit box
		    remove_meta_box( 'submitdiv', $this->post_type_id, 'normal' );

			// generate meta boxes
			$this->_custom_add_metaboxes();
		}


		/**
		* Add Meta Boxes
		*
		* Add the custom meta-boxes.
		*
		* @package		Shortcodes Pro
		* @subpackage	Shortcodes Class
		* @since		1.0
		*
		*/

		function _custom_add_metaboxes()
		{
			// add submit box
		 	add_meta_box( 'submitdiv', __( 'Publish', 'shortcodes-pro' ), array( &$this, '_custom_post_submit_box' ), $this->post_type_id, 'side', 'high' );

			// loop trough and create meta boxes
			foreach ( $this->meta_boxes as $meta_box )
			add_meta_box(
				$meta_box['id'],
				$meta_box['title'],
				array( &$this, 'custom_metabox_callback' ),
				$this->post_type_id,
				$meta_box['context'],
				$meta_box['priority'],
				array( 'meta_box' => $meta_box )
			 );

		}


		/**
		* Custom Featured Box
		*
		* Define the buttons image box.
		*
		* @package		Shortcodes Pro
		* @subpackage	Shortcodes Class
		* @since		1.0
		*
		*/

		function _custom_featured_box()
		{
			remove_meta_box( 'postimagediv', $this->post_type_id, 'side' );
			add_meta_box( 'postimagediv', __( 'Button Image', 'shortcodes-pro' ), 'post_thumbnail_meta_box', $this->post_type_id, 'side' );
		}


		/**
		* Custom Post Submit Box
		*
		* Modify the custom post submit box.
		*
		* @package		Shortcodes Pro
		* @subpackage	Shortcodes Class
		* @since		1.0
		*
		*/

		function _custom_post_submit_box( $post )
		{
			global $action;

			$post_type = $post->post_type;
			$post_type_object = get_post_type_object( $post_type );
			$can_publish = current_user_can( $post_type_object->cap->publish_posts );

			?>
			<div class="submitbox" id="submitpost">

				<div id="major-publishing-actions">

					<div id="delete-action">
					<?php
					if ( current_user_can( "delete_post", $post->ID ) ) {
						if ( ! EMPTY_TRASH_DAYS )
							$delete_text = __( 'Delete Permanently', 'shortcodes-pro' );
						else
							$delete_text = __( 'Move to Trash', 'shortcodes-pro' );
						?>
					<a class="submitdelete deletion" href="<?php echo get_delete_post_link( $post->ID ); ?>"><?php echo $delete_text; ?></a><?php
					} ?>
					</div>

					<div id="publishing-action">
						<img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" id="ajax-loading" style="visibility:hidden;" alt="" />
						<?php
						if ( ! in_array( $post->post_status, array( 'publish', 'future', 'private' ) ) || 0 == $post->ID ) {
							if ( $can_publish ) :
								if ( ! empty( $post->post_date_gmt ) && time() < strtotime( $post->post_date_gmt . ' .0000' ) ) : ?>
								<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Schedule' ) ?>" />
								<input name="publish" type="submit" class="button-primary" id="publish" tabindex="5" accesskey="p" value="<?php esc_attr_e( 'Schedule', 'shortcodes-pro' ) ?>" />
						<?php	else : ?>
								<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Publish' ) ?>" />
								<input name="publish" type="submit" class="button-primary" id="publish" tabindex="5" accesskey="p" value="<?php esc_attr_e( 'Publish', 'shortcodes-pro' ) ?>" />
						<?php	endif;
							else : ?>
								<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Submit for Review' ) ?>" />
								<input name="publish" type="submit" class="button-primary" id="publish" tabindex="5" accesskey="p" value="<?php esc_attr_e( 'Submit for Review', 'shortcodes-pro' ) ?>" />
						<?php
							endif;
						} else { ?>
								<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Update', 'shortcodes-pro' ) ?>" />
								<input name="save" type="submit" class="button-primary" id="publish" tabindex="5" accesskey="p" value="<?php esc_attr_e( 'Update', 'shortcodes-pro' ) ?>" />
						<?php
						} ?>
					</div>

					<div class="clear"></div>

				</div>

			</div>

			<?php
		}


		/**
		* Custom Update Messages
		*
		* Modify the update messages.
		*
		* @package		Shortcodes Pro
		* @subpackage	Shortcodes Class
		* @since		1.0
		*
		*/

		function _custom_update_messages( $messages )
		{
			global $post;
			global $post_ID;

			$messages[$this->post_type_id] = array(
				0 => '',
				1 => __( 'Shortcode updated.', 'shortcodes-pro' ),
				2 => __( 'Custom field updated.', 'shortcodes-pro' ),
				3 => __( 'Custom field deleted.', 'shortcodes-pro' ),
				4 => __( 'Shortcode updated.', 'shortcodes-pro' ),
				5 => isset( $_GET['revision'] ) ? sprintf( __( 'Shortcode restored to revision from %s', 'shortcodes-pro' ), wp_post_revision_title( ( int ) $_GET['revision'], false ) ) : false,
				6 => __( 'Shortcode published', 'shortcodes-pro' ),
				7 => __( 'Shortcode saved.', 'shortcodes-pro' ),
				8 => __( 'Shortcode submitted.', 'shortcodes-pro' ),
				9 => __( 'Shortcode scheduled.', 'shortcodes-pro' ),
				10 => __( 'Shortcode draft updated.', 'shortcodes-pro' ),
			 );

			return $messages;

		}


		/**
		* Remove Autosave
		*
		* Disable autosave feature.
		*
		* @package		Shortcodes Pro
		* @subpackage	Shortcodes Class
		* @since		1.0.7
		*
		*/

		function remove_autosave() {
			if ( ! empty( $_GET['post'] ) || ( ! empty( $_GET['post_type'] ) && $_GET['post_type'] == $this->post_type_id ) ) {
				$post_type = '';

				if ( ! empty( $_GET['post'] ) ) {
					$post_type = get_post_type( intval( $_GET['post'] ) );
				}
				else if ( ! empty( $_GET['post_type'] ) ) {
					$post_type = $_GET['post_type'];
				}

				if ( ! empty( $post_type ) && $post_type == $this->post_type_id )
				{
					wp_deregister_script( 'autosave' );
				}
			}

		}


		/**
		* Filter Bulk Actions
		*
		* Remove the edit action.
		*
		* @package		Shortcodes Pro
		* @subpackage	Shortcodes Class
		* @since		1.0
		*
		*/

		function filter_bulk_actions( $actions )
		{
			unset( $actions['edit'] );
			return $actions;
		}


		/**
		* Custom Help Content
		*
		* Prints links to the online help documentation
		*
		* @package		Shortcodes Pro
		* @subpackage	Shortcodes Class
		* @since		1.0.9.5
		*
		*/

		function _custom_help_content( $contextual_help, $screen_id, $screen )
		{

			switch ( $screen_id )
			{

				case 'settings_page_shortcodes-pro/inc/shortcodespro-options':
					$help = '<p>'.__( '<strong>Hide Menu</strong> - Check this box to hide the &ldquo;Shortcodes&rdquo; menu found on the admin sidebar. Uncheck to make it visible.', 'shortcodes-pro' ).'.</p>';
					break;

				case 'shortcodepro_page_shortcodespro-options':
					$help = '<p>'.__( 'On this page you can easily change the order of your shortcodes buttons', 'shortcodes-pro' ).'.</p>'.
					'<p>'.__( 'Drag and drop the ( || separator ) button to add spaces in between the buttons. Double click them to remove', 'shortcodes-pro' ).'.</p> '.
					'<p>'.__( 'Changes on this page will be automatically saved', 'shortcodes-pro' ).'.</p>';
					break;

				case 'shortcodepro':
					$help = '<p>'.__( '<strong>Name</strong> - Each shortcode requires a custom name, an unique slug will be automatically generated from it. This is the action identifier of your custom shortcode', 'shortcodes-pro' ).'.</p>'.
					'<p>'.__( '<strong>Behavior</strong> - There are two possible behaviors, these change the way the shortcode works. "Wrap Content With" allows you to inject some code before and after a selection, most common and simple shortcodes can be achieved this way. "Insert Custom Code" gives you more freedom and the choice to accept Attribute Values and different code languages ( HTML and PHP )', 'shortcodes-pro' ).'.</p>'.
					'<p>'.__( '<strong>Attributes</strong> - Similar to built in shortcode buttons, you can ask input for different values trough an automatically generated overlay window. At this version, it supports Text, Textarea and Select fields', 'shortcodes-pro' ).'.</p>'.
					'<p>'.__( '<strong>Before Selection</strong> - Set the code you want to add before the current selection', 'shortcodes-pro' ).'.</p>'.
					'<p>'.__( '<strong>After Selection</strong> - Set the code you want to add after the current selection', 'shortcodes-pro' ).'.</p>'.
					'<p>'.__( '<strong>Language</strong> - This behavior allows two types of languages. HTML and PHP. This will modify the way the plugin process the inserted code', 'shortcodes-pro' ).'.</p>'.
					'<p>'.__( '<strong>Code</strong> - Insert here your custom code. Insert Custom Code features template tags, these can be used as wildcards and will be replaced with dynamic content', 'shortcodes-pro' ).'.</p>'.
					'<p>'.__( '<strong>Enable</strong> - Mark the checkbox to generate a custom TinyMCE button', 'shortcodes-pro' ).'.</p>'.
					'<p>'.__( '<strong>Prevent</strong> - The Prevent feature will alert the user if there is no selection present', 'shortcodes-pro' ).'.</p>'.
					'<p>'.__( '<strong>Row</strong> - TinyMCE ships with up to 4 rows of buttons. Select the desired row from the list', 'shortcodes-pro' ).'.</p>'.
					'<p>'.__( '<strong>Button Image</strong> - Use to set the button\'s image. Works exactly as <a href="http://en.support.wordpress.com/featured-images/#setting-a-featured-image" title="Featured Images">Featured Images</a>. Best results are with images of 20x20 pixels', 'shortcodes-pro' ).'.</p>';
					break;

				case 'settings_page_shortcodes-pro/inc/shortcodespro-options':
					$help = "";
					break;

				case 'edit-shortcodepro';
					$help = "";
					break;
			}


			if ( isset( $help ) )
			{
				$contextual_help = '<h3><strong>' . __( 'Shortcodes Pro', 'shortcodes-pro' ) . '</strong></h3>' .
					$help.'<p><p><a href="http://www.mattvarone.com/featured-content/shortcodes-pro/#faqs" target="_blank">'.__( 'FAQ', 'shortcodes-pro' ).'</a><br/>'.
					'<a href="http://lab.mattvarone.com/plugins/shortcodes-pro/docs" target="_blank">'.__( 'Documentation', 'shortcodes-pro' ).'</a><br/>'.
					'<a href="http://twitter.com/sksmatt" target="_blank">'.__( 'Updates', 'shortcodes-pro' ).'</a></p>'.
					'<p><small>'.__( 'Version Installed:', 'shortcodes-pro' ).' <strong>'.MV_SHORTCODES_PRO_VERSION.'</strong>.</small> '.
					'<small>'.__( 'Brought to you by', 'shortcodes-pro' ).' <a href="http://mattvarone.com" title="Matt Varone" target="_blank">Matt Varone</a>  | <a href="http://www.mattvarone.com/donate" target="_blank"><strong>'.__( 'Donate', 'shortcodes-pro' ).'</strong></a> &hearts;.</small></p>';
			}

			return $contextual_help;

		}

		/* CUSTOM POST HEAD METHODS
		/////////////////////////////*/

		/**
		* Custom Post Scripts
		*
		* Enqueue the Shortcodes Pro admin script.
		*
		* @package		Shortcodes Pro
		* @subpackage	Shortcodes Class
		* @since		1.0
		*
		*/

		function _custom_post_scripts()
		{

			if ( ! $this->is_admin_custom_post() ) return;
			wp_enqueue_script( 'shortcodespro', plugins_url( MV_SHORTCODES_PRO_FOLDER.'/js/shortcodespro-admin.js' ), array( 'jquery', 'thickbox' ), MV_SHORTCODES_PRO_VERSION );

			$params = array(
			    'in_edit' =>  __( 'Edit', 'shortcodes-pro' ),
			    'in_delete' =>  __( 'Delete', 'shortcodes-pro' ),
			    'in_delete_confirmation' =>  __( 'Delete this attribute?', 'shortcodes-pro' ),
			    'in_validation_alert' =>  __( 'Please complete all required fields (*)', 'shortcodes-pro' ),
			);

			wp_localize_script('shortcodespro', 'mv_shortcodespro_js_params', $params );

		}


		/**
		* Custom Post Styles
		*
		* Enqueue the Shortcodes Pro admin styles.
		*
		* @package		Shortcodes Pro
		* @subpackage	Shortcodes Class
		* @since		1.0
		*
		*/

		function _custom_post_styles()
		{
			if ( ! $this->is_admin_custom_post() ) return;

			wp_enqueue_style( 'thickbox' );
		}


		/* CUSTOM POST LISTING METHODS
		/////////////////////////////*/

		/**
		* Custom Remove Action
		*
		* Remove the quick actions.
		*
		* @package		Shortcodes Pro
		* @subpackage	Shortcodes Class
		* @since		1.0
		*
		*/

		function _custom_remove_action( $actions, $post )
		{
		    if( $post->post_type == $this->post_type_id )
			{
		        unset( $actions['inline hide-if-no-js'] );
				unset( $actions['view'] );
		    }

		    return $actions;
		}


		/**
		* Custom Columns
		*
		* Set the shortcodes type columns.
		*
		* @package		Shortcodes Pro
		* @subpackage	Shortcodes Class
		* @since		1.0
		*
		*/

		function _custom_columns( $columns )
		{
			$new_columns['cb'] = '<input type="checkbox" />';
			$new_columns['title'] = __( 'Shortcode', 'shortcodes-pro' );
			$new_columns['action'] = __( 'Action', 'shortcodes-pro' );
			$new_columns['attributes'] = __( 'Attributes', 'shortcodes-pro' );
			$new_columns['behavior'] = __( 'Behavior', 'shortcodes-pro' );
			$new_columns['editor-button'] = __( 'Button', 'shortcodes-pro' );
			$new_columns['row'] = __( 'Row', 'shortcodes-pro' );

			return $new_columns;
		}


		/**
		* Columns Content
		*
		* Return content for each column.
		*
		* @package		Shortcodes Pro
		* @subpackage	Shortcodes Class
		* @since		1.0
		*
		*/

		function _columns_content( $column )
		{
			global $post;
			global $custom_metabox;

			switch ( $column )
			{
				// Post Name
				case 'action':
					echo '<strong>'.$post->post_name.'</strong>';
				break;

				// Type of Shortcode
				case 'behavior':
					$behavior = $this->humanize_string( get_post_meta( $post->ID, 'type', true ) );
					$behavior = str_replace( array( ' Custom', ' With' ), '', $behavior );
                    _e( $behavior, 'shortcodes-pro' );
				break;

				// Row of Shortcode
				case 'row':
					$row = $this->humanize_string( get_post_meta( $post->ID, 'row', true ) );

					if ( $row == "" )
					echo '-';
					else
					_e( $row, 'shortcodes-pro' );

				break;

				// List of attributes
				case 'attributes':
					$enabled = get_post_meta( $post->ID, 'attributes', true );

					if ( $enabled == true )
					{
						$options = get_post_custom( $post->ID );
						if ( $slug = $this->get_attributes( $options ) )
						{

							$out = "";

							foreach ( $slug as $slug_name )
							{
								if ( $slug_name != "" )
								$out .= $slug_name.', ';
							}

							$out = rtrim( str_replace( '%', '', $out ), ' , ' );

							echo $out;
						} else {
							_e( 'none', 'shortcodes-pro' );
						}
					}
					else
					{
						_e( 'none', 'shortcodes-pro' );
					}
				break;

				// Editor Button Status
				case 'editor-button':

					$enabled = get_post_meta( $post->ID, 'button', true );

					if ( $enabled == "on" )
						_e( 'enabled', 'shortcodes-pro' );
					else
						_e( 'disabled', 'shortcodes-pro' );

				break;
			}
		}


		/**
		* Column Register Sortable
		*
		* Allow listing sorting.
		*
		* @package		Shortcodes Pro
		* @subpackage	Shortcodes Class
		* @since		1.0
		*
		*/

		function column_register_sortable( $columns )
		{
			$columns['behavior'] = 'behavior';
			$columns['editor-button'] = 'button';
			$columns['row'] = 'row';

			return $columns;
		}


		/**
		* Column Order By
		*
		* Modify vars for order by.
		*
		* @package		Shortcodes Pro
		* @subpackage	Shortcodes Class
		* @since		1.0
		*
		*/

		function column_orderby( $vars ) {


			if ( isset( $vars['orderby'] ) && 'row' == $vars['orderby'] ) {
				$vars = array_merge( $vars, array(
					'meta_key' => 'row',
					'orderby' => 'meta_value'
				 ) );
			}

			if ( isset( $vars['orderby'] ) && 'editor-button' == $vars['orderby'] ) {
				$vars = array_merge( $vars, array(
					'meta_key' => 'button',
					'orderby' => 'meta_value'
				 ) );
			}

			if ( isset( $vars['orderby'] ) && 'behavior' == $vars['orderby'] ) {
				$vars = array_merge( $vars, array(
					'meta_key' => 'type',
					'orderby' => 'meta_value'
				 ) );
			}

			return $vars;
		}


		/* AJAX METHODS
		/////////////////////////////*/

		/**
		* Shortcodes Pro Add Attribute ( Ajax )
		*
		* Add new attribute overlay.
		*
		* @package		Shortcodes Pro
		* @subpackage	Shortcodes Class
		* @since		1.0
		*
		*/

		function ajax_add_attribute()
		{
				$this->set_attributes_fields();

				?>
				 <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
				<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en-US">
				<head>
				<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
				<title><?php _e( 'Add New Attribute', 'shortcodes-pro' ) ?></title>
				</head>
				<body>
					<style type="text/css" media="screen">
						#media-items h3 { font-family: Georgia, "Times New Roman", Times, serif;font-weight: normal;color: #5A5A5A; font-size:1.6em}
						#media-items {border-width: 1px;border-style: solid; padding:15px;border-color: #DFDFDF;}
						#media-items th, #media-items label {font-weight:bold}
						#media-items .desc { font-style: italic; color:#333; font-size:12px}
						#media-items .button.add_attribute { margin-left:10px;}
						#video-form, #media-items { overflow:hidden;}
						#errors { color:red;font-weight:bold;}
						.sscatt_options { display:none;}
						#sphelp {padding: 15px;width: 90%;background: #f4f4f4;border: 1px dotted #ddd;font-style: normal;}
					</style>

					<script type="text/javascript" charset="utf-8">
					jQuery( function() {

							jQuery( '#TB_ajaxContent' ).css( 'height', '97%' );

							var selected = jQuery( '#att_type' ).val();

							filterOverlay( selected );

							function filterOverlay( selection ) {
								if( selection==="select" ) {
									jQuery( '.sscatt_options' ).show();
								} else {
									jQuery( '.sscatt_options' ).hide();
								}
							}

					} );
					</script>
					<h3 class="media-title"><?php _e( 'New Attribute Details', 'shortcodes-pro' ) ?></h3>
					<form enctype="multipart/form-data" method="post" action="#" class="media-upload-form type-form validate" id="video-form">

						<p id="errors"></p>

						<div id="media-items">
							<table class="form-table shortcodespro-meta-box">
								<tbody>
								<?php
									foreach ( $this->attributes_fields as $field )
									$this->do_metabox_content( $field, false, '20' );
								?>
								</tbody>
							</table>

					<p><a class="button add_attribute"><?php _e( 'Add Attribute', 'shortcodes-pro' ) ?></a></p>

						</div>
					</form>
				</body>
				</html>
				<?php
				exit();
		}


		/**
		* Shortcodes Pro Edit Attribute ( Ajax )
		*
		* Edit attribute overlay.
		*
		* @package		Shortcodes Pro
		* @subpackage	Shortcodes Class
		* @since		1.0
		*
		*/

		function ajax_edit_attribute()
		{
			$this->set_attributes_fields();

			if ( ! isset( $_GET['id'] ) )
			die( __( 'Attribute not valid', 'shortcodes-pro' ) );

			$attr_id = esc_attr( $_GET['id'] );

			?>
			 <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
			<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en-US">
			<head>
			<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
			<title><?php _e( 'Edit Attribute', 'shortcodes-pro' ) ?></title>
			</head>
			<body>

			<style type="text/css" media="screen">
				#media-items h3 { font-family: Georgia, "Times New Roman", Times, serif;font-weight: normal;color: #5A5A5A; font-size:1.6em}
				#media-items {border-width: 1px;border-style: solid; padding:15px;border-color: #DFDFDF;}
				#media-items th, #media-items label {font-weight:bold}
				#media-items .desc { font-style: italic; color:#333; font-size:12px}
				#media-items .button.add_attribute { margin-left:10px;}
				#errors { color:red;font-weight:bold;}
				.sscatt_options { display:none;}
				#sphelp {padding: 15px;width: 90%;background: #f4f4f4;border: 1px dotted #ddd;font-style: normal;}
			</style>

			<script type="text/javascript" charset="utf-8">
				jQuery( function() {

					jQuery( '#TB_ajaxContent' ).css( 'height', '97%' );

					var slug = '<?php echo $attr_id; ?>',
					    label = jQuery( '#att_label_'+slug ).val(),
					    desc = jQuery( '#att_desc_'+slug ).val(),
					    type = jQuery( '#att_type_'+slug ).val(),
					    value = jQuery( '#att_value_'+slug ).val(),
					    options = jQuery( '#att_options_'+slug ).val();

					jQuery( '#att_name' ).val( slug ).attr( 'disabled', 'disabled' );
					jQuery( '#att_value' ).val( value );
					jQuery( '#att_options' ).val( options );
					jQuery( '#att_label' ).val( label );
					jQuery( '#att_type' ).val( type );
					jQuery( '#att_desc' ).val( desc );

					var selected = jQuery( '#att_type' ).val();

					filterOverlay( selected );

					function filterOverlay( selection ) {
						if( selection==="select" ) {
							jQuery( '.sscatt_options' ).show();
						} else {
							jQuery( '.sscatt_options' ).hide();
						}
					}

				} );
			</script>


				<h3 class="media-title"><?php _e( 'Edit Attribute Details', 'shortcodes-pro' ) ?></h3>

				<form enctype="multipart/form-data" method="post" action="#" class="media-upload-form type-form validate" id="video-form">

				<p id="errors"></p>

				<div id="media-items">
					<table class="form-table shortcodespro-meta-box">
						<tbody>
							<?php
							foreach ( $this->attributes_fields as $field )
							$this->do_metabox_content( $field, true, '20' );
							?>
						</tbody>
					</table>

					<p><a class="button update_attribute" id="att_<?php echo $attr_id; ?>"><?php _e( 'Update Attribute', 'shortcodes-pro' ) ?></a></p>
				</div>
				</form>
			</body>
			</html>
			<?php
			exit();
		}


		/* CUSTOM METABOXES FIELDS
		/////////////////////////////*/

		/**
		* Custom Define Meta-Boxes Fields
		*
		* Define the shortcodes type fields.
		*
		* @package		Shortcodes Pro
		* @subpackage	Shortcodes Class
		* @since		1.0
		*
		*/

		function _custom_define_metaboxes()
		{
			$this->meta_boxes = array(

				/* MAIN DETAILS
				/////////////////////////////*/

				array(
				'id' => 'details',
				'title' => __( 'Shortcode Details', 'shortcodes-pro' ),
				'context' => 'normal',
				'priority' => 'high',
				'fields' => array(

					array(
					'name' => __( 'Name', 'shortcodes-pro' ),
					'desc' => __( 'Enter the shortcode name.', 'shortcodes-pro' ),
					'id' => 'post_title',
					'type'=>'text',
					'std' => ''
					 ),

				 	array(
					'name' => __( 'Action', 'shortcodes-pro' ),
					'id' => 'post_name',
					'type'=>'slug',
					'desc' => __( 'Use this action to trigger the shortcode.', 'shortcodes-pro' ),
					'std' => ''
					 ),

					array(
					'name' => __( 'Behavior', 'shortcodes-pro' ),
					'desc' => __( 'Select the type of behavior you want to apply to this shortcode.', 'shortcodes-pro' ),
					'id' => 'type',
					'type'=>'select',
					'std' => 'wrap-content-with',
					'options' => array(
						array( 'id'=> 'wrap-content-with', 'title' =>  __( 'Wrap content with', 'shortcodes-pro' ) ),
						array( 'id'=> 'insert-custom-code', 'title' => __( 'Insert custom code', 'shortcodes-pro' ) )
					 ) ),

					array(
					'name' => __( 'Attributes', 'shortcodes-pro' ),
					'desc' => __( 'Check to enable attributes for this shortcode.', 'shortcodes-pro' ),
					'id' => 'attributes',
					'type'=>'checkbox',
					'std' => ''
					 )

				 ) ),

				/* ATTRIBUTES VALUES
				/////////////////////////////*/

				array(
				'id' => 'attributes-values',
				'title' => __( 'Attributes', 'shortcodes-pro' ),
				'context' => 'normal',
				'priority' => 'low',
				'fields' => array(

					array(
					'type' => 'header',
					'id' => 'attributes-header',
					'fields' => array( __( 'Order', 'shortcodes-pro' ), __( 'Slug', 'shortcodes-pro' ), __( 'Label', 'shortcodes-pro' ), __( 'Action', 'shortcodes-pro' ) ) ),

					array(
					'type' => 'hiddens',
					'id' => 'hiddens',
					'fields' => array(
						array( 'id' => 'attrvals', 'type'=>'text', 'std' => '', 'desc' => '' ),
						array( 'id' => 'totalattr', 'type'=>'text', 'std' => '0', 'desc' => '' ),
						array( 'id' => 'lastattr', 'type'=>'text', 'std' => '0', 'desc' => '' )
					 ) ),

					array( 'type' => 'attributes', 'id'=>'attributes-list' ),

					array(
					'type' => 'button',
					'id' => 'add-new-attributed',
					'name' => __( 'Add Attribute', 'shortcodes-pro' ),
					'class'=> 'thickbox',
					'action' => admin_url( 'admin-ajax.php?action=scpaddattribute&width=640' ) ),
				 ) ),

				/* WARP SELECTION WITH
				/////////////////////////////*/

				array(
				'id' => 'wrap-content-with',
				'title' => __( 'Wrap Content With', 'shortcodes-pro' ),
				'context' => 'normal',
				'priority' => 'low',
				'fields' => array(

					array(
					'name' => __( 'Before selection', 'shortcodes-pro' ),
					'desc' => __( 'Enter the HTML code to insert before the selection.', 'shortcodes-pro' ),
					'id' => 'before',
					'type'=>'textarea',
					'std' => ''
					 ),

					array(
					'name' => __( 'After selection', 'shortcodes-pro' ),
					'desc' => __( 'Enter the HTML code to insert after the selection.', 'shortcodes-pro' ),
					'id' => 'after',
					'type'=>'textarea',
					'std' => ''
					 ),

				 ) ),

				/* INSERT CODE
				/////////////////////////////*/

				array(
				'id' => 'insert-custom-code',
				'title' => __( 'Insert Custom Code', 'shortcodes-pro' ),
				'context' => 'normal',
				'priority' => 'low',
				'fields' => array(

					array(
					'name' => __( 'Language' ),
					'desc' => __( 'Select the code language.', 'shortcodes-pro' ),
					'id' => 'language',
					'type'=>'select',
					'options' => array( 'html' => 'HTML', 'php' => 'PHP' ),
					'std' => 'HTML'
					 ),

					array(
					'name' => __( 'Code', 'shortcodes-pro' ),
					'desc' => __( 'Enter the code to insert. Use the template tag <strong>%%content%%</strong> to access the shortcode content ( selection ).<span class="attributes-desc" style="display:none"> Use <strong>%%attribute_slug%%</strong> to access the attributes values.</span>', 'shortcodes-pro' ),
					'id' => 'insert-html',
					'type'=>'textarea',
					'std' => ''
					 ),

					array(
					'name' => __( 'Code', 'shortcodes-pro' ),
					'desc' => __( 'Enter the code to run. Return your result. Use the variable <strong>$content</strong> to access the current content selection ( if any ). <span class="attributes-desc" style="display:none"> Use <strong>$atts</strong> to access the attributes values <em>( array )</em>.</span>', 'shortcodes-pro' ),
					'id' => 'insert-php',
					'type'=>'textarea',
					'std' => 'return'
					 ),
				 ) ),

				/* RICH EDITOR BUTTON
				/////////////////////////////*/

				array(
				'id' => 'rich-editor-button',
				'title' => __( 'Rich Editor Button', 'shortcodes-pro' ),
				'context' => 'advanced',
				'priority' => 'low',
				'fields' => array(

					array(
					'name' => __( 'Enable', 'shortcodes-pro' ),
					'desc' => __( 'Check to include a button to access this shortcode on the rich text editor.', 'shortcodes-pro' ),
					'id' => 'button',
					'type'=>'checkbox',
					'std' => '' ),

					array(
					'name' => __( 'Prevent', 'shortcodes-pro' ),
					'desc' => __( 'Check to prevent this shortcode for running when no content is selected.' , 'shortcodes-pro' ),
					'id' => 'prevent',
					'type'=>'checkbox',
					'std' => '' ),

					array(
					'name' => __( 'Row', 'shortcodes-pro' ),
					'desc' => __( 'Select the row where you want to include this button. ( <a href="'.get_admin_url().'edit.php?post_type=shortcodepro&page=shortcodespro-options.php" title="Sort Buttons">Sort Buttons</a> )', 'shortcodes-pro' ),
					'id' => 'row',
					'class' => 'rowbutton',
					'type'=>'select',
					'options' => array(
                       'row-1' =>  array( 'id' => 'row-1', 'title' => __( 'Row 1', 'shortcodes-pro' ) ),
                       'row-2' =>  array( 'id' => 'row-2', 'title' => __( 'Row 2', 'shortcodes-pro' ) ),
                       'row-3' =>  array( 'id' => 'row-3', 'title' => __( 'Row 3', 'shortcodes-pro' ) ),
                       'row-4' =>  array( 'id' => 'row-4', 'title' => __( 'Row 4', 'shortcodes-pro' ) ),
					 ),
					'std' => 'row-1' ),

					array(
					'name' => __( 'Short Description', 'shortcodes-pro' ),
					'desc' => __( 'Enter a one line description for this shortcode button.', 'shortcodes-pro' ),
					'id' => 'desc',
					'type'=>'text',
					'std' => '' ),


					array(
					'name' => __( 'Quicktag', 'shortcodes-pro' ),
					'desc' => __( 'Check to include a "Quicktag" button to access this shortcode on the html view of the rich text editor.', 'shortcodes-pro' ),
					'id' => 'quicktag',
					'type'=>'checkbox',
					'std' => '' ),

					array(
					'name' => __( 'Long Description', 'shortcodes-pro' ),
					'desc' => __( 'Enter a more detailed description for this shortcode button.', 'shortcodes-pro' ),
					'id' => 'desclong',
					'type'=>'textarea',
					'std' => '' ),

					array(
					'name' => __( 'Overlay width' , 'shortcodes-pro' ),
					'desc' => __( 'Enter the width of the overlay window. ( pixels )', 'shortcodes-pro' ),
					'id' => 'width',
					'type'=>'text',
					'std' => '490' ),

					array(
					'name' => __( 'Overlay height' , 'shortcodes-pro' ),
					'desc' => __( 'Enter the height of the overlay window. ( pixels )', 'shortcodes-pro' ),
					'id' => 'height',
					'type'=>'text',
					'std' => '300' ),

				 ) ),

			 ); // meta_boxes
		}


		/**
		* Set Attributes Fields
		*
		* Define the attribute fields.
		*
		* @package		Shortcodes Pro
		* @subpackage	Shortcodes Class
		* @since		1.0
		*
		*/

		function set_attributes_fields()
		{

			$this->attributes_fields = array(

				array(
				'name' => __( 'Slug', 'shortcodes-pro' ),
				'desc' => __( 'Enter the slug for this attribute. Used to retrieve this attribute value.<br/> Allowed: a-z, 0-9 ( examples: color, size, videoid, fileurl )', 'shortcodes-pro' ),
				'id' => 'att_name',
				'type'=>'text',
				'req' => 'true',
				'std' => ''
				 ),

				array(
				'name' => __( 'Label', 'shortcodes-pro' ),
				'desc' => __( 'Enter a label for this attribute. ( Used on the rich editor button )', 'shortcodes-pro' ),
				'id' => 'att_label',
				'req' => 'true',
				'type'=>'text',
				'std' => ''
				 ),

				array(
				'name' => __( 'Type', 'shortcodes-pro' ),
				'desc' => __( 'Select the attribute type. ( Used on the rich editor button )', 'shortcodes-pro' ),
				'id' => 'att_type',
				'type'=>'select',
				'req' => 'true',
				'std' => '',
				'options' => array(
                        'text' =>  array( 'id' => 'text', 'title' => __( 'Text', 'shortcodes-pro' ) ),
                        'textarea' =>  array( 'id' => 'textarea', 'title' => __( 'Textarea', 'shortcodes-pro' ) ),
                        'select' =>  array( 'id' => 'select', 'title' => __( 'Select', 'shortcodes-pro' ) ),
                )),

				array(
				'name' => __( 'Options', 'shortcodes-pro' ),
				'desc' => __( 'Use the following syntax to add multiple options:<br/><div id="sphelp" style="padding: 15px;width: 90%;background: #f4f4f4;border: 1px dotted #ddd;font-style: normal;"><strong>OptionName1|OptionValue1, <br/>OptionName2|OptionValue2, </strong><br/>[etc]<br/><br/>eg: Small|12px, Normal|16px, Huge|21px</div>', 'shortcodes-pro' ),
				'id' => 'att_options',
				'type'=>'textarea',
				'req' => 'true',
				'std' => '',
				'rows' => 4,
				 ),

				array(
				'name' => __( 'Default Value', 'shortcodes-pro' ),
				'desc' => __( 'Enter the default value.', 'shortcodes-pro' ),
				'id' => 'att_value',
				'type'=>'text',
				'std' => '',
				'req' => 'true',
				 ),

				array(
				'name' => __( 'Desc', 'shortcodes-pro' ),
				'desc' => __( 'Enter a description for this attribute. ( Used on the rich editor button )', 'shortcodes-pro' ),
				'id' => 'att_desc',
				'type'=>'textarea',
				'std' => '',
				'rows' => 4,
				 ),
			 );

		}

	} // class

} // exists

new MV_Shortcodes_Pro_shortcodes();