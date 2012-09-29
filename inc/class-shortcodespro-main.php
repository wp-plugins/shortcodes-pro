<?php
/**
* Shortcodes Pro Main Class
*
* @package		Shortcodes Pro
* @author       Matt Varone
*/

if ( ! class_exists( 'MV_Shortcodes_Pro' ) )
{

	/* SHORTCODES PRO CLASS
	/////////////////////////////*/

	class MV_Shortcodes_Pro
	{

		/**
		* Construct
		*
		* @package		Shortcodes Pro
		* @subpackage	Main Class
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
		* @subpackage	Main Class
		* @since		1.1.0
		*/

		function init() {

			add_action( 'after_setup_theme', array( &$this, 'add_thumbnail_support' ), 9999 );

			if ( is_admin() ) {

				// Get shortcodes buttons
				$this->get_buttons_by_row();

				// get options
				$options = get_option( 'shortcodespro' );

				// check hide ui option
				if ( ! isset( $options['hide-ui'] ) OR $options['hide-ui'] != "yes" )
				add_action( 'admin_head', array( &$this, 'custom_plugin_header' ) );

				// TINY MCE BUTTONS
				if ( current_user_can( 'edit_posts' ) &&
					 current_user_can( 'edit_pages' ) &&
					 get_user_option( 'rich_editing' ) == 'true' ) {
					// Add all new buttons
					add_filter( 'mce_external_plugins', array( &$this, 'add_plugins' ) );

					// Register buttons on correct row
					add_filter( 'mce_buttons',   array( &$this, 'register_buttons' ), 9999 );
					add_filter( 'mce_buttons_2', array( &$this, 'register_buttons' ), 9999 );
					add_filter( 'mce_buttons_3', array( &$this, 'register_buttons' ), 9999 );
					add_filter( 'mce_buttons_4', array( &$this, 'register_buttons' ), 9999 );

					// Regresh TinyMCE version
					add_filter( 'tiny_mce_version', array( &$this, 'refresh_mce_version' ) );

					// Do the quicktags
					if ( $this->is_edit_page() ) {
						if ( version_compare( get_bloginfo( 'version' ), '3.3' ) >= 0 )
							add_action('admin_print_footer_scripts', array( &$this, 'generate_quicktags_33' ) );
						else
							add_action( 'admin_footer', array( &$this, 'generate_quicktags' ) );

					}
				}

			}

		}


		/**
		* Add Thumbnail Support
		*
		* @package		Shortcodes Pro
		* @subpackage	Main Class
		* @since		1.1.0
		*
		*/

		function add_thumbnail_support() {
			if ( function_exists( 'add_theme_support' ) )
			add_theme_support( 'post-thumbnails' );
		}


		/**
		* Custom Plugin Header
		*
		* Print styles for Shortcodes Pro menu icon.
		*
		* @package		Shortcodes Pro
		* @subpackage	Main Class
		* @since		1.0
		*
		*/

		function custom_plugin_header() {

			// Admin color scheme with fallback for 3.0-

			$current_user = wp_get_current_user();

			if( $current_user->admin_color == 'classic' )
				$pos = 65;
			else
				$pos = 33;

			?>
			<style type="text/css" media="screen">
				body #adminmenu #menu-posts-shortcodepro .wp-menu-image {background:transparent url( "<?php echo MV_SHORTCODES_PRO_URL.'/img/shortcodespro.png';?>" ) no-repeat 7px -<?php echo $pos; ?>px;}
				body #adminmenu #menu-posts-shortcodepro:hover .wp-menu-image, body #adminmenu #menu-posts-shortcodepro.wp-has-current-submenu .wp-menu-image{background:transparent url( "<?php echo MV_SHORTCODES_PRO_URL.'/img/shortcodespro.png';?>" ) no-repeat 7px -1px;}
			</style>
			<?php
		}


		/**
		* Get active buttons by row
		*
		* Get all shortcodes with button=on and sort by row.
		*
		* @package		Shortcodes Pro
		* @subpackage	Main Class
		* @since		1.0
		*
		*/

		function get_buttons_by_row() {

			$buttons = get_transient( 'sp.get.buttons.main' );

			if ( $buttons == "" OR empty( $buttons ) ) {

				for ( $i=1; $i < 5; $i++ ) {
						$args = array(
							'post_type' => 'shortcodepro',
							'posts_per_page' => -1,
							'post_status' => 'publish',
							'meta_query' => array(
								array(
									'key' => 'button',
									'value' => 'on',
								 ),
								array(
									'key' => 'row',
									'value' => 'row-'.$i,
								 )
							 ),
							'orderby' => 'menu_order',
							'order'	=> 'ASC',
						 );


						$results =  new WP_Query( $args );

						if ( is_object( $results ) ) {
							while ( $results->have_posts() ) : $results->the_post();

								$sep_after = get_post_meta( $results->post->ID, 'sep_after', true );

								if ( isset( $sep_after ) && $sep_after == 'yes' )
								$buttons['row-'.$i][] = array( $results->post->post_name );
								else
								$buttons['row-'.$i][] = $results->post->post_name;

							endwhile;
						}

				}

				set_transient( 'sp.get.buttons.main', $buttons );

			}

			$this->buttons_by_row = $buttons;

		}


		/**
		* Register Buttons
		*
		* Register each button and separator on the correct row.
		*
		* @package		Shortcodes Pro
		* @subpackage	Main Class
		* @since		1.0
		*
		*/

		function register_buttons( $buttons ) {

			$filter = current_filter();

			switch ( $filter ) {

				case 'mce_buttons_2':
					$row = 'row-2';
				break;

				case 'mce_buttons_3':
					$row = 'row-3';
				break;

				case 'mce_buttons_4':
					$row = 'row-4';
				break;

				default:
					$row = 'row-1';
				break;
			}

			if ( isset( $this->buttons_by_row[$row] ) && ! empty( $this->buttons_by_row[$row] ) ) {

				if ( $row != 'row-3' && $row != 'row-4' )
				array_push( $buttons, 'separator' );

				foreach ( $this->buttons_by_row[$row] as $button ) {

					if ( is_array( $button ) ) {
						array_push( $buttons, mv_shortcodes_pro_safe_slug( $button[0] ) );
						array_push( $buttons, 'separator' );
					}
					else {
						array_push( $buttons, mv_shortcodes_pro_safe_slug( $button ) );
					}

				}

			}

			return $buttons;

		}


		/**
		* Add Plugins
		*
		* Adds the new buttons to the plugin array.
		*
		* @package		Shortcodes Pro
		* @subpackage	Main Class
		* @since		1.0
		*
		*/

		function add_plugins( $plugin_array ) {
			if ( isset( $this->buttons_by_row ) && ! empty( $this->buttons_by_row ) )
			{
				foreach ( $this->buttons_by_row as $row )
				{
					foreach ( $row as $buttons )
					{
						if ( is_array( $buttons ) )
						$button_name = $buttons[0];
						else
						$button_name = $buttons;

						$safe_slug = mv_shortcodes_pro_safe_slug( $button_name );
						$plugin_array[$safe_slug] = plugins_url( MV_SHORTCODES_PRO_FOLDER.'/inc/shortcodespro-edit-js.php' );
					}
				}
			}

			return $plugin_array;

		}


		/**
		* Refresh MCE Version
		*
		* Returns a new version number.
		*
		* @package		Shortcodes Pro
		* @subpackage	Main Class
		* @since		1.0
		*
		*/

		function refresh_mce_version( $ver )  {
		  return ++$ver;
		}


		/**
		* Is Edit Page
		*
		* Checks user is on a page with rte.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0.9.7
		*
		*/

		function is_edit_page()
		{
			global $pagenow;

			$pages = array( 'post.php','post-new.php','page-new.php','page.php' );

			if ( in_array( $pagenow, $pages ) )
			return true;

			return false;
		}


		/**
		* Process Quicktags for 3.3 upwards.
		*
		* Return quicktags JS.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.1.0
		*
		*/

		function generate_quicktags_33() {

			$out = get_transient( 'sp.get.quicktags.main.33' );

			if ( $out == "" ) {

				$buttons = $this->get_quicktag_buttons();

				if ( ! isset( $buttons ) OR empty( $buttons ) )
				return;

				$out  = '
					<script type="text/javascript">
					/* <![CDATA[ */'."\n";

				$out .= "if (typeof window.QTags === 'function') {\n";

				foreach ( $buttons as $shortcode )  {
					$shortcode_type = get_post_meta( $shortcode->ID, 'type', true );
					$shortcode_quicktag = get_post_meta( $shortcode->ID, 'quicktag', true );

					if ( $shortcode_type == "wrap-content-with" AND $shortcode_quicktag == "on" ) {

						$shortcode_desc   = get_post_meta( $shortcode->ID, 'desc', true );
						$out .= "QTags.addButton( '".$shortcode->post_name."', '".$shortcode->post_title."', '[do action=\"".$shortcode->post_name."\"]', '[/do]', '','".$shortcode_desc."' );\n";
					}

				}

				$out .=	"}\n";

				$out .=	'/* ]]> */
					</script>'."\n\n";

				set_transient( 'sp.get.quicktags.main', $out );

			}

			echo $out;

		}


		/**
		* Process Quicktags for 3.2 downwards.
		*
		* Return quicktags JS.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0.9.7
		*
		*/

		function generate_quicktags() {

			$out = get_transient( 'sp.get.quicktags.main' );

			if ( $out == "" ) {

				$buttons = $this->get_quicktag_buttons();

				if ( ! isset( $buttons ) OR empty( $buttons ) )
				return;

				$out  = '
					<script type="text/javascript">
					/* <![CDATA[ */'."\n";

				foreach ( $buttons as $shortcode )  {
					$shortcode_type = get_post_meta( $shortcode->ID, 'type', true );
					$shortcode_quicktag = get_post_meta( $shortcode->ID, 'quicktag', true );

					if ( $shortcode_type == "wrap-content-with" AND $shortcode_quicktag == "on" ) {

						$shortcode_desc   = get_post_meta( $shortcode->ID, 'desc', true );

						$out .='

							var wpaqNr, wpaqBut;
							wpaqNr = edButtons.length;

							edButtons[wpaqNr] = new edButton("ed_"+wpaqNr, "'.$shortcode->post_title.'", "[do action=\"'.$shortcode->post_name.'\"]", "[/do]", "");

							var wpaqBut = wpQuickTagsToolbar.lastChild;

							while (wpaqBut.nodeType != 1) {
								wpaqBut = wpaqBut.previousSibling;
							}

							wpaqBut = wpaqBut.cloneNode(true);
							wpaqBut.id = "ed_"+wpaqNr;
							wpaqBut._idx = wpaqNr;
							wpaqBut.value = "'.$shortcode->post_title.'";
							wpaqBut.title = "'.$shortcode_desc.'";
							wpaqBut.onclick = function() {edInsertTag(edCanvas, this._idx); return false; }
							wpQuickTagsToolbar.appendChild(wpaqBut);

						'."\n";
					}

				}

				$out .=	'/* ]]> */
					</script>'."\n\n";

				set_transient( 'sp.get.quicktags.main', $out );

			}

			echo $out;

		}


		/**
		* Get Quicktag Buttons
		*
		* Get all shortcodes with qucktag=on.
		*
		* @package		Shortcodes Pro
		* @subpackage	Base Class
		* @since		1.0.9.7
		*
		*/

		function get_quicktag_buttons() {
			$args = array(
				'post_type' => 'shortcodepro',
				'port_status' => 'publish',
				'meta_key' => 'quicktag',
				'meta_value' => 'on',
				'orderby' => 'menu_order',
				'numberposts' => -1,
				'order' => 'ASC'
			);

			return get_posts($args);
		}

	}
}

new MV_Shortcodes_Pro();