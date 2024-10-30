<?php 
/**
 * Basic setup functions for the plugin
 *
 * @since 1.0
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Plugin activatation
 *
 * This function runs when user activates the plugin. Used in register_activation_hook in the main plugin file. 
 * @since 1.0
 */
function inwc_activate_plugin() {
    $option = get_option('inwc_settings_group');

    $settings = array(
        'inwc_settings_turn_on' => isset($option['inwc_settings_turn_on']) ? sanitize_text_field( $option['inwc_settings_turn_on'] ) : '1',
        'inwc_settings_API_key' => isset($option['inwc_settings_API_key']) ? sanitize_text_field( $option['inwc_settings_API_key'] ) : '',
        'inwc_settings_colum_orders_page' => isset($option['inwc_settings_colum_orders_page']) ? sanitize_text_field( $option['inwc_settings_colum_orders_page'] ) : '1',
        'inwc_settings_show_in_admin_email' => isset($option['inwc_settings_show_in_admin_email']) ? sanitize_text_field( $option['inwc_settings_show_in_admin_email'] ) : '',
    );
    update_option('inwc_settings_group', $settings);
}


/**
 * Load plugin text domain
 *
 * @since 1.0
 */
function inwc_load_plugin_textdomain() {
    load_plugin_textdomain( 'integrate-nekorekten-wc', false, '/integrate-nekorekten-wc/languages/' );
}
add_action( 'plugins_loaded', 'inwc_load_plugin_textdomain' );

/**
 * Print direct link to plugin settings in plugins list in admin
 *
 * @since 1.0
 */
function inwc_settings_link( $links ) {
	return array_merge(
		array(
			'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=inwc_tab' ) . '">' . __( 'Settings', 'integrate-nekorekten-wc' ) . '</a>'
		),
		$links
	);
}
add_filter( 'plugin_action_links_' . INWC_PLUGIN . '/integrate-nekorekten-wc.php', 'inwc_settings_link' );

/**
 * Add donate and other links to plugins list
 *
 * @since 1.0
 */
function inwc_plugin_row_meta( $links, $file ) {
	if ( strpos( $file, 'integrate-nekorekten-wc.php' ) !== false ) {
		$new_links = array(
				'donate' 	=> '<a href="https://revolut.me/mvalchev" target="_blank">Donate</a>',
				'hireme' 	=> '<a href="https://martinvalchev.com/#contact" target="_blank">Hire Me For A Project</a>',
				);
		$links = array_merge( $links, $new_links );
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'inwc_plugin_row_meta', 10, 2 );



