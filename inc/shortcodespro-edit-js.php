<?php
/**
* Shortcodes Pro Edit JS
*
* @package  Shortcodes Pro
* @author   Matt Varone
*/

// set the correct header
header("Content-type: application/x-javascript");

// require wordpress
require_once( '../../../../wp-load.php' );

if ( ! defined( 'MV_SHORTCODES_PRO_VERSION' ) )
die( __( 'Shortcodes Pro error. Could not initialize WordPress', 'shortcodes-pro' ) );

$out = "";//get_transient( 'sp.get.buttons.edit' );

if ( $out == "" )
{
	// start the base class
	$sp_base = new MV_ShortcodesPro_Base();

	// get all shortcode buttons
	$shortcodes = $sp_base->get_buttons();

	// check if we have shortcodes
	if ( count( $shortcodes ) > 0 )
	{
		// start the output
		$out =  "(function() {\n";

		// loop trough each shortcode
		foreach ( $shortcodes as $shortcode )
		{

			// create shortcode variables
			$shortcode_title  		= $shortcode->post_title;
			$shortcode_desc   		= get_post_meta( $shortcode->ID, 'desc', true );
			$shortcode_type   		= get_post_meta( $shortcode->ID, 'type', true );
			$shortcode_attributes	= get_post_meta( $shortcode->ID, 'attributes', true );
			$total_attributes		= get_post_meta( $shortcode->ID, 'totalattr', true );
			$shortcode_width_meta 	= (int) get_post_meta( $shortcode->ID, 'width', true );
			$shortcode_height_meta 	= (int) get_post_meta( $shortcode->ID, 'height', true );
			$shortcode_prevent	    = get_post_meta( $shortcode->ID, 'prevent', true );

			// create button title
			$button_title = ( $shortcode_desc ) ? $shortcode_title.': '.$shortcode_desc : $shortcode_title;

			// Verify shortcode with/height dimensions
			$shortcode_width  = ( $shortcode_width_meta > 490 ) ? $shortcode_width_meta : 490;
			$shortcode_height = ( $shortcode_height_meta > 300 ) ? $shortcode_height_meta : 300;

			// remove slashes from the slug
			$safe_slug = mv_shortcodes_pro_safe_slug( $shortcode->post_name );

			// verify the overlay is needed
			if ( $shortcode_type == 'insert-custom-code' )
			{
				if ( $total_attributes == NULL OR ( int )$total_attributes == 0 OR $shortcode_attributes != "on" )
				$shortcode_type = 'default';
			}

			// start this button output
			$out .= "tinymce.create('tinymce.plugins.".$safe_slug."', { init : function(ed, url) {
					ed.addButton('".$safe_slug."', {
					title : '".esc_js($button_title)."',\n";

			// check button image
			if ( function_exists( 'has_post_thumbnail' ) )
			{
				if ( has_post_thumbnail( $shortcode->ID ) )
				{
					$image_url = wp_get_attachment_image_src( get_post_thumbnail_id( $shortcode->ID ) );
					$out .= "image : '".esc_url($image_url[0])."', ";
				}
			}

			// generate shortcode button behavior
			switch ( $shortcode_type )
			{
				//	INSERT CUSTOM CODE
				case 'insert-custom-code':
					$out .= "cmd: '".$safe_slug."',});\n";
					$out .= "ed.addCommand('".$safe_slug."', function() {

						var sel = ed.selection.getContent({format : 'raw'});";

					// check if empty prevent is on
					if ( $shortcode_prevent == "on" )
					{
						$out .= "

							if ( sel.length === 0  ) {
								alert('".__('Please make a selection.', 'shortcodes-pro')."');
								return;
							}";
					}

					// output overlay command
					$out .="

									ed.windowManager.open({
										file : url + '/shortcodespro-overlay.php?shortcode=".$shortcode->post_name."',
										width : ".esc_js($shortcode_width)." + ed.getLang('".$safe_slug.".delta_width', 0),
										height : ".esc_js($shortcode_height)." + ed.getLang('".$safe_slug.".delta_height', 0),
										inline : 1,
										scrollbars : 'yes',
									}, {
										plugin_url : url,
										selection : sel,
									});
							});\n";
				break;

				//	WRAP CONTENT WITH
				default:
					$out .= "onclick : function() {

						// get selection
						var sel = ed.selection.getContent({format : 'html'}),
							sel2 = ed.selection.getContent({format : 'raw'}),
							bm  = ed.selection.getBookmark(),
							sel = sel.replace(/^\s+|\s+$/g, '');";

					// check if empty prevent is on
					if ( $shortcode_prevent == "on" )
					{
						$out .= "

							if ( sel.length === 0  ) {
								alert('".__('Please make a selection.', 'shortcodes-pro')."');
								return;
							}";
					}

					//Return normal shortcode
					$out .= "

						if ( sel.length > 0  ) {
							// content
							var content = '[do action=\"".$shortcode->post_name."\"]' + sel + '[/do]';
							ed.execCommand('mceReplaceContent', false, content);
						} else {
							// no content
							var content = '[do action=\"".$shortcode->post_name."\"/]';
							ed.execCommand('mceReplaceContent', false, content);
						}

						ed.selection.moveToBookmark(bm);
						ed.save();";

					$out .= "}});\n";

				break;
			}

			// finish shortcode button declaration
			$out .= "ed.onNodeChange.add(function(ed, cm, n) {
							cm.setActive('".$safe_slug."', n.nodeName == 'IMG');
						});
					},
					createControl : function(n, cm) {
						return null;
					},
					getInfo : function() {
						return {
							longname : '".esc_js($shortcode_title)."',
							author : 'Shortcodes Pro',
							authorurl : '".esc_js(get_bloginfo( 'url' ))."',
							infourl : '".esc_js(get_bloginfo( 'url' ))."',
							version : '".MV_SHORTCODES_PRO_VERSION."'
						};
					}
				});
				tinymce.PluginManager.add('".$safe_slug."', tinymce.plugins.".$safe_slug.");\n";
		}

		// close js function
		$out .= "})();";

		// SAVE CACHE
		set_transient( 'sp.get.buttons.edit', $out, 3600 );

		// echo the js content
		if ( $out != "" ) echo $out;

	}
} else {
	echo $out;
}