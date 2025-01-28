<?php
/*
Plugin Name: AtmoApps User Moderation
Plugin URI: https://example.com
Description: Customizes user registration modes and adds user moderation functionality
Version: 0.9
Author: Max Jones
Author URI: https://atmoapps.com
*/

if (!defined('ABSPATH')) exit; // Prevent direct access

if (!class_exists('AtmoApps_User_Moderation')) {
    final class AtmoApps_User_Moderation {

        private static $instance = null;
        const WHITELIST = 1;
        const FREEFORALL = 2;

        const UNVERIFIED = 50;
        const UNAPPROVED = 40;
        const INACTIVE = 30;
        const DENIED = 20;
        const ACTIVE = 0;

        // Constructor to initialize the plugin
        private function __construct() {
            $this->init_hooks();
        }

        // Singleton instance
        public static function get_instance() {
            if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        // Initialize hooks
        private function init_hooks() {

            // Register the settings, section, and field
            add_action('admin_init', array($this, 'atmo_apps_register_settings'));

            // Hook into the admin menu
            add_action('admin_menu', array($this, 'atmo_apps_registration_mode_menu'));

            //Hook into registration_errors 
            add_filter('registration_errors', array($this, 'validate_email_domain_on_registration'), 10, 3);

            //Set user's initial status
            add_action('user_register', array($this, 'set_initial_user_status'));

            //Update user status after initial password reset
            add_action('after_password_reset', array($this, 'update_user_status_after_password_reset'), 10, 2);

            //Add Users menu to view those requiring approval
            add_action('admin_menu', array($this, 'add_custom_users_submenu'));

            //Block login from unapproved users
            add_filter('wp_authenticate_user', array($this, 'check_user_status_on_login'));

            //Add the bulk deactivation action to user management page
            add_filter('bulk_actions-users', array($this, 'add_deactivation_bulk_action'));
            add_filter('handle_bulk_actions-users', array($this, 'handle_deactivation_bulk_action'), 10, 3);
            add_action('admin_notices', array($this, 'deactivation_bulk_action_admin_notice'));

            //Add the bulk activation action to user management page
            add_filter('bulk_actions-users', array($this, 'add_activation_bulk_action'));
            add_filter('handle_bulk_actions-users', array($this, 'handle_activation_bulk_action'), 10, 3);
            add_action('admin_notices', array($this, 'activation_bulk_action_admin_notice'));

            //Add the deactivation quick edit link to user entries
            add_filter('user_row_actions', array($this, 'add_deactivate_quick_edit_link'), 10, 2);
            add_action('admin_init', array($this, 'handle_user_deactivation'));
            add_action('admin_notices', array($this, 'display_user_deactivation_notice'));

            //Add the deactivation quick edit link to user entries
            add_filter('user_row_actions', array($this, 'add_activate_quick_edit_link'), 10, 2);
            add_action('admin_init', array($this, 'handle_user_activation'));
            add_action('admin_notices', array($this, 'display_user_activation_notice'));

            //Add user status column to Users Table
            add_filter('manage_users_columns', array($this, 'add_aaum_user_status_column'));
            add_action('manage_users_custom_column', array($this, 'show_aaum_user_status_column'), 10, 3);

            //Functions managing the alteration of the registration page, and generation of username from first and last name
            add_action('login_head', array($this, 'aaum_hide_username_field_css'));
            add_action( 'register_form', array($this, 'aaum_custom_registration_fields'));
            add_filter('registration_errors', array($this, 'aaum_override_field_errors'), 10, 3);
            add_action('user_register', array($this, 'aaum_save_custom_registration_fields'));
            add_filter('pre_user_login', array($this, 'aaum_generate_username'));

            //Function to make user status searchable
            add_action('pre_user_query', array($this,'add_user_meta_to_search_meta_query'));
        }

        // Prevent cloning
        private function __clone() {}

        // Prevent unserializing
        public function __wakeup() {}

        // Function to add the submenu item
        function atmo_apps_registration_mode_menu() {
            add_options_page(
                'AA User Moderation',      // Page title
                'AA User Moderation',      // Menu title
                'manage_options',         // Capability required to see this option
                'atmo-apps-user-moderation', // Menu slug
                array($this, 'atmo_apps_user_moderation_page') // Callback function to render the page
            );
        }

        // Function to render the settings page
        function atmo_apps_user_moderation_page() {
            ?>
            <div class="wrap">
                <h1>Registration Mode Settings</h1>
                <form method="post" action="options.php">
                    <?php
                    // Output security fields for the registered setting
                    settings_fields('aaum_registration_options');
                    // Output setting sections and fields
                    do_settings_sections('aaum-registration');
                    // Submit button
                    submit_button();
                    ?>
                </form>
            </div>
            <?php
        }

        function atmo_apps_register_settings() {
            // Register the setting
            register_setting('aaum_registration_options', 'aaum_reg_mode');
            register_setting('aaum_registration_options', 'aaum_whitelisted_domains');
            register_setting('aaum_registration_options', 'aaum_require_approval');


            // Add a settings section
            add_settings_section(
                'aaum_registration_section', //id
                'Select Registration Mode', //title
                null, //callback
                'aaum-registration' //page
            );

            // Add the dropdown field
            add_settings_field(
                'aaum_reg_mode', //id
                'Registration Mode', //title
                array($this, 'aaum_registration_mode_dropdown'), //callback
                'aaum-registration', //page
                'aaum_registration_section' //section
            );

            // Text input for Whitelisted Domains (only added if "WHITELIST" is selected)
            add_settings_field(
                'aaum_whitelisted_domains',
                'Whitelisted Domains',
                array($this, 'aaum_whitelisted_domains_field'),
                'aaum-registration',
                'aaum_registration_section'
            );

            // Checkbox for Require Approval
            add_settings_field(
                'aaum_require_approval',
                'Require Approval',
                array($this, 'aaum_require_approval_checkbox'),
                'aaum-registration',
                'aaum_registration_section'
            );
        }

        // Callback function to render the dropdown
        function aaum_registration_mode_dropdown() {
            // Get the current value from the database
            $selected_mode = get_option('aaum_reg_mode', 'WHITELIST');
            $options = ['WHITELIST' => self::WHITELIST, 'FREEFORALL' => self::FREEFORALL];
            ?>
            <select name="aaum_reg_mode">
                <?php foreach ($options as $option => $value) : ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($selected_mode, $value); ?>>
                        <?php echo esc_html($option); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php
        }

        // Callback function to render the "Whitelisted Domains" text field
        function aaum_whitelisted_domains_field() {
            $whitelisted_domains = get_option('aaum_whitelisted_domains', '');
            ?>
            <input type="text" name="aaum_whitelisted_domains" value="<?php echo esc_attr($whitelisted_domains); ?>" />
            <p class="description">Enter domains separated by commas (e.g., example.com, sample.org).</p>
            <?php
        }

        // Callback function to render the "Require Approval" checkbox
        function aaum_require_approval_checkbox() {
            $require_approval = get_option('aaum_require_approval', false);
            ?>
            <input type="checkbox" name="aaum_require_approval" value="1" <?php checked($require_approval, 1); ?> />
            <p class="description">Enable this option to require approval for registrations.</p>
            <?php
        }


        /*
         * If the reg mode is whitelist only then the domain name is checked, otherwise registration proceeds
         */
        function validate_email_domain_on_registration($errors, $sanitized_user_login, $user_email) {
            $reg_mode = get_option('aaum_reg_mode', 'WHITELIST');

            if ( $reg_mode == self::WHITELIST ) {
                // Get the whitelisted domains from the options table
                $whitelisted_domains = get_option('aaum_whitelisted_domains', '');

                // Check if the option is empty
                if (empty($whitelisted_domains)) {
                    $errors->add('whitelist_domain_error', __('Registration is restricted. No email domains are whitelisted.', 'textdomain'));
                    return $errors;
                }

                // Convert the comma-separated list into an array and trim spaces
                $domain_list = array_map('trim', explode(',', $whitelisted_domains));

                // Extract the domain from the user's email
                $email_domain = substr(strrchr($user_email, "@"), 1);

                // Check if the email domain is in the list of whitelisted domains
                if (!in_array($email_domain, $domain_list)) {
                    $errors->add('invalid_email_domain', __('Your email domain is not allowed for registration.', 'textdomain'));
                }
            }

            return $errors;
        }

        /*
         * As soon as the user registers they receive a user status of UNVERIFIED
         */
        function set_initial_user_status($user_id) {
            // Update the user_status to 2 for the newly registered user
            update_user_meta( $user_id, 'aaum_user_status', self::UNVERIFIED);
        }

        /*
         * When the user does their initial password reset it updates their status
         */
        function update_user_status_after_password_reset($user, $new_password) {
            $status = get_user_meta( $user->ID, 'aaum_user_status', true);

            // Check if the user's status is UNVERIFIED
            if ($status == self::UNVERIFIED) {
                // Get the value of the wp_option "aaum_require_approval"
                $require_approval = get_option('aaum_require_approval', false);

                // Set the new status based on the option value
                $new_status = $require_approval ? self::UNAPPROVED : 0;

                // Update the user's status
                update_user_meta( $user->ID, 'aaum_user_status', $new_status);

                // Clear the user cache to ensure the updated status is reflected
                clean_user_cache($user->ID);
            }
        }





        function add_custom_users_submenu() {
            add_users_page(
                'Approve Users', // Page title
                'Approve Users',        // Menu title
                'list_users',            // Capability required to access this page
                'approve-users',        // Menu slug
                array($this, 'display_unapproved_users') // Callback function
            );
        }

        // Callback function to display the custom user list
        function display_unapproved_users() {
            // Check that the current user has permission to list users
            if (!current_user_can('list_users')) {
                wp_die(__('You do not have sufficient permissions to access this page.'));
            }

            // Handle form submission to update user meta
            if (!empty($_POST['user_ids'])) {
                $user_ids = array_map('intval', $_POST['user_ids']);
                
                if (isset($_POST['approve_users'])) {
                    foreach ($user_ids as $user_id) {
                        update_user_meta($user_id, 'aaum_user_status', self::ACTIVE);
                    }
                    echo '<div class="updated notice"><p>Selected users have been approved.</p></div>';
                } elseif (isset($_POST['deny_users'])) {
                    foreach ($user_ids as $user_id) {
                        update_user_meta($user_id, 'aaum_user_status', self::DENIED);
                    }
                    echo '<div class="updated notice"><p>Selected users have been denied.</p></div>';
                }
            }

            // Query users with user status of Unapproved
            $args = array(
                'meta_query' => array(
                    array(
                        'key' => 'aaum_user_status',
                        'value' => self::UNAPPROVED,
                        'compare' => '='
                    )
                )
            );
            $users = get_users($args);

            // Display users in a form with a table format
            echo '<div class="wrap">';
            echo '<h1 class="wp-heading-inline">Users Requiring Approval</h1>';
            echo '<form method="post" action="">';
            echo '<table class="wp-list-table widefat fixed striped users">';
            echo '<thead>
                    <tr>
                        <th scope="col" class="manage-column column-cb check-column"><input type="checkbox" id="select-all"></th>
                        <th scope="col" class="manage-column column-username">Username</th>
                        <th scope="col" class="manage-column column-name">Name</th>
                        <th scope="col" class="manage-column column-email">Email</th>
                    </tr>
                  </thead>';
            echo '<tbody>';

            if (!empty($users)) {
                foreach ($users as $user) {
                    echo '<tr>';
                    echo '<th scope="row" class="check-column"><input type="checkbox" name="user_ids[]" value="' . esc_attr($user->ID) . '"></th>';
                    echo '<td>' . esc_html($user->user_login) . '</td>';
                    echo '<td>' . esc_html($user->display_name) . '</td>';
                    echo '<td>' . esc_html($user->user_email) . '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="4">No users require approval.</td></tr>';
            }

            echo '</tbody>';
            echo '</table>';
            echo '<p>
                <input type="submit" name="approve_users" class="button button-primary" value="Approve">
                <input type="submit" name="deny_users" class="button button-secondary" value="Deny">
              </p>';
            echo '</form>';
            echo '</div>';

            // Add JavaScript for "Select All" functionality
            echo '<script type="text/javascript">
                document.getElementById("select-all").addEventListener("click", function() {
                    var checkboxes = document.querySelectorAll("input[name=\'user_ids[]\']");
                    for (var checkbox of checkboxes) {
                        checkbox.checked = this.checked;
                    }
                });
            </script>';
        }

        function check_user_status_on_login($user) {
            if (is_wp_error($user)) {
                return $user;
            }

            // Get the user ID
            $user_id = $user->ID;

            // Get the 'aaum_user_status' user meta
            $user_status = get_user_meta($user_id, 'aaum_user_status', true);

            // Check if 'aaum_user_status' is not 0
            if (!empty($user_status) && $user_status != self::ACTIVE) {
                // Prevent login by returning a WP_Error object
                return new WP_Error('account_blocked', __('Your account is not active.'));
            }

            return $user;
        }

        /*
         * Functions managing bulk deactivation
         */
        // Add custom bulk action to the dropdown
        function add_deactivation_bulk_action($bulk_actions) {
            $bulk_actions['deactivate'] = __('Deactivate', 'textdomain');
            return $bulk_actions;
        }

        // Handle the custom bulk action
        function handle_deactivation_bulk_action($redirect_to, $doaction, $user_ids) {
            if ($doaction !== 'deactivate') {
                return $redirect_to;
            }

            foreach ($user_ids as $user_id) {
                update_user_meta($user_id, 'aaum_user_status', self::INACTIVE);
            }

            $redirect_to = add_query_arg('bulk_deactivate_users', count($user_ids), $redirect_to);
            return $redirect_to;
        }

        // Display a notice after users have been deactivated
        function deactivation_bulk_action_admin_notice() {
            if (!empty($_REQUEST['bulk_deactivate_users'])) {
                $count = intval($_REQUEST['bulk_deactivate_users']);
                printf('<div id="message" class="updated notice is-dismissible"><p>' .
                    _n('%s user has been deactivated.', '%s users have been deactivated.', $count, 'textdomain') . '</p></div>', $count);
            }
        }

        // Add activation bulk action to the dropdown
        function add_activation_bulk_action($bulk_actions) {
            $bulk_actions['activate'] = __('Activate', 'textdomain');
            return $bulk_actions;
        }

        // Handle the activation bulk action
        function handle_activation_bulk_action($redirect_to, $doaction, $user_ids) {
            if ($doaction !== 'activate') {
                return $redirect_to;
            }

            foreach ($user_ids as $user_id) {
                update_user_meta($user_id, 'aaum_user_status', self::ACTIVE);
            }

            $redirect_to = add_query_arg('bulk_activate_users', count($user_ids), $redirect_to);
            return $redirect_to;
        }

        // Display a notice after users have been activated
        function activation_bulk_action_admin_notice() {
            if (!empty($_REQUEST['bulk_activate_users'])) {
                $count = intval($_REQUEST['bulk_activate_users']);
                printf('<div id="message" class="updated notice is-dismissible"><p>' .
                    _n('%s user has been activated.', '%s users have been activated.', $count, 'textdomain') . '</p></div>', $count);
            }
        }

        /*
         * Functions managing individual deactivation/activation
         */
        // Add 'Deactivate' link to user row actions
        function add_deactivate_quick_edit_link($actions, $user_object) {
            // Check if the user is not already deactivated
            $status = get_user_meta($user_object->ID, 'aaum_user_status', true);
            if ($status != self::INACTIVE && $status != self::DENIED) {
                $actions['deactivate_user'] = '<a href="' . esc_url(add_query_arg([
                    'action' => 'deactivate_user',
                    'user_id' => $user_object->ID,
                    '_wpnonce' => wp_create_nonce('deactivate_user_' . $user_object->ID),
                ], admin_url('users.php'))) . '">' . __('Deactivate', 'textdomain') . '</a>';
            }
            return $actions;
        }

        // Handle the deactivation when the link is clicked
        function handle_user_deactivation() {
            if (isset($_GET['action'], $_GET['user_id'], $_GET['_wpnonce']) && $_GET['action'] === 'deactivate_user') {
                $user_id = intval($_GET['user_id']);
                
                // Verify nonce for security
                if (!wp_verify_nonce($_GET['_wpnonce'], 'deactivate_user_' . $user_id)) {
                    wp_die(__('Security check failed.', 'textdomain'));
                }

                // Update the user meta to set 'aaum_user_status' to 30
                update_user_meta($user_id, 'aaum_user_status', self::INACTIVE);

                // Redirect to the users page with a query arg to display the admin notice
                wp_redirect(add_query_arg('user_deactivated', 1, admin_url('users.php')));
                exit;
            }
        }

        // Display a notice after a user has been deactivated
        function display_user_deactivation_notice() {
            if (isset($_GET['user_deactivated'])) {
                echo '<div id="message" class="updated notice is-dismissible"><p>' . __('The user has been deactivated.', 'textdomain') . '</p></div>';
            }
        }

        // Add 'Activate' link to user row actions
        function add_activate_quick_edit_link($actions, $user_object) {
            // Check if the user is not already deactivated
            $status = get_user_meta($user_object->ID, 'aaum_user_status', true);
            if (!empty($status) && $status != self::ACTIVE && $status != self::UNVERIFIED) {
                $actions['activate_user'] = '<a href="' . esc_url(add_query_arg([
                    'action' => 'activate_user',
                    'user_id' => $user_object->ID,
                    '_wpnonce' => wp_create_nonce('activate_user_' . $user_object->ID),
                ], admin_url('users.php'))) . '">' . __('Activate', 'textdomain') . '</a>';
            }
            return $actions;
        }

        // Handle the activation when the link is clicked
        function handle_user_activation() {
            if (isset($_GET['action'], $_GET['user_id'], $_GET['_wpnonce']) && $_GET['action'] === 'activate_user') {
                $user_id = intval($_GET['user_id']);
                
                // Verify nonce for security
                if (!wp_verify_nonce($_GET['_wpnonce'], 'activate_user_' . $user_id)) {
                    wp_die(__('Security check failed.', 'textdomain'));
                }

                // Update the user meta to set 'aaum_user_status' to ACTIVE
                update_user_meta($user_id, 'aaum_user_status', self::ACTIVE);

                // Redirect to the users page with a query arg to display the admin notice
                wp_redirect(add_query_arg('user_activated', 1, admin_url('users.php')));
                exit;
            }
        }

        // Display a notice after a user has been deactivated
        function display_user_activation_notice() {
            if (isset($_GET['user_activated'])) {
                echo '<div id="message" class="updated notice is-dismissible"><p>' . __('The user has been activated.', 'textdomain') . '</p></div>';
            }
        }

        /*
         * Display user status in the Users table
         */
        // Add a new column to the Users table
        function add_aaum_user_status_column($columns) {
            $columns['aaum_user_status'] = 'User Status';
            return $columns;
        }

        // Populate the new column with the 'aaum_user_status' meta value
        function show_aaum_user_status_column($value, $column_name, $user_id) {
            if ('aaum_user_status' == $column_name) {
                $user_status = get_user_meta($user_id, 'aaum_user_status', true);
                switch ($user_status) {
                    case self::UNVERIFIED :
                        $value = 'Unverified';
                        break;
                    case self::UNAPPROVED;
                        $value = 'Unapproved';
                        break;
                    case self::INACTIVE;
                        $value = 'Inactive';
                        break;
                    case self::DENIED;
                        $value = 'Denied';
                        break;
                    default :
                        $value = 'Active';

                }
            }
            return $value;
        }

        /*
         * Functions managing the alteration of the registration page, and generation of username from first and last name
         */
        // Hide the username field using CSS
        function aaum_hide_username_field_css() {
            ?>
            <style>
                #registerform > p:first-child{
                    display:none;
                }
            </style>
            <?php
        }

        // Add custom fields to the registration form
        function aaum_custom_registration_fields() {
            ?>
            <p>
                <label for="first_name"><?php esc_html_e('First Name', 'textdomain') ?><br />
                <input type="text" name="first_name" id="first_name" class="input" value="<?php echo esc_attr( wp_unslash( $_POST['first_name'] ?? '' ) ); ?>" size="25" /></label>
            </p>
            <p>
                <label for="last_name"><?php esc_html_e('Last Name', 'textdomain') ?><br />
                <input type="text" name="last_name" id="last_name" class="input" value="<?php echo esc_attr( wp_unslash( $_POST['last_name'] ?? '' ) ); ?>" size="25" /></label>
            </p>
            <?php
        }

        // Remove the username field from the registration form
        function aaum_override_field_errors( $wp_error, $sanitized_user_login, $user_email ) {
            if(isset($wp_error->errors['empty_username'])){
                unset($wp_error->errors['empty_username']);
            }

            if(isset($wp_error->errors['username_exists'])){
                unset($wp_error->errors['username_exists']);
            }

            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);

            if ( empty( $first_name ) ) {
                $wp_error->add('first_name_error', __('<strong>Error</strong>: First name is required.', 'textdomain'));
            }
            if ( empty( $last_name ) ) {
                $wp_error->add('last_name_error', __('<strong>Error</strong>: Last name is required.', 'textdomain'));
            }
            return $wp_error;
        }

        // Save custom registration fields
        function aaum_save_custom_registration_fields($user_id) {
            if (isset($_POST['first_name'])) {
                update_user_meta($user_id, 'first_name', sanitize_text_field($_POST['first_name']));
            }
            if (isset($_POST['last_name'])) {
                update_user_meta($user_id, 'last_name', sanitize_text_field($_POST['last_name']));
            }
        }

        // Ensure unique username by appending a number if necessary
        function aaum_generate_username($sanitized_user_login) {
            if (!empty($_POST['first_name']) && !empty($_POST['last_name'])) {
                $username_base = sanitize_user(strtolower($_POST['first_name'] . '-' . $_POST['last_name']));
                $username = $username_base;
                $i = 1;

                while (username_exists($username)) {
                    $username = $username_base . '-' . $i;
                    $i++;
                }

                $sanitized_user_login = $username;
            }

            return $sanitized_user_login;
        }

        /*
         * Function to make user status searchable
         */
        function add_user_meta_to_search_meta_query($query) {
            // Only modify queries in the admin and for the user list
            if (is_admin() && isset($query->query_vars['search'])) {

                // Get the search term
                $search = $query->query_vars['search'];
                $search = trim($search, '*'); // Remove wildcards added by WordPress

                if (defined('self::'.strtoupper($search))) {
                    global $wpdb;

                    $meta_search = constant('self::'.strtoupper($search));
                    // Add custom meta query for aaum_user_status
                    $query->query_from .= " 
                        LEFT JOIN {$wpdb->usermeta} aaum_meta
                        ON ({$wpdb->users}.ID = aaum_meta.user_id AND aaum_meta.meta_key = 'aaum_user_status')";

                    $query->query_where .= " OR aaum_meta.meta_value='{$meta_search}'";
                }
            }
        }
    }
}

// Initialize the plugin
AtmoApps_User_Moderation::get_instance();
