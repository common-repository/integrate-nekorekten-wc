<?php
/**
 * Admin setup for the plugin
 *
 * @since 1.0
 */

// Exit if accessed directly
if ( ! defined('ABSPATH') ) exit;

/**
 * Enqueue Admin CSS and JS
 *
 * @since 1.5
 */
function inwc_enqueue_css_js( $hook ) {


	if ( $hook != "woocommerce_page_wc-settings" && $hook !== 'woocommerce_page_wc-orders' && $hook != "post.php" && $hook != "edit.php" ) {
		return;
	}

//    if ( isset( $_GET['tab'] ) && $_GET['tab'] === 'inwc_tab' ) { // if settings page in Woocommerce
//
//        // CSS
//
//        // JS
//
//    }

    // JS
    wp_enqueue_script('inwc-admin', INWC_URL . 'includes/js/inwc-admin.js', array('jquery', 'wp-color-picker'), false, true);
    wp_enqueue_script( 'sweetalert2', INWC_URL . 'includes/sweetalert2/sweetalert2.all.min.js', __FILE__ );
    wp_enqueue_script('intl-tel-input', INWC_URL . 'includes/intl-tel-input/build/js/intlTelInput-jquery.min.js', array('jquery'), '18.2.1', true);
    wp_enqueue_script('intl-tel-input-utils', INWC_URL . 'includes/intl-tel-input/build/js/utils.js', array('jquery', 'intl-tel-input'), '18.2.1', true);
    wp_enqueue_script('jquery-ui-dialog');
    // CSS
    wp_enqueue_style('inwc-admin-css', INWC_URL . 'includes/css/admin/admin.css', '', INWC_VERSION_NUM);
    wp_enqueue_style('inwc_fontawesome', INWC_URL . 'includes/fontawesome5/css/all.css', '', INWC_VERSION_NUM);
    wp_enqueue_style('intl-tel-input-css', INWC_URL . 'includes/intl-tel-input/build/css/intlTelInput.min.css', array(), '18.2.1');

    /**
     * Translate array for JS inwc-admin
     *
     * @since 1.1
     */
    $translation_array = array(
        'required' => __( 'This field is required', 'integrate-nekorekten-wc' ),
        'oops' => __( 'Oops..', 'integrate-nekorekten-wc' ),
        'validateFB_URL' => __( 'Must contain a valid Facebook URL', 'integrate-nekorekten-wc' ),
        'validateWebsite_URL' => __( 'Must contain a valid url (https:// or http://)', 'integrate-nekorekten-wc' ),
        'ip_copied' => __( 'IPv4 Address copied to clipboard.', 'integrate-nekorekten-wc' ),
    );
    wp_localize_script( 'inwc-admin', 'translate_obj', $translation_array );

}
add_action( 'admin_enqueue_scripts', 'inwc_enqueue_css_js' );

/**
 * Save settings
 * @since 1.0
 */
function inwc_save_settings() {

    // Verify nonce
    if (isset($_POST['inwc_settings_nonce']) && wp_verify_nonce(wp_unslash(sanitize_text_field($_POST['inwc_settings_nonce'])), 'inwc_settings_nonce')) {

        $turnON = isset($_POST['inwc_settings_turn_on']) ? sanitize_text_field($_POST['inwc_settings_turn_on']) : '';
        $APIKey = isset($_POST['inwc_settings_API_key']) ? sanitize_text_field($_POST['inwc_settings_API_key']) : '';
        $ShowColumn = isset($_POST['inwc_settings_colum_orders_page']) ? sanitize_text_field($_POST['inwc_settings_colum_orders_page']) : '';
        $ShowInAdminEmail = isset($_POST['inwc_settings_show_in_admin_email']) ? sanitize_text_field($_POST['inwc_settings_show_in_admin_email']) : '';

        $settings = array(
            'inwc_settings_turn_on' => $turnON,
            'inwc_settings_API_key' => $APIKey,
            'inwc_settings_colum_orders_page' => $ShowColumn,
            'inwc_settings_show_in_admin_email' => $ShowInAdminEmail,
        );
        update_option('inwc_settings_group', $settings);

    } else {
        // Nonce verification failed
        wp_die('Nonce verification failed.');
    }

}
add_filter('woocommerce_settings_save_inwc_tab', 'inwc_save_settings');

/**
 * Add a new tab to WooCommerce settings page.
 * @since 1.0
 */
function inwc_add_custom_settings_tab( $tabs ) {
    $tabs['inwc_tab'] = __( 'Integrate nekorekten.com', 'integrate-nekorekten-wc' );
    return $tabs;
}
add_filter( 'woocommerce_settings_tabs_array', 'inwc_add_custom_settings_tab', 50 );


/**
 * Register Settings and view
 * @since 1.0
 */
function inwc_custom_settings() {
    ?>
    <div class="wrap inwc-wrap">
        <?php
        settings_fields('inwc_settings_group');
        do_settings_sections('inwc_settings_group');
        wp_nonce_field('inwc_settings_nonce', 'inwc_settings_nonce');
        ?>
    </div>
    <?php
}

/**
 * Plugin info view
 * @since 1.0
 */
function inwc_plugin_info() {
    $allowed_tags = array(
        'a' => array(
            'href' => array(),
            'target' => array(),
        ),
        'br' => array(),
    );
    $inwc_footer_text = sprintf( __( 'If you like this plugin, please <a href="%s" target="_blank">make a donation</a> or leave me a <a href="%s" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a> rating to support continued development. Thanks a bunch!', 'integrate-nekorekten-wc' ),
        esc_url('https://revolut.me/mvalchev'),
        esc_url('https://wordpress.org/support/plugin/integrate-nekorekten-wc/reviews/?rate=5#new-post')
    );
    $inwc_support_links = sprintf( __( '<a href="%s" target="_blank">Get support</a>', 'integrate-nekorekten-wc' ),
        esc_url('https://wordpress.org/support/plugin/integrate-nekorekten-wc/#new-post'),
    );
    ?>
    <div id="postbox-container-1" class="postbox-container inwc_postbox-container" style="float: none;">
        <div id="side-sortables" class="meta-box-sortables ui-sortable" style="">
            <div id="submitdiv" class="postbox ">
                <div class="postbox-header">
                    <h2 class="hndle ui-sortable-handle" style="padding: 0 10px; justify-content: center;"><?php echo esc_html__('Integrate nekorekten.com for WooCommerce' , 'integrate-nekorekten-wc') ?></h2>
                </div>
                <div class="inside" style="text-align: center;">
                    <p><?php echo wp_kses($inwc_footer_text, $allowed_tags); ?></p>
                    <p style="text-align: center;"><?php echo wp_kses($inwc_support_links, $allowed_tags); ?> | <a href="https://translate.wordpress.org/projects/wp-plugins/integrate-nekorekten-wc/" target="_blank"><span class="dashicons dashicons-translation"></span></a></p>
                    <hr>
                    <p style="text-align: center"><?php echo esc_html__('Integrate nekorekten.com for WooCommerce version:' , 'integrate-nekorekten-wc') ?> <?php echo esc_html(INWC_VERSION_NUM) ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php
}
add_action( 'woocommerce_after_settings_inwc_tab', 'inwc_plugin_info' );


/**
 * Register Settings
 * @since 1.0
 */
function inwc_register_settings() {
    add_settings_section(
        'inwc_settings_section',
        __( 'Integrate nekorekten.com for WooCommerce Settings', 'integrate-nekorekten-wc' ),
        'inwc_settings_section_callback',
        'inwc_settings_group'
    );

    add_settings_field(
        'inwc_settings_turn_on',
        __( 'Turn on nekorekten.com', 'integrate-nekorekten-wc' ),
        'inwc_settings_turn_on_callback',
        'inwc_settings_group',
        'inwc_settings_section'
    );

    register_setting( 'vwg_settings_group', 'inwc_settings_turn_on', array(
        'type' => 'boolean',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => true
    ) );

    add_settings_field(
        'inwc_settings_API_key',
        __( 'API Key', 'integrate-nekorekten-wc' ),
        'inwc_settings_API_key_callback',
        'inwc_settings_group',
        'inwc_settings_section'
    );

    register_setting( 'inwc_settings_group', 'inwc_settings_API_key', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field'
    ) );

    add_settings_field(
        'inwc_settings_colum_orders_page',
        __( 'Show the "Correct Customer Status" column', 'integrate-nekorekten-wc' ),
        'inwc_settings_colum_orders_page_callback',
        'inwc_settings_group',
        'inwc_settings_section'
    );

    register_setting( 'vwg_settings_group', 'inwc_settings_colum_orders_page', array(
        'type' => 'boolean',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => true
    ) );

    add_settings_field(
        'inwc_settings_show_in_admin_email',
        __( 'Show if there are incorrect alerts in the email for a new admin order', 'integrate-nekorekten-wc' ),
        'inwc_settings_show_in_admin_email_callback',
        'inwc_settings_group',
        'inwc_settings_section'
    );

    register_setting( 'vwg_settings_group', 'inwc_settings_show_in_admin_email', array(
        'type' => 'boolean',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => true
    ) );

}

/**
 * Settings section
 * @since 1.1
 */
function inwc_settings_section_callback() {

}

function inwc_settings_turn_on_callback() {
    $option = get_option('inwc_settings_group');
    $checked = ($option['inwc_settings_turn_on'] === '1') ? 'checked' : '';
    ?>
    <div class="can-toggle">
        <input type="checkbox" name="inwc_settings_turn_on" value="1" <?php echo esc_attr($checked); ?> />
        <label for="inwc_settings_turn_on">
            <div class="can-toggle__switch" data-checked="<?php echo esc_html__('On', 'integrate-nekorekten-wc') ?>"
                 data-unchecked="<?php echo esc_html__('Off', 'integrate-nekorekten-wc') ?>">
            </div>
        </label>
    </div>
    <?php
}

function inwc_settings_API_key_callback() {
    $option = get_option( 'inwc_settings_group' );
    $server_ipv4 = inwc_get_server_ipv4();
    $localhost_checker = gethostbyname($_SERVER['SERVER_NAME']);
    ?>
    <?php if ($localhost_checker == '127.0.0.1') : ?>
        <p style="font-size: 12px; color: #d09e35;"><?php echo esc_html__('Uses the local server plugin, it is recommended to use your public IP address for API key configuration in nekorekten.com. You can check your public ip address' , 'integrate-nekorekten-wc') ?> <a href="https://www.ipaddress.my/" target="_blank"><?php echo esc_html__('here' , 'integrate-nekorekten-wc') ?></a>.</p>
    <?php else: ?>
        <p style="font-size: 12px;"><?php echo esc_html__('Server IPv4 Address:' , 'integrate-nekorekten-wc') ?> <code id="inwc_server_ip" style="cursor: pointer;"><?php echo esc_attr($server_ipv4); ?></code> <span><?php echo esc_html__('( use for API key configuration at nekorekten.com )' , 'integrate-nekorekten-wc') ?></span></p>
        <div id="inwc_clipboard-alert" style="display: none;"></div>
    <?php endif; ?>
    <input type="text" id="inwc_settings_API_key" name="inwc_settings_API_key" value="<?php echo esc_attr($option['inwc_settings_API_key']) ?>" />
    <?php
}

function inwc_settings_colum_orders_page_callback() {
    $option = get_option('inwc_settings_group');
    $checked = ($option['inwc_settings_colum_orders_page'] === '1') ? 'checked' : '';
    ?>
    <input type="checkbox" name="inwc_settings_colum_orders_page" value="1" <?php echo esc_attr($checked); ?> />
    <?php
}

function inwc_settings_show_in_admin_email_callback() {
    $option = get_option('inwc_settings_group');
    $checked = ($option['inwc_settings_show_in_admin_email'] === '1') ? 'checked' : '';
    ?>
    <input type="checkbox" name="inwc_settings_show_in_admin_email" value="1" <?php echo esc_attr($checked); ?> />
    <?php
}

add_action( 'admin_init', 'inwc_register_settings' );
add_action( 'woocommerce_settings_tabs_inwc_tab', 'inwc_custom_settings' );

/**
 * Function return Server IPv4 Address
 * @since 1.1
 */
function inwc_get_server_ipv4() {
    $server_ip = gethostbyname(gethostname());
    return $server_ip;
}


