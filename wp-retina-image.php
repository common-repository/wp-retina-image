<?php
/*
   Plugin Name: WP Retina Image
   Plugin URI: https://www.coffee-break-designs.com/production/wp-retina-image/
   Version: 1.0.1
   Author: wadadanet
   Author URI: http://www.coffee-break-designs.com/
   Description: Just by uploading foo@2x.png, resized 1x are created automatically
   Text Domain: wp-retina-image
   License: GPLv3
   Domain Path: /languages
  */

/**
 * Just by uploading foo@2x.png, resized 1x are created automatically
 *
 * @package WP_Retina_Image
 * @version 1.0.1
 * @author Minoru Wada <wada@coffee-break-designs.com>
 * @copyright Copyright (c) 2017 coffee break designs.com .
 * @license http://opensource.org/licenses/gpl-2.0.php GPLv2
 * @link https://www.coffee-break-designs.com/production/wp-retina-image/
 */
/**
 * @package WP_Retina_Image
 */


$WpRetinaImage_minimalRequiredPhpVersion = '5.0';

/**
 * Check the PHP version and give a useful error message if the user's version is less than the required version
 * @return boolean true if version check passed. If false, triggers an error which WP will handle, by displaying
 * an error message on the Admin page
 */
function WpRetinaImage_noticePhpVersionWrong() {
	global $WpRetinaImage_minimalRequiredPhpVersion;
	echo '<div class="updated fade">' .
	  __('Error: plugin "WP Retina Image" requires a newer version of PHP to be running.',  'wp-retina-image').
			'<br/>' . __('Minimal version of PHP required: ', 'wp-retina-image') . '<strong>' . $WpRetinaImage_minimalRequiredPhpVersion . '</strong>' .
			'<br/>' . __('Your server\'s PHP version: ', 'wp-retina-image') . '<strong>' . phpversion() . '</strong>' .
		 '</div>';
}

function WpRetinaImage_PhpVersionCheck() {
	global $WpRetinaImage_minimalRequiredPhpVersion;
	if (version_compare(phpversion(), $WpRetinaImage_minimalRequiredPhpVersion) < 0) {
		add_action('admin_notices', 'WpRetinaImage_noticePhpVersionWrong');
		return false;
	}
	return true;
}

/**
 * Initialize internationalization (i18n) for this plugin.
 * References:
 *      http://codex.wordpress.org/I18n_for_WordPress_Developers
 *      http://www.wdmac.com/how-to-create-a-po-language-translation#more-631
 * @return void
 */
function WpRetinaImage_i18n_init() {
	$pluginDir = dirname(plugin_basename(__FILE__));
	load_plugin_textdomain('wp-retina-image', false, $pluginDir . '/languages/');
}


//////////////////////////////////
// Run initialization
/////////////////////////////////

// Initialize i18n
add_action('plugins_loaded','WpRetinaImage_i18n_init');

// Run the version check.
// If it is successful, continue with initialization for this plugin
if (WpRetinaImage_PhpVersionCheck()) {
	// Only load and run the init function if we know PHP version can parse it
	include_once('wp-retina-image_init.php');
	WpRetinaImage_init(__FILE__);
}
