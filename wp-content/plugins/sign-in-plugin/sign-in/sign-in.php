<?php
/**
 * Plugin Name: Sign In
 * Description: A WordPress plugin for tracking sign ins.
 * Version: 1.0.0
 * Author: Atmosphere Apps
 * Author URI: https://atmoapps.com/
 * Text Domain: signin
 * Domain Path: /languages
 */
define( 'SIGN_IN_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'SIGN_IN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

class SignIn {
    private static $instance;

    public static $scaffold = array();

    private function __construct() {
		add_action( 'pre_get_posts', array( $this, 'filter_sign_in_event_post_type_archive' ) );

		add_action( 'wp_ajax_manage_account', array( $this, 'manage_account' ) );

		add_action( 'wp_ajax_close_sign_in_event', array( $this, 'close_sign_in_event_ajax' ) );

		add_action( 'wp_ajax_share_sign_in_event', array( $this, 'share_sign_in_event_ajax' ) );

		/*
		 * Fixed functions for the base plugin
		 *
		 */
    	register_activation_hook( __FILE__, array( $this, 'sign_in_base_activate_plugin' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_signin_scripts' ) );

		add_action( 'admin_menu', array( $this, 'sign_in_create_menu' ) );

		add_action( 'init', array( $this, 'create_sign_in_event_post_type' ), 0 );

		add_action( 'init', array( $this, 'create_sign_in_attendee_post_type' ), 0 );

		add_action( 'init', array( $this, 'sign_in_custom_post_statuses' ) );

		add_action( 'init', array( $this, 'create_sign_in_user_roles' ) );

		add_action( 'init', array( $this, 'set_default_user_role_to_customer_rep' ) );

		add_action( 'admin_init', array ( $this, 'redirect_non_admin_users' ) );

		add_action( 'template_redirect', array( $this, 'authentication_redirects' ) );

		// Hook the function into WordPress using 'wp' action so it runs on each page load
		add_action( 'parse_request', array( $this, 'update_upcoming_posts_to_open' ) );

		add_action( 'after_setup_theme', array( $this, 'create_scaffold') );

		// Hook into the 'template_include' filter
		add_filter( 'template_include', array( $this, 'sign_in_base_templates') );

		add_action( 'wp_ajax_create_sign_in_event', array( $this, 'create_sign_in_event_ajax' ) );

		add_action( 'wp_ajax_edit_sign_in_event', array( $this, 'edit_sign_in_event_ajax' ) );

		add_action( 'wp_ajax_begin_check_in', array( $this, 'begin_check_in_ajax' ) );

		add_action( 'wp_ajax_pause_check_in', array( $this, 'pause_check_in_ajax' ) );

		add_action( 'wp_ajax_npi_lookup', array( $this, 'npi_lookup_ajax' ) );
		add_action( 'wp_ajax_nopriv_npi_lookup', array( $this, 'npi_lookup_ajax' ) );

		add_action( 'wp_ajax_pre_reg_lookup', array( $this, 'pre_reg_lookup_ajax' ) );
		add_action( 'wp_ajax_nopriv_pre_reg_lookup', array( $this, 'pre_reg_lookup_ajax' ) );

		add_action( 'wp_ajax_nopriv_lookup_alm_user', array( $this, 'lookup_alm_user_ajax' ) );

		add_action( 'wp_ajax_nopriv_pre_register_attendee', array( $this, 'pre_register_attendee_ajax' ) );

		add_action( 'wp_ajax_nopriv_register_attendee', array( $this, 'register_attendee_ajax' ) );
    }

    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init() {
        // Add your plugin initialization code here
        require_once( SIGN_IN_PLUGIN_PATH.'inc/Event.php' );
        require_once( SIGN_IN_PLUGIN_PATH.'inc/Attendee.php' );
        require_once( SIGN_IN_PLUGIN_PATH.'inc/common.php' );
        require_once( SIGN_IN_PLUGIN_PATH.'inc/RegistrationForm.php' );
        require_once( SIGN_IN_PLUGIN_PATH.'inc/NLMNPI.php' );
        require_once( SIGN_IN_PLUGIN_PATH.'inc/LocalPreReg.php' );
        require_once( SIGN_IN_PLUGIN_PATH.'inc/CVENT.php' );
        require_once( SIGN_IN_PLUGIN_PATH.'inc/ALM.php' );
        require_once( SIGN_IN_PLUGIN_PATH.'inc/NotificationsTool.php' );
        require_once( SIGN_IN_PLUGIN_PATH.'inc/PDFGenerator.php' );
        require_once( SIGN_IN_PLUGIN_PATH.'inc/CSVGenerator.php' );
        require_once( SIGN_IN_PLUGIN_PATH.'inc/ConcurGenerator.php' );
    }

	public function close_sign_in_event_ajax() {

	    // Check for nonce security
	    if ( !isset( $_POST['close_sign_in_event_nonce'] ) || !wp_verify_nonce( $_POST['close_sign_in_event_nonce'], 'close_sign_in_event' ) ) {
	        wp_send_json_error( array( 'message' => 'Nonce verification failed.' ), 403 );
	    }

		// Get the post object
		$post = get_post( $_POST['id'] );

		// Get the current user's data
		$current_user = wp_get_current_user();

		// Get the event author's data
		$author = get_userdata( $post->post_author );

		// Check if the current user is allowed to alter the post.
		if ( $post->post_author != $current_user->ID && !current_user_can( 'edit_others_posts' ) ) {
	        wp_send_json_error( array( 'message' => 'You don\'t have permission to do that.' ), 401 );
		}

	    // Get the post object
	    $post = get_post( $_POST['id'] );
	    if ( !$post ) {
	    	wp_send_json_error( array( 'message' => 'Post not found.' ), 500 );
	    }

	    // Get post meta for the post
	    $post_meta = get_post_meta( $post->ID );

	    $event = new Event();
	    $attendees = array();

	    $event->id = $post->ID;
	    $event->metadata['guid'] = $post->post_title;
	    $event->metadata['rep_name'] = aasgnn_first_last( $author->ID);
	    $event->metadata['rep_email'] = $author->user_email;

	    foreach( $post_meta as $key => $value ) {
	    	$event->metadata[$key] = $value[0];
	    }

	    // Get children posts of type 'attendee' that are children of this post
	    $args = array(
	        'post_type' => 'sign-in-attendee',
	        'post_parent' => $post->ID,
	        'numberposts' => -1
	    );

	    $child_posts = get_posts( $args );

	    // Iterate over each child post
	    foreach ( $child_posts as $child_post ) {
	        // Get post meta for the child post
	        $attendee_post_meta = get_post_meta( $child_post->ID );
	    	$attendee = new Attendee( $attendee_post_meta );

	        $attendees[] = $attendee;
	    }

	    // Close the sign-in event post
	    $post_id = wp_update_post( array(
	    	'ID'		   => sanitize_text_field( $post->ID ),
	        'post_status' => 'closed',
	    ), true );

	    $CSVGen = new CSVGenerator();
		$CSVGen->CreateAttendanceCSV( $event, $attendees );

	    $nt = new NotificationsTool();

	    $attendance_recipients = $author->user_email;

	    if ( $event->metadata['event_type'] === 'planned-event' ) {
	    	$additional_emails = esc_attr( get_option( 'planned_attendance_recipients' ) );
	    	if ( !empty( $additional_emails ) ) {
	    		$attendance_recipients .= ',' . $additional_emails;
	    	}
	    } else if ( $event->metadata['event_type'] === 'ad-hoc-event' ) {
	    	$additional_emails = esc_attr( get_option( 'ad_hoc_attendance_recipients' ) );
	    	if ( !empty( $additional_emails ) ) {
	    		$attendance_recipients .= ',' . $additional_emails;
	    	}
	    }

	    $nt->SendNotification( NotificationType::EMAIL_ATTENDANCE, $attendance_recipients, 'Attendance Report', $event, $attendees );

		if ( get_option( 'concur_active', false ) ) {
		    if ( !empty( $_POST['concur_note'] ) ) {
		    	$event->metadata['concur_note'] = $_POST['concur_note'];
		    	update_post_meta( $post_id, 'concur_note', sanitize_text_field( $_POST['concur_note'] ) );
		    }

		    $ConcurGen = new ConcurGenerator();
			$ConcurGen->CreateConcurXLSX( $event, $attendees );
	    	$concur_recipients = $author->user_email;
	    	$nt->SendNotification( NotificationType::EMAIL_CONCUR, $concur_recipients, 'Concur Report', $event, $attendees );
		}

	    add_post_meta( $post_id, "close_check_in", gmdate( "Y-m-d H:i:s" ) );

	    // Send success response
	    wp_send_json_success( array( 'message' => 'Sign-in event closed successfully.' ) );
	}

	public function share_sign_in_event_ajax() {
	    // Check for nonce security
	    if ( !isset( $_POST['share_sign_in_event_nonce'] ) || !wp_verify_nonce( $_POST['share_sign_in_event_nonce'], 'share_sign_in_event' ) ) {
	        wp_send_json_error( array( 'message' => 'Nonce verification failed.' ), 403 );
	    }

		$event = new Event();
		$event->id = $_POST['id'];
		$event->guid = $_POST['event_name'];

	    // Get post meta for the post
	    $post_meta = get_post_meta( $_POST['id'] );

	    foreach( $post_meta as $key => $value ) {
	    	$event->$key = $value[0];
	    }

	    $nt = new NotificationsTool();

	    $nt->SendNotification( NotificationType::EMAIL_SHARE_HTML, $_POST['recipients'], $event->event_name, $event, array() );

	    // Send success response
	    wp_send_json_success( array( 'message' => 'Event shared successfully.' ) );

	}

	public function filter_sign_in_event_post_type_archive( $query ) {
	    if ( !is_admin() && $query->is_main_query() ) {
	        if ( is_post_type_archive( 'sign-in-event' ) ) {

	        	$query->set( 'posts_per_page', -1 );

	        	$current_user = wp_get_current_user();

		        // Check if the user is 'customer_rep'
		        if ( in_array( 'customer_rep', ( array ) $current_user->roles ) ) {
		            // Set the query to only show posts authored by the current user
		            $query->set( 'author', $current_user->ID );
		        }

	        	if ( !empty( $_GET['status'] ) ) {
	        		if ( $_GET['status'] === 'closed' ) {
			            $query->set( 'post_status', 'closed' );
			        } 
	        	}
	        	else {
            		$query->set( 'post_status', array( 'upcoming', 'open' ) );
		        }
	        }

	        if ( $query->is_search ) {
    	        // Retrieve the search term from the query.
		        $search_term = get_search_query();
	            $query->set( 'post_status', array( 'upcoming', 'open', 'closed' ) );

		        // If there's a search term, modify the query to search in the 'event_name' post meta.
		        if ( !empty( $search_term ) ) {
		        	$query->set('s', '');
		            $query->set( 'meta_query', array(
                    	'relation' => 'OR',
		                array(
		                    'key'     => 'event_name',
		                    'value'   => $search_term,
		                    'compare' => 'LIKE',
		                ),
		            ) );

		            // Set posts per page.
		            $query->set( 'posts_per_page', 10 );

		            // Use 'paged' parameter for pagination.
		            $paged = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
		            $query->set( 'paged', $paged );

		            // Ensure no posts are returned if the 'event_name' meta query doesn't match.
	        		$query->set( 'post_type', 'sign-in-event' );
		            $query->set( 'meta_key', 'event_name' );
		            $query->set( 'orderby', 'meta_value' );

		            // Check if 'sort' parameter is set and valid; otherwise, default to ascending order.
		            if ( isset( $_GET['sort'] ) && in_array( $_GET['sort'], array( 'ASC', 'DESC' ) ) ) {
		                $query->set( 'order', sanitize_text_field( $_GET['sort'] ) );
		            } else {
		                $query->set( 'order', 'ASC' );
		            }
		        }
		    }	
	    }
	}

	public function manage_account() {
		$errors = array();
		$reset_password = false;
		$user = wp_get_current_user();

	    // Check for nonce security
	    if ( !isset( $_POST['manage_account_nonce'] ) || !wp_verify_nonce( $_POST['manage_account_nonce'], 'manage_account' ) ) {
	    	$errors[] = 'Nonce verification failed.';
	    }

        if ( empty( $_POST['current_password'] ) ) {
        	$errors[] = 'Password required.';
        }

        $current_password = $_POST['current_password'];

        if ( !empty( $_POST['current_password'] ) && !wp_check_password( $current_password, $user->data->user_pass, $user->ID ) ) {
        	$errors[] = 'Incorrect password.';
        }

        if ( !isset( $_POST['first_name'] ) ) {
        	$errors[] = 'First name is required.';
        }

        if ( !isset( $_POST['last_name'] ) ) {
        	$errors[] = 'Last name is required.';
        }

	    if ( !empty( $_POST['new_password'] ) ) {
	    	$reset_password = true;
	        $new_password = $_POST['new_password'];
	        $confirm_password = $_POST['confirm_password'];

	        // Check if the new passwords match.
	        if ( $new_password !== $confirm_password) {
        		$errors[] = 'New passwords don\'t match.';
	        }
	    }

	    if ( count( $errors ) > 0 ) {
    		wp_send_json_error( $errors, 400 );
	    }

        update_user_meta( $user->ID, 'first_name', sanitize_text_field( $_POST['first_name'] ) );
        update_user_meta( $user->ID, 'last_name', sanitize_text_field( $_POST['last_name'] ) );

        if ( $reset_password ) {
        	wp_set_password( $new_password, $user->ID );
        }

	    wp_send_json_success( array( 'message' => 'Account details updated.' ) );
	}

	/*
	 * Fixed functions for the base plugin
	 *
	 */

    public function sign_in_base_activate_plugin() {
	    // Array of pages to create
	    $pages = array(
	        'Create Sign In Event',
	        'Edit Sign In Event',
	        'Sign In Event Complete Registration',
	        'Sign In Event Start Registration',
	        'Sign In NPI Lookup'
	    );

	    foreach ($pages as $page_title) {
	        // Check if a page with the given title already exists
	        $existing_page = get_page_by_title($page_title, OBJECT, 'page');
	        if (!$existing_page) {
	            // Create a new page if it doesn't exist
	            $new_page = [
	                'post_title'   => $page_title,
	                'post_content' => '', // You can add default content here if needed
	                'post_status'  => 'publish',
	                'post_type'    => 'page'
	            ];
	            wp_insert_post($new_page);
	        }
	    }
    }

	public function enqueue_signin_scripts() {

	    switch ( get_option('pre_reg_system', 'local') ) {
	    	case 'local' :
				wp_enqueue_script('aasgnn-local-scripts', SIGN_IN_PLUGIN_URL . 'js/local-scripts.js', array('jquery'), false, false);
	    		break;
    		case 'cvent' :
				wp_enqueue_script('aasgnn-cvent-scripts', SIGN_IN_PLUGIN_URL . 'js/cvent-scripts.js', array('jquery'), false, false);
    			break;
    		case 'alm' :
				wp_enqueue_script('aasgnn-alm-scripts', SIGN_IN_PLUGIN_URL . 'js/alm-scripts.js', array('jquery'), false, false);
    			break;
	    }
  		//wp_enqueue_script('bootstrap-scripts', '//cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js', array('jquery'), '', true);
		wp_enqueue_script('aasgnn-scripts', SIGN_IN_PLUGIN_URL . 'js/main-scripts.js', array('jquery'), false, false);

		wp_enqueue_style('bootstrap-styles', '//cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css');
		wp_enqueue_style('aasgnn-styles', SIGN_IN_PLUGIN_URL . 'css/styles.css', array(), '1.0.0' );

		// Localize the script with new data
		wp_localize_script('aasgnn-scripts', 'aasgnn_vars', array(
		'ajax_url' => admin_url('admin-ajax.php'),
		'lookup_alm_user_nonce' => wp_create_nonce('lookup_alm_user'),
		'cvent_users' => array(),
		'site_url' => get_site_url(),
		'plugin_url' => SIGN_IN_PLUGIN_URL,
		'loading_icon' => aasgnn_image( 'loading', 'gif' ),
		'planned_icon' => aasgnn_image( 'planned_icon', 'svg' ),
		'planned_icon_white' => aasgnn_image( 'planned_icon_white', 'svg' ),
		'ad_hoc_icon' => aasgnn_image( 'ad_hoc_icon', 'svg' ),
		'ad_hoc_icon_white' => aasgnn_image( 'ad_hoc_icon_white', 'svg' )
		));
	}

	public function sign_in_create_menu() {
	    //create new submenu under Settings
	    add_options_page(
	        'Sign In Settings',
	        'Sign In',
	        'manage_options',
	        'sign-in',
	        array( $this, 'sign_in_settings_page' )
	    );

	    //call register settings function
	    add_action( 'admin_init', array( $this, 'register_sign_in_settings' ) );
	}

	public function register_sign_in_settings() {
	    //register our settings
	    register_setting( 'sign-in-settings-group', 'pre_reg_system' );

	    register_setting( 'sign-in-settings-group', 'cvent_client_id' );
	    register_setting( 'sign-in-settings-group', 'cvent_client_password' );
	    register_setting( 'sign-in-settings-group', 'planned_attendance_recipients' );
	    register_setting( 'sign-in-settings-group', 'ad_hoc_attendance_recipients' );

	    register_setting( 'sign-in-settings-group', 'concur_active' );



	    // Add a settings section
	    add_settings_section(
	        'pre_reg_settings_section', // ID
	        'Pre-Reg System Settings', // Title
	        null, // Callback for section description
	        'pre-reg-settings' // Page slug
	    );

	    // Add the checkbox field
	    add_settings_field(
	        'pre_reg_system', // ID
	        'Pre-Reg System', // Label
	        array($this,'render_pre_reg_system_select'), // Callback to render the checkbox
	        'pre-reg-settings', // Page slug
	        'pre_reg_settings_section' // Section ID
	    );



	    // Add a settings section
	    add_settings_section(
	        'cvent_settings_section', // ID
	        'Cvent Settings', // Title
	        null, // Callback for section description
	        'cvent-settings' // Page slug
	    );

	    // Add the checkbox field
	    add_settings_field(
	        'cvent_client_id', // ID
	        'Cvent Client ID', // Label
	        array($this,'render_cvent_client_id_input'), // Callback to render the checkbox
	        'cvent-settings', // Page slug
	        'cvent_settings_section' // Section ID
	    );

	    // Add the checkbox field
	    add_settings_field(
	        'cvent_client_password', // ID
	        'Cvent Client Password', // Label
	        array($this,'render_cvent_client_password_input'), // Callback to render the checkbox
	        'cvent-settings', // Page slug
	        'cvent_settings_section' // Section ID
	    );



	    // Add a settings section
	    add_settings_section(
	        'email_settings_section', // ID
	        'Email Settings', // Title
	        null, // Callback for section description
	        'email-settings' // Page slug
	    );

	    // Add the checkbox field
	    add_settings_field(
	        'planned_attendance_recipients', // ID
	        'Planned Attendance Report Recipients', // Label
	        array($this,'render_planned_attendance_recipients_input'), // Callback to render the checkbox
	        'email-settings', // Page slug
	        'email_settings_section' // Section ID
	    );

	    // Add the checkbox field
	    add_settings_field(
	        'ad_hoc_attendance_recipients', // ID
	        'Planned Attendance Report Recipients', // Label
	        array($this,'render_ad_hoc_attendance_recipients_input'), // Callback to render the checkbox
	        'email-settings', // Page slug
	        'email_settings_section' // Section ID
	    );



	    // Add a settings section
	    add_settings_section(
	        'concur_settings_section', // ID
	        'Concur Activation Settings', // Title
	        null, // Callback for section description
	        'concur-settings' // Page slug
	    );

	    // Add the checkbox field
	    add_settings_field(
	        'concur_active', // ID
	        'Enable Concur', // Label
	        array($this,'render_concur_checkbox'), // Callback to render the checkbox
	        'concur-settings', // Page slug
	        'concur_settings_section' // Section ID
	    );
	}

	// Render the checkbox field
	function render_pre_reg_system_select() {
	    // Get the current value of the option
	    $pre_reg_system = get_option('pre_reg_system', '');
	    ?>
	    <label>
	        <select name="pre_reg_system">
	        	<option value="local" <?php selected("local", $pre_reg_system, true); ?> >Local</option>
	        	<option value="cvent" <?php selected("cvent", $pre_reg_system, true); ?> >Cvent</option>
	        	<option value="alm" <?php selected("alm", $pre_reg_system, true); ?> >ALM</option>
	        </select>
	    </label>
	    <?php
	}

	// Render the checkbox field
	function render_cvent_client_id_input() {
	    // Get the current value of the option
	    $cvent_client_id = get_option('cvent_client_id', '');
	    ?>
	    <label>
	        <input type="text" name="cvent_client_id" value="<?= $cvent_client_id; ?>" />
	    </label>
	    <?php
	}

	// Render the checkbox field
	function render_cvent_client_password_input() {
	    // Get the current value of the option
	    $cvent_client_password = get_option('cvent_client_password', '');
	    ?>
	    <label>
	        <input type="text" name="cvent_client_password" value="<?= $cvent_client_password; ?>" />
	    </label>
	    <?php
	}

	// Render the checkbox field
	function render_planned_attendance_recipients_input() {
	    // Get the current value of the option
	    $planned_attendance_recipients = get_option('planned_attendance_recipients', '');
	    ?>
	    <label>
	        <input type="text" name="planned_attendance_recipients" value="<?= $planned_attendance_recipients; ?>" />
	    </label>
	    <?php
	}

	// Render the checkbox field
	function render_ad_hoc_attendance_recipients_input() {
	    // Get the current value of the option
	    $ad_hoc_attendance_recipients = get_option('ad_hoc_attendance_recipients', '');
	    ?>
	    <label>
	        <input type="text" name="ad_hoc_attendance_recipients" value="<?= $ad_hoc_attendance_recipients; ?>" />
	    </label>
	    <?php
	}

	// Render the checkbox field
	function render_concur_checkbox() {
	    // Get the current value of the option
	    $concur_active = get_option('concur_active', false);
	    ?>
	    <label>
	        <input type="checkbox" name="concur_active" value="1" <?php checked(1, $concur_active, true); ?> />
	        Check to activate Concur
	    </label>
	    <?php
	}

	public function sign_in_settings_page()  {
	?>
	<div class="wrap">
	<h1>Sign In Settings</h1>

	<form method="post" action="options.php">
	    <?php
	    // Output security fields for the registered settings
	    settings_fields( 'sign-in-settings-group' );
        // Output setting sections and their fields
        do_settings_sections('pre-reg-settings');
        // Output setting sections and their fields
        do_settings_sections('cvent-settings');
        // Output setting sections and their fields
        do_settings_sections('email-settings');
        // Output setting sections and their fields
        do_settings_sections('concur-settings');
        ?>

	    <?php submit_button(); ?>

	</form>
	</div>
	<?php 
	}

    public function create_sign_in_event_post_type() {
	    // Set UI labels for Custom Post Type
	    $labels = array(
	        'name'                => _x( 'Sign In Events', 'Post Type General Name' ),
	        'singular_name'       => _x( 'Sign In Event', 'Post Type Singular Name' ),
	        'menu_name'           => __( 'Sign In Events' ),
	        'parent_item_colon'   => __( 'Parent Sign In Event' ),
	        'all_items'           => __( 'All Sign In Events' ),
	        'view_item'           => __( 'View Sign In Event' ),
	        'add_new_item'        => __( 'Add New Sign In Event' ),
	        'add_new'             => __( 'Add New' ),
	        'edit_item'           => __( 'Edit Sign In Event' ),
	        'update_item'         => __( 'Update Sign In Event' ),
	        'search_items'        => __( 'Search Sign In Events' ),
	        'not_found'           => __( 'Not Found' ),
	        'not_found_in_trash'  => __( 'Not found in Trash' ),
	    );
	    
	    // Set other options for Custom Post Type
	    $args = array(
	        'label'               => __( 'sign_in_events' ),
	        'description'         => __( 'Sign In Event news and reviews' ),
	        'labels'              => $labels,
	        // Features this CPT supports in Post Editor
	        'supports'            => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'revisions', 'custom-fields', ),
	        // You can associate this CPT with a taxonomy or custom taxonomy. 
	        'taxonomies'          => array( 'genres' ),
	        /* A hierarchical CPT is like Pages and can have
	        * Parent and child items. A non-hierarchical CPT
	        * is like Posts.
	        */ 
	        'hierarchical'        => false,
	        'public'              => true,
	        'show_ui'             => true,
	        'show_in_menu'        => true,
	        'show_in_nav_menus'   => true,
	        'show_in_admin_bar'   => true,
	        'menu_position'       => 5,
	        'can_export'          => true,
	        'has_archive'         => true,
	        'exclude_from_search' => false,
	        'publicly_queryable'  => true,
	        'capability_type'     => 'post',
	        'show_in_rest'        => true, // This enables the Gutenberg editor for the CPT
	    );
	    
	    // Registering your Custom Post Type
	    register_post_type( 'sign-in-event', $args );
	}

    public function create_sign_in_prereg_post_type() {
	    // Set UI labels for Custom Post Type
	    $labels = array(
	        'name'                => _x( 'Preregs', 'Post Type General Name' ),
	        'singular_name'       => _x( 'Prereg', 'Post Type Singular Name' ),
	        'menu_name'           => __( 'Preregs' ),
	        'parent_item_colon'   => __( 'Parent Prereg' ),
	        'all_items'           => __( 'All Preregs' ),
	        'view_item'           => __( 'View Prereg' ),
	        'add_new_item'        => __( 'Add New Prereg' ),
	        'add_new'             => __( 'Add New' ),
	        'edit_item'           => __( 'Edit Prereg' ),
	        'update_item'         => __( 'Update Prereg' ),
	        'search_items'        => __( 'Search Preregs' ),
	        'not_found'           => __( 'Not Found' ),
	        'not_found_in_trash'  => __( 'Not found in Trash' ),
	    );
	    
	    // Set other options for Custom Post Type
	    $args = array(
	        'label'               => __( 'preregs' ),
	        'description'         => __( 'Prereg news and reviews' ),
	        'labels'              => $labels,
	        // Features this CPT supports in Post Editor
	        'supports'            => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'revisions', 'custom-fields', ),
	        // You can associate this CPT with a taxonomy or custom taxonomy. 
	        'taxonomies'          => array( 'genres' ),
	        /* A hierarchical CPT is like Pages and can have
	        * Parent and child items. A non-hierarchical CPT
	        * is like Posts.
	        */ 
	        'hierarchical'        => false,
	        'public'              => true,
	        'show_ui'             => true,
	        'show_in_menu'        => true,
	        'show_in_nav_menus'   => true,
	        'show_in_admin_bar'   => true,
	        'menu_position'       => 6,
	        'can_export'          => true,
	        'has_archive'         => true,
	        'exclude_from_search' => false,
	        'publicly_queryable'  => true,
	        'capability_type'     => 'post',
	        'show_in_rest'        => true, // This enables the Gutenberg editor for the CPT
	    );
	    
	    // Registering your Custom Post Type
	    register_post_type( 'sign-in-prereg', $args );
	}

    public function create_sign_in_attendee_post_type() {
	    // Set UI labels for Custom Post Type
	    $labels = array(
	        'name'                => _x( 'Attendees', 'Post Type General Name' ),
	        'singular_name'       => _x( 'Attendee', 'Post Type Singular Name' ),
	        'menu_name'           => __( 'Attendees' ),
	        'parent_item_colon'   => __( 'Parent Attendee' ),
	        'all_items'           => __( 'All Attendees' ),
	        'view_item'           => __( 'View Attendee' ),
	        'add_new_item'        => __( 'Add New Attendee' ),
	        'add_new'             => __( 'Add New' ),
	        'edit_item'           => __( 'Edit Attendee' ),
	        'update_item'         => __( 'Update Attendee' ),
	        'search_items'        => __( 'Search Attendees' ),
	        'not_found'           => __( 'Not Found' ),
	        'not_found_in_trash'  => __( 'Not found in Trash' ),
	    );
	    
	    // Set other options for Custom Post Type
	    $args = array(
	        'label'               => __( 'attendees' ),
	        'description'         => __( 'Attendee news and reviews' ),
	        'labels'              => $labels,
	        // Features this CPT supports in Post Editor
	        'supports'            => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'revisions', 'custom-fields', ),
	        // You can associate this CPT with a taxonomy or custom taxonomy. 
	        'taxonomies'          => array( 'genres' ),
	        /* A hierarchical CPT is like Pages and can have
	        * Parent and child items. A non-hierarchical CPT
	        * is like Posts.
	        */ 
	        'hierarchical'        => false,
	        'public'              => true,
	        'show_ui'             => true,
	        'show_in_menu'        => true,
	        'show_in_nav_menus'   => true,
	        'show_in_admin_bar'   => true,
	        'menu_position'       => 6,
	        'can_export'          => true,
	        'has_archive'         => true,
	        'exclude_from_search' => false,
	        'publicly_queryable'  => true,
	        'capability_type'     => 'post',
	        'show_in_rest'        => true, // This enables the Gutenberg editor for the CPT
	    );
	    
	    // Registering your Custom Post Type
	    register_post_type( 'sign-in-attendee', $args );
	}

    public function sign_in_custom_post_statuses() {
	    // Register "upcoming" status
	    register_post_status( 'upcoming', array(
	        'label'                     => _x( 'Upcoming', 'Sign In Event' ),
	        'public'                    => true,
	        'exclude_from_search'       => false,
	        'show_in_admin_all_list'    => true,
	        'show_in_admin_status_list' => true,
	        'label_count'               => _n_noop( 'Upcoming <span class="count">(%s)</span>', 'Upcoming <span class="count">(%s)</span>' ),
	    ) );

	    // Register "open" status
	    register_post_status( 'open', array(
	        'label'                     => _x( 'Open', 'Sign In Event' ),
	        'public'                    => true,
	        'exclude_from_search'       => false,
	        'show_in_admin_all_list'    => true,
	        'show_in_admin_status_list' => true,
	        'label_count'               => _n_noop( 'Open <span class="count">(%s)</span>', 'Open <span class="count">(%s)</span>' ),
	    ) );

	    // Register "closed" status
	    register_post_status( 'closed', array(
	        'label'                     => _x( 'Closed', 'Sign In Event' ),
	        'public'                    => true,
	        'exclude_from_search'       => false,
	        'show_in_admin_all_list'    => true,
	        'show_in_admin_status_list' => true,
	        'label_count'               => _n_noop( 'Closed <span class="count">(%s)</span>', 'Closed <span class="count">(%s)</span>' ),
	    ) );
	}

	public function create_sign_in_user_roles() {
	    // Get the author and editor roles
	    $author_role = get_role( 'author' );
	    $editor_role = get_role( 'editor' );

	    // Check if the role already exists to avoid re-adding it
	    if ( !get_role( 'customer_rep' ) ) {
	        // Create 'Customer Rep' with author capabilities
	        add_role( 'customer_rep', 'Customer Rep', $author_role->capabilities );
	    }

	    if ( !get_role( 'customer_coordinator' ) ) {
	        // Create 'Customer Coordinator' with author capabilities
	        add_role( 'customer_coordinator', 'Customer Coordinator', $editor_role->capabilities );
	    }

	    if ( !get_role( 'customer_admin' ) ) {
	        // Create 'Customer Administrator' with editor capabilities
	        add_role( 'customer_admin', 'Customer Administrator', $editor_role->capabilities );
	    }
	}

	public function set_default_user_role_to_customer_rep() {
	    // Check if the 'customer_rep' role exists before setting it as the default user role.
	    if ( wp_roles()->is_role( 'customer_rep')) {
	        update_option( 'default_role', 'customer_rep');
	    }
	}

	public function redirect_non_admin_users() {
	    // Check if the current user is not an administrator and is trying to access an admin page
	    if ( is_admin() && !current_user_can( 'administrator' ) && !defined( 'DOING_AJAX' ) ) {
	        // Redirect them to the home page
	        wp_redirect( home_url() );
	        exit;
	    }
	}

	public function sign_in_base_templates($template) {
		if (is_post_type_archive('sign-in-event')) {
			// Check if the custom template file exists in the plugin directory
			$plugin_template = plugin_dir_path(__FILE__) . 'templates/archive-sign-in-event.php';
			if (file_exists($plugin_template)) {
				return $plugin_template;
			}
		}

		if (is_page() && get_the_title() === 'Create Sign In Event') {
			// Check if the custom template file exists in the plugin directory
			$plugin_template = plugin_dir_path(__FILE__) . 'templates/page-create-sign-in-event.php';
			if (file_exists($plugin_template)) {
				return $plugin_template;
			}
		}

		if (is_page() && get_the_title() === 'Edit Sign In Event') {
			// Check if the custom template file exists in the plugin directory
			$plugin_template = plugin_dir_path(__FILE__) . 'templates/page-edit-sign-in-event.php';
			if (file_exists($plugin_template)) {
				return $plugin_template;
			}
		}

		if (is_single() && get_post_type() === 'sign-in-event') {
			// Check if the custom template file exists in the plugin directory
			$plugin_template = plugin_dir_path(__FILE__) . 'templates/single-sign-in-event.php';
			if (file_exists($plugin_template)) {
				return $plugin_template;
			}
		}

		if (is_page() && get_the_title() === 'Sign In Event Start Registration') {
			// Check if the custom template file exists in the plugin directory
			$plugin_template = plugin_dir_path(__FILE__) . 'templates/page-start-registration.php';
			if (file_exists($plugin_template)) {
				return $plugin_template;
			}
		}

		if (is_page() && get_the_title() === 'Sign In Event Complete Registration') {
			// Check if the custom template file exists in the plugin directory
			$plugin_template = plugin_dir_path(__FILE__) . 'templates/page-complete-registration.php';
			if (file_exists($plugin_template)) {
				return $plugin_template;
			}
		}

		if (is_page() && get_the_title() === 'Sign In NPI Lookup') {
			// Check if the custom template file exists in the plugin directory
			$plugin_template = plugin_dir_path(__FILE__) . 'templates/page-npi-lookup.php';
			if (file_exists($plugin_template)) {
				return $plugin_template;
			}
		}

		return $template;
	}

    public function create_scaffold() {
		$builder = array();

		$builder['registration_form'] = array(
			'element' => 'h2',
			'label' => 'Registration Form',
			'classes' => 'full',
			'ignore' => true
		);

		$builder['honorific'] = array(
			'element' => 'text',
			'label' => 'Honorific',
			'classes' => 'full'
		);

		$builder['first_name'] = array(
			'element' => 'text',
			'label' => 'First Name',
			'classes' => 'full required'
		);

		$builder['last_name'] = array(
			'element' => 'text',
			'label' => 'Last Name',
			'classes' => 'full required'
		);

		$builder['email_address'] = array(
			'element' => 'email',
			'label' => 'Email Address',
			'classes' => 'full required'
		);

		$builder['work_address'] = array(
			'element' => 'h2',
			'label' => 'Work Address',
			'classes' => 'full',
			'ignore' => true
		);

		$builder['work_address_1'] = array(
			'element' => 'text',
			'label' => 'Address 1',
			'classes' => 'full'
		);

		$builder['work_address_2'] = array(
			'element' => 'text',
			'label' => 'Address 2',
			'classes' => 'full'
		);

		$builder['work_country'] = array(
			'element' => 'text',
			'label' => 'Country',
			'classes' => 'half required'
		);

		$builder['work_city'] = array(
			'element' => 'text',
			'label' => 'City',
			'classes' => 'half required'
		);

		$builder['work_state'] = array(
			'element' => 'text',
			'label' => 'State/Province',
			'classes' => 'half required'
		);

		$builder['work_postal_code'] = array(
			'element' => 'text',
			'label' => 'Zip/Postal Code',
			'classes' => 'half required'
		);

		$builder['work_phone'] = array(
			'element' => 'text',
			'label' => 'Work Phone',
			'classes' => 'half'
		);

		$builder['npi_number'] = array(
			'element' => 'text',
			'label' => 'NPI',
			'classes' => 'half required'
		);

		$builder['signature'] = array(
			'element' => 'checkbox',
			'label' => 'Electronic Signature',
			'classes' => 'full required'
		);

		$builder = apply_filters( 'aasgnn_alter_scaffold', $builder );

		self::$scaffold = $builder;
	}

	public function create_sign_in_event_ajax() {

	    // Check for nonce security
	    if ( !isset( $_POST['create_sign_in_event_nonce'] ) || !wp_verify_nonce( $_POST['create_sign_in_event_nonce'], 'create_sign_in_event' ) ) {
	        wp_send_json_error( array( 'message' => 'Nonce verification failed.' ), 403 );
	    }

	    // Check if user is logged in
	    if ( !is_user_logged_in() ) {
	        wp_send_json_error( array( 'message' => 'User not logged in.' ), 401 );
	    }
	    
        if ( !isset( $_POST['event_type'] ) ) {
        	wp_send_json_error( array( 'message' => 'Event type is required.' ), 400 );
        }
	    
        if ( !isset( $_POST['start_time'] ) ) {
        	wp_send_json_error( array( 'message' => 'Start Time is required.' ), 400 );
        }
	    
        if ( !isset( $_POST['end_time'] ) ) {
        	wp_send_json_error( array( 'message' => 'End Time is required.' ), 400 );
        }
	    
        if ( !isset( $_POST['event_name'] ) ) {
        	wp_send_json_error( array( 'message' => 'Event Name is required.' ), 400 );
        }
	    
        if ( !isset( $_POST['event_description'] ) ) {
        	wp_send_json_error( array( 'message' => 'Event Description is required.' ), 400 );
        }

        $guid = $this->generate_guid();

	    // Create new sign-in event post
	    $post_id = wp_insert_post( array(
	        'post_title'   => $guid,
	        'post_content' => sanitize_text_field( $_POST['event_description'] ),
	        'post_status'  => 'upcoming',
	        'post_type'    => 'sign-in-event',
	    ) );

	    // Check for errors
	    if ( is_wp_error( $post_id ) ) {
	        wp_send_json_error( array( 'message' => $post_id->get_error_message() ), 500 );
	    }

	    $this->generate_qr_code( $post_id, $guid );

	    // Save additional post meta
	    $meta_keys = array( 'event_type', 'start_time', 'end_time', 'event_name', 'event_description' );

	    if ( $_POST['event_type'] === 'planned-event' ) {
	    	$meta_keys[] = 'cvent_id';
	    }

	    foreach ( $meta_keys as $key ) {
	        if ( isset( $_POST[$key] ) ) {
	            update_post_meta( $post_id, $key, sanitize_text_field( $_POST[$key] ) );
	        }
	    }

	    $pdfi = new PDFGenerator();

	    $pdfi->GenerateInstructions( $post_id, $guid, false );

	    update_post_meta( $post_id, "start_time_utc", aasgnn_utc_from_local( $_POST['start_time'], $_POST['local_time'] ) );

	    // Send success response
	    wp_send_json_success( array( 'message' => 'Sign-in event created successfully.', 'post_id' => $post_id, 'post_title' => $guid ) );
	}

	public function edit_sign_in_event_ajax() {

	    // Check for nonce security
	    if ( !isset( $_POST['edit_sign_in_event_nonce'] ) || !wp_verify_nonce( $_POST['edit_sign_in_event_nonce'], 'edit_sign_in_event' ) ) {
	    }

		// Get the post object
		$post = get_post( $_POST['id'] );

		// Get the current user's data
		$current_user = wp_get_current_user();

		// Check if the current user is allowed to alter the post.
		if ( $post->post_author != $current_user->ID && !current_user_can( 'edit_others_posts' ) ) {
	        wp_send_json_error( array( 'message' => 'You don\'t have permission to do that.' ), 403 );
		}

	    // Check if user is logged in
	    if ( !is_user_logged_in() ) {
	        wp_send_json_error( array( 'message' => 'User not logged in.' ), 401 );
	    }
	    
        if ( !isset( $_POST['event_type'] ) ) {
        	wp_send_json_error( array( 'message' => 'Event type is required.' ), 400 );
        }
	    
        if ( !isset( $_POST['start_time'] ) ) {
        	wp_send_json_error( array( 'message' => 'Start Time is required.' ), 400 );
        }
	    
        if ( !isset( $_POST['end_time'] ) ) {
        	wp_send_json_error( array( 'message' => 'End Time is required.' ), 400 );
        }
	    
        if ( !isset( $_POST['event_name'] ) ) {
        	wp_send_json_error( array( 'message' => 'Event Name is required.' ), 400 );
        }
	    
        if ( !isset( $_POST['event_description'] ) ) {
        	wp_send_json_error( array( 'message' => 'Event Description is required.' ), 400 );
        }

	    // Create new sign-in event post
	    $post_id = wp_update_post( array(
	    	'ID'		   => sanitize_text_field( $_POST['id'] ),
	        'post_content' => sanitize_text_field( $_POST['event_description'] ),
	    ), true );

	    // Check for errors
	    if ( is_wp_error( $post_id ) ) {
	        wp_send_json_error( array( 'message' => $post_id->get_error_message() ), 500 );
	    }

	    $guid = get_the_title( $post_id );

	    // Save additional post meta
	    $meta_keys = array('cvent_id', 'event_type', 'start_time', 'end_time', 'event_name', 'event_description');

	    do_action_ref_array('alter_event_meta_keys', [&$meta_keys]);

	    foreach ($meta_keys as $key) {
    		delete_post_meta( $post_id, $key);

	        if (isset( $_POST[$key])) {
	            update_post_meta($post_id, $key, sanitize_text_field($_POST[$key]));
	        }
	    }

	    $pdfi = new PDFGenerator();

	    $pdfi->GenerateInstructions( $post_id, $guid, true );

	    update_post_meta( $post_id, "start_time_utc", aasgnn_utc_from_local( $_POST['start_time'], $_POST['local_time'] ) );

	    // Send success response
	    wp_send_json_success( array( 'message' => 'Sign-in event edited successfully.', 'post_id' => $post_id, 'post_title' => get_the_title( $post_id ) ) );
	}

	public function begin_check_in_ajax() {

	    // Check for nonce security
	    if ( !isset( $_POST['begin_check_in_nonce'] ) || !wp_verify_nonce( $_POST['begin_check_in_nonce'], 'begin_check_in' ) ) {
	        wp_send_json_error( array( 'message' => 'Nonce verification failed.' ), 403 );
	    }

		// Get the post object
		$post = get_post( $_POST['id'] );

		// Get the current user's data
		$current_user = wp_get_current_user();

		// Check if the current user is allowed to alter the post.
		if ( $post->post_author != $current_user->ID && !current_user_can( 'edit_others_posts' ) ) {
	        wp_send_json_error( array( 'message' => 'You don\'t have permission to do that.' ), 403 );
		}

	    // Create new sign-in event post
	    $post_id = wp_update_post( array(
	    	'ID'		   => sanitize_text_field( $_POST['id'] ),
	        'post_status' => 'open',
	    ), true );

	    // Check for errors
	    if ( is_wp_error( $post_id ) ) {
	        wp_send_json_error( array( 'message' => $post_id->get_error_message() ), 500 );
	    }

	    add_post_meta( $post_id, "begin_check_in", gmdate( "Y-m-d H:i:s" ) );

	    // Send success response
	    wp_send_json_success( array( 'message' => 'Check in has begun.', 'post_id' => $post_id ) );
	}

	public function pause_check_in_ajax() {

	    // Check for nonce security
	    if ( !isset( $_POST['pause_check_in_nonce'] ) || !wp_verify_nonce( $_POST['pause_check_in_nonce'], 'pause_check_in' ) ) {
	        wp_send_json_error( array( 'message' => 'Nonce verification failed.' ), 403 );
	    }

		// Get the post object
		$post = get_post( $_POST['id'] );

		// Get the current user's data
		$current_user = wp_get_current_user();

		// Check if the current user is allowed to alter the post.
		if ( $post->post_author != $current_user->ID && !current_user_can( 'edit_others_posts' ) ) {
	        wp_send_json_error( array( 'message' => 'You don\'t have permission to do that.' ), 403 );
		}

	    // Create new sign-in event post
	    $post_id = wp_update_post( array(
	    	'ID'		   => sanitize_text_field( $_POST['id'] ),
	        'post_status' => 'upcoming',
	    ), true );

	    // Check for errors
	    if ( is_wp_error( $post_id ) ) {
	        wp_send_json_error( array( 'message' => $post_id->get_error_message() ), 500 );
	    }

	    add_post_meta( $post_id, "pause_check_in", gmdate( "Y-m-d H:i:s" ) );

	    // Send success response
	    wp_send_json_success( array( 'message' => 'Check in has been paused.', 'post_id' => $post_id ) );
	}

	public function npi_lookup_ajax() {

	    // Check for nonce security
	    if ( !isset( $_POST['npi_lookup_nonce'] ) || !wp_verify_nonce( $_POST['npi_lookup_nonce'], 'npi_lookup' ) ) {
	        wp_send_json_error( array( 'message' => 'Nonce verification failed.' ), 403 );
	    }

	    $nlmnpi = new NLMNPI();

		$response = $nlmnpi->GetData( $_POST['s'], $_POST['limit'] );

		$contacts = array();

		foreach ( $response[3] as $participant ) {
			$contact = array();
	        $contact["honorific"] = $participant[0];
	        $contact["first_name"] = $participant[1];
	        $contact["last_name"] = $participant[2];
	        $contact["work_address_1"] = $participant[3];
	        $contact["work_address_2"] = $participant[4];
	        $contact["work_country_code"] = $participant[5];
	        $contact["work_country"] = "";
	        $contact["work_city"] = $participant[7];
	        $contact["work_state_code"] = $participant[6];
	        $contact["work_state"] = $participant[6];
	        $contact["work_postal_code"] = $participant[8];
	        $contact["work_phone"] = $participant[9];
	        $contact["npi_number"] = $participant[11];
	        $contact["primary_taxonomy"] = $participant[10];

	        $contacts[] = $contact;
		}

		$contacts = apply_filters( 'aasgnn_npi_results', $contacts, $response );

	    wp_send_json_success( array( 'message' => 'NPI results returned.', 'suggestions' => $contacts ) );
	}

	//retrieve participants for a cvent id
	public function pre_reg_lookup_ajax() {

	    // Check for nonce security
	    if ( !isset( $_POST['pre_reg_lookup_nonce'] ) || !wp_verify_nonce( $_POST['pre_reg_lookup_nonce'], 'pre_reg_lookup' ) ) {
	        wp_send_json_error( array( 'message' => 'Nonce verification failed.' ), 403 );
	    }

		$contacts = array();

	    switch ( get_option('pre_reg_system', 'local') ) {
	    	case 'local' :
	    		$local_pre_reg = new LocalPreReg();
	    		$contacts = $local_pre_reg->get_participants( $_POST['id'] );
				$contacts = apply_filters( 'aasgnn_planned_local_results', $contacts, $local_pre_reg->registrants );
	    		break;
    		case 'cvent' :
			    $cventID = get_post_meta( $_POST['id'], 'cvent_id', true );
				$CVENT = new CVENT();
				$rval = $CVENT->get_participants( $cventID );
				$registrant_data = $rval['data'];
				$contacts = apply_filters( 'aasgnn_planned_cvent_results', $contacts, $registrant_data );
    			break;
    		case 'alm' :
				$ALM = new ALM();
				$rval = $ALM->get_participants( $_POST['s'] );
				$registrant_data = $rval['data'];
				$contacts = apply_filters( 'aasgnn_planned_alm_results', $contacts, $registrant_data );
    			break;
	    }

	    wp_send_json_success( array( 'message' => 'Users retrieved successfully.', 'suggestions' => $contacts ) );
	}

	public function lookup_alm_user_ajax() {
	    // Check for nonce security
	    if ( !isset( $_POST['lookup_alm_user_nonce'] ) || !wp_verify_nonce( $_POST['lookup_alm_user_nonce'], 'lookup_alm_user' ) ) {
	        wp_send_json_error( array( 'message' => 'Nonce verification failed.' ), 403 );
	    }

		$ALM = new ALM();

		$rval = $ALM->get_individual( $_POST['email'] );

		$info = $rval['data'];

		$contact = apply_filters( 'aasgnn_lookup_alm_user', $info );

	    wp_send_json_success( array( 'message' => 'Users retrieved successfully.', 'contact' => $contact ) );

	}

	public function pre_register_attendee_ajax() {

	    // Check for nonce security
	    if ( !isset( $_POST['pre_register_attendee_nonce'] ) || !wp_verify_nonce( $_POST['pre_register_attendee_nonce'], 'pre_register_attendee' ) ) {
	        wp_send_json_error( array( 'message' => 'Nonce verification failed.' ), 401 );
	    }

	    // Create new sign-in event post
	    $post_id = wp_insert_post( array(
	        'post_title'   => sanitize_text_field( $_POST['first_name'] )." ".sanitize_text_field( $_POST['last_name'] ),
	        'post_content' => sanitize_text_field( sanitize_text_field( $_POST['npi_number'] ) ),
	        'post_status'  => 'publish',
	        'post_type'    => 'sign-in-prereg',
	        'post_parent' => sanitize_text_field( $_POST['id'] )
	    ) );

	    // Check for errors
	    if ( is_wp_error( $post_id ) ) {
	        wp_send_json_error( array( 'message' => $post_id->get_error_message() ), 500 );
	    }

	    foreach ( self::$scaffold as $key => $values ) {
	        if ( isset( $_POST[$key] ) && empty( $values['ignore'] ) ) {
	            update_post_meta( $post_id, $key, $_POST[$key] );
	        } else if ( empty( $values['ignore'] ) ){
	            update_post_meta( $post_id, $key, "" );
	        }
	    }

		add_post_meta( $post_id, "attendee_pre_reg_time", gmdate( "Y-m-d H:i:s" ) );
	    add_post_meta( sanitize_text_field( $_POST['id'] ), "attendee_pre_reg", gmdate( "Y-m-d H:i:s" ) );

	    // Send success response
	    wp_send_json_success( array( 'message' => 'Attendee pre-registered.', 'post_id' => $post_id ) );
	}

	public function register_attendee_ajax() {

	    // Check for nonce security
	    if ( !isset( $_POST['register_attendee_nonce'] ) || !wp_verify_nonce( $_POST['register_attendee_nonce'], 'register_attendee' ) ) {
	        wp_send_json_error( array( 'message' => 'Nonce verification failed.' ), 401 );
	    }

	    // Create new sign-in event post
	    $post_id = wp_insert_post( array(
	        'post_title'   => sanitize_text_field( $_POST['first_name'] )." ".sanitize_text_field( $_POST['last_name'] ),
	        'post_content' => sanitize_text_field( sanitize_text_field( $_POST['npi_number'] ) ),
	        'post_status'  => 'publish',
	        'post_type'    => 'sign-in-attendee',
	        'post_parent' => sanitize_text_field( $_POST['id'] )
	    ) );

	    // Check for errors
	    if ( is_wp_error( $post_id ) ) {
	        wp_send_json_error( array( 'message' => $post_id->get_error_message() ), 500 );
	    }

	    foreach ( self::$scaffold as $key => $values ) {
	        if ( isset( $_POST[$key] ) && empty( $values['ignore'] ) ) {
	            update_post_meta( $post_id, $key, $_POST[$key] );
	        } else if ( empty( $values['ignore'] ) ){
	            update_post_meta( $post_id, $key, "" );
	        }
	    }

		add_post_meta( $post_id, "attendee_check_in_time", gmdate( "Y-m-d H:i:s" ) );
	    add_post_meta( sanitize_text_field( $_POST['id'] ), "attendee_checked_in", gmdate( "Y-m-d H:i:s" ) );

	    // Send success response
	    wp_send_json_success( array( 'message' => 'Attendee registered.', 'post_id' => $post_id ) );
	}

	function authentication_redirects() {
        global $post;

		// Get the current user's data
		$current_user = wp_get_current_user();

	    if ( is_single() && $post->post_type === 'sign-in-event' ) {

	        // Check if the current user is allowed to view the post.
	        if ( $post->post_author != $current_user->ID && !current_user_can( 'edit_others_posts' ) ) {
	            // Redirect to the homepage.
	            wp_redirect( home_url() );
	            exit; // Always call exit after wp_redirect.
	        }
	    }

	    //want to make editing happen on the single sign-in-event page with a url param
	    if ( is_page( 'edit-sign-in-event' ) ) {
	    	$event_title = sanitize_text_field( $_GET['event']);

	        // Attempt to retrieve a post by its title.
	        $posts = get_posts( array(
	            'title'        => $event_title,
	            'post_type'   => 'sign-in-event',
        		'post_status' => 'any',
	            'numberposts' => 1,
	        ) );

	        // Check if the current user is allowed to view the post.
	        if ( $posts[0]->post_author != $current_user->ID && !current_user_can( 'edit_others_posts' ) ) {
	            // Redirect to the homepage.
	            wp_redirect( home_url() );
	            exit; // Always call exit after wp_redirect.
	        }
	    }

		if ( !is_page( 'sign-in-overview' ) && !is_page( 'sign-in-event-start-registration' ) && !is_page( 'sign-in-npi-lookup' ) && !is_page( 'sign-in-event-complete-registration' ) ) {
	        if ( !is_user_logged_in() ) {
	        	global $wp;
	        	wp_redirect( wp_login_url( home_url( $wp->request ) ) );
	        }
		}
	}

	public function update_upcoming_posts_to_open() {
	    // Set the current time
	    $current_time = current_time('Y-m-d H:i:s', time() );
	    // Set the time 90 minutes from now
	    $ninety_minutes_later = date('Y-m-d H:i:s', strtotime($current_time . ' + 90 minutes'));

	    // Create a query to get all posts with the post status "upcoming" and a meta_key of "event_datetime"
	    $query = new WP_Query([
	        'post_type' => 'sign-in-event', // Change to your custom post type if needed
	        'post_status' => 'upcoming',
	        'meta_query' => [
	            [
	                'key'     => 'start_time_utc', // Change this to the actual meta key for the datetime field
	                'value'   => [$current_time, $ninety_minutes_later],
	                'compare' => 'BETWEEN',
	                'type'    => 'DATETIME', 
	            ],
	        ],
	    ]);

	    // Check if there are any posts found
	    if ($query->have_posts()) {
	        while ($query->have_posts()) {
	            $query->the_post();
	            
	            // Update post status to 'open'
	            $post_id = get_the_ID();
	            if (empty(get_post_meta($post_id, 'auto_begin_check_in', true))) {
		            wp_update_post([
		                'ID'          => $post_id,
		                'post_status' => 'open',
		            ]);

		    		add_post_meta( $post_id, "auto_begin_check_in", gmdate( "Y-m-d H:i:s" ) );
		    	}
	        }
	    }

	    // Reset post data after custom query
	    wp_reset_postdata();
	}

	public function generate_guid() {
	    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	    $blocks = [];

	    for ( $i = 0; $i < 4; $i++ ) {
	        $block = '';
	        for ( $j = 0; $j < 8; $j++ ) {
	            $block .= $characters[rand( 0, strlen( $characters ) - 1 )];
	        }
	        $blocks[] = $block;
	    }

	    return implode( '-', $blocks );
	}

	public function generate_qr_code( $post_id, $guid ) {
		$url = get_site_url( null, 'sign-in-registration/?event='.$guid );
		
		$image_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode( $url );

		$filename = $guid . '.png';

		$upload_dir = wp_upload_dir();

		//Get the file
		$image_data = file_get_contents( $image_url );

		//Store in the filesystem.
		if ( wp_mkdir_p( $upload_dir['path'] ) ) {
	  		$file = $upload_dir['path'] . '/' . $filename;
		}
		else {
	  		$file = $upload_dir['basedir'] . '/' . $filename;
		}

		file_put_contents( $file, $image_data );

		// Check the type of file. We'll use this as the 'post_mime_type'.
	    $filetype = wp_check_filetype( basename( $filename ), null );

	    // Prepare an array of post data for the attachment.
	    $attachment = array(
	        'post_mime_type' => $filetype['type'],
	        'post_title'     => sanitize_file_name( $filename ),
	        'post_content'   => '',
	        'post_status'    => 'inherit'
	    );

	    // Insert the attachment.
	    $attach_id = wp_insert_attachment( $attachment, $file, $post_id );

	    // Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
	    require_once( ABSPATH . 'wp-admin/includes/image.php' );

	    // Generate the metadata for the attachment, and update the database record.
	    $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
	    wp_update_attachment_metadata( $attach_id, $attach_data );

		//set as featured image for parent post
		set_post_thumbnail( $post_id, $attach_id );
	}
}

// Initialize the plugin
SignIn::get_instance()->init();