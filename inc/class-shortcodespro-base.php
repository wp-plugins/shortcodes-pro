<?php
/**
* Shortcodes Pro Plugin Base Class
*
* @package		Shortcodes Pro
* @author       Matt Varone
*/

if ( ! class_exists( 'MV_ShortcodesPro_Base' ) )
{

	class MV_ShortcodesPro_Base
	{
		public $c 			 = 1; // columns
		public $input_size   = "30"; // default input size
		public $input_width  = "97%"; // default input width
		public $unset        = array( 'post_name', 'attributes-header', 'hiddens', 'attributes-list', 'add-new-attributed' ); // reserved fields
		public $post_type_id = 'shortcodepro';

		/* SHORTCODE METHODS
		/////////////////////////////*/

		/**
		* SP Do Shortcode
		*
		* Fires the nested shortcodes parser.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0.8
		*
		*/

		function sp_do_shortcode( $content ) {
			return $this->replace_do_shortcode( $content );
		}


		/**
		* Replace Do Shortcode
		*
		* Searches and replaces up to 3 levels of [do] shortcode.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0.8
		*
		*/

		function replace_do_shortcode( $content ) {
			// short shortcode
			$pattern = '~\[do action\=\"[^\"]*\"\s?[^\]]*?\/]~';
			$content =  preg_replace_callback( $pattern, array( &$this, 'replace_do_shortcode_callback' ), $content );

			// normal shortcode/no nesting
			$pattern = '~\[do action\=\"[^\"]*\"\s?[^\]\/]*\]( [^\[\d\o\n]* )\[\/do\]~';
			$content =  preg_replace_callback( $pattern, array( &$this, 'replace_do_shortcode_callback' ), $content );

			return $content;
		}


		/**
		* Replace Do Shortcode Callback
		*
		* Callback that replaces up to 3 levels of [do] shortcode.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0.8
		*
		*/

		function replace_do_shortcode_callback( $matches ) {

			$total_matches = count( $matches );

			// level placeholders
			$first_level = "";
			$second_level = "";
			$third_level = "";

			if ( $total_matches > 3 ) $total_matches = 3;

			for ( $i=0; $i < $total_matches; $i++ )
			{
				if ( isset( $matches[$i] ) )
				{
					switch( $i )
					{
						case 0:
							$first_level = $matches[$i];
						break;

						case 1:
							$second_level = $matches[$i];
						break;

						case 2:
							$third_level = $matches[$i];
						break;
					}
				}
			}

			// Process from inside out
			$second_level_pre = $second_level;

			if ( isset( $third_level ) && $third_level != "" )
			{
				$third_level_processed = do_shortcode( $third_level );
				$second_level_pre = str_replace( $third_level, $third_level_processed, $second_level_pre );
			}

			if ( isset( $second_level ) && $second_level != "" )
			{
				$second_level_processed = do_shortcode( $second_level_pre );
				$first_level = str_replace( $second_level, $second_level_processed, $first_level );
			}

			$first_level = do_shortcode( $first_level );

			return $first_level;

		}


		/**
		* Register Shortcode
		*
		* Register the "do" shortcode.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0
		*
		*/

		function _register_shortcode() {
			add_shortcode( 'do', array( &$this, 'do_shortcode' ) );
		}


		/**
		* Do Shortcode
		*
		* Starts the shortcode process.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0
		*
		*/

		function do_shortcode( $atts = NULL, $content="" ){
			global $wpdb;

			if ( ! isset( $atts['action'] ) )
			{
				return $content;
			}
			else
			{
				$shortcode = $this->get_by_slug( $atts['action'], $this->post_type_id );

				if ( $shortcode == NULL ) return;

				$options = get_post_custom( $shortcode->ID );

				$content = $this->clean_content( $content );

				$result = $this->_do_shortcode_content( $options, $content, $atts );

				return $result;

			}

		}


		/**
		* Do Shortcode Content
		*
		* Process shortcode based on behavior.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0
		*
		*/

		function _do_shortcode_content( $options, $content, $atts = NULL ) {

			if ( ! isset( $options['type'][0] ) )
			return $content;

			switch ( $options['type'][0] )
			{
				/* INSERT CODE */

				case 'insert-custom-code':

					if ( ! isset( $options['language'][0] ) )
					return $content;

					switch ( $options['language'][0] )
					{
						case 'php':
							if ( ! isset( $options['insert-php'][0] ) ) return $content;
							$atts = $this->_do_shortcode_atts( $options, '', $atts, true );
							return eval( $options['insert-php'][0] );
						break;

						case 'css':
							if ( ! isset( $options['insert-css'][0] ) ) return $content;
							$out = preg_replace( "/\%\%content\%\%/i", $content, $options['insert-css'][0] );
							return '<style type="text/css" media="screen">'.$this->_do_shortcode_atts( $options, $out, $atts ).'</style>';
						break;

						case 'html':
							if ( ! isset( $options['insert-html'][0] ) ) return $content;
							$out = preg_replace( "/\%\%content\%\%/i", $content, $options['insert-html'][0] );
							return $this->_do_shortcode_atts( $options, $out, $atts );
						break;
					}

				break;

				/* WARP SELECTION WITH */

				case 'wrap-content-with':

					if ( ! isset( $options['before'][0] ) OR ! isset( $options['after'][0] ) )
					return $content;

					return $options['before'][0].$content.$options['after'][0];

				break;

				/* DEFAULT */

				default:
					return $content;
				break;

			}
		}


		/**
		* Do Shortcode Attributes
		*
		* Prepares the attribute values of a shortcode.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0
		*
		*/

		function _do_shortcode_atts( $options, $out = NULL, $atts = NULL, $array = false ) {

			if ( $slugs = $this->get_attributes( $options ) )
			{
					foreach ( $slugs as $slug )
					{
						if ( $slug != "" )
						{
							$slg = str_replace( '%', '', $slug );

							if ( isset( $options['att_slug_'.$slg] ) )
							{

								$att_slug = ( isset( $options['att_slug_'.$slg][0] ) )?$options['att_slug_'.$slg][0]:"";

								if ( $att_slug == "" ) continue;

								$type = ( isset( $options['att_type_'.$slg][0] ) )?$options['att_type_'.$slg][0]:"";
								$def  = ( isset( $options['att_value_'.$slg][0] ) )?$options['att_value_'.$slg][0]:"";
								$value = ( isset( $atts[$att_slug] ) )?$value=$atts[$att_slug]:$value=$def;

								if ( $array === true )
								{
									$out[$att_slug] = $value;
								}
								else
									$out = preg_replace( "/\%\%".$att_slug."\%\%/i", $value, $out );

							}

						}
					}


			}

			return $out;
		}


		/**
		* Process Attributes
		*
		* Process the attribute values of a shortcode.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0
		*
		*/

		function process_attributes( $attributes, $options ) {
			// Fields collector
			$fields = array();

			// Map attribute values
			$attrs = array(
				'order' => 'order',
				'slug' => 'id',
				'label' => 'name',
				'value' => array( 'value', 'std' ),
				'options' => 'options',
				'type' => 'type',
				'desc' => 'desc',
				'width' => '',
				'size' => '',
			 );

			// Fill fields with all attributes
			foreach ( $attributes as $attribute )
			{
				if ( ! empty( $attribute ) )
				{

					$attr_array = array();

					$attr = str_replace( '%', '', $attribute );

					foreach ( $attrs as $old => $new )
					{
						$key = 'att_'.$old.'_'.$attr;


						if ( is_array( $new ) )
						{
							foreach ( $new as $newf )
							$attr_array[$newf] = ( isset( $options[$key][0] ) ) ? $options[$key][0] : '' ;

						} else {
							$attr_array[$new] = ( isset( $options[$key][0] ) ) ? $options[$key][0] : '' ;
						}
					}

					$fields[] = $attr_array;
				}

			}

			return $this->sort_array_by( $fields, 'order' );
		}



		/**
		* Clean Content
		*
		* Cleans WordPress shortcode formatting.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0.5
		*
		* @author http://donalmacarthur.com/articles/cleaning-up-wordpress-shortcode-formatting/
		*
		*/

		function clean_content( $content ) {

			$content = shortcode_unautop( $content );

			// Remove '</p>' from the start of the string.
			if ( substr( $content, 0, 4 ) == '</p>' )
				$content = substr( $content, 4 );

			// Remove '<p>' from the end of the string.
			if ( substr( $content, -3, 3 ) == '<p>' )
				$content = substr( $content, 0, -3 );

			return $this->remove_empty_elements( $content );

		}


		/**
		* Remove empty elements
		*
		* Fixes possible wrong formatting after fiddling with the text rich editor.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0.8
		*
		*/

		function remove_empty_elements( $content ){
			return str_replace( array( '<p></p>', '<p> </p>', '<p>&nbsp;</p>' ), '', $content );
		}

		/* SHORTCODES DATA HELPERS
		/////////////////////////////*/

		/**
		* Get By Slug
		*
		* Returns content object based on a slug.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0
		*
		*/

		function get_by_slug( $post_name = NULL, $post_type = 'post' ) {

			global $wpdb;

			if ( ! $post_name )
				return NULL;

			$args = array(
				'name' => $post_name,
				'post_type' => $post_type,
				'post_status' => 'publish',
				'limit' => 1
			 );

			$post = get_posts( $args );

			if ( is_array( $post ) && isset( $post[0] ) )
				return $post[0];

			return NULL;
		}


		/**
		* Get Buttons
		*
		* Get all shortcodes with button=on.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0
		*
		*/

		function get_buttons() {
			$buttons = get_transient( 'sp.get.buttons' );

			if ( $buttons != "" )
			return $buttons;

			$args = array(
				'post_type' => 'shortcodepro',
				'port_status' => 'publish',
				'meta_key' => 'button',
				'meta_value' => 'on',
				'orderby' => 'menu_order',
				'numberposts' => -1,
				'order' => 'ASC'
			 );

			$results = get_posts( $args );

			set_transient( 'sp.get.buttons', $results );

			return $results;
		}


		/**
		* Get Attributes
		*
		* Get an array with all the attributes of a shortcode.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0
		*
		*/

		function get_attributes( $options ) {
			if ( isset( $options['attributes'][0] ) && $options['attributes'][0] == 'on' && isset( $options['totalattr'][0] ) && $options['totalattr'][0] > 0 && isset( $options['attrvals'][0] ) )
				return explode( '|', $options['attrvals'][0] );

			return NULL;
		}


		/**
		* Get Shortcode
		*
		* Get a shortcode based on the post_name.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0
		*
		*/

		function get_shortcode( $shortcode = NULL ) {
			if ( ! $shortcode ) return;

			return $this->get_by_slug( $shortcode, 'shortcodepro' );
		}


		/**
		* Delete Cache
		*
		* Delete all shortcodes cache stored.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0
		*
		*/

		function delete_cache( $id ) {
			global $post;

			if ( isset( $post->post_type ) )
			{
				if ( $post->post_type == $this->post_type_id )
				{
					delete_transient( 'sp.get.buttons' );
					delete_transient( 'sp.get.buttons.main' );
					delete_transient( 'sp.get.buttons.edit' );
					delete_transient( 'sp.get.quicktags.main' );
					delete_transient( 'sp.get.quicktags.main.33' );
				}
			}

		}

		/* UI/META-BOXES HELPERS
		/////////////////////////////*/

		/**
		* Custom Save Meta-boxes
		*
		* Takes care of saving the Shortcodes post type meta values.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0
		*
		*/

		function custom_save_metaboxes( $post_id ) {
			if ( ! isset( $_POST[$this->post_type_id.'_meta_box_nonce'] ) )
			return $post_id;

			if ( ! wp_verify_nonce( $_POST[$this->post_type_id.'_meta_box_nonce'], basename( __FILE__ ) ) )
			return $post_id;

			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return $post_id;

			if ( $this->post_type_id != $_POST['post_type'] )
			return $post_id;


			if ( isset( $_POST['totalattr'] ) )
			{

				$total = ( int )$_POST['totalattr'];

				$att_fields = array();

				if ( $total > -1 )
				{

					$slugs = explode( '|', $_POST['attrvals'] );

					foreach ( $slugs as $slug )
					{
						if ( $slug != ""  )
						{
							$slg = str_replace( '%', '', $slug );

							if ( isset( $_POST['att_slug_'.$slg] ) )
							{
								foreach ( $this->attribute_fields as $attribute )
								$att_fields[] = array( 'id' => 'att_'.$attribute.'_'.$slg );
							}

						}
					}

					$ids = array( 'attrvals', 'totalattr', 'lastattr' );

					foreach ( $ids as $id )
					$att_hidden[] = array( 'id'=>$id );

					$this->att_hidden = array( array( 'fields'=>$att_hidden ) );
					$this->att_fields = array( array( 'fields'=>$att_fields ) );

					$att_all_fields   = array_merge_recursive( $this->att_fields, $this->att_hidden );
					$this->meta_boxes = array_merge_recursive( $att_all_fields, $this->meta_boxes );

				}

			}

			foreach ( $this->meta_boxes as $meta_box )
			{
				foreach ( $meta_box['fields'] as $field )
				{

					if ( isset( $field['id'] ) && ! in_array( $field['id'], $this->unset ) )
					{

						$old = get_post_meta( $post_id, $field['id'], true );
						$new = isset($_POST[$field['id']]) ? $_POST[$field['id']] : '';

						if ( $new && $new != $old ) {
							update_post_meta( $post_id, $field['id'], $new );
						} elseif ( '' == $new && $old ) {
							delete_post_meta( $post_id, $field['id'], $old );
						}

					}
				}
			}

		}


		/**
		* Custom Meta-box Callback
		*
		* Creates a meta-box.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0
		*
		*/

		function custom_metabox_callback( $post, $meta_box ) {
			global $post;
			$this->c = 1;

			echo '<input type="hidden" name="'.$this->post_type_id.'_meta_box_nonce" value="', wp_create_nonce( basename( __FILE__ ) ), '" />';
			echo '<table class="form-table shortcodespro-meta-box">';

			foreach ( $meta_box['args']['meta_box']['fields'] as $field )
			{
				$this->do_metabox_content( $field, false, 15 );
			}

			echo '</table>';
		}


		/**
		* Do Meta-box Content
		*
		* Generates the custom meta-boxes content
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0
		*
		*/

		function do_metabox_content( $field, $edit = false, $width=NULL ) {
			global $post;

			switch ( $field['type'] )
			{
				// Print attributes
				case 'attributes':

						$meta = get_post_custom( $post->ID );

						if ( isset( $meta['totalattr'][0] ) && $meta['totalattr'][0] > 0 && isset( $meta['attrvals'][0] ) )
						{

							$slugs = explode( '|', $meta['attrvals'][0] );

							$fields = array();

							foreach ( $slugs as $slug )
							{
								if ( $slug != ""  )
								{
									$slg = str_replace( '%', '', $slug );

									if ( isset( $meta['att_slug_'.$slg] ) )
									{
										$field['desc']		= ( isset( $meta['att_desc_'.$slg][0] ) )	? $meta['att_desc_'.$slg][0]	:"";
										$field['type']		= ( isset( $meta['att_type_'.$slg][0] ) )	? $meta['att_type_'.$slg][0]	:"";
										$field['value']		= ( isset( $meta['att_value_'.$slg][0] ) )	? $meta['att_value_'.$slg][0]	:"";
										$field['soptions']	= ( isset( $meta['att_options_'.$slg][0] ) )? $meta['att_options_'.$slg][0]	:"";
										$field['order']		= ( isset( $meta['att_order_'.$slg][0] ) )	? $meta['att_order_'.$slg][0]	:"";
										$field['slug']		= ( isset( $meta['att_slug_'.$slg][0] ) )	? $meta['att_slug_'.$slg][0]	:"";
										$field['label']		= ( isset( $meta['att_label_'.$slg][0] ) )	? $meta['att_label_'.$slg][0]	:"";
										$field['slg']		= $slg;

										$fields[] = $field;
									}

								}
							}

							$fields = $this->sort_array_by( $fields, 'order' );

							foreach ( $fields as $field )
							{
								$hidden = '
								<input type="hidden" name="att_desc_'.$field['slg'].'" value="'.$field['desc'].'" id="att_desc_'.$field['slg'].'"/>
								<input type="hidden" name="att_type_'.$field['slg'].'" value="'.$field['type'].'" id="att_type_'.$field['slg'].'"/>
								<input type="hidden" name="att_value_'.$field['slg'].'" value="'.$field['value'].'" id="att_value_'.$field['slg'].'"/>
								<input type="hidden" name="att_options_'.$field['slg'].'" value="'.$field['soptions'].'" id="att_options_'.$field['slg'].'"/>';

								echo '<tr class="sscrow" id="row_'.$field['slg'].'"><td width="25%">
								<input type="text" name="att_order_'.$field['slg'].'" id="att_order_'.$field['slg'].'" value="'.$field['order'].'"size="3"></td><td width="25%">
								<input type="text" name="att_slug_'.$field['slg'].'" id="att_slug_'.$field['slg'].'" value="'.$field['slug'].'" readonly="readonly" size="15"></td><td width="25%">
								<input type="text" name="att_label_'.$field['slg'].'" id="att_label_'.$field['slg'].'" value="'.$field['label'].'" readonly="readonly" size="15"></td><td width="25%">
								<a title="'.__( 'Edit Attribute', 'shortcodes-pro' ).'" class="edit button thickbox" href="admin-ajax.php?action=scpeditattribute&width=640&id='.$field['slug'].'">'.__( 'Edit', 'shortcodes-pro' ).'</a>
								<a title="'.__( 'Delete Attribute', 'shortcodes-pro' ).'" class="delete button" href="#">'.__( 'Delete', 'shortcodes-pro' ).'</a>'.$hidden.'</td></tr>';
							}

						}
				break;

				// Start a new row of fields
				case 'row':
					$this->fields_row( $field );
				break;

				// Create a fields header row
				case 'header':
					$this->fields_header( $field );
				break;

				// Create multiple hidden elements
				case 'hiddens':
				echo '<tr style="display:none"><td colspan="'.$this->c.'">';

				foreach ( $field['fields'] as $field_s )
				{
					$meta = get_post_meta( $post->ID, $field_s['id'], true );

					if ( isset( $meta ) && $meta != "" )
					$field_s['value'] = $meta;
					else
					$field_s['value'] = $field_s['std'];

					$this->field_hidden( $field_s, $meta );
				}

				echo '</td></tr>';
				break;

				// All the rest
				default:
					// check if slug is set
					if ( $field['type'] == 'slug' && isset( $post ) && $post->post_name == "" )
					return;

					// double check description
					if ( isset( $field['desc'] ) )
					$field['desc'] = '<br/><div class="desc">'.$field['desc']."</div>";
					else
					$field['desc'] = '';

					$class = ( isset( $field['class'] ) && $field['type'] != 'button' ) ? 'ssc'.$field['class'] : 'ssc'.$field['id'];


					// opening row
					echo '<tr class="'.$class.'">';

					// generate label
					if ( isset( $field['name'] ) && $field['type'] != 'button' )
					{
						if ( isset( $width ) )
							$this->field_label( $field, $width );
						else
							$this->field_label( $field );
					}

					// start column
					if ( isset( $width ) )
						$nwidth = 100 - ( int )$width;
					else
						$nwidth = 100;


					echo '<td colspan="'.$this->c.'" width="'.$nwidth.'%">';

					// get meta content
					if ( $edit==true )
					{
						$meta = NULL;
					}
					else
					{
						if ( isset( $post ) )
						$meta = get_post_meta( $post->ID, $field['id'], true );
						else
						$meta = NULL;
					}

					$extra = "";

					$this->field_type( $field, $meta, $extra );

					echo '</td></tr>';
				break;
			}

		}


		/**
		* Do Over-fields
		*
		* Generates the custom fields on a shortcode overlay.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0
		*
		*/

		function do_overfields( $fields ) {
			if ( ! empty( $fields ) )
			{
				// opening table
				echo '<table class="form-table shortcodes-pro-overlay" width="100%">';

				foreach ( $fields as $field )
				{

					$class = ( isset( $field['class'] ) ) ? 'ssc'.$fields['class'] : 'ssc'.$field['id'];

					// opening row
					echo '<tr class="ssc'.$class.'" width="100%">';

					// Check and prepare the description field
					if ( isset( $field['desc'] ) && trim( $field['desc'] ) != "" )
					$field['desc'] = '<br/><div class="desc">'.$field['desc']."</div>";
					else
					$field['desc'] = '';

					// no meta for this fields
					$meta = NULL;

					// Generate the field label
					$this->field_label( $field, 140 );

					// opening column
					echo '<td width="300" valign="top">';

					// Depurate options and values
					switch ( $field['type'] )
					{
						case 'select':

							$values = preg_split( "~, ~", $field['options'] );
							$selected = "";
							$new_values = array();

							foreach ( $values as $value )
							{
								$option = preg_split( "~\|~", trim( $value ) );

								if ( ! empty( $option ) && count( $option ) == 2 )
								{

									$title = $option[0];
									$id = $option[1];

									$new_values[] = array( 'title'=>$title, 'id'=>$id );

								}

							}

							$field['options'] = $new_values;

						break;
					}

					//	Generate the field input
					$this->field_type( $field, $meta );

					// closing row
					echo '<td></tr>';
				}
				// closing table
				echo '</table>';
			}
		}


		/**
		* Field Type
		*
		* Route to correct field type creation.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0
		*
		*/

		function field_type( $field, $meta=NULL, $extra=NULL ) {
			// organize data
			$field['std']   = ( isset( $field['std'] ) ) ? $field['std'] : '';
			$field['value'] = ( isset( $meta ) && trim( $meta ) != "" ) ? $meta : $field['std'];
			$field['size']  = ( isset( $field['size'] ) ) ? $field['size'] : $this->input_size;
			$field['width'] = ( isset( $field['width'] ) ) ? $field['width'] : $this->input_width;
			$field['desc']  = ( isset( $field['desc'] ) ) ? $field['desc'] : "";
			$field['extra'] = ( isset( $extra ) ) ? $extra : '40';

			// run field type
			switch ( $field['type'] )
			{
				case 'text':
					$this->field_text( $field, $meta );
				break;

				case 'textarea':
					$this->field_textarea( $field, $meta );
				break;

				case 'select':
					$this->field_select( $field, $meta );
				break;

				case 'radio':
					$this->field_radio( $field, $meta );
				break;

				case 'checkbox':
					$this->field_checkbox( $field, $meta );
				break;

				case 'button':
					$this->field_button( $field );
				break;

				case 'slug':
					$this->field_slug( $field, $meta );
				break;

				case 'disabled':
					$this->field_disabled( $field, $meta );
				break;

				case 'hidden':
					$this->field_hidden( $field, $meta );
				break;

				case 'hiddens':
					$this->fields_hidden( $field, $meta );
				break;

			}
		}


		/**
		* Fields Header
		*
		* Prints a field header.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0
		*
		*/

		function fields_header( $field ) {
			$this->c = count( $field['fields'] );
			$p = floor( 100 / $this->c );
			echo '<tr class="scp-'.$field['id'].'">';

			foreach ( $field['fields'] as $title )
				echo '<th>'.$title.'</th>';
			echo '</tr>';
		}


		/**
		* Fields Row
		*
		* Prints a row of fields.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0
		*
		*/

		function fields_row( $field ) {
			echo '<tr class="ssc', $field['type'], '">';
			$this->c = count( $field['fields'] );

			if( $this->c > 0 ) $p = floor( 100 / $this->c ); else $p = 1;

			foreach ( $field['fields'] as $row_field )
			{

				$row_field['desc'] = "";
				$meta = get_post_meta( $post->ID, $row_field['id'], true );

				echo '<td width="'.$p.'%">';
					$this->field_type( $row_field, $meta );
				echo '</td>';
			}

			echo '</tr>';
		}


		/**
		* Field: Text
		*
		* Prints a text field.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0
		*
		*/

		function field_text( $field, $meta=NULL ) {
			echo '<input type="text" name="'.$field['id'].'" id="'.$field['id'].'" value="'.$field['value'].'" size="'.$field['size'].'" style="width:'.$field['width'].'" />'.$field['desc'];
		}


		/**
		* Field: Text-area
		*
		* Prints a text-area field.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0
		*
		*/

		function field_textarea( $field, $meta=NULL ) {
			if ( isset( $field['extra'] ) ) $cols = 'cols="'.$field['extra'].'"'; else $cols = "";
			if ( isset( $field['rows'] ) ) $rows = $field['rows']; else $rows = '8';

			echo '<textarea name="'.$field['id'].'" id="'.$field['id'].'" '.$cols.' rows="'.$rows.'" style="width:'.$field['width'].'">'.$field['value'].'</textarea>'.$field['desc'];
		}


		/**
		* Field: Checkbox
		*
		* Prints a checkbox field.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0
		*
		*/

		function field_checkbox( $field, $meta=NULL ) {
			$field['value'] = ( isset( $field['value'] ) && trim( $field['value'] ) != "" ) ? ' checked="checked"' : '';
			echo '<input type="checkbox" name="'.$field['id'].'" id="'.$field['id'].'" '.$field['value'].' />'.$field['desc'];
		}


		/**
		* Field: Radio
		*
		* Prints a radio button.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0
		*
		*/

		function field_radio( $field, $meta=NULL ) {
			$field['value'] = ( isset( $field['value'] ) && trim( $field['value'] ) != "" ) ? ' checked="checked"' : '';
			foreach ( $field['options'] as $option )
			echo ' <input type="radio" name="'.$field['id'].'" value="'.$option['value'].'"'.$field['value'].' /> '.$field['desc'];
		}


		/**
		* Field: Select
		*
		* Prints a select field.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0
		*
		*/

		function field_select( $field, $meta=NULL ) {
			echo '<select name="'.$field['id'].'" id="'.$field['id'].'">';
			foreach ( $field['options'] as $option )
			{

				if ( ! is_array( $option ) ) {
					$selected = ( $field['value'] == sanitize_title( $option ) ) ? ' selected="selected"' : '';
					echo '<option  value="'.sanitize_title( $option ).'" '.$selected.'>'.$option.'</option>';
				}
				else {
					$selected = ( $field['value'] == $option['id'] ) ? ' selected="selected"' : '';
					echo '<option  value="'.$option['id'].'"'.$selected.'>'.$option['title'].'</option>';
				}
			}
			echo '</select>'.$field['desc'];
		}


		/**
		* Field: Button
		*
		* Prints a button element.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0
		*
		*/

		function field_button( $field ) {
			if ( isset( $field['class'] ) ) $class = $field['class']; else $class = "";
			echo '<a id="'.$field['id'].'" title="'.$field['name'].'" class="'.$class.' button" href="'.$field['action'].'">'.$field['name'].'</a>';
		}


		/**
		* Field: Hidden
		*
		* Prints a hidden input.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0
		*
		*/

		function field_hidden( $field, $meta=NULL ) {
			echo '<input type="hidden" name="'.$field['id'].'" id="'.$field['id'].'" value="'.$field['value'].'"/>';
		}

		/**
		* Field: Disabled
		*
		* Prints a disabled input.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0
		*
		*/

		function field_disabled( $field, $meta=NULL ) {
			echo '<input type="text" name="'.$field['id'].'" id="'.$field['id'].'" value="'.$field['value'].'" size="'.$field['size'].'" style="width:'.$field['width'].'" disabled="disabled" />'.$field['desc'];
		}

		/**
		* Field: Label
		*
		* Prints a field label.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0
		*
		*/

		function field_label( $field, $size = NULL ) {
			if ( isset( $size ) ) $size = 'width="'.$size.'"'; else $size = 'width="20%"';
			if ( isset( $field['req'] ) && $field['req'] == "true" ) $req = '<span class="alignright"><abbr title="required" class="required">*</abbr></span>'; else $req ='';
			echo '<th '.$size.' valign="top"><label for="'.$field['id'].'">'.$field['name'].'</label>'.$req.'</th>';
		}

		/**
		* Field: Slug
		*
		* Prints a field slug.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0
		*
		*/

		function field_slug( $field, $meta = NULL ) {
			global $post;
			echo '<input type="text" name="'.$field['id'].'" id="'.$field['id'].'" value="'.$post->post_name.'" size="30" disabled="disabled" readonly="readonly"/>'.$field['desc'];
		}


		/**
		* JS fields
		*
		* Prints a set of attribute fields for JS.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0
		*
		*/

		function js_fields( $fields, $execute = false ) {
			$i = 0;
			$fields_out = "";

			foreach ( $fields as $field )
			{
				$label = "sp_".$field['id'];
				$value = '"\'+sp_'.$field['id'].'+\'"';

				echo "	var ".$label." = document.getElementById( '".$field['id']."' ).value;\n";

				if ( $execute == false )
				{
					echo "shortcodeContent = shortcodeContent+' ".$field['id']."=".$value."';\n";
				}
				else
				{
					$fields_out .= ( $i > 0 ) ? ", " : "";
					$fields_out .= "'".$field['id']."':".$label;
				}

				$i = $i + 1;
			}

			if ( trim( $fields_out ) != "" )
			{
				$fields_out = "{".$fields_out."}";
				echo "\n var fields = ".$fields_out;
			}

			echo "\n";
		}

		/* HELPERS
		/////////////////////////////*/

		/**
		* Is SSL
		*
		* Checks for SSL.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0
		*
		*/

		function is_ssl() {
			if ( isset( $_SERVER['HTTPS'] ) )
			{
				if ( 'on' == strtolower( $_SERVER['HTTPS'] ) )
				return true;

				if ( '1' == $_SERVER['HTTPS'] )
				return true;
			}
			elseif ( isset( $_SERVER['SERVER_PORT'] ) && ( '443' == $_SERVER['SERVER_PORT'] ) )
			{
				return true;
			}

			return false;
		}


		/**
		* Is admin custom post
		*
		* Checks if it's the admin custom edit/add page.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0
		*
		*/

		function is_admin_custom_post() {
			global $pagenow;

			if ( $pagenow != "post.php" && $pagenow != "post-new.php" )
			return false;

			if ( isset( $_GET['post_type'] ) && $_GET['post_type'] != $this->post_type_id )
			return false;

			return true;
		}


		/**
		* Humanize String
		*
		* Convert a slug string into readable text.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0
		*
		*/

		function humanize_string( $text = NULL ) {
			$text = str_replace( array( '-' ), ' ', $text );
			$text = trim( ucwords( $text ) );
			return $text;
		}


		/**
		* Sort Array By
		*
		* Sort associative array by an index.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0
		*
		*/

		function sort_array_by( $array, $index, $order = 'asc', $natsort = FALSE, $case_sensitive = FALSE ) {
			if( is_array( $array ) && count( $array ) > 0 )
			{
			   foreach( array_keys( $array ) as $key )
				   $temp[$key] = $array[$key][$index];
				   if( ! $natsort )
					   ( $order == 'asc' )? asort( $temp ) : arsort( $temp );
				  else
				  {
					 ( $case_sensitive )? natsort( $temp ) : natcasesort( $temp );
					 if( $order != 'asc' )
						 $temp = array_reverse( $temp, TRUE );
			   }
			   foreach( array_keys( $temp ) as $key )
				   ( is_numeric( $key ) )? $sorted[]=$array[$key] : $sorted[$key]=$array[$key];
			   return $sorted;
		  }
		  return $array;
		}


		/**
		* Die Error
		*
		* Die and print error.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0
		*
		*/

		function die_error( $error="" ) {
			die( $error );
			exit;
		}
	}
}