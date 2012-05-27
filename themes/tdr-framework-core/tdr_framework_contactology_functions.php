<?php
/* CONTACTOLOGY EMAIL LIST
******************************************************************************/

/* Site settings for API key management */
add_action('admin_init', 'tdr_email_list_settings_init');

function tdr_email_list_settings_init() {
		// Add to general settings section
		add_settings_section( 'tdr_email_list_settings', 'Email List Subscriptions', 'tdr_email_list_settings_fn', 'general' );
		// Add the field for the API key
		add_settings_field( 'tdr_email_list_api_key', 'Email List API Key', 'tdr_email_list_api_key_fn', 'general', 'tdr_email_list_settings' );
		// Register the setting
		register_setting( 'general', 'tdr_email_list_api_key', $sanitize_callback = 'esc_html' );
}
// Outputs the setting section description
function tdr_email_list_settings_fn() {
	echo( '<p>Here you can define the settings that handle the site email lists.</p>' );
}
// Outputs the API key setting field
function tdr_email_list_api_key_fn() {
	echo( '<input name="tdr_email_list_api_key" id="tdr_email_list_api_key" type="text" value="' . get_option( 'tdr_email_list_api_key' ) . '" class="code" style="width:255px;" /> The email list api key for this site' );
}

// Registers the handler for AJAX calls for email capture
add_action( 'wp_ajax_nopriv_tdr_email_list_subscribe', 'tdr_email_list_subscribe' );
add_action( 'wp_ajax_tdr_email_list_subscribe', 'tdr_email_list_subscribe' );

/**
 * AJAX handler for Email Capture
 */
function tdr_email_list_subscribe() {
/*
	HOW TO IMPLEMENT THE E-MAIL SUBSCRIPTION LIST
    ===========================================================================
    **** YOUR SITE ****
        Set the API key to use in the site's General settings page under Email List Subscriptions -> Email List API Key
	**** YOUR FORM ****
	Attributes:
	    data-tdr-list-id="INT_ID" // The List ID from Contactology
		data-tdr-group-id="INT_ID" // The Group ID from Contactology
		data-tdr-opt-in-source="OPT_IN_SOURCE" // The Opt-In source from Contactology
		data-tdr-pw-length="INT_MINIMUM_LIMIT" // The minimum password length
		data-tdr-user-registration="TRUE|FALSE BOOLEAN" // Create a WordPress subscriber?

	*** YOUR FORM BUTTON ***
		<button> with type="submit" class="tdr_register_email"

    *** AJAX SUBMISSION events ***
		.tdr_register_email_working // Shows while the form submission is performed via AJAX
		.tdr_register_email // The <button> hidden during form submission via AJAX

	*** INPUT TAGS ***
		wrap input tags in containers with control-group class // allows for validation errors

	*** DIVS FOR SUCCESS AND ERROR ***
	Success and Failure divs as siblings (outside of) form tag
		* (Display: none)
		class=tdr_register_email_error
		class=tdr_register_email_validation_error
		class=tdr_register_email_success
*/

	//Require the Contactology base class
	require get_template_directory() . '/class.Contactology.php';

	// Get API Key -- default to DigitalBrands API key if not set (Site General Settings)
	$contactology_api_key = get_option( 'tdr_email_list_api_key', $default = '91475b8ada42e6336141da18b84168db' );

	// Start contactology with API key
	$c = new Contactology( $contactology_api_key );

	// Setup request return info for client callback
	$return_array = array(
		'message' => '',
        'error' => '',
		'invalid_message' => '',
        'invalid' => array()
	);

	// Parse Data into associative array from JSON
	$form_data = json_decode( stripslashes( $_POST['data'] ), true );
	// Fail & return error if parsing problem
	if ( empty( $form_data ) ) {
		$return_array['error'] = 'There was a problem processing your request'; // parsed JSON was empty
	}
	// Otherwise proceed
	else {
		// Decode user information
		$form_data['user_information'] = array_map( 'urldecode', $form_data['user_information'] );
		// Filter and sanitize form data
        $list_id = filter_var( $form_data['request_details']['list_id'], FILTER_VALIDATE_INT ); // Looks for integer numbers or returns false
        $group_id = filter_var( $form_data['request_details']['group_id'], FILTER_VALIDATE_INT ); // Looks for integer numbers or returns false
		$opt_in_source = esc_html ( $form_data['request_details']['opt_in_source'] ); // Filters html content from opt-in source field
		$user_email = filter_var( $form_data['user_information']['user_email'], FILTER_VALIDATE_EMAIL ); // Validates email based on format
        $user_email_confirm = filter_var( $form_data['user_information']['user_email_confirm'], FILTER_VALIDATE_EMAIL ); // Validates email based on format
        $user_email_confirm_in_form = array_key_exists( 'user_email_confirm', $form_data['user_information'] ); // Detects if form had a repeat email field
		$first_name = esc_html( $form_data['user_information']['first_name'] ); // Filters html content from first name field
		$last_name = esc_html ( $form_data['user_information']['last_name'] ); // Filters html content from last name field
		/*---------------------------------------------------------*/
		$user_registration = filter_var( $form_data['request_details']['user_registration'], FILTER_VALIDATE_BOOLEAN ); // Defaults user registration as WP subscribers to false if unable to evaluate to true
		$user_password = filter_var( $form_data['user_information']['user_password'], FILTER_SANITIZE_STRING ); /* DOES affect user password! */
        $user_password_confirm = filter_var( $form_data['user_information']['user_password_confirm'], FILTER_SANITIZE_STRING ); /* DOES affect user password! */	
        $user_password_confirm_in_form = array_key_exists( 'user_password_confirm', $form_data['user_information'] ); // Detects if form had a repeat password field
		$password_required_length = filter_var( $form_data['request_details']['password_length'], FILTER_VALIDATE_INT ); // Validates required password length is integer
		if ( $password_required_length < 8 ) {
			$password_required_length = 8; // Force character limit if too small
		}
		// For forms that have fields to enter password twice, check to see if the passwords supplied match
		if ( ( $user_password_confirm_in_form ) && ( $user_password != $user_password_confirm ) ) {
			$return_array['invalid_message'] = 'Supplied passwords do not match';
            $user_password_matches = false;
            $return_array['invalid'][] = 'user_password';
            $return_array['invalid'][] = 'user_password_confirm';
		}
		// For forms that only ask for password once, PASS tests for entry confirmation
		else {
			$user_password_matches = true;
		}
		// For forms that have fields to enter email twice, check to see if the emails supplied match
		if ( ( $user_email_confirm_in_form ) && ( $user_email != $user_email_confirm ) ) {
			$return_array['invalid_message'] = 'Supplied email addresses do not match';
            $user_email_matches = false;
            $return_array['invalid'][] = 'user_email';
            $return_array['invalid'][] = 'user_email_confirm';
		}
		// For forms that only ask for email once, PASS tests for entry confirmation
		else {
			$user_email_matches = true;
		}
		// Contains arguments to wp_insert_user -- escape HTML from received data
		$user_information = array_map( 'esc_html', $form_data['user_information'] );
		
		// Check for required form data -- IF registration is to be performed, email(s) match, and password is long enough and matches, proceed
		if ( ( $user_registration ) && ( strlen( $user_password ) >= $password_required_length ) && ( $user_password_matches ) && ( $user_email_matches ) ) {
			if ( $user_email ) {
				// Attempt to register user
				// Default values for registration
				$default_registration_values = array(
					'user_pass' => $user_password,
					'user_login' => $user_email,
					'user_nicename' => '',
					'user_url' => '',
					'user_email' => $user_email,
					'display_name' => '',
					'nickname' => '',
					'first_name' => $first_name,
					'last_name' => $last_name,
					'description' => '',
					'rich_editing' => '',
					'user_registered' => '',
					'role' => 'subscriber',
					'jabber' => '', 
					'aim' => '',
					'yim' => ''
				);
				// Merge above defaults with supplied values
				$registration_args = wp_parse_args( $user_information, $default_registration_values );
				// Gets rid of unused values contained in user_information array such as user_password_confirm
				$registration_args = array_intersect_key( $registration_args, $default_registration_values );
				// Use values to attempt a subscriber registration
				$registration_attempt = wp_insert_user( $registration_args );
				// If registration failed with a WP error, report the error
				if ( is_wp_error( $registration_attempt ) ) {
					$return_array['error'] = 'There was a problem creating a subscriber account';
				}
				// Otherwise report the success
				else {
					$return_array['message'] = 'Successfully added new subscriber account';
				}
			}
			// Registration was enabled, but the supplied email failed validation
            else {
                $return_array['invalid_message'] = 'A valid email address is required';
                // Have field highlighted on front-end
                $return_array['invalid'][] = 'user_email';
                // Highlight email confirmation field if present in form
                if ( $user_email_confirm_in_form ) {
                    $return_array['invalid'][] = 'user_email_confirm';
                }
			}
		}
		// If registration was enabled and password was too short or did not match, or emails did not match
		// (non-matching fields are already reported at check performed farther above)
		// Only need to report issues of short passwords in this block
        else if ( $user_registration ) {
			// If password was too short and has not already been reported
            if ( strlen( $user_password ) < $password_required_length && !in_array( 'user_password', $return_array['invalid'] ) ) {
				// Report and have highlighted in front end
                $return_array['invalid'][] = 'user_password';
                // If password confirmation field is present in form, also have that field reported and highlighted if not already reported
                if ( $user_password_confirm_in_form && !in_array( 'user_password_confirm', $return_array['invalid'] ) ) {
                    $return_array['invalid'][] = 'user_password_confirm';
                }
            }
			$return_array['invalid_message'] = 'Missing required information for registration.'; // May be password length, password matching, or email matching
		}
		// Check to see if values were blank -- uses originally passed values to differentiate from validation errors
		if ( ( !empty( $form_data['user_information']['user_email'] ) ) && ( !empty( $form_data['request_details']['list_id'] ) ) && ( $user_email_matches ) ) {
			// Continue processing if email and list_id passed validation
			if ( $user_email && $list_id ) {
				
				// Set Opt-in-Source Fallback
				if ( empty( $opt_in_source ) ) {
					$opt_in_source = 'None';
				}	
				// Define contacts to subscribe
				$contacts = array( array( 'email' => $user_email, 'first_name' => $first_name, 'last_name' => $last_name ) );
					
				// Push new contact to list
                $add_contact_results = $c->List_Import_Contacts( $list_id, $opt_in_source, $contacts );
                // Add to group if group-id sent
                if ( array_key_exists( 'group_id', $form_data['request_details'] ) && !empty( $group_id ) ) {
                    $add_to_group_results = $c->Group_Add_Contact( $group_id, $user_email );
                }
				$return_array['message'] = 'Thanks for subscribing!';
			}
			// Error is returned for supplying an invalid email address or tampering with the list_id
			else {
                $return_array['invalid_message'] = 'A valid email address is required.';
                // Report validation error if not done previously somewhere else
                if ( !in_array( 'user_email', $return_array['invalid'] ) ) {
                    $return_array['invalid'][] = 'user_email';
                    // Report and highlight email confirmation field as well if was present in form
                    if ( $user_email_confirm_in_form ) {
                        $return_array['invalid'][] = 'user_email_confirm';
                    }
                }
			}
		}
		// Error is returned because a list ID and email address are needed to add subscribers
		// List ID errors not user-facing
		else {
			$return_array['invalid_message'] = 'There was a problem processing your request';
			// Report user email as validation error and highlight if not already reported
			if ( !in_array( 'user_email', $return_array['invalid'] ) ) {
				$return_array['invalid'][] = 'user_email';
				// Report and highlight email confirmation field if included in form
				if ( $user_email_confirm_in_form ) {
					$return_array['invalid'][] = 'user_email_confirm';
				}
			}
		}
	}
	// Convert result to JSON
    $return_json = json_encode( $return_array );
    // Print out JSON Response
    echo $return_json;
	die(); // this is required to return a proper result
}
?>
