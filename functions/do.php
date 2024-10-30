<?php
/**
 * Operations of the plugin are included here.
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

$option = get_option( 'inwc_settings_group' );
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

/**
 * Enqueue CSS and JS
 *
 * @since 1.0
 */
//function inwc_enqueue_scripts( $hook ) {
//
//}
//add_action( 'wp_enqueue_scripts', 'inwc_enqueue_scripts' );

/**
 * Settings turn on / turn off global
 */
if (isset($option['inwc_settings_turn_on']) && $option['inwc_settings_turn_on'] == '1') {

    /**
     * Add Signals from nekorekten.com meta box to WooCommerce order edit page
     *
     * @since 1.5
     */
    function inwc_add_signals_meta_box_to_order_edit_page()
    {
        $screen = class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' ) && wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
            ? wc_get_page_screen_id( 'shop-order' )
            : 'shop_order';

        add_meta_box(
            'signals_meta_box',
            __('Signals from nekorekten.com', 'integrate-nekorekten-wc'),
            'inwc_render_signals_meta_box',
            $screen,
            'advanced',
            'high'
        );
    }

    add_action('add_meta_boxes', 'inwc_add_signals_meta_box_to_order_edit_page');


    /**
     * Render content for the Signals from nekorekten.com meta box
     *
     * @since 1.5
     */
    function inwc_render_signals_meta_box($post)
    {
        // Get the settings option
        $option = get_option('inwc_settings_group');
        $validPhone = false;

        // Get the order object
        $order = wc_get_order($post->ID);
        if (!$order) {
            return;
        }

        $api_key = $option['inwc_settings_API_key'];
        $firstName = $order->get_billing_first_name();
        $lastName = $order->get_billing_last_name();

        $phone = $order->get_billing_phone();
        $email = $order->get_billing_email();

        $api_url = 'https://api.nekorekten.com/api/v1/reports';
        $restcountriesApiUrl = 'https://restcountries.com/v3.1/all?fields=idd,name';

        $query_args_phone = array(
            'phone' => $phone
        );

        $query_args_email = array(
            'email' => $email
        );

        $headers = array(
            'Content-Type' => 'application/json',
            'Api-Key' => $api_key
        );

        $responsePhone = wp_remote_get(add_query_arg($query_args_phone, $api_url), array('headers' => $headers));
        $responseEmail = wp_remote_get(add_query_arg($query_args_email, $api_url), array('headers' => $headers));
        $responseAllCountryCodes = wp_remote_get($restcountriesApiUrl);

        if (is_wp_error($responseAllCountryCodes)) {
            echo "Error retrieving data";
        } else {
            $countries = json_decode(wp_remote_retrieve_body($responseAllCountryCodes), true);
            $phoneCountryCode = null;

            foreach ($countries as $country) {
                $countryName = $country['name']['common'];
                $countryCode = $country['idd']['root'];
                $countrySuffixes = $country['idd']['suffixes'];

                if (empty($countryCode) && empty($countrySuffixes)) {
                    continue;
                }

                if ($countryCode == '+1') {
                    $phoneCountryCode = $countryCode;
                } elseif ($countryName == 'Vatican City') {
                    $phoneCountryCode = '+39';
                } else {
                    $phoneCountryCode = $countryCode . $countrySuffixes[0];
                }

                // Remove any non-digit characters from the phone number
                $cleanedNumber = preg_replace('/\D/', '', $phone);
                // Check if the cleaned number is valid
                if (preg_match('/^\+?\d{1,3}\d{8,15}$/', $cleanedNumber)) {
                    $countryCode = preg_match('/^\+?(\d{1,3})/', $cleanedNumber, $matches) ? $matches[1] : null;

                    if ($countryCode == $phoneCountryCode) {
                        $validPhone = true;
                        break;
                    }
                } else {
                    // Invalid phone number
                    echo "Not a valid phone number";
                    return;
                }
            }
        }

        if ((!is_wp_error($responsePhone) || !is_wp_error($responseEmail)) && (wp_remote_retrieve_response_code($responsePhone) === 200 || wp_remote_retrieve_response_code($responseEmail) === 200)) {
            $data_phone = json_decode(wp_remote_retrieve_body($responsePhone));
            $data_email = json_decode(wp_remote_retrieve_body($responseEmail));

            if (($data_email && isset($data_email->count) && $data_email->count > 0) || ($data_phone && isset($data_phone->count) && $data_phone->count > 0)) {
                $order->update_meta_data('inwc_correct_customer_status_data', 'incorrect');
            } else {
                $order->update_meta_data('inwc_correct_customer_status_data', 'correct');
            }

            $order->save();

            ?>
            <div class="signal-wrapper">
                <?php

                if ($data_email && isset($data_email->items) && !empty($data_email->items)) {
                    ?>
                    <div class="signals-email">
                        <div style="padding-top: 10px; text-align: center;"><?php echo esc_html__('Results by email:', 'integrate-nekorekten-wc') ?>
                            <b style="color: red;"><?php echo esc_attr($email); ?></b></div>
                        <?php
                        foreach ($data_email->items as $signal) {
                            $dateTime = new DateTime($signal->createDate);
                            $formattedDate = $dateTime->format('Y-m-d H:i:s');
                            $formattedDateForShow = date_i18n('d M Y, H:i', strtotime($formattedDate));
                            ?>
                            <div class="comment-box">
                                <div class="comment-header">
                                    <div class="comment-user-info">
                                        <?php if ($signal->firstName || $signal->lastName) : ?>
                                            <span><i class="fas fa-user"></i>&nbsp;&nbsp;<?php echo esc_attr($signal->firstName) ?> <?php echo esc_attr($signal->lastName) ?> </span>
                                        <?php endif; ?>
                                        <?php if ($signal->email) : ?>
                                            <span><i class="fas fa-at"></i>&nbsp;&nbsp;<?php echo esc_attr($signal->email) ?> </span>
                                        <?php endif; ?>
                                        <?php if ($signal->phone) : ?>
                                            <span><i class="fas fa-phone-square-alt"></i>&nbsp;&nbsp;<?php echo esc_attr($signal->phone) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($signal->facebookUrl || $signal->siteUrl) : ?>
                                        <div class="comment-urls">
                                            <?php if ($signal->facebookUrl) : ?>
                                                &nbsp;<a href="<?php echo esc_url($signal->facebookUrl) ?>"
                                                         target="_blank" style="color: inherit"><i
                                                            class="fab fa-facebook-square"></i></a>&nbsp;
                                            <?php endif; ?>
                                            <?php if ($signal->siteUrl) : ?>
                                                &nbsp;<a href="<?php echo esc_url($signal->siteUrl) ?>" target="_blank"
                                                         style="color: inherit"><i class="fas fa-globe"></i></a>&nbsp;
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="comment-text">
                                    <?php echo esc_attr($signal->text) ?>
                                </div>
                                <div class="comment-footer">
                                    <div class="comment-info">
                           <span class="comment-author">
                                <span><?php echo esc_attr($signal->user->firstName) ?><?php echo esc_attr($signal->user->lastName) ?></span>
                            </span>
                                        <span class="comment-date"><?php echo esc_attr($formattedDateForShow) ?></span>
                                    </div>

                                    <div class="comment-actions">
                                        <span><i class="fas fa-eye"></i> <?php echo esc_attr($signal->views) ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php }
                        ?> </div> <?php
                } else {
                    ?>
                    <div class="signals-email">
                        <div style="padding-top: 10px; text-align: center;"><?php echo esc_html__('No results found for this email:', 'integrate-nekorekten-wc') ?>
                            <br><b style="color: green;"><?php echo esc_attr($email); ?></b>
                            <br><i class="fas fa-check-circle"
                                   style="color: green; font-size: 60px; padding-top: 30px;"></i>
                        </div>
                    </div>
                    <hr class="mobile-hr">
                    <?php
                }

                if ($data_phone && isset($data_phone->items) && !empty($data_phone->items)) {
                    ?>
                    <div class="signals-phone">
                        <div style="padding-top: 10px; text-align: center;"><?php echo esc_html__('Results by phone:', 'integrate-nekorekten-wc') ?>
                            <b style="color: red;"><?php echo esc_attr($phone); ?></b></div>
                        <?php
                        foreach ($data_phone->items as $signal) {
                            $dateTime = new DateTime($signal->createDate);
                            $formattedDate = $dateTime->format('Y-m-d H:i:s');
                            $formattedDateForShow = date_i18n('d M Y, H:i', strtotime($formattedDate));
                            ?>
                            <div class="comment-box">
                                <div class="comment-header">
                                    <div class="comment-user-info">
                                        <?php if ($signal->firstName || $signal->lastName) : ?>
                                            <span><i class="fas fa-user"></i>&nbsp;&nbsp;<?php echo esc_attr($signal->firstName) ?> <?php echo esc_attr($signal->lastName) ?> </span>
                                        <?php endif; ?>
                                        <?php if ($signal->email) : ?>
                                            <span><i class="fas fa-at"></i>&nbsp;&nbsp;<?php echo esc_attr($signal->email) ?> </span>
                                        <?php endif; ?>
                                        <?php if ($signal->phone) : ?>
                                            <span><i class="fas fa-phone-square-alt"></i>&nbsp;&nbsp;<?php echo esc_attr($signal->phone) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($signal->facebookUrl || $signal->siteUrl) : ?>
                                        <div class="comment-urls">
                                            <?php if ($signal->facebookUrl) : ?>
                                                &nbsp;<a href="<?php echo esc_url($signal->facebookUrl) ?>"
                                                         target="_blank" style="color: inherit"><i
                                                            class="fab fa-facebook-square"></i></a>&nbsp;
                                            <?php endif; ?>
                                            <?php if ($signal->siteUrl) : ?>
                                                &nbsp;<a href="<?php echo esc_url($signal->siteUrl) ?>" target="_blank"
                                                         style="color: inherit"><i class="fas fa-globe"></i></a>&nbsp;
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="comment-text">
                                    <?php echo esc_attr($signal->text) ?>
                                </div>
                                <div class="comment-footer">
                                    <div class="comment-info">
                           <span class="comment-author">
                              <span><?php echo esc_attr($signal->user->firstName) ?><?php echo esc_attr($signal->user->lastName) ?></span>
                            </span>
                                        <span class="comment-date"><?php echo esc_attr($formattedDateForShow) ?></span>
                                    </div>

                                    <div class="comment-actions">
                                        <span><i class="fas fa-eye"></i> <?php echo esc_attr($signal->views) ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php }
                        ?> </div> <?php
                } else {
                    ?>
                    <div class="signals-phone">
                        <div style="padding-top: 10px; text-align: center;"><?php echo esc_html__('No results found for this phone:', 'integrate-nekorekten-wc') ?>
                            <br><b style="color: green;"><?php echo esc_attr($phone); ?></b>
                            <br><i class="fas fa-check-circle"
                                   style="color: green; font-size: 60px; padding-top: 30px;"></i>
                        </div>
                    </div>
                    <hr class="mobile-hr">
                    <?php
                }

                ?>
            </div>

            <div style="text-align: center; margin: 15px 0;">
                <a href="javascript:;" id="btn-report"
                   class="button button-primary"><?php echo esc_html__('Report it', 'integrate-nekorekten-wc') ?></a>
            </div>

            <div id="report-overlay"></div>
            <div id="report-popup">
                <div id="popup-header">
                    <h2><?php echo esc_html__('Report as incorrect', 'integrate-nekorekten-wc') ?></h2>
                    <span id="close-popup"><i class="fas fa-times"></i></span>
                </div>
                <form id="report-form" method="post"
                      action="<?php echo esc_url(admin_url('admin-post.php?action=inwc_form_submission_signal')); ?>">
                    <input type="hidden" id="inwc_submission_nonce" name="inwc_submission_nonce" value="<?php echo wp_create_nonce('inwc_form_submission_nonce'); ?>"/>
                    <div class="inwc-row">
                        <div class="field-wrapper">
                            <label for="first-name"><?php echo esc_html__('First Name', 'integrate-nekorekten-wc') ?></label>
                            <input type="text" id="first-name" name="first-name"
                                   value="<?php echo isset($firstName) ? esc_attr($firstName) : ''; ?>" <?php echo isset($firstName) ? 'readonly' : ''; ?>>
                        </div>
                        <div class="field-wrapper">
                            <label for="last-name"><?php echo esc_html__('Last Name', 'integrate-nekorekten-wc') ?></label>
                            <input type="text" id="last-name" name="last-name"
                                   value="<?php echo isset($lastName) ? esc_attr($lastName) : ''; ?>" <?php echo isset($lastName) ? 'readonly' : ''; ?>>
                        </div>
                    </div>

                    <div class="inwc-row">
                        <div class="field-wrapper">
                            <label for="phone"><?php echo esc_html__('Phone', 'integrate-nekorekten-wc') ?></label>
                            <?php if ($validPhone) : ?>
                                <input type="tel" id="phone" name="phone"
                                       value="<?php echo isset($phone) ? esc_attr($phone) : ''; ?>" <?php echo isset($phone) ? 'readonly' : ''; ?> >
                            <?php else : ?>
                                <input type="tel" id="phone" class="field-with-choose-code" name="phone">
                            <?php endif; ?>
                        </div>

                        <div class="field-wrapper">
                            <label for="email"><?php echo esc_html__('Email', 'integrate-nekorekten-wc') ?></label>
                            <input type="email" id="email" name="email"
                                   value="<?php echo isset($email) ? esc_attr($email) : ''; ?>" <?php echo isset($email) ? 'readonly' : ''; ?>>
                        </div>
                    </div>

                    <div class="inwc-row">
                        <div class="field-wrapper">
                            <label for="website-url"><?php echo esc_html__('Website URL', 'integrate-nekorekten-wc') ?></label>
                            <input type="text" id="website-url" name="website-url">
                        </div>

                        <div class="field-wrapper">
                            <label for="facebook-url"><?php echo esc_html__('Facebook URL', 'integrate-nekorekten-wc') ?></label>
                            <input type="text" id="facebook-url" name="facebook-url">
                        </div>
                    </div>

                    <div class="inwc-row">
                        <div class="field-wrapper">
                            <label for="description"><?php echo esc_html__('Description', 'integrate-nekorekten-wc') ?>
                                <em style="font-weight: bold; color: #d63638">*</em></label>
                            <textarea style="width: 100%" rows="4" id="description" name="description"></textarea>
                        </div>
                    </div>

                    <div style="text-align: center; margin-top: 10px;">
                        <button class="button button-primary" id="btn-report-post"
                                style="margin-top: 0;"><?php echo esc_html__('Report', 'integrate-nekorekten-wc') ?></button>
                    </div>

                </form>
            </div>

            <?php
        } else {
            $data_phone = json_decode(wp_remote_retrieve_body($responsePhone));
            $data_email = json_decode(wp_remote_retrieve_body($responseEmail));

            if (isset($data_phone->message) || isset($data_email->message)) {
                if ($data_phone->message == $data_email->message) {
                    echo '<p style="margin-top: 0; padding: 20px 0 8px 0; color: red;">' . esc_html($data_phone->message) . '</p>';
                } else {
                    echo '<p style="margin-top: 0; padding: 20px 0 8px 0; color: red;">' . esc_html($data_phone->message) . '</p>';
                    echo '<p style="margin-top: 0; padding: 20px 0 8px 0; color: red;">' . esc_html($data_email->message) . '</p>';
                }
            } else {
                echo '<p style="margin-top: 0; padding: 20px 0 8px 0; color: red;">' . esc_html__('Unable to retrieve data from nekorekten.com API, check if you have configured the correct API key or wait a few minutes because you may be doing 5 requests per minute', 'integrate-nekorekten-wc') . '</p>';
            }

        }
    }


    /**
     *  Hook the function to the WooCommerce new order action and update customer correct status
     *
     * @since 1.5
     */
    function inwc_new_order_received_update_signals_data($order_id) {
        // Get the order object
        $order = wc_get_order($order_id);

        if (!$order) {
            return; // Exit if order is not found
        }

        $option = get_option('inwc_settings_group');

        // Ensure the API key exists
        if (!isset($option['inwc_settings_API_key'])) {
            return; // Exit if API key is not found
        }

        $api_key = $option['inwc_settings_API_key'];

        // Get billing phone and email from the order
        $phone = $order->get_billing_phone();
        $email = $order->get_billing_email();

        $api_url = 'https://api.nekorekten.com/api/v1/reports';

        $query_args_phone = array(
            'phone' => $phone
        );

        $query_args_email = array(
            'email' => $email
        );

        $headers = array(
            'Content-Type' => 'application/json',
            'Api-Key' => $api_key
        );

        $responsePhone = wp_remote_get(add_query_arg($query_args_phone, $api_url), array('headers' => $headers));
        $responseEmail = wp_remote_get(add_query_arg($query_args_email, $api_url), array('headers' => $headers));

        // Check if responses are valid and process them
        if ((!is_wp_error($responsePhone) && wp_remote_retrieve_response_code($responsePhone) === 200) ||
            (!is_wp_error($responseEmail) && wp_remote_retrieve_response_code($responseEmail) === 200)) {

            $data_phone = json_decode(wp_remote_retrieve_body($responsePhone));
            $data_email = json_decode(wp_remote_retrieve_body($responseEmail));

            // Check if either data set has a count greater than 0
            if (($data_email && isset($data_email->count) && $data_email->count > 0) ||
                ($data_phone && isset($data_phone->count) && $data_phone->count > 0)) {
                $order->update_meta_data('inwc_correct_customer_status_data', 'incorrect');
            } else {
                $order->update_meta_data('inwc_correct_customer_status_data', 'correct');
            }

            // Save the updated meta data
            $order->save();
        }
    }
    add_action('woocommerce_new_order', 'inwc_new_order_received_update_signals_data');



    /**
     * AJAX handler for processing form submission signal
     *
     * @since 1.0
     */
    function inwc_form_submission_signal()
    {
        // Verify nonce
        if (isset($_POST['nonce']) && wp_verify_nonce(wp_unslash(sanitize_text_field($_POST['nonce'])), 'inwc_form_submission_nonce')) {

            $option = get_option('inwc_settings_group');
            $api_key = $option['inwc_settings_API_key'];
            $api_url = 'https://api.nekorekten.com/api/v1/reports';

            $first_name = isset($_POST['firstName']) ? sanitize_text_field($_POST['firstName']) : '';
            $last_name = isset($_POST['lastName']) ? sanitize_text_field($_POST['lastName']) : '';
            $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
            $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
            $website_url = isset($_POST['websiteUrl']) ? esc_url_raw($_POST['websiteUrl']) : '';
            $facebook_url = isset($_POST['facebookUrl']) ? esc_url_raw($_POST['facebookUrl']) : '';
            $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';

            $query_args = array(
                'firstName' => $first_name,
                'lastName' => $last_name,
                'phone' => $phone,
                'email' => $email,
                'siteUrl' => $website_url,
                'facebookUrl' => $facebook_url,
                'text' => $description,
            );

            $headers = array(
                'Content-Type' => 'application/json',
                'Api-Key' => $api_key
            );

            $response = wp_remote_post($api_url, array('headers' => $headers, 'body' => json_encode($query_args)));

            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                // Request was successful
                $response_body = wp_remote_retrieve_body($response);

                // wp_send_json_success('Form submitted successfully');
                wp_send_json_success($response_body);

            } else {
                // Request failed
                $error_message = is_wp_error($response) ? $response->get_error_message() : 'Unknown error occurred';

                // wp_send_json_error('Form submission failed');
                wp_send_json_error($response);
            }
        } else {
            wp_send_json_error('Nonce verification failed.');
        }
    }

    add_action('wp_ajax_inwc_form_submission_signal', 'inwc_form_submission_signal');

    /**
     * Settings show column
     */
    if (isset($option['inwc_settings_colum_orders_page']) && $option['inwc_settings_colum_orders_page'] == '1') {

        /**
         * Add column after "Status" column in shop_order admin page
         *
         * @since 1.5
         */
        function inwc_add_order_column($columns)
        {
            // Add the custom column after the "Status" column
            $new_columns = array();

            foreach ($columns as $key => $column) {
                $new_columns[$key] = $column;
                if ($key === 'order_status') {
                    $new_columns['correct_customer_status'] = __('Correct Customer status', 'integrate-nekorekten-wc');
                }
            }

            return $new_columns;
        }

        add_filter('manage_edit-shop_order_columns', 'inwc_add_order_column');
        add_filter('manage_woocommerce_page_wc-orders_columns', 'inwc_add_order_column'); // Support HPOS functions


        /**
         * View column Correct Customer Status
         *
         * @since 1.5
         */
        function inwc_view_order_column_correct_customer_status($column, $post_id)
        {

            if ($column === 'correct_customer_status') {

                $allowed_tags = array(
                    'i' => array(
                        'class' => array(),
                        'style' => array(),
                    ),
                );

                $order = wc_get_order($post_id);

                $correct_customer_status_data = $order->get_meta('inwc_correct_customer_status_data', true);

                if ($correct_customer_status_data == 'incorrect') {
                    echo wp_kses('<i class="fas fa-thumbs-down" style="color: #d63638; font-size: 20px;"></i>', $allowed_tags);
                } else if ($correct_customer_status_data == 'correct') {
                    echo wp_kses('<i class="fas fa-thumbs-up" style="color: green; font-size: 20px;"></i>', $allowed_tags);
                }

            }
        }

        add_action('manage_shop_order_posts_custom_column', 'inwc_view_order_column_correct_customer_status', 25, 2);
        add_action('manage_woocommerce_page_wc-orders_custom_column', 'inwc_view_order_column_correct_customer_status', 25, 2); // Support HPOS functions


    } /** END Settings show column */

    /**
     * Settings show signal in admin email for new order
     */
    if (isset($option['inwc_settings_show_in_admin_email']) && $option['inwc_settings_show_in_admin_email'] == '1') {

        /**
         * Add content after the order table in the new order email for admin
         *
         * @since 1.5
         */
        function inwc_new_order_email_content_for_admin($order, $sent_to_admin, $plain_text, $email)
        {
            // Check if the email is sent to the admin and if it's the 'new_order' email
            if ($sent_to_admin && $email->id === 'new_order') {

                // Get the settings option
                $option = get_option('inwc_settings_group');
                $api_key = $option['inwc_settings_API_key'];

                // Get the order ID and order object
                $order_id = $order->get_id();

                // Get billing phone and email using WooCommerce order methods
                $phone = $order->get_billing_phone();
                $email_address = $order->get_billing_email();

                // Define the API URL and query parameters
                $api_url = 'https://api.nekorekten.com/api/v1/reports';

                $query_args_phone = array(
                    'phone' => $phone
                );

                $query_args_email = array(
                    'email' => $email_address
                );

                // Define the headers for the API requests
                $headers = array(
                    'Content-Type' => 'application/json',
                    'Api-Key' => $api_key
                );

                // Make the API requests
                $responsePhone = wp_remote_get(add_query_arg($query_args_phone, $api_url), array('headers' => $headers));
                $responseEmail = wp_remote_get(add_query_arg($query_args_email, $api_url), array('headers' => $headers));

                // Check if the responses are successful
                if ((!is_wp_error($responsePhone) || !is_wp_error($responseEmail)) && (wp_remote_retrieve_response_code($responsePhone) === 200 || wp_remote_retrieve_response_code($responseEmail) === 200)) {
                    $data_phone = json_decode(wp_remote_retrieve_body($responsePhone));
                    $data_email = json_decode(wp_remote_retrieve_body($responseEmail));

                    echo '<h3 style="text-align: center;">' . esc_html__('Information from nekorekten.com', 'integrate-nekorekten-wc') . '</h3>';

                    // Display results by email
                    if ($data_email && isset($data_email->items) && !empty($data_email->items)) {
                        echo '<p style="text-align: center;">' . esc_html__('Results by email:', 'integrate-nekorekten-wc') . ' <b style="color: #d63638">' . esc_attr($email_address) . '</b></p>';
                        foreach ($data_email->items as $signal) {
                            echo '<p style="margin: 16px 0 0;">' . esc_html($signal->firstName) . ' ' . esc_html($signal->lastName) . '</p>';
                            echo '<p style="margin: 0;">' . esc_html($signal->email) . '</p>';
                            echo '<p style="margin: 0;">' . esc_html($signal->phone) . '</p>';
                            echo '<p style="margin: 16px 0 16px;">' . esc_html($signal->text) . '</p>';
                            echo '<hr>';
                        }
                    }

                    // Display results by phone
                    if ($data_phone && isset($data_phone->items) && !empty($data_phone->items)) {
                        echo '<p style="text-align: center;">' . esc_html__('Results by phone:', 'integrate-nekorekten-wc') . ' <b style="color: #d63638">' . esc_attr($phone) . '</b></p>';
                        foreach ($data_phone->items as $signal) {
                            echo '<p style="margin: 16px 0 0;">' . esc_html($signal->firstName) . ' ' . esc_html($signal->lastName) . '</p>';
                            echo '<p style="margin: 0;">' . esc_html($signal->email) . '</p>';
                            echo '<p style="margin: 0;">' . esc_html($signal->phone) . '</p>';
                            echo '<p style="margin: 16px 0 16px;">' . esc_html($signal->text) . '</p>';
                            echo '<hr>';
                        }
                    }

                    // Provide a link to view more details in the admin order page
                    echo '<p style="text-align: center;"><a href="' . esc_url(admin_url('post.php?post=' . $order_id . '&action=edit')) . '" style="display: inline-block; background-color: #d63638; color: #fff; padding: 8px 16px; text-decoration: none; border-radius: 4px;" target="_blank">' . esc_html__('See more', 'integrate-nekorekten-wc') . '</a></p>';
                }
            }
        }

        add_action('woocommerce_email_after_order_table', 'inwc_new_order_email_content_for_admin', 10, 4);

    } /** END Settings show signal in admin email for new order */


} /** END Settings turn on / turn off global */




