<?php
/**
 * Loads the plugin files
 *
 * @since 1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Load basic setup. Plugin list links, text domain, footer links etc. 
require_once( INWC_DIR . 'admin/basic-setup.php' );

// Load admin setup. Register menus and settings
require_once( INWC_DIR . 'admin/admin-ui-setup.php' );

// Do plugin operations
require_once( INWC_DIR . 'functions/do.php' );

// Version updater
//require_once( INWC_DIR . 'update.php' );