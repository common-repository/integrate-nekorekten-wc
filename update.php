<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Update function when update to new version
 *
 * @since 1.0
 */
function inwc_update_to_new_version() {
    $plugin_data = get_file_data( INWC_DIR . '/integrate-nekorekten-wc.php', array( 'Version' ) );
    $plugin_version = $plugin_data[0];

    // Check if the current plugin version matches the desired version
//    if ($plugin_version === '1.0') {
//
//    }
}
add_action( 'admin_init', 'inwc_update_to_new_version' );



/**
 * Admin notice info if have version msg
 *
 * @since 1.0
 */
// Function to display admin message
function inwc_show_admin_message_for_version() {
    $plugin_data = get_file_data( INWC_DIR . '/integrate-nekorekten-wc.php', array( 'Version' ) );
    $plugin_version = $plugin_data[0];

    // Check if the current plugin version matches the desired version
//    if ($plugin_version === '1.0') {
//        $message = __('', 'integrate-nekorekten-wc');
//        echo '<div class="notice notice-info is-dismissible"><p>' . esc_html($message) . '</p></div>';
//    }
}
add_action( 'admin_notices', 'inwc_show_admin_message_for_version' );

