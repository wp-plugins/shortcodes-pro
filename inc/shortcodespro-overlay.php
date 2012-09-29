<?php
/**
* Shortcodes Pro Overlay Page
*
* @package  Shortcodes Pro
* @author   Matt Varone
*/

// Start WordPress
require_once( '../../../../wp-load.php' );

// Check for Shortcodes Pro
if ( ! defined( 'MV_SHORTCODES_PRO_VERSION' ) )
die( __( 'Shortcodes Pro error. Plugin not initialized.', 'shortcodes-pro' ) );

// Check user is logged and with correct permissions.
if ( ! is_user_logged_in() OR ! current_user_can( 'edit_posts' ) )
$sp_base->die_error( __( 'Cheating?. Please verify your settings', 'shortcodes-pro' ) );

// Start the base class
$sp_base = new MV_ShortcodesPro_Base();

// Check to see if shortcode is being passed
if ( ! isset( $_GET['shortcode'] ) )
$sp_base->die_error( __( 'Shortcode not found. Please verify your settings', 'shortcodes-pro' ) );

// Get the shortcode
$shortcode = $sp_base->get_shortcode( esc_attr( $_GET['shortcode'] ) );

// Check Shortcode
if ( ! $shortcode )
$sp_base->die_error( __( 'The Shortcode does not exist. Please verify your settings', 'shortcodes-pro' ) );

// Get Shortcode data
$post = get_post( $shortcode, OBJECT );
$options = get_post_custom( $post->ID );

// Check Button
if ( isset( $options['button'][0] ) && $options['button'][0] != "on" )
$sp_base->die_error( __( 'Shortcode button not enabled. Please verify this shortcode settings.', 'shortcodes-pro' ) );

// Parse attributes
$attributes = $sp_base->get_attributes( $options );
if ( count( $attributes ) < 1 )
$sp_base->die_error( __( 'Shortcode Error. Please verify this shortcode settings.', 'shortcodes-pro' ) );

// Set fields
$fields = $sp_base->process_attributes( $attributes, $options );

// Set the title
$shortcode_title = $post->post_title;
if ( isset( $options['desc'][0] ) && count( $options['desc'][0] ) < 15 )
$shortcode_title .=  ': ' . $options['desc'][0];

?><!DOCTYPE>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?php echo $post->post_title; ?></title>
<meta http-equiv="Content-Type" content="<?php bloginfo( 'html_type' ); ?>; charset=<?php echo get_option( 'blog_charset' ); ?>" />
<script language="javascript" type="text/javascript" src="<?php echo includes_url( 'js/jquery/jquery.js' ) ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo includes_url( 'js/tinymce/tiny_mce_popup.js' ) ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo includes_url( 'js/tinymce/utils/mctabs.js' ) ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo includes_url( 'js/tinymce/utils/form_utils.js' ) ?>"></script>
<link rel="stylesheet" href="<?php echo MV_SHORTCODES_PRO_URL ?>/css/shortcodespro-admin.css" type="text/css" media="all"/>

<script type="text/javascript">

jQuery.noConflict();

jQuery(function() {

	jQuery('#insert').click(function(){
		checkSubmit();
		return false;
	});

function init() {
	tinyMCEPopup.resizeToInnerSize();
}

function checkSubmit()
{
	var shortcodeContent = "",
	    selection = tinyMCEPopup.getWindowArg('selection');

	if ( selection === "") {
	    selection = ""
	};

	shortcodeContent = '[do action="<?php echo $post->post_name; ?>"';

	var fields = new Array()

	<?php

	// Print JS fields
	$sp_base->js_fields($fields);

	?>

	if (selection === "") {
	 shortcodeContent = shortcodeContent+'/]';
	} else {
	 selection = selection.replace(/^\s+|\s+$/g, "");
	 shortcodeContent = shortcodeContent+']'+selection+'[/do]';
	}

	if(window.tinyMCE) {
		window.tinyMCE.execInstanceCommand('content', 'mceInsertContent', false, shortcodeContent);
		tinyMCEPopup.editor.execCommand('mceRepaint');
		tinyMCEPopup.close();
	}

	return false;
}
} );
</script>
</head>
<body>
<?php

	$img = "";

	// check button image
	if ( function_exists( 'has_post_thumbnail' ) ) {
		if ( has_post_thumbnail( $post->ID ) ) {
			$image_url = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ) );
			if ( isset( $image_url ) && ! empty( $image_url ) )
			$img = '<img src="'.esc_url($image_url[0]).'" alt="'.esc_attr($post->post_title).'" width="20" height="20" />';
		}
	}

	// Print title
	echo '<h3 class="media-title">'.$img.' '.$shortcode_title.'</h3>';

	// Check for long description
	if ( isset( $options['desclong'][0] ) && trim( $options['desclong'][0] ) != "" )
	echo '<p>'.$options['desclong'][0].'</p>'; ?>

<div class="media-form"><form id="shortcode-form" action="#" method="get">

<?php  $sp_base->do_overfields( $fields, true );  // Generate the overlay fields ?>

<div class="mceActionPanel">
	<div style="float: left">
		<input type="button" id="cancel" name="cancel" value="<?php echo __( 'Cancel', 'shortcodes-pro' ); ?>" onclick="tinyMCEPopup.close();" />
	</div>

	<div style="float: right">
		<input type="submit" id="insert" name="insert" value="<?php echo __( 'Insert',  'shortcodes-pro'  ); ?>" onclick="checkSubmit();" />
	</div>
</div>

</form>
</div>
</body>
</html>