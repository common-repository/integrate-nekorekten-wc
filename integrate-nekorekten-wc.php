<?php
/**
 * Plugin Name: Integrate nekorekten.com for WooCommerce
 * Plugin URI:
 * Description: Integrate nekorekten.com for WooCommerce is a plugin that helps to check your customers - whether there are negative reviews about them in the nekorekten.com system, as well as you can report incorrect customers. Everything is done to facilitate the check in the order, also there is an option to receive directly in the email about a new order, if there are negative reviews.
 * Author: Martin Valchev
 * Author URI: https://martinvalchev.com/
 * Requires Plugins: woocommerce
 * Version: 1.6
 * Text Domain: integrate-nekorekten-wc
 * Domain Path: /languages
 * License: GPL v2 - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Define constants
 *
 * @since 1.0
 */
if ( ! defined( 'INWC_VERSION_NUM' ) ) 		    define( 'INWC_VERSION_NUM'		    , '1.5' ); // Plugin version constant
if ( ! defined( 'INWC_PLUGIN' ) )		define( 'INWC_PLUGIN'		, trim( dirname( plugin_basename( __FILE__ ) ), '/' ) ); // Name of the plugin folder eg - 'integrate-nekorekten-wc'
if ( ! defined( 'INWC_DIR' ) )	define( 'INWC_DIR'	, plugin_dir_path( __FILE__ ) ); // Plugin directory absolute path with the trailing slash. Useful for using with includes eg - /var/www/html/wp-content/plugins/integrate-nekorekten-wc/
if ( ! defined( 'INWC_URL' ) )	define( 'INWC_URL'	, plugin_dir_url( __FILE__ ) ); // URL to the plugin folder with the trailing slash. Useful for referencing src eg - http://localhost/wp/wp-content/plugins/integrate-nekorekten-wc/
if ( ! defined( 'INWC_PHP_MINIMUM_VERSION' ) )	define( 'INWC_PHP_MINIMUM_VERSION'	, '7.4' );
if ( ! defined( 'INWC_WP_MINIMUM_VERSION' ) )	define( 'INWC_WP_MINIMUM_VERSION'	, '4.8' );
if ( ! defined( 'INWC_PLUGIN_NAME' ) )	        define( 'INWC_PLUGIN_NAME'	        ,  get_file_data(__FILE__, ['Plugin Name'], false)[0] ); // Name plugin - 'Integrate nekorekten.com for WooCommerce'

//require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

/**
 * Check Woocommerce is deactivate
 *
 * @since 1.0
 */
function inwc_deactivate_on_woocommerce_deactivate() {
    if ( ! class_exists( 'woocommerce' ) || ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
        inwc_admin_notice_err(__('Integrate nekorekten.com for WooCommerce requires WooCommerce to be installed and activated. Please activate WooCommerce and try again.', 'integrate-nekorekten-wc'));
        deactivate_plugins( plugin_basename(__FILE__) );
    }
}
add_action( 'admin_init', 'inwc_deactivate_on_woocommerce_deactivate' );


/**
 * Check Woocommerce is activate
 *
 * @since 1.0
 */
function inwc_activate_on_woocommerce_activate() {
    if ( class_exists( 'woocommerce' ) && is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
        inwc_admin_notice_err(false);
    }
}
add_action( 'admin_init', 'inwc_activate_on_woocommerce_activate' );


/**
 * Database upgrade
 *
 * @since 1.0
 */
function inwc_upgrader() {
	
	// Get the current version of the plugin stored in the database.
	$current_ver = get_option( 'abl_inwc_version', '0.0' );
	
	// Return if we are already on updated version. 
	if ( version_compare( $current_ver, INWC_VERSION_NUM, '==' ) ) {
		return;
	}
	
	// This part will only be excuted once when a user upgrades from an older version to a newer version.
	
	// Finally add the current version to the database. Upgrade
	update_option( 'abl_inwc_version', INWC_VERSION_NUM );

}
add_action( 'admin_init', 'inwc_upgrader' );

/**
 * Admin notice err
 *
 * @since 1.6
 */
function inwc_admin_notice_err($msg = '') {
    if (!empty($msg)) :
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($msg); ?></p>
        </div>
    <?php endif;
}
add_action( 'admin_notices', 'inwc_admin_notice_err' );


// Load everything
require_once( INWC_DIR . 'loader.php' );

// Register activation hook (this has to be in the main plugin file or refer bit.ly/2qMbn2O)
register_activation_hook(__FILE__, 'inwc_activate_plugin');