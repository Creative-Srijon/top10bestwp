<?php
/* ENABLE PROMOTIONS IN ADMIN BACKEND
******************************************************************************/
// Add action to call $tdr_promotions->init(); with proper hook time
add_action( 'admin_menu', 'tdr_promotions_menu_setup' );
function tdr_promotions_menu_setup() {
    $promotions = new tdr_promotions_menus();
    $promotions->init(); // May also call setup_admin_menus directly
}
/* JQUERY-UI DATEPICKER IN ADMIN BACKEND
******************************************************************************/
// DISABLE DATEPICKER SCRIPT UNTIL IN USE AGAIN
/*
add_action( 'admin_enqueue_scripts', 'tdr_promotions_enqueue_scripts' );
function tdr_promotions_enqueue_scripts( $hooked_page ) {
	if ( 'toplevel_page_tdr_promotions' != $hooked_page ) {
		return;
	}
	else {
		wp_enqueue_script( 'jquery-ui-datepicker', 'jquery-ui-core' );
		wp_enqueue_style( 'jquery-ui-smoothness', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.21/themes/smoothness/jquery-ui.css', true );
	}
}
*/
/* ACTIVE PROMOTION HOOKS
******************************************************************************/
add_action( 'wp_footer', 'tdr_promotions_trigger' );
function tdr_promotions_trigger() {
    global $post;
    $tdr_promotion = new tdr_promotions;
    $promotion_list = $tdr_promotion->get_the_decoded_promotions();
    // Start a list of all active promotions
	$active_promotions = array();
	// Loop through all saved promotions
    foreach ( $promotion_list as $promotion => $promotion_data ) {
		// Filter to only campaigns in production status
		if ( $promotion_data['campaign_ready'] === true ) {
			$active_pages = $promotion_data['include'];
			// Show promotion if at least one include condition is met
			if ( ( is_page() && in_array( $post->ID, $active_pages ) ) || // Page whose ID has been tagged
			 ( is_archive() && in_array( 'archive', $active_pages ) ) || // Archive page if enabled
			 ( is_single() && in_array( 'single', $active_pages ) && 'tdr_product' != get_post_type() ) ) { // Single if enabled (excludes product review pages)
				 $active_promotions[ $promotion ] = $promotion_data;
				 // Hook for active campaigns
				 do_action( 'tdr_promotions_active_campaign_output', $promotion, $promotion_data ); // Send slug and (optionally) data to hook
				 if ( $promotion_data['type'] === 'custom' ) {
					 // Hook for active custom campaigns
					 do_action( 'tdr_promotions_active_custom_campaign_output', $promotion, $promotion_data ); // Send slug and (optionally) data to hook
				 }
			}
		}
    }
    // Hook for list of all active campaigns
    do_action( 'tdr_promotions_all_active_campaigns_ouput', $active_promotions ); // Send array of all active promotions to hook
}

/* CAMPAIGN LIST AJAX
******************************************************************************/
add_action( 'wp_ajax_nopriv_tdr_promotions_send_entry_code_reminder', 'tdr_promotions_send_entry_code_reminder' );
add_action( 'wp_ajax_tdr_promotions_send_entry_code_reminder', 'tdr_promotions_send_entry_code_reminder' );
function tdr_promotions_send_entry_code_reminder() {
	// Setup request return info for client callback
	$return_array = array(
		'message' => '',
        'error' => '',
		'invalid_message' => '',
        'invalid' => array(),
	);

	// Parse Data into associative array from JSON
	$form_data = json_decode( stripslashes( $_POST['data'] ), true );
	// Fail & return error if parsing problem
	if ( empty( $form_data ) ) {
		$return_array['error'] = 'There was a problem processing your request'; // parsed JSON was empty
	}
	// Otherwise proceed
	else {
		// Process fields
		$campaign_slug = sanitize_title( $form_data['campaign'] ); // Get the campaign slug and sanitize it
		$user_email = filter_var( $form_data['email'], FILTER_VALIDATE_EMAIL ); // Validates email based on format
		// Check that campaign slug is unchanged and the email was marked as valid
		if ( ( $campaign_slug != $form_data['campaign'] ) || ( !$user_email ) ) {
			$return_array['error'] = 'The request data was not valid';
			// Mark email as invalid if it was bad
			if ( !$user_email ) {
				$return_array['invalid'][] = 'email';
			}
		}
		else {
			// Get the campaign data
			$promotion = new tdr_promotions();
			$campaign_data = $promotion->get_the_decoded_promotion_by_slug( $campaign_slug );
			// Look for a user with the provided email address
			if ( $campaign_data && $user_email ) {
				// Look for user with given email
				$matching_user = get_user_by( 'email', $user_email );
				// If no user found, return error [ No such email found ]
				if ( !$matching_user ) {
					$return_array['error'] = 'No contestant found with given email';
				}
				// Otherwise get their entry code
				else {
					$entry_code = get_user_meta( $matching_user->ID, 'referral_id', true ); // Get the entry code from user meta
					// If entry code defined, try to email
					if ( !empty( $entry_code ) ) {
						$site_domain = parse_url( site_url(), PHP_URL_HOST ); // Get the site's domain
						$site_domain = strtolower( $site_domain ); // Lowercase the domain
						if ( strpos( $site_domain, 'www.' ) === 0 ) { // Strip the www subdomain if present
							$site_domain = substr( $site_domain, 4 );
						}
						$email_data = array(
							'subject' => 'Your Contest Entry Code',
							'message' => 'Your entry code for the ' . $campaign_data['name'] . ' contest is ' . $entry_code,
							'headers' => 'From: ' . get_bloginfo('name') . ' <donotreply@' . $site_domain . '>' ."\r\n",
							'attachments' => ''
						);
						$email_success = wp_mail ( $user_email, $email_data['subject'], $email_data['message'], $email_data['headers'], $email_data['attachments'] ); // Try emailing and store result
						// If success, great
						if ( $email_success ) {
							$return_array['message'] = 'Email successfully sent';
						}
						// If not, report error
						else {
							$return_array['error'] = 'Problem sending email';
						}
					}
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


/* CAMPAIGN LIST AJAX
******************************************************************************/
add_action( 'wp_ajax_nopriv_tdr_promotions_list_active_campaigns', 'tdr_promotions_list_active_campaigns' );
add_action( 'wp_ajax_tdr_promotions_list_active_campaigns', 'tdr_promotions_list_active_campaigns' );
// List campaigns by contest page slug
function tdr_promotions_list_active_campaigns() {
	// Get list of campaigns
	$promotion = new tdr_promotions();
	$active_promotions = $promotion->get_the_raw_promotions();
	$campaign_slug_list = array(); // Create empty array to hold hashtag list
	foreach ( $active_promotions as $campaign ) {
		if ( $campaign['campaign_ready'] === true ) { // Only push campaigns in production status to the active campaign array
			$campaign_slug_list[] = $campaign['campaign_slug'];
		}
	}
	// Convert result to JSON
    $return_json = json_encode( $campaign_slug_list );
    // Print out JSON Response
    echo $return_json;
	die(); // this is required to return a proper result		
}


add_action( 'wp_ajax_nopriv_tdr_promotions_list_active_campaign_hashtags', 'tdr_promotions_list_active_campaign_hashtags' );
add_action( 'wp_ajax_tdr_promotions_list_active_campaign_hashtags', 'tdr_promotions_list_active_campaign_hashtags' );
// List campaigns by twitter hash tag
function tdr_promotions_list_active_campaign_hashtags() {
	// Get list of campaigns
	$promotion = new tdr_promotions();
	$active_promotions = $promotion->get_the_decoded_promotions();
	$contest_hashtag_list = array(); // Create empty array to hold hashtag list
	foreach ( $active_promotions as $campaign ) {
		if ( $campaign['campaign_ready'] === true ) { // Only push campaigns in production status to the active campaign array
			$contest_hashtag_list[] = '#' . $campaign['twitter_hashtag'];
		}
	}
	// Convert result to JSON
    $return_json = json_encode( $contest_hashtag_list );
    // Print out JSON Response
    echo $return_json;
	die(); // this is required to return a proper result		
}

/* TWITTER SCRAPER TWEET PROCESSING
******************************************************************************/
add_action( 'wp_ajax_nopriv_tdr_promotions_twitter_scrape', 'tdr_promotions_process_tweets' );
add_action( 'wp_ajax_tdr_promotions_twitter_scrape', 'tdr_promotions_process_tweets' );
function tdr_promotions_process_tweets() {
	// Set defaults
	$return_code = false;
	$contest_found = false;
	$query_referral_id = '';
	// Get tweet data
	$tweet = json_decode( stripslashes( $_POST['data'] ), true );
	if ( !empty( $tweet ) ) {
		
		// Look for relevant entity (holding referral id)
		foreach ( $tweet['entities']['urls'] as $result_url ) {
			// Check the expanded_url
			$url_params = parse_url( $result_url['expanded_url'], PHP_URL_QUERY );	// Get query string from link
			parse_str( $url_params, $url_param_array );								// Parse query string into array
			if ( array_key_exists( 'ref', $url_param_array ) ) {					// 'ref' param was found
				$located_referral_id = $url_param_array['ref'];								// Add to list
				$query_referral_id = $located_referral_id;
				$query_referral_id = strtoupper( $located_referral_id );			// Capitalize the referral id just in case
				break;																// Stop looking through links for this tweet
			}
			else {
				// Not found
			}
		}		
		// Avoid touching the database unless a promising URL was found
		if ( !empty( $query_referral_id ) ) {
			// Look find hash tag that matches an active contest
			foreach ( $tweet['entities']['hashtags'] as $hash_tag ) {
				$slugified_hash_tag = str_replace( '_', '-', $hash_tag['text'] ); // Slugify underscored hash tags
				$hash_tag_list[] = $slugified_hash_tag;
			}
			$hash_tag_list = array_map( 'sanitize_title', $hash_tag_list );
			
			// Filter hash tags to find ONE contest
			$promotion = new tdr_promotions();
			// Remove hash tags that don't have a campaign associated with them
			$hash_tag_list = array_filter( $hash_tag_list, array( $promotion, 'get_the_raw_promotion_by_hashtag' ) );
			if ( count( $hash_tag_list ) == 1 ) {
				// Get the campaign slug from the hashtag
				$contest_slug = $promotion->get_the_campaign_slug_by_hashtag( $hash_tag_list[0] );
				if ( $contest_slug ) {
					$contest_found = true;
				}
			}
			
			// Look for a user in the contest with the found referral ID
			if ( ( !empty( $query_referral_id ) ) && ( $contest_found ) ) {
				// User query for given referral ID and campaign-related role
				$contest_user_query_args = array(
					'role' => 'promo-' . $contest_slug,
					'meta_key' => 'referral_id',
					'meta_value' => $query_referral_id
				);
				// Perform the user query
				$contest_user_query = new WP_User_Query( $contest_user_query_args );
				// If results found, fetch them
				if ( $contest_user_query->total_users != "0" ) {
					$contest_users_from_scrape = $contest_user_query->get_results();
					foreach( $contest_users_from_scrape as $contest_user ) {
						// Get tweet limit information
						$campaign_data = $promotion->get_the_raw_promotion_by_slug( $contest_slug ); // Get the campaign data
						$tweet_limit = $campaign_data['tweet_limit_per_day']; // Extract the tweet limit from the campaign data
						// Get the user tweet history
						$tweet_history = get_user_meta( $contest_user->ID, 'tweet_history', true );
						$award_entries = true;
						// Tweet history meta found
						if ( !empty( $tweet_history ) ) {
							$last_tweet_date = getdate( $tweet_history['date'] ); // Get date of last tweet
							$tweets_for_day = $tweet_history['count']; // Get number of tweets for that date
							$current_date = getdate(); // Tweets are collected in real time -- compare to current date
							// Either the years or days of the year are different
							if ( ( $last_tweet_date['year'] != $current_date['year'] ) || ( $last_tweet_date['yday'] != $current_date['yday'] ) ) {
								$tweet_history['date'] = time(); // Change the date to today
								$tweet_history['count'] = 1; // Set the tweet count for the day to one
							}
							// The tweets already awarded for the day are less than the tweet limit
							else if ( $tweets_for_day < $tweet_limit ) {
								// Keep the date the same so tweets on the same day can accumulate
								// Update tweet history
								$tweet_history['count']++; // Increase the tweet count by one
							}
							else {
								$award_entries = false; // Do not award entries or update the tweet history meta
							}
						}
						// No tweet history meta
						else {
							$tweet_history = array(
								'date' => time(),
								'count' => 1
							);
						}
						// Continue processing approved tweets
						if ( $award_entries ) {
							update_usermeta( $contest_user->ID, 'tweet_history', $tweet_history ); // Update tweet history meta
							// Award the entries
							$current_contest_entries = get_user_meta( $contest_user->ID, 'contest_entries', true ); // Get the current meta value
							$referral_id = get_user_meta( $contest_user->ID, 'referral_id', true ); // Get the user's referral id
							$current_contest_entries['sharing'][ 'twitter' ]++; // Credit the user for the number of new shares found
							// Perform the meta update
							update_usermeta( $contest_user->ID, 'contest_entries', $current_contest_entries );
							tdr_check_entry_cap( $contest_user->ID, $contest_slug ); // Check to see if entry cap is reached; Move to capped group if so
							$return_code = true;
						}
					}
				}
			}
		}
	}
	$return_json = json_encode( $return_code );
    echo $return_json;
	die(); // this is required to return a proper result	
}

/* SOCIAL NETWORK SCRAPER
******************************************************************************/
add_action('wp', 'tdr_promotions_social_media_scraper_listener');
function tdr_promotions_social_media_scraper_listener() {
	if ( !wp_next_scheduled( 'tdr_promotions_social_media_scraper_job' ) ) {
		wp_schedule_event( current_time( 'timestamp' ), 'hourly', 'tdr_promotions_social_media_scraper_job');
	}
}
add_action('tdr_promotions_social_media_scraper_job', 'tdr_promotions_social_media_scraper_queue');
function tdr_promotions_social_media_scraper_queue() {
	// Set scraper targets
	$social_network_list = array( 'facebook' );
	// Run scraper on each social network
	foreach ( $social_network_list as $social_network ) {
		tdr_promotions_social_media_scraper( $social_network );
	}
}
function tdr_promotions_social_media_scraper ( $social_network ) {
	// Get list of campaigns
	$promotion = new tdr_promotions();
	$campaign_list = $promotion->get_the_decoded_promotions();	
	// Run Scrape for $campaign_slug with contest page url $contest_permalink
	foreach($campaign_list as $campaign_slug => $campaign_data) {
		if ( $campaign_data['campaign_ready'] === true ) { // Only scrape campaigns in production status
			// Find contest page permalink based on campaign slug
			// Get list of pages with the contest template
			$contest_page_list = get_pages(array(
				'meta_key' => '_wp_page_template',
				'meta_value' => 'template-contest-' . $campaign_slug . '.php',
				'hierarchical' => false // Allow subpages to be found
			));
			$contest_page_found = false;
			foreach ( $contest_page_list as $contest_page ) {
				if ( $campaign_data['url_slug'] === $contest_page->post_name ) {
					$contest_page_id = $contest_page->ID;
					$contest_page_found = true;
					break;
				}
			}
			if ( $contest_page_found ) {
				$contest_permalink = get_permalink( $contest_page_id ); // Contest permalink for scraping
			}
			$db_slug = str_replace( '-', '_', $campaign_slug );
			switch ( $social_network ) {
				case 'facebook':
					$api_base_url = 'https://graph.facebook.com/search';
					$transient_name = 'tdr_promo_fb_scr_' . $db_slug;
					/*
					$site_title_format = get_option( 'tdr_promotions_site_title_function' ); // Get title function for site -- extract from within
					// Site title function is defined and is callable
					if ( ( false !== $site_title_format ) && ( function_exists( $site_title_format ) ) ) {
						$contest_page_title = $site_title_format( $contest_page->ID );
						$contest_page_title = str_replace('|', '\\|', $contest_page_title );
					}
					// Fallback to page title
					else {
						$contest_page_title = get_the_title( $contest_page->ID );
					}
					*/
					// DEBUG echo( 'using ' . $contest_page_title . ' for facebook scrapes' );
					$request_array = array(
						'q' => /*'test_term'*/$campaign_data['facebook_share_title'],
						'type' => 'post',
						'access_token' => '',
						'limit' => '100',
						'date_format' => 'U' // Get times in unix timestamp format
						//'since' => 'unix_timestamp_from_last_search'
					);
					break;
				default:
					return;
			}
			// If transient not set, do new query
			$refresh_api_query = get_transient( $transient_name );
			if ( true /* <-- DEBUG UNCOMMENT OTHERWISE !$refresh_api_query*/ ) {
				// DEBUG echo('no transient found');
				switch ( $social_network ) {
					case 'facebook':
						$api_request = $api_base_url . '?' . http_build_query( $request_array );
						break;
				}
			// END STANDARD QUERY
			}
			// IF TRANSIENT DEFINED, USE REFRESH URL
			else {
				// DEBUG echo('transient was found');
				switch ( $social_network ) {
					case 'facebook':
						$api_request = $refresh_api_query; // Transient holds full request url
						break;
				}
			}

			// Perform API GET request
			// DEBUG echo('making request to '. $api_request );
			$wp_remote_get_args = array(
				'method' => 'GET',
				'timeout' => 25,
				'redirection' => 5,
				'user-agent' => 'tdr-promos/1.0',
				'blocking' => true,
				'compress' => true,
				'decompress' => true,
				'sslverify' => true,
				'httpversion' => '1.1',
				'headers' => array(),
				'body' => null,
				'cookies' => array()
			);
			$request_results = wp_remote_get( $api_request, $wp_remote_get_args );
			// On errors, retry with exponential backing off
			$wait_time = 1;
			while ( is_wp_error( $request_results ) ) { // Api request failure -- retry with exponential backing off
				sleep ( $wait_time );
				$request_results = wp_remote_get( $api_request, $wp_remote_get_args );
				if ( $wait_time > 600 ) {
					return; // End function on repeated errors beyond 10 minutes retry wait ( aggregate of 21 minutes after timeouts )
				}
				$wait_time = $wait_time * 2;
				/*DEBUG var_dump( $request_results );
				echo( 'first request died' );
				echo( '<br>' . $api_request ); */
			}
			$parsed_results = json_decode( $request_results['body'], true ); // Parse response JSON
			
			// Update transient with refresh URL
			switch ( $social_network ) {
				case 'facebook':
					if ( !empty( $parsed_results['data'] ) ) {
						// Get unix timestamp of most recent result
						$most_recent_result = $parsed_results['data'][0]['created_time']; // Most recent result is returned first by fb graph api
						$request_array['since'] = $most_recent_result; // Add since parameter to request array
						$refresh_query = $api_base_url . '?' . http_build_query( $request_array ); // Build url for api refresh request
					}
					else {
						$refresh_query = $api_request; // Next time repeat a normal query if there were no results this time
					}
					break;
			}
			
			set_transient( $transient_name, $refresh_query, 60*60*24*7 ); // Give transient an expiration of a week
			$referral_id_list = array(); // Create empty array to hold referall ids from search results
			
			// Look through the search results
			switch ( $social_network ) {
				case 'facebook':
					$i = 0; // Set a loop counter so pagination links can be followed after the first iteration
					$pagination_api_request = ''; // Set a variable for holding pagination urls in scope for all iterations of the loop
					$time_array = array();
					while ( true ) { // Loop until the results are empty
						if ( $i > 4 ) {
							break;
						}
						// Follow pagination links for new iterations of the loop
						if ( $i > 0 ) {
							/* PROFILING CODE
							// Start time logging*/
							//$start_time = microtime( true );
							$request_results = wp_remote_get( $pagination_api_request, $wp_remote_get_args );
							//$end_time = microtime( true );
							//$execution_time = round( $end_time - $start_time, 4 );
							//$time_array [] = $execution_time;
							// END PROFILING CODE
							$timeout = false;
							$wait_time = 1;
							while ( is_wp_error( $request_results ) ) { // Api request failure -- retry with exponential backing off
								sleep ( $wait_time );
								$request_results = wp_remote_get( $pagination_api_request, $wp_remote_get_args );
								if ( $wait_time > 150 ) {
									$timeout = true;
									break; // End function on repeated errors beyond 2.5 minutes retry wait ( aggregate of ~7.5 minutes after timeouts )
								}
								$wait_time = $wait_time * 2;
								/* DEBUG echo( 'died at iteration ' . $i );
								var_dump( $pagination_api_request );
								var_dump( $request_results ); */
							}
							if ( $timeout ) {
								break; // Stop scraping and process any collected entries
							}
							$parsed_results = json_decode( $request_results['body'], true ); // Parse response JSON						
						}
						// Get pagination link, if any
						if ( array_key_exists( 'paging', $parsed_results ) ) {
							$pagination_link = $parsed_results['paging']['next'];
							$pagination_api_request = $pagination_link;
						}
						// Otherwise break the loop
						else {
							break; // End of results-- end loop
						}
						// Filter the pagination so only non-scraped results are shown
						$url_params = parse_url( $api_request, PHP_URL_QUERY );	// Get query string from link
						parse_str( $url_params, $url_param_array );								// Parse query string into array
						if ( array_key_exists( 'since', $url_param_array ) ) { // Check if there was a since parameter in the original request
							$pagination_api_request .= '&since=' . $url_param_array['since']; // Append it to facebook's suggested pagination links
						}
						// Loop through the results for the current page
						foreach ( $parsed_results['data'] as $result ) {
							// Look for relevant entity
							$url_params = parse_url( $result['link'], PHP_URL_QUERY );	// Get query string from link
							parse_str( $url_params, $url_param_array );								// Parse query string into array
							if ( array_key_exists( 'ref', $url_param_array ) ) {					// 'ref' param was found
								$located_referral_id = $url_param_array['ref'];								// Add to list
								$located_referral_id = strtoupper( $located_referral_id ); 					// Capitalize referral ids just in case
								// User previously found in results
								if ( array_key_exists( $located_referral_id, $referral_id_list ) ) {
									$referral_id_list[ $located_referral_id ]++; // Increment user's count
								}
								// Otherwise is a new user
								else {
									$referral_id_list[ $located_referral_id ] = 1; // Add referral id to list
								}				
							}
							else {
								// Not found
								// DEBUG echo('no match');
							}
						}
						$i++; // Increment loop counter
						sleep( 1 ); // Be nice to facebook and impose a rate limit of 1 second between loops
					}
					// Write to /tmp
					break;
			}
			/*
			 * PROFILING CODE
			$fp = fopen('/tmp/twitterscrape.txt', 'w');
			fwrite($fp, $i);
			fwrite($fp, "\n min " . min( $time_array ) . " / max " . max( $time_array ) . " / average " . array_sum($time_array) / count(array_filter($time_array)) . " /  total " . array_sum( $time_array ) );
			fclose($fp);
			*/
			
			
	// DEBUG var_dump( $referral_id_list );
			// User query for referral ID IN list
			// Credit with tweet/facebook post
			$query_referral_id_list = array_keys( $referral_id_list );
			// Define the wordpress user query parameters
			$contest_user_query_args = array(
				'role' => 'promo-' . $campaign_slug,
				'meta_key' => 'referral_id',
				'meta_value' => $query_referral_id_list,
				'meta_compare' => 'IN'//,
				//'number' => -1 // Return all results
			);
			// Perform the user query
			$contest_user_query = new WP_User_Query( $contest_user_query_args );
			// If results found, fetch them
			if ( $contest_user_query->total_users != "0" ) {
				$contest_users_from_scrape = $contest_user_query->get_results();
				foreach( $contest_users_from_scrape as $contest_user ) {
					$current_contest_entries = get_user_meta( $contest_user->ID, 'contest_entries', true ); // Get the current meta value
					$referral_id = get_user_meta( $contest_user->ID, 'referral_id', true ); // Get the user's referral id
					$current_contest_entries['sharing'][ $social_network ]+= $referral_id_list[ $referral_id ]; // Credit the user for the number of new shares found
					// Perform the meta update
					update_usermeta( $contest_user->ID, 'contest_entries', $current_contest_entries );
					tdr_check_entry_cap( $contest_user->ID, $campaign_slug ); // Check to see if entry cap is reached; Move to capped group if so
					// DEBUG var_dump( $contest_user->ID );
				}
			}
	// DEBUG		var_dump( $contest_user_query );
	// DEBUG		var_dump( $contest_users_from_scrape );

			//$fp = fopen('/tmp/twitterscrape.txt', 'w');
			//fwrite($fp, $request_results);
			//fwrite($fp, var_dump( $user_list ) );
			//fclose($fp);
			// Write to /tmp
		}
	}
}
/* SOCIAL MEDIA ACCOUNT BINDING TO USER ACCOUNTS
******************************************************************************/
// Registers the handler for AJAX calls for social media account binding
add_action( 'wp_ajax_nopriv_tdr_bind_social_media_accounts', 'tdr_bind_social_media_accounts' );
add_action( 'wp_ajax_tdr_bind_social_media_accounts', 'tdr_bind_social_media_accounts' );

/**
 * AJAX handler for Social Media Account Binding
 */
function tdr_bind_social_media_accounts() {
/*
	HOW TO IMPLEMENT THE SOCIAL MEDIA ACCOUNT CAPTURE FORM
    ===========================================================================
	**** YOUR FORM ****
	Attributes:
		data-tdr-promotion-name="STRING_PROMOTION_SLUG" // The slug for the promotion
		data-tdr-promotion-referrer="STRING_REFERRAL_ID" // The referral code for the contest

	*** YOUR FORM BUTTON ***
		<button> with type="submit" class="tdr_bind_social_media_accounts"

    *** AJAX SUBMISSION events ***
		.tdr_bind_social_media_working // Shows while the form submission is performed via AJAX
		.tdr_bind_social_media_accounts // The <button> hidden during form submission via AJAX
		successful promotion entries trigger a jQuery event 'tdr_bind_social_media_success' on the FORM element

	*** INPUT TAGS ***
		wrap input tags in containers with control-group class // allows for validation errors

	*** DIVS FOR SUCCESS AND ERROR ***
	Success and Failure divs as siblings (outside of) form tag
		* (Display: none)
		class=tdr_bind_social_media_error
		class=tdr_bind_social_media_validation_error
		class=tdr_bind_social_media_success
*/

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
		$promotion_name = sanitize_title( $form_data['request_details']['promotion_name'] );
		$provided_referral_id = filter_var( $form_data['request_details']['referrer_id'], FILTER_SANITIZE_STRING );
		
		// Escape HTML from received data
		$user_information = array_map( 'esc_html', $form_data['user_information'] );
		
		// Check for required form data -- IF promotion name and referral id are present, go ahead
		if ( ( !emtpy( $promotion_name ) ) && ( !empty( $provided_referral_id ) ) ) {
			$promotion_object = new tdr_promotions();
			// Validate promotion name matches a real campaign -- so meta update filter below only gets valid promotions
			if ( !empty( $promotion_name ) && ( $promotion_object->get_the_raw_promotion_by_slug( $promotion_name ) ) ) {
				$filter_update_meta_result = false;
				// Allow social media meta to be defined on a promotion-by-promotion basis
				$filter_update_meta_result = apply_filters( 'tdr_promotions_create_user', $filter_update_meta_result, $promotion_name, $provided_referral_id, $form_data['user_information'] );
				// If filter not used or unsuccessful, use standard rules for twitter/facebook/google+
				if ( !$user_registration_result ) {
					// Query for the user
					$referral_id_query_args = array(
						'role' => 'promo-' . $promotion_name,
						'meta_key' => 'referral_id',
						'meta_value' => $provided_referral_id
					);
					// Execute search for referral id
					$referral_id_query = new WP_User_Query( $referral_id_query_args );
					// Check if match was found
					if ( $referral_id_query->total_users == "1" ) {
						$user_list = $referral_id_query->get_results(); // Get the query results
						$user_id = $user_list[0]->ID; // Get the user ID
						// Perform the meta update for expected fields that are defined
						$social_media_fields = array(
							/* field name => meta_key */
							'facebook_id' => 'facebook_id',
							'google_plus' => 'google_plus',
							'twitter_handle' => 'twitter_handle'
						);
						foreach ( $social_media_fields as $field_name => $meta_key_name ) {
							if ( array_key_exists( $field_name, $user_information ) ) { // Field was in form
								update_usermeta( $user_id, $meta_key_name, $user_information[ $field_name ] );
							}
						}
					}
					$return_array['message'] = 'Successfully associated social media account(s) with contestant account';
				}
			}
			else {
				$return_array['invalid_message'] = 'There was a problem processing your request'; // Bad promotion name
				$return_array['error'] = true; // Client only checks to see if error is not false
			}
		}
		// Either the referral id or promotion name was not present
		else {
			// Referral id may be user-submitted. Add to invalid fields list if was empty
			if ( empty( $provided_referral_id ) ) {
				// Report and have highlighted in front end
				$return_array['invalid'][] = 'referrer_id';
			}
			$return_array['invalid_message'] = 'Missing required information for registration.'; // May be referral id or promotion name
			// Promotion name is never meant to be user defined -- just returning na error is important
			// Either field being wrong throws an error:
			$return_array['error'] = true; // Client only checks to see if error is not false
		}
	}
	// Convert result to JSON
    $return_json = json_encode( $return_array );
    // Print out JSON Response
    echo $return_json;
	die(); // this is required to return a proper result
}

/* ENTRY CAP CHECKER
******************************************************************************/
function tdr_check_entry_cap( $user_id, $campaign_slug, $user_email = '' ) {
	// Fetch the campaign
	$promotion = new tdr_promotions();
	$campaign_data = $promotion->get_the_decoded_promotion_by_slug( $campaign_slug );
	$entry_limit = $campaign_data['entry_limit']; // Get campaign scoring limit
	$meta_key_map_array = array( // Map the locations of user entry categories to campaign entry multipliers
		'signup' => 'signup_entries',
		'sharing' => array(
			'facebook' => 'share_facebook',
			'twitter' => 'share_twitter'/*,
			'google' => 'share_google_plus',
			'email' => 'share_email',*/
		),
		'referrals' => 'referrals'
	);
	$current_contestant_entries = get_user_meta( $user_id, 'contest_entries', true ); // Get the contestant's entries
	// Get the multiplier values for each category and find the user's subtotals, then add them together
	$user_overall_entry_total = 0;
	foreach ( $meta_key_map_array as $entry_category => $campaign_multiplier ) {
		if ( is_array( $campaign_multiplier ) ) {
			foreach ( $campaign_multiplier as $entry_subcategory => $campaign_submultiplier ) {
				$category_total = ( ( (int) $current_contestant_entries[ $entry_category ][ $entry_subcategory ] ) * ( (int) $campaign_data[ $campaign_submultiplier ] ) );
				$user_overall_entry_total += $category_total;
			}
		}
		else {
			$category_total = ( ( (int) $current_contestant_entries[ $entry_category ] ) * ( (int) $campaign_data[ $campaign_multiplier ] ) );
			$user_overall_entry_total += $category_total;
		}
	}
	// Skip user if they have no entries
	if ( $user_overall_entry_total >= $entry_limit ) {
		// Lookup email address when not provided
		if ( empty( $user_email ) ) {
			// Get user information
			$user_info = get_userdata( $user_id );
			$user_email = $user_info->{'user_email'};
		}
		$confirm_group = $campaign_data['verified_contact_group_id'];
		$maxed_entries_group = $campaign_data['capped_contact_group_id'];
		// Remove them from the verified group
		tdr_remove_from_email_group( $confirm_group, $user_email );
		// Add them to the maxed entries group
		tdr_add_to_email_group( $maxed_entries_group, $user_email );
	}
}

/* PROMOTIONS DEFINITION CLASS
******************************************************************************/
Class tdr_promotions {
	private $fields = array(); // Holds definitions for campaign data fields
	function __construct() {
		$this->fields =	array(
			'page1' => array(
				/* Promotion Name */ 
				array(
				'form_name' => 'promotion_name', // name of form field
				'store_name' => 'name', // array key in saved campaign
				'post_sanitize' => false, // sanitation callback for raw post data 
				'save_validation' => array(
					'filter_var' => false, // use filter_var on value
					'fail_false' => false, // fail validation on false return values
					'callback' => false // generic validation callback function
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => false, // field allowed to be empty ( but key present )
				'urlencoded' => true, // raw value is urlencoded
				'default_value' => ''
				),
				/* Campaign Slug */ 
				array(
				'form_name' => 'campaign_slug',
				'store_name' => 'campaign_slug',
				'post_sanitize' => 'sanitize_title',
				'save_validation' => array(
					'filter_var' => false,
					'fail_false' => false,
					'callback' => false
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => false,
				'urlencoded' => false, // Makes easily available to raw campaign helper functions
				'default_value' => ''
				),
				/* URL Slug */ 
				array(
				'form_name' => 'url_slug',
				'store_name' => 'url_slug',
				'post_sanitize' => 'sanitize_title',
				'save_validation' => array(
					'filter_var' => false,
					'fail_false' => false,
					'callback' => false
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => false,
				'urlencoded' => false, // Makes easily available to raw campaign helper functions
				'default_value' => ''
				),
				/* Twitter Hashtag */ 
				array(
				'form_name' => 'twitter_hashtag',
				'store_name' => 'twitter_hashtag',
				'post_sanitize' => 'sanitize_title',
				'save_validation' => array(
					'filter_var' => false,
					'fail_false' => false,
					'callback' => false
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => false,
				'urlencoded' => true,
				'default_value' => ''
				),
				/* Start Date */
				/* 
				array(
				'form_name' => 'start',
				'store_name' => 'start',
				'post_sanitize' => array( $this, 'validate_date_format' ),
				'save_validation' => array(
					'filter_var' => false,
					'fail_false' => true,
					'callback' => array( $this, 'isValidTimeStamp' )
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => true,
				'urlencoded' => false,
				'default_value' => ''
				),
				*/
				/* End Date */ 
				/*
				array(
				'form_name' => 'end',
				'store_name' => 'end',
				'post_sanitize' => array( $this, 'validate_date_format' ),
				'save_validation' => array(
					'filter_var' => false,
					'fail_false' => true,
					'callback' => array( $this, 'isValidTimeStamp' )
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => true,
				'urlencoded' => false,
				'default_value' => ''
				),
				*/
				/* Impressions After Conversion */ 
				array(
				'form_name' => 'display_after_conversion',
				'store_name' => 'display_after_conversion',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => FILTER_VALIDATE_BOOLEAN,
					'fail_false' => true,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => true,
				'urlencoded' => false,
				'default_value' => 0
				),
				/* Timed Delay Before Impressions */ 
				array(
				'form_name' => 'impression_delay',
				'store_name' => 'impression_delay',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => FILTER_VALIDATE_INT,
					'fail_false' => true,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => true,
				'urlencoded' => false,
				'default_value' => 1000
				),
				/* Time Interval Between Impressions */ 
				array(
				'form_name' => 'cycle_time',
				'store_name' => 'cycle_time',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => FILTER_VALIDATE_INT,
					'fail_false' => false,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => true,
				'urlencoded' => false,
				'default_value' => 60
				),
				/* Contactology List ID */ 
				array(
				'form_name' => 'contact_list_id',
				'store_name' => 'contact_list_id',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => FILTER_VALIDATE_INT,
					'fail_false' => false,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => true,
				'urlencoded' => false,
				'default_value' => ''
				),
				/* Contactology Group ID */ 
				array(
				'form_name' => 'contact_group_id',
				'store_name' => 'contact_group_id',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => FILTER_VALIDATE_INT,
					'fail_false' => false,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => true,
				'urlencoded' => false,
				'default_value' => ''
				),
				/* Contactology Verified Group ID */ 
				array(
				'form_name' => 'verified_contact_group_id',
				'store_name' => 'verified_contact_group_id',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => FILTER_VALIDATE_INT,
					'fail_false' => false,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => true,
				'urlencoded' => false,
				'default_value' => ''
				),
				/* Contactology Verified Group ID */ 
				array(
				'form_name' => 'capped_contact_group_id',
				'store_name' => 'capped_contact_group_id',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => FILTER_VALIDATE_INT,
					'fail_false' => false,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => true,
				'urlencoded' => false,
				'default_value' => ''
				),
				/* Active Pages for Impressions */ 
				array(
				'form_name' => 'include',
				'store_name' => 'include',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => false,
					'fail_false' => false,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => true,
				'urlencoded' => false,
				'default_value' => ''
				),
				/* Medium to Display Promotion */ 
				array(
				'form_name' => 'type',
				'store_name' => 'type',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => false,
					'fail_false' => false,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => false,
				'urlencoded' => false,
				'default_value' => ''
				)
			),
			'page2' => array(
				/* Signup Page CSS Classes */ 
				array(
				'form_name' => 'signup_class',
				'store_name' => 'signup_class',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => false,
					'fail_false' => false,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => false,
				'urlencoded' => true,
				'default_value' => ''
				),
				/* Signup Header Markup */ 
				array(
				'form_name' => 'signup_header',
				'store_name' => 'signup_header',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => false,
					'fail_false' => false,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => false,
				'urlencoded' => true,
				'default_value' => ''
				),
				/* Signup Body Markup */ 
				array(
				'form_name' => 'signup_body',
				'store_name' => 'signup_body',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => false,
					'fail_false' => false,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => false,
				'urlencoded' => true,
				'default_value' => ''
				),
				/* Signup Footer Markup */ 
				array(
				'form_name' => 'signup_footer',
				'store_name' => 'signup_footer',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => false,
					'fail_false' => false,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => false,
				'urlencoded' => true,
				'default_value' => ''
				),
				/* Signup Custom Markup */ 
				array(
				'form_name' => 'signup_custom_text',
				'store_name' => 'signup_custom_text',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => false,
					'fail_false' => false,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => true,
				'urlencoded' => true,
				'default_value' => ''
				),
				/* Confirmation Page CSS Classes */ 
				array(
				'form_name' => 'confirmation_class',
				'store_name' => 'confirmation_class',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => false,
					'fail_false' => false,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => false,
				'urlencoded' => true,
				'default_value' => ''
				),
				/* Confirmation Header Markup */ 
				array(
				'form_name' => 'confirmation_header',
				'store_name' => 'confirmation_header',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => false,
					'fail_false' => false,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => false,
				'urlencoded' => true,
				'default_value' => ''
				),
				/* Confirmation Body Markup */ 
				array(
				'form_name' => 'confirmation_body',
				'store_name' => 'confirmation_body',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => false,
					'fail_false' => false,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => false,
				'urlencoded' => true,
				'default_value' => ''
				),
				/* Confirmation Footer Markup */ 
				array(
				'form_name' => 'confirmation_footer',
				'store_name' => 'confirmation_footer',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => false,
					'fail_false' => false,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => false,
				'urlencoded' => true,
				'default_value' => ''
				),
				/* Confirmation Custom Markup */ 
				array(
				'form_name' => 'confirmation_custom_text',
				'store_name' => 'confirmation_custom_text',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => false,
					'fail_false' => false,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => true,
				'urlencoded' => true,
				'default_value' => ''
				),
				/* Confirmation Facebook Text */ 
				array(
				'form_name' => 'confirmation_facebook_text',
				'store_name' => 'confirmation_facebook_text',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => false,
					'fail_false' => false,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => true,
				'urlencoded' => true,
				'default_value' => ''
				),
				/* Confirmation Twitter Text */ 
				array(
				'form_name' => 'confirmation_twitter_text',
				'store_name' => 'confirmation_twitter_text',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => false,
					'fail_false' => false,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => true,
				'urlencoded' => true,
				'default_value' => ''
				),
				/* Confirmation Facebook Text */ 
				array(
				'form_name' => 'confirmation_email_text',
				'store_name' => 'confirmation_email_text',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => false,
					'fail_false' => false,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => true,
				'urlencoded' => true,
				'default_value' => ''
				),
				/* Contest Page Signup */ 
				array(
				'form_name' => 'contest_page_signup',
				'store_name' => 'contest_page_signup',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => false,
					'fail_false' => false,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => true,
				'urlencoded' => true,
				'default_value' => ''
				),
				/* Contest Page Confirmation */ 
				array(
				'form_name' => 'contest_page_confirmation',
				'store_name' => 'contest_page_confirmation',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => false,
					'fail_false' => false,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => true,
				'urlencoded' => true,
				'default_value' => ''
				),
				/* Contest Page Facebook Text */ 
				array(
				'form_name' => 'contest_page_facebook_text',
				'store_name' => 'contest_page_facebook_text',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => false,
					'fail_false' => false,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => true,
				'urlencoded' => true,
				'default_value' => ''
				),
				/* Contest Page Twitter Text */ 
				array(
				'form_name' => 'contest_page_twitter_text',
				'store_name' => 'contest_page_twitter_text',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => false,
					'fail_false' => false,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => true,
				'urlencoded' => true,
				'default_value' => ''
				),
				/* Contest Page Referral Text */ 
				array(
				'form_name' => 'contest_page_referral_text',
				'store_name' => 'contest_page_referral_text',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => false,
					'fail_false' => false,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => true,
				'urlencoded' => true,
				'default_value' => ''
				),
				/* Contest Page Sharing Gutter */ 
				array(
				'form_name' => 'contest_page_gutter',
				'store_name' => 'contest_page_gutter',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => false,
					'fail_false' => false,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => true,
				'urlencoded' => true,
				'default_value' => ''
				),
				/* Contest Tracker Body Markup */ 
				array(
				'form_name' => 'contest_tracker_body',
				'store_name' => 'contest_tracker_body',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => false,
					'fail_false' => false,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => true,
				'urlencoded' => true,
				'default_value' => ''
				),
				/* Tweet Text */ 
				array(
				'form_name' => 'tweet_text',
				'store_name' => 'tweet_text',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => false,
					'fail_false' => false,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => true,
				'urlencoded' => true,
				'default_value' => ''
				),
				/* Contest Email Subject */ 
				array(
				'form_name' => 'email_subject',
				'store_name' => 'email_subject',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => false,
					'fail_false' => false,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => true,
				'urlencoded' => true,
				'default_value' => ''
				),
				/* Contest Email Body */ 
				array(
				'form_name' => 'email_body',
				'store_name' => 'email_body',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => false,
					'fail_false' => false,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => true,
				'urlencoded' => true,
				'default_value' => ''
				),
				/* Facebook Share Title */ 
				array(
				'form_name' => 'facebook_share_title',
				'store_name' => 'facebook_share_title',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => false,
					'fail_false' => false,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => true,
				'urlencoded' => true,
				'default_value' => ''
				),
				/* Facebook Share Summary */ 
				array(
				'form_name' => 'facebook_share_summary',
				'store_name' => 'facebook_share_summary',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => false,
					'fail_false' => false,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => true,
				'urlencoded' => true,
				'default_value' => ''
				),
				/* Facebook Share Image */ 
				array(
				'form_name' => 'facebook_share_image',
				'store_name' => 'facebook_share_image',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => false,
					'fail_false' => false,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => true,
				'urlencoded' => true,
				'default_value' => ''
				)
			),
			'page3' => array(
				/* Entries Limit */ 
				array(
				'form_name' => 'entry_limit',
				'store_name' => 'entry_limit',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => FILTER_VALIDATE_INT,
					'fail_false' => true,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => true,
				'urlencoded' => false,
				'default_value' => 500
				),
				/* Signup Entries */ 
				array(
				'form_name' => 'signup_entries',
				'store_name' => 'signup_entries',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => FILTER_VALIDATE_INT,
					'fail_false' => true,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => true,
				'urlencoded' => false,
				'default_value' => 0
				),
				/* Entries Per Facebook Share */ 
				array(
				'form_name' => 'share_facebook',
				'store_name' => 'share_facebook',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => FILTER_VALIDATE_INT,
					'fail_false' => true,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => true,
				'urlencoded' => false,
				'default_value' => 0
				),
				/* Entries Per Twitter Tweet */ 
				array(
				'form_name' => 'share_twitter',
				'store_name' => 'share_twitter',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => FILTER_VALIDATE_INT,
					'fail_false' => true,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => true,
				'urlencoded' => false,
				'default_value' => 0
				),
				/* Entries Per Twitter Tweet */ 
				array(
				'form_name' => 'tweet_limit_per_day',
				'store_name' => 'tweet_limit_per_day',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => FILTER_VALIDATE_INT,
					'fail_false' => true,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => true,
				'urlencoded' => false,
				'default_value' => 1
				),
				/* Entries for Google Plus Share */ 
				array(
				'form_name' => 'share_google_plus',
				'store_name' => 'share_google_plus',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => FILTER_VALIDATE_INT,
					'fail_false' => true,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => true,
				'urlencoded' => false,
				'default_value' => 0
				),
				/* Entries per Email Action */ 
				array(
				'form_name' => 'share_email',
				'store_name' => 'share_email',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => FILTER_VALIDATE_INT,
					'fail_false' => true,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => true,
				'urlencoded' => false,
				'default_value' => 0
				),
				/* Entries per Referral Signup */ 
				array(
				'form_name' => 'referrals',
				'store_name' => 'referrals',
				'post_sanitize' => false,
				'save_validation' => array(
					'filter_var' => FILTER_VALIDATE_INT,
					'fail_false' => true,
					'callback' => false,
				), // validation callback for merged data saving/updating, returns boolean
				'allow_empty' => true,
				'urlencoded' => false,
				'default_value' => 0
				)		
			)
		);
	}
/*	function validate_date_format( $input ) {
		$return_value = DateTime::createFromFormat( 'm/d/Y', $input );
		if ( $return_value ) { // Get timestamps for dates that were parsable
			$return_value = $return_value->getTimestamp();
		}
		return $return_value;
	}
*/
	/**
	 *  Gets the promotion winner given the campaign slug
	 *  @param string $campaign_slug the Slug name of the campaign to judge
	 *  @return array the contest winner details | false if error judging
	 */
	function get_the_promotion_winner( $campaign_slug ) {
		// Fetch the campaign
		$campaign_data = $this->get_the_decoded_promotion_by_slug( $campaign_slug );
		$entry_limit = $campaign_data['entry_limit']; // Get campaign scoring limit
		$meta_key_map_array = array( // Map the locations of user entry categories to campaign entry multipliers
			'signup' => 'signup_entries',
			'sharing' => array(
				'facebook' => 'share_facebook',
				'twitter' => 'share_twitter'/*,
				'google' => 'share_google_plus',
				'email' => 'share_email',*/
			),
			'referrals' => 'referrals'
		);
		// Query for verified contestants
			$referral_id_query_args = array(
				'role' => 'promo-' . $campaign_slug,
				'meta_key' => 'confirmed_account',
				'meta_value' => true
			);
			// Execute search for referral id
			$contestant_query = new WP_User_Query( $referral_id_query_args );
			// If user with given referral id is found, pull their account info
			if ( $contestant_query->total_users != "0" ) {
				$aggregate_entries = 0;
				$user_list = $contestant_query->get_results(); // Get the query results
				$entry_list = array();
				foreach ( $user_list as $user ) {
					$user_id = $user->ID; // Get the user ID
					$current_contestant_entries = get_user_meta( $user_id, 'contest_entries', true ); // Get the contestant's entries
					// Get the multiplier values for each category and find the user's subtotals, then add them together
					$user_overall_entry_total = 0;
					foreach ( $meta_key_map_array as $entry_category => $campaign_multiplier ) {
						if ( is_array( $campaign_multiplier ) ) {
							foreach ( $campaign_multiplier as $entry_subcategory => $campaign_submultiplier ) {
								$category_total = ( ( (int) $current_contestant_entries[ $entry_category ][ $entry_subcategory ] ) * ( (int) $campaign_data[ $campaign_submultiplier ] ) );
								$user_overall_entry_total += $category_total;
							}
						}
						else {
							$category_total = ( ( (int) $current_contestant_entries[ $entry_category ] ) * ( (int) $campaign_data[ $campaign_multiplier ] ) );
							$user_overall_entry_total += $category_total;
						}
					}
					// Skip user if they have no entries
					if ( $user_overall_entry_total == 0 ) {
							continue;
					}
					
					// Limit users to the entry cap
					$user_overall_entry_total = min( $user_overall_entry_total, $entry_limit );
					
					// Track aggregate
					$aggregate_entries += $user_overall_entry_total;
					$entry_list[] = array(
						'id' => $user_id,
						'max_entry_number' => $aggregate_entries - 1,
						'entries' => $user_overall_entry_total
					);
				} // END track user entries
				// Pick random number
					$winning_number = rand( 0, $aggregate_entries - 1 );
				// Find owner of number
					foreach( $entry_list as $contestant ) {
						if ( $winning_number <= $contestant['max_entry_number'] ) {
							$winning_user_id = $contestant['id'];
							// Get owner details
							$winner = get_userdata( $winning_user_id );
							$return_value = array(
								'id' => $winning_user_id,
								'email' => $winner->user_email,
								'entries' => $contestant['entries'],
								'contest_entries' => $aggregate_entries
							);
							break;
						}
					}
			}
			// No qualified contestants found
			else {
				$return_value = false;
			}
		return $return_value;
	}
    function get_the_raw_promotions() {
        // Fetch wp_option subgroup
        $return_value = get_option( 'thunder_promotions_list', array() );
        if ( !empty( $return_value ) ) {
            $return_value = $return_value['campaigns'];
        }
        return $return_value;
    }
    function get_the_decoded_promotions() {
        // Fetch wp_option subgroup
        $return_value = get_option( 'thunder_promotions_list', array() );
        if ( !empty( $return_value ) ) {
            $return_value = $return_value['campaigns'];
            foreach ( $return_value as $campaign_slug => $campaign_data ) {
				foreach ( $this->fields as $fieldset ) {
					foreach ( $fieldset as $field ) {
						$key = $field['store_name'];
						if ( $field['urlencoded'] ) {
							$return_value[ $campaign_slug ][ $key ] = urldecode( $return_value[ $campaign_slug ][ $key ] );
							$return_value[ $campaign_slug ][ $key ] = stripslashes( $return_value[ $campaign_slug ][ $key ] );
						}
					}
				}			
			}
        }
        return $return_value;
    }    
    /**
     * Gets the promotion given its slug-- decodes fields for front-end use
     * @param string $slug the Slug name of the campaign to find
     * @return array decoded promotion if found | false on failure
     */
    function get_the_decoded_promotion_by_slug( $slug ) { // More accurately, finds a campaign rather than a promotion
        // Fetch wp_option subgroup
        $saved_promotions = get_option( 'thunder_promotions_list', array() );
        if ( !empty( $saved_promotions ) ) {
            $saved_campaigns = $saved_promotions['campaigns'];
            if ( array_key_exists( $slug, $saved_campaigns ) ) {
				$return_value = $saved_campaigns[ $slug ];
				foreach ( $this->fields as $fieldset ) {
					foreach ( $fieldset as $field ) {
						$key = $field['store_name'];
						if ( $field['urlencoded'] ) {
							$return_value[ $key ] = urldecode( $return_value[ $key ] );
							$return_value[ $key ] = stripslashes( $return_value[ $key ] );
						}
					}
				}
			}
			else {
				$return_value = false;
			}
        }
        else {
			$return_value = false;
		}
        return $return_value;
    }    
    /**
     * Gets the promotion given its slug-- contains encoded values
     * @param string $slug the Slug name of the campaign to find
     * @return array the promotion (with encoded values) if found | false on failure
     */
    function get_the_raw_promotion_by_slug( $slug ) { // More accurately, finds a campaign rather than a promotion
        // Fetch wp_option subgroup
        $saved_promotions = get_option( 'thunder_promotions_list', array() );
        if ( !empty( $saved_promotions ) ) {
            $saved_campaigns = $saved_promotions['campaigns'];
            if ( array_key_exists( $slug, $saved_campaigns ) ) {
				$return_value = $saved_campaigns[ $slug ];
			}
			else {
				$return_value = false;
			}
        }
        else {
			$return_value = false;
		}
        return $return_value;
    }
    /**
     * Gets the promotion given its hashtag-- decodes fields for front-end use
     * @param string $hashtag the twitter hashtag of the campaign to find
     * @return array decoded promotion if found | false on failure
     */
    function get_the_decoded_promotion_by_hashtag( $hashtag ) { // More accurately, finds a campaign rather than a promotion
		$hashtag = str_replace('#', '', $hashtag); // strip any # characters
		$hashtag = sanitize_title( $hashtag ); // pass through wordpress slug generator
		$campaign_found = false; // Set campaign as not found to start
        // Fetch wp_option subgroup
        $saved_promotions = get_option( 'thunder_promotions_list', array() );
        if ( !empty( $saved_promotions ) ) {
            $saved_campaigns = $saved_promotions['campaigns'];
            foreach ( $saved_campaigns as $campaign ) {
				$current_campaign_hashtag = urldecode( $campaign['twitter_hashtag'] );
				// On match, grab campaign
				if ( $current_campaign_hashtag === $hashtag ) {
					$return_value = $campaign;
					foreach ( $this->fields as $fieldset ) {
						foreach ( $fieldset as $field ) {
							$key = $field['store_name'];
							if ( $field['urlencoded'] ) {
								$return_value[ $key ] = urldecode( $return_value[ $key ] );
								$return_value[ $key ] = stripslashes( $return_value[ $key ] );
							}
						}
					}
					$campaign_found = true;					
					break;
				}
			}
			if ( !$campaign_found ) {
				$return_value = false;
			}
        }
        else {
			$return_value = false;
		}
        return $return_value;
    }
    /**
     * Gets the promotion given its hashtag-- contains encoded values
     * @param string $hashtag the twitter hashtag of the campaign to find
     * @return array decoded promotion (with encoded values) if found | false on failure
     */
    function get_the_raw_promotion_by_hashtag( $hashtag ) { // More accurately, finds a campaign rather than a promotion
		$hashtag = str_replace('#', '', $hashtag); // strip any # characters
		$hashtag = sanitize_title( $hashtag ); // pass through wordpress slug generator
		$campaign_found = false; // Set campaign as not found to start
        // Fetch wp_option subgroup
        $saved_promotions = get_option( 'thunder_promotions_list', array() );
        if ( !empty( $saved_promotions ) ) {
            $saved_campaigns = $saved_promotions['campaigns'];
            foreach ( $saved_campaigns as $campaign ) {
				$current_campaign_hashtag = urldecode( $campaign['twitter_hashtag'] );
				// On match, grab campaign
				if ( $current_campaign_hashtag === $hashtag ) {
					$return_value = $campaign;
					$campaign_found = true;					
					break;
				}
			}
			if ( !$campaign_found ) {
				$return_value = false;
			}
        }
        else {
			$return_value = false;
		}
        return $return_value;
    } 
    /**
     * Gets the promotion's campaign slug given its hashtag
     * @param string $hashtag the twitter hashtag of the campaign slug to find
     * @return string campaign slug for promotion if found | false on failure
     */
    function get_the_campaign_slug_by_hashtag( $hashtag ) {
		$hashtag = str_replace('#', '', $hashtag); // strip any # characters
		$hashtag = sanitize_title( $hashtag ); // pass through wordpress slug generator
		$campaign_found = false; // Set campaign as not found to start
        // Fetch wp_option subgroup
        $saved_promotions = get_option( 'thunder_promotions_list', array() );
        if ( !empty( $saved_promotions ) ) {
            $saved_campaigns = $saved_promotions['campaigns'];
            foreach ( $saved_campaigns as $campaign_slug => $campaign_data ) {
				$current_campaign_hashtag = urldecode( $campaign_data['twitter_hashtag'] );
				// On match, grab campaign slug
				if ( $current_campaign_hashtag === $hashtag ) {
					$return_value = $campaign_slug;
					$campaign_found = true;					
					break;
				}
			}
			if ( !$campaign_found ) {
				$return_value = false;
			}
        }
        else {
			$return_value = false;
		}
        return $return_value;
    }
    /**
     * Gets the promotion given its url slug-- contains encoded values
     * @param string $url_slug the url slug of the campaign to find
     * @return array decoded promotion (with encoded values) if found | false on failure
     */
    function get_the_raw_promotion_by_url_slug( $url_slug ) { // More accurately, finds a campaign rather than a promotion
		$url_slug = strtolower( $url_slug ); // make url_slug lowercase
		$campaign_found = false; // Set campaign as not found to start
        // Fetch wp_option subgroup
        $saved_promotions = get_option( 'thunder_promotions_list', array() );
        if ( !empty( $saved_promotions ) ) {
            $saved_campaigns = $saved_promotions['campaigns'];
            foreach ( $saved_campaigns as $campaign ) {
				$current_campaign_url_slug = strtolower( $campaign['url_slug'] );
				// On match, grab campaign
				if ( $current_campaign_url_slug === $url_slug ) {
					$return_value = $campaign;
					$campaign_found = true;					
					break;
				}
			}
			if ( !$campaign_found ) {
				$return_value = false;
			}
        }
        else {
			$return_value = false;
		}
        return $return_value;
    }
    /**
     * Gets the promotion given its url slug-- decodes fields for front-end use
     * @param string $url_slug the url slug of the campaign to find
     * @return array decoded promotion if found | false on failure
     */
    function get_the_decoded_promotion_by_url_slug( $url_slug ) { // More accurately, finds a campaign rather than a promotion
		$url_slug = strtolower( $url_slug ); // make url_slug lowercase
		$campaign_found = false; // Set campaign as not found to start
        // Fetch wp_option subgroup
        $saved_promotions = get_option( 'thunder_promotions_list', array() );
        if ( !empty( $saved_promotions ) ) {
            $saved_campaigns = $saved_promotions['campaigns'];
            foreach ( $saved_campaigns as $campaign ) {
				$current_campaign_url_slug = strtolower( $campaign['url_slug'] );
				// On match, grab campaign
				if ( $current_campaign_url_slug === $url_slug ) {
					$return_value = $campaign;
					foreach ( $this->fields as $fieldset ) {
						foreach ( $fieldset as $field ) {
							$key = $field['store_name'];
							if ( $field['urlencoded'] ) {
								$return_value[ $key ] = urldecode( $return_value[ $key ] );
								$return_value[ $key ] = stripslashes( $return_value[ $key ] );
							}
						}
					}
					$campaign_found = true;					
					break;
				}
			}
			if ( !$campaign_found ) {
				$return_value = false;
			}
        }
        else {
			$return_value = false;
		}
        return $return_value;
    }
    /**
     * Returns a user promotion draft, if it exists
     * @return false when not set or array user draft when set
     */
    function get_promotion_draft() {
		$current_user = wp_get_current_user();
		$current_user = $current_user->user_login;
		$promotion_option = get_option( 'thunder_promotions_list', array() );
		if ( !empty( $promotion_option['drafts'][ $current_user ] ) ) {
			$return_value = $promotion_option['drafts'][ $current_user ];
		}
		else {
			$return_value = false;
		}
		return $return_value;		
	}
	/**
	 * Returns a campaign transient -- a work in process edit of an existing campaign
	 * @return false when not set or array campaign transient when set
	 */
	function get_campaign_transient( $campaign_slug ) {
        // Ensure slug is set
        if ( empty( $campaign_slug ) ) {
            $return_value = false;
        }
        else {
		    $underscored_slug = str_replace('-', '_', $campaign_slug ); // Follow WP database underscore convention
		    if ( false === ( $campaign_transient = get_transient( 'tdr_promotions_' . $underscored_slug ) ) ) {
		    	$return_value = false;
		    }
		    else {
			    $return_value = $campaign_transient;
            }
        }
		return $return_value;
	}
	/**
	 * Updates a user's promotion draft
	 * @return bool The success/failure updating data
	 */
	function update_promotion_draft( $campaign_data ) {
		// Proceed if data is defined
		if ( !empty( $campaign_data ) ) {
			// Get user information
			$current_user = wp_get_current_user();
			$current_user = $current_user->user_login;
			// Save draft
			$promotion_option = get_option( 'thunder_promotions_list', array() );
			$promotion_option['drafts'][ $current_user ] = $campaign_data;
			$return_value = update_option( 'thunder_promotions_list', $promotion_option );
		}
		else {
			$return_value = false;
		}
		return $return_value;
    }
    /**
     * Updates the transient holding edits to an existing promotion campaign
     * @ return bool The success/failure updating data
     */
    function update_promotion_transient ( $campaign_data, $campaign_slug ) {
        $underscored_slug = str_replace( '-', '_', $campaign_slug ); // Follow WP database underscore convention 
        $return_value = set_transient( 'tdr_promotions_' . $underscored_slug, $campaign_data, 60*15 );
        return $return_value;
    }
    /**
     * Removes a user's promotion draft
     * @return bool The success/failure of removing the draft
     */
    function remove_promotion_draft () {
		// Get user information
		$current_user = wp_get_current_user();
		$current_user = $current_user->user_login;
		// Get promotions list
        $promotion_option = get_option( 'thunder_promotions_list', array() );
        // Remove draft
		unset( $promotion_option['drafts'][ $current_user ] );
		$return_value = update_option( 'thunder_promotions_list', $promotion_option );
		return $return_value;
    }
    /**
     * Removes a transient holding edits to an existing promotion campaign
     * @return bool The success/failure of removing the transient
     */
    function remove_promotion_transient ( $original_campaign_slug ) {
        $underscored_slug = str_replace( '-', '_', $original_campaign_slug ); // Follow WP database underscore convention
        $return_value = delete_transient( 'tdr_promotions_' . $underscored_slug ); 
        return $return_value;
    }
    function page_selection_array_filter( $array_value ) {
        switch ( $array_value ) {
            case 'archive':
                $return_var = true;
                break;
            case 'single':
                $return_var = true;
                break;
            default:
                $return_var = filter_var( $array_value, FILTER_VALIDATE_INT ); 
        }
        return $return_var;
    }
/*
    // http://stackoverflow.com/questions/2524680/check-whether-the-string-is-a-unix-timestamp
	function isValidTimeStamp( $timestamp ) {
		return ((int) $timestamp === $timestamp) 
			&& ($timestamp <= PHP_INT_MAX)
			&& ($timestamp >= ~PHP_INT_MAX);		
	}
*/
	function validate_fields ( $campaign_data, $page_number ) {
		$page_number = 'page' . $page_number;
		$validation_passed = true;
		foreach( $this->fields[ $page_number ] as $field ) {
			if ( !$validation_passed ) {
				$break;
			}
			$key = $field['store_name'];
			$break = false;
			switch ( $key ) {
				case 'include':
				case 'type':
					$break = true;
					break;
			}
			if ( $break ) {
				break;
			}
			if ( isset( $campaign_data[ $key ] ) ) {
				// Validation rule defined for field
				if ( $field['save_validation']['callback'] && is_callable( $field['save_validation'] ) ) {
					// Validation failed
					if ( !call_user_func( $field['save_validation']['callback'], $campaign_data[ $key ] ) ) {
						if ( $field['allow_empty'] ) {
							$campaign_data[ $key ] = $field['default_value'];
						}
						else {
							$validation_passed = false;
						}
					}
					// Else what about values that are not false?
				}
				elseif ( $field['save_validation']['filter_var'] ) {
					$campaign_data[ $key ] = filter_var( $campaign_data[ $key ], $field['save_validation']['filter_var'] );
					if ( ( $field['save_validation']['fail_false'] ) && ( !$campaign_data[ $key ] ) ) {
						if ( $field['allow_empty'] ) {
							$campaign_data[ $key ] = $field['default_value'];
						}
						else {
							$validation_passed = false;
						}
					}
				}
				// Disallowed empty field
				elseif ( ( !$field['allow_empty'] ) && ( empty( $campaign_data[ $key ] ) ) ) {
					$validation_passed = false;
				}
				elseif ( empty( $campaign_data[ $key ] ) ) {
					$campaign_data[ $key ] = $field['default_value'];
				}
			}
			else if ( $field['allow_empty'] ) {
				$campaign_data[ $key ] = $field['default_value'];
			}
			else {
				$validation_passed = false;
			}
		}
		if ( isset( $campaign_data['include'] ) ) {
			$campaign_data['include'] = array_filter( $campaign_data['include'], array( $this, 'page_selection_array_filter' ) ); // Array map with integer/archive/single
			// Requires campaign to define page(s) to be active on
			if ( empty( $campaign_data['include'] ) ) {
				$validation_passed = false;
			}
		}
		else {
			// Do not require
			$campaign_data['include'] = array();
		}
		if ( isset( $campaign_data['type'] ) ) {
			switch( $campaign_data['type'] ) {
				case 'modal': 
				case 'hello':
				case 'goodbye':
				case 'custom':
					$promotion_type = $campaign_data['type'];
					break;
				default:
					$validation_passed = false;
					$promotion_type = '';
			}
			$campaign_data['type'] = $promotion_type; // Ensure valid type
		}
		else {
			$validation_passed = false;
		}					
		if( false === $validation_passed ) {
			$return_value = false; 
		}
		else {
			$return_value = $campaign_data;
		}
		return $return_value;
	}
    function output_promotion_admin_settings_page() {
        // Put pretty markup here
        ?>
        <div id="icon-options-general" class="icon32"><br></div>
        <h1>Promotions Manager</h1>
        <?php
        if( $_GET['action'] == 'add' ) {
            if( $_GET['tab'] === '1' ) {
				// process_changes()
				// output_appropriate_screen()
				$this->process_promotion_campaign_properties( 'add' );
            }
            else if ( $_GET['tab'] === '2' ) {
				$this->process_promotion_campaign_content( 'add' );
            }
            else if ( $_GET['tab'] === '3' ) {
				$this->process_promotion_campaign_scoring( 'add' );
            }
        }
        else if( $_GET['action'] == 'edit' ) {
			// Filter "selected" var
				// (ensure is valid slug)
				$campaign_slug_valid = false;
				if ( isset ( $_GET['campaign'] ) ) {
					$campaign_slug = sanitize_title( $_GET['campaign'] );
					if ( $campaign_slug == $_GET['campaign'] ) {
						$campaign_slug_valid = true;   
					}
				}			
			if ( $campaign_slug_valid ) {
				// Check for existence of campaign
				$promotion_to_edit = $this->get_the_raw_promotion_by_slug( $campaign_slug );	
				//var_dump( $promotion_to_edit );
				// Set transient if not already defined
				if ( $promotion_to_edit ) {
					$underscored_slug = str_replace('-', '_', $_GET['campaign'] );
					if (  false === ( $promotion_edit_transient = get_transient( 'tdr_promotions_' . $underscored_slug ) ) ) {
						set_transient( 'tdr_promotions_' . $underscored_slug, $promotion_to_edit, 60*15 ); // Set transient for 15 minutes
						// Allow option to define how long to keep edit transient
					}
					if( $_GET['tab'] === '1' ) {
						$this->process_promotion_campaign_properties( 'edit' );
					}
					else if ( $_GET['tab'] === '2' ) {
						$this->process_promotion_campaign_content( 'edit' );
					}
					else if ( $_GET['tab'] === '3' ) {
						$this->process_promotion_campaign_scoring( 'edit' );
					}
				}
				else {
					echo ( '<div class="updated"><p>Could not find saved data for campaign</p></div>' );
				}			
			}
			else {
				echo( '<div class="updated"><p>Invalid campaign name to edit</p></div>' );
			}
        }
        else if ( $_GET['action'] === 'delete' ) {
			?>
			<h2>Delete Promotions</h2>
			<h3 class="nav-tab-wrapper"> 
				<a class="nav-tab" href="?page=tdr_promotions">Promotions Overview</a>
			</h3>
			<?php			
			$selected_campaigns_list = array_map( 'sanitize_title', $_GET['selected'] ); // Make sure is proper slug
			$selected_campaigns_list = array_filter( $selected_campaigns_list, array( $this, 'get_the_raw_promotion_by_slug' ) ); // Remove slugs for which saved campaigns cannot be found
			if ( !empty( $selected_campaigns_list ) ) { // Make sure at least one campaign was found
				if ( 'true' === $_GET['confirm'] ) {
					// Check is admin and such
					if ( current_user_can( 'manage_options' ) ) {
						// Get saved campaigns
						$promotion_list = get_option( 'thunder_promotions_list', array() ); // Get saved promotions
						foreach ( $selected_campaigns_list as $selected_campaign ) {
							unset( $promotion_list['campaigns'][ $selected_campaign ] ); // Unset campaigns to be removed
						}
						$delete_status = update_option( 'thunder_promotions_list', $promotion_list ); // Update campaign list
						if ( $delete_status ) {
							echo( '<div class="updated"><p>Successfully removed campaign(s).</p></div>' );
						}
						else {
							echo( '<div class="updated"><p>There was a problem removing the campaign(s).</p></div>' );
						}
					}
					else {
						echo( '<div class="updated"><p>You must be an administrator to remove campaigns.</p></div>' );
					}
				}
				else {
					echo 'You have opted to delete the following campaigns:';
					?>
					<ol>
					<?php foreach ( $selected_campaigns_list as $selected_campaign ) {
					?>
						<li>
							<?php $campaign = $this->get_the_decoded_promotion_by_slug( $selected_campaign ); ?>
							<?php echo $campaign['name']; ?>
						</li>
					<?php
					}
					?>
					</ol>
					<form method="get" action="admin.php">
						<input type="hidden" name="page" value="tdr_promotions" />
						<input type="hidden" name="action" value="delete" />
						<?php foreach ( $selected_campaigns_list as $selected_campaign ): ?>
							<input type="hidden" name="selected[]" value="<?php echo $selected_campaign; ?>" />
						<?php endforeach; ?>
						<input type="hidden" name="confirm" value="true" />
						<p>Would you like to proceed?</p>
						<a class="button" href="admin.php?page=tdr_promotions">No, get me out of here!</a>
						<button type="submit" class="button">Yes, delete permanently</button>
					</form>
				<?php
				}
			}
			// Report when no proper campaigns selected
			else {
				echo( '<div class="updated"><p>No valid (saved) campaigns selected for deletion.</p></div>' );
			}
        }
        else if ( $_GET['action'] === 'pause' ) {
			?>
			<h2>Pause Promotions</h2>
			<h3 class="nav-tab-wrapper"> 
				<a class="nav-tab" href="?page=tdr_promotions">Promotions Overview</a>
			</h3>
			<?php			
			$selected_campaigns_list = array_map( 'sanitize_title', $_GET['selected'] ); // Make sure is proper slug
			$selected_campaigns_list = array_filter( $selected_campaigns_list, array( $this, 'get_the_raw_promotion_by_slug' ) ); // Remove slugs for which saved campaigns cannot be found
			if ( !empty( $selected_campaigns_list ) ) { // Make sure at least one campaign was found
				if ( 'true' === $_GET['confirm'] ) {
					// Check is admin and such
					if ( current_user_can( 'manage_options' ) ) {
						// Get saved campaigns
						$promotion_list = get_option( 'thunder_promotions_list', array() ); // Get saved promotions
						$already_paused = false;
						foreach ( $selected_campaigns_list as $selected_campaign ) {
							if ( $promotion_list['campaigns'][ $selected_campaign ]['campaign_ready'] === true ) { // Pause campaigns if they are in production status
								$promotion_list['campaigns'][ $selected_campaign ]['campaign_ready'] = false; // Pause the campaign
							}
							else { // At least one campaign was already paused (in testing status)
								$already_paused = true;
							}
						}
						$pause_status = update_option( 'thunder_promotions_list', $promotion_list ); // Update campaign list
						if ( $pause_status ) { // Successfully paused
								echo( '<div class="updated"><p>Successfully paused campaign(s).</p></div>' );
						}
						else if ( $already_paused ) { // Already paused
							echo( '<div class="updated"><p>At least one campaign was already paused.</p></div>' );
						}
						else { // Error pausing campaign data
							echo( '<div class="updated"><p>There was a problem pausing the campaign(s).</p></div>' );
						}
					}
					else {
						echo( '<div class="updated"><p>You must be an administrator to pause campaigns.</p></div>' );
					}
				}
				else {
					echo 'You have opted to pause the following campaigns:';
					?>
					<ol>
					<?php foreach ( $selected_campaigns_list as $selected_campaign ) {
					?>
						<li>
							<?php $campaign = $this->get_the_decoded_promotion_by_slug( $selected_campaign ); ?>
							<?php echo $campaign['name']; ?>
						</li>
					<?php
					}
					?>
					</ol>
					<form method="get" action="admin.php">
						<input type="hidden" name="page" value="tdr_promotions" />
						<input type="hidden" name="action" value="pause" />
						<?php foreach ( $selected_campaigns_list as $selected_campaign ): ?>
							<input type="hidden" name="selected[]" value="<?php echo $selected_campaign; ?>" />
						<?php endforeach; ?>
						<input type="hidden" name="confirm" value="true" />
						<p>Would you like to proceed?</p>
						<a class="button" href="admin.php?page=tdr_promotions">No, get me out of here!</a>
						<button type="submit" class="button">Yes, pause now</button>
					</form>
				<?php
				}
			}
			// Report when no proper campaigns selected
			else {
				echo( '<div class="updated"><p>No valid (saved) campaigns selected for pausing.</p></div>' );
			}
        }
        else if ( $_GET['action'] === 'resume' ) {
			?>
			<h2>Pause Promotions</h2>
			<h3 class="nav-tab-wrapper"> 
				<a class="nav-tab" href="?page=tdr_promotions">Promotions Overview</a>
			</h3>
			<?php			
			$selected_campaigns_list = array_map( 'sanitize_title', $_GET['selected'] ); // Make sure is proper slug
			$selected_campaigns_list = array_filter( $selected_campaigns_list, array( $this, 'get_the_raw_promotion_by_slug' ) ); // Remove slugs for which saved campaigns cannot be found
			if ( !empty( $selected_campaigns_list ) ) { // Make sure at least one campaign was found
				if ( 'true' === $_GET['confirm'] ) {
					// Check is admin and such
					if ( current_user_can( 'manage_options' ) ) {
						// Get saved campaigns
						$promotion_list = get_option( 'thunder_promotions_list', array() ); // Get saved promotions
						$already_resumed = false;
						foreach ( $selected_campaigns_list as $selected_campaign ) {
							if ( $promotion_list['campaigns'][ $selected_campaign ]['campaign_ready'] === false ) { // Resume campaigns if they are in testing status
								$promotion_list['campaigns'][ $selected_campaign ]['campaign_ready'] = true; // Resume the campaign
							}
							else { // At least one campaign was already resumed (in production status)
								$already_resumed = true;
							}
						}
						$resume_status = update_option( 'thunder_promotions_list', $promotion_list ); // Update campaign list
						if ( $resume_status ) { // Successfully resumed
								echo( '<div class="updated"><p>Successfully resumed campaign(s).</p></div>' );
						}
						else if ( $already_resumed ) { // Already resumed
							echo( '<div class="updated"><p>At least one campaign was already resumed.</p></div>' );
						}
						else { // Error resuming campaign data
							echo( '<div class="updated"><p>There was a problem resuming the campaign(s).</p></div>' );
						}
					}
					else {
						echo( '<div class="updated"><p>You must be an administrator to resume campaigns.</p></div>' );
					}
				}
				else {
					echo 'You have opted to resume the following campaigns:';
					?>
					<ol>
					<?php foreach ( $selected_campaigns_list as $selected_campaign ) {
					?>
						<li>
							<?php $campaign = $this->get_the_decoded_promotion_by_slug( $selected_campaign ); ?>
							<?php echo $campaign['name']; ?>
						</li>
					<?php
					}
					?>
					</ol>
					<form method="get" action="admin.php">
						<input type="hidden" name="page" value="tdr_promotions" />
						<input type="hidden" name="action" value="resume" />
						<?php foreach ( $selected_campaigns_list as $selected_campaign ): ?>
							<input type="hidden" name="selected[]" value="<?php echo $selected_campaign; ?>" />
						<?php endforeach; ?>
						<input type="hidden" name="confirm" value="true" />
						<p>Would you like to proceed?</p>
						<a class="button" href="admin.php?page=tdr_promotions">No, get me out of here!</a>
						<button type="submit" class="button">Yes, resume now</button>
					</form>
				<?php
				}
			}
			// Report when no proper campaigns selected
			else {
				echo( '<div class="updated"><p>No valid (saved) campaigns selected for resuming.</p></div>' );
			}
        }
        else if ( $_GET['action'] === 'judge' ) {
			?>
			<h2>Judge Promotions</h2>
			<h3 class="nav-tab-wrapper"> 
				<a class="nav-tab" href="?page=tdr_promotions">Promotions Overview</a>
			</h3>
			<?php			
			$selected_campaigns_list = array_map( 'sanitize_title', $_GET['selected'] ); // Make sure is proper slug
			$selected_campaigns_list = array_filter( $selected_campaigns_list, array( $this, 'get_the_raw_promotion_by_slug' ) ); // Remove slugs for which saved campaigns cannot be found
			if ( !empty( $selected_campaigns_list ) ) { // Make sure at least one campaign was found
				if ( 'true' === $_GET['confirm'] ) {
					// Check is admin and such
					if ( current_user_can( 'manage_options' ) ) {
						// Get saved campaigns
						$promotion_list = get_option( 'thunder_promotions_list', array() ); // Get saved promotions
						$judging_error = false;
						$already_judged = false;
						$judging_results_array = array(); // Empty array to hold the results of each campaign
						foreach ( $selected_campaigns_list as $selected_campaign ) {
							if ( empty( $promotion_list['campaigns'][ $selected_campaign ]['winner'] ) ) { // Judge campaigns with no winner
								$judging_results = $this->get_the_promotion_winner( $selected_campaign ); // Do the judging
								if ( $judging_results ) { // Judging was successful
									$promotion_list['campaigns'][ $selected_campaign ]['winner'] = $judging_results['email']; // Set the winner to the email of the winner
									$judging_results['name'] = urldecode( $promotion_list['campaigns'][ $selected_campaign ]['name'] ); // Add campaign name to array
									$judging_results_array[] = $judging_results; // Push judging results for current contest to array
								}
								else { // At least one campaign had an issue being judged
									$judging_error = true;
								}
							}
							else { // At least one campaign was already judged
								$already_judged = true;
							}
						}
						$judge_status = update_option( 'thunder_promotions_list', $promotion_list ); // Update campaign list
						if ( $judge_status ) { // Successfully judged
								echo( '<div class="updated"><p>Successfully judged campaign(s).</p></div>' );
						}
						else if ( $judging_error ) { // Error judging
							echo( '<div class="updated"><p>At least one campaign had an issue being judged.</p></div>' );
						}
						else if ( $already_judged ) { // Already judged
							echo( '<div class="updated"><p>At least one campaign was already judged.</p></div>' );
						}
						else { // Error updating campaign data
							echo( '<div class="updated"><p>There was a problem judging the campaign(s).</p></div>' );
						}
						// Print judging info for all successfully judged campaigns
						echo '<ol>';
						foreach( $judging_results_array as $judging_results ) {
							?>
								<li>
									<strong><?php echo $judging_results['name']; ?></strong>
									<ul>
										<li>User-ID: <?php echo $judging_results['id']; ?></li>
										<li>Email: <?php echo $judging_results['email']; ?></li>
										<li>User Entries: <?php echo $judging_results['entries']; ?></li>
										<li>Entries in Contest: <?php echo $judging_results['contest_entries']; ?></li>
									</ul>
								</li>
							<?php
						}
						echo '</ol>';
					}
					else {
						echo( '<div class="updated"><p>You must be an administrator to judge campaigns.</p></div>' );
					}
				}
				else {
					echo 'You have opted to judge the following campaigns:';
					?>
					<ol>
					<?php foreach ( $selected_campaigns_list as $selected_campaign ) {
					?>
						<li>
							<?php $campaign = $this->get_the_decoded_promotion_by_slug( $selected_campaign ); ?>
							<?php echo $campaign['name']; ?>
						</li>
					<?php
					}
					?>
					</ol>
					<form method="get" action="admin.php">
						<input type="hidden" name="page" value="tdr_promotions" />
						<input type="hidden" name="action" value="judge" />
						<?php foreach ( $selected_campaigns_list as $selected_campaign ): ?>
							<input type="hidden" name="selected[]" value="<?php echo $selected_campaign; ?>" />
						<?php endforeach; ?>
						<input type="hidden" name="confirm" value="true" />
						<p>Would you like to proceed?</p>
						<a class="button" href="admin.php?page=tdr_promotions">No, get me out of here!</a>
						<button type="submit" class="button">Yes, judge now</button>
					</form>
				<?php
				}
			}
			// Report when no proper campaigns selected
			else {
				echo( '<div class="updated"><p>No valid (saved) campaigns selected for judging.</p></div>' );
			}
        }
        else {
			$this->output_promotion_overview_page();
        }
    }
    function process_promotion_campaign_properties( $action ) {
		// Prepare to check for form submission and merge with any saved data
		// Unless defined fields are found, do not process form data
		$form_submitted = false;
		foreach( $this->fields['page1'] as $field ) {
			$field_name = $field['form_name']; // Name in $_POST object
			$key = $field['store_name']; // Name of key in campaign data
			// Field was found in post data
			if ( isset( $_POST[ $field_name ] ) ) {
				// Urlencode posts if set in field's definition
				if ( $field['urlencoded'] ) {
					$_POST[ $field_name ] = urlencode( $_POST[ $field_name ] );
				}
				// Sanitize rule defined for field
				if ( $field['post_sanitize'] && is_callable( $field['post_sanitize'] ) ) {
					$_POST[ $field_name ] = call_user_func( $field['post_sanitize'], $_POST[ $field_name ] );
				}
				$post_array [ $key ] = $_POST[ $field_name ]; // Include found fields to be merged
				$form_submitted = true; // Flag the form as submitted since post data was found
			}
			elseif ( $field['allow_empty'] ) {
				$post_array[ $key ] = $field['default_value'];
			}
		}
		// Check form submissions
		if ( $form_submitted )  {
			// Get any saved data
			switch( $action ) {
				case 'add':
					$temp_campaign_data = $this->get_promotion_draft(); // NOTE: data is serialized automatically
					break;
				case 'edit': 
                    $campaign_slug = $_GET['campaign']; // Already filtered for all calls to this class method
        			$temp_campaign_data = $this->get_campaign_transient( $campaign_slug );
					break;
			} // Function not triggered when action is unexpected
						
			// Perform the merge if post data was submitted
			if ( $temp_campaign_data !== false ) {
				$campaign_data = array_merge( $temp_campaign_data, $post_array );
			}
			else if ( $temp_campaign_data === false ) {
				$campaign_data = $post_array;
			}			
			
			// Ensure all required on page are defined and valid
			$validation_passed = true;
			$campaign_data = $this->validate_fields( $campaign_data, 1 );
			if ( false === $campaign_data ) {
				$validation_passed = false;
			}
			
			if ( $validation_passed ) {
				// Switch on action
				// get temp data
				// if not set, set it.
				// if set, overwrite it.
				switch ( $action ) {
					case 'add':
						$this->update_promotion_draft( $campaign_data ); // TODO: optional- use return status
						break;
                    case 'edit':
						$this->update_promotion_transient( $campaign_data, $campaign_slug ); // TODO: optional- use return status
						break;
				} // No need for a default block
				
				// Redirect to next step if passed
				$this->output_promotion_campaign_content( $action );
			}
			else {
				// Else show form again with errors
				echo( '<div class="updated"><p>Please ensure the form is filled out properly, then resubmit</p></div>' );
				$this->output_promotion_campaign_properties( $action );
			}
		}
		else {
			$this->output_promotion_campaign_properties( $action );
		}		
	}
    function output_promotion_campaign_properties ( $action ) {
		// Fetch temp data based on action -- draft or transient
		switch( $action ) {
			case 'add':
				$temp_campaign_data = $this->get_promotion_draft();
				break;
            case 'edit':
                $campaign_slug = $_GET['campaign']; // Already filtered for all calls to this class method
                $temp_campaign_data = $this->get_campaign_transient( $campaign_slug );
                // Detect if changes have been made
                $original_data = $this->get_the_raw_promotion_by_slug( $campaign_slug );
                $edited_data = array_diff_assoc( $original_data, $temp_campaign_data );
                if ( $temp_campaign_data != $original_data ) { // Does not compare references but instead checks differences recursively
					echo( '<div class="updated"><p>You have made unsaved changes to this campaign.</div>' );
				}
				break;
		} // Function not triggered when action is unexpected		

		if ( !$temp_campaign_data ) {
			$campaign_data = array();
		}
		else {
			$campaign_data = $temp_campaign_data;
		}
	?>
		<!-- HIDE DATEPICKER SCRIPTS
		<script type="text/javascript">
		jQuery( document ).ready( function() {
			jQuery( 'input[name="start"], input[name="end"]' ).datepicker();
		});		
		</script>
		-->
		<h2>Step 1 of 3</h2>
		<h3 class="nav-tab-wrapper"> 
		    <a class="nav-tab" href="?page=tdr_promotions">Promotions Overview</a>
			<a class="nav-tab nav-tab-active" href="?page=tdr_promotions&action=<?php echo $action; ?>&tab=1<?php if( $action === 'edit' ) { echo '&campaign=' . $campaign_slug; } ?>">1. Campaign Properties</a>
			<a class="nav-tab" href="?page=tdr_promotions&action=<?php echo $action; ?>&tab=2<?php if( $action === 'edit' ) { echo '&campaign=' . $campaign_slug; } ?>">2. Campaign Content</a>
			<a class="nav-tab" href="?page=tdr_promotions&action=<?php echo $action; ?>&tab=3<?php if( $action === 'edit' ) { echo '&campaign=' . $campaign_slug; } ?>">3. Campaign Scoring</a>
		</h3>
        <form method="post" action="admin.php?page=tdr_promotions&action=<?php echo $action; if ( $action === 'edit' ) { echo( '&campaign=' . $campaign_slug ); } ?>&tab=1">
			<table>
			<tr>
				<td style="width: 100px;"><label>Name</label></td>
				<td><input type="text" name="promotion_name" value="<?php
				if ( !empty ( $campaign_data['name'] ) ) {
					echo stripslashes( urldecode( $campaign_data['name'] ) );
				} ?>" /></td>
			</tr>
			<tr>
				<td style="width: 100px;"><label>Campaign Slug</label></td>
				<td><input type="text" name="campaign_slug" value="<?php
				if ( !empty ( $campaign_data['campaign_slug'] ) ) {
					echo $campaign_data['campaign_slug'];
				} ?>" /></td>
			</tr>
			<tr>
				<td style="width: 100px;"><label>URL Slug</label></td>
				<td><input type="text" name="url_slug" value="<?php
				if ( !empty ( $campaign_data['url_slug'] ) ) {
					echo $campaign_data['url_slug'];
				} ?>" /></td>
			</tr>
			<tr>
				<td style="width: 100px;"><label>Twitter Hashtag (no #)</label></td>
				<td><input type="text" name="twitter_hashtag" value="<?php
				if ( !empty ( $campaign_data['twitter_hashtag'] ) ) {
					echo stripslashes( urldecode( $campaign_data['twitter_hashtag'] ) );
				} ?>" /></td>
			</tr>
			<!-- HIDE DATE FIELDS	
			<tr>
				<td><label>Start</label></td>
				<td><input type="text" name="start" value="<?php /*
				if ( !empty ( $campaign_data['start'] ) ) {
					echo date('m/d/Y', (int) $campaign_data['start'] );
				} */ ?>" /></td>
			</tr>
			<tr>
				<td><label>End</label></td>
				<td><input type="text" name="end" value="<?php /*
				if ( !empty ( $campaign_data['end'] ) ) {
					echo date('m/d/Y', (int) $campaign_data['end'] );
				} */ ?>" /></td>
            </tr>
            -->
            <tr>
                <td><label>Display after Conversion</label></td>
                <td><input type="checkbox" name="display_after_conversion" <?php 
                    if( !empty( $campaign_data['display_after_conversion'] ) ) {
                        echo( 'checked="checked"' );
                    }
                ?> value="1" /></td>
            </tr>
			<tr>
				<td><label>Impression Delay (in ms)</label></td>
				<td><input type="text" name="impression_delay" value="<?php
				if ( array_key_exists ( 'impression_delay', $campaign_data ) ) {
					echo $campaign_data['impression_delay'];
				} ?>" /></td>
            </tr>            
			<tr>
				<td><label>Display interval (in seconds)</label></td>
				<td><input type="text" name="cycle_time" value="<?php
				if ( array_key_exists ( 'cycle_time', $campaign_data ) ) {
					echo $campaign_data['cycle_time'];
				} ?>" /></td>
            </tr>
			<tr>
				<td><label>Contact list ID</label></td>
				<td><input type="text" name="contact_list_id" value="<?php
				if ( array_key_exists ( 'contact_list_id', $campaign_data ) ) {
					echo $campaign_data['contact_list_id'];
				} ?>" /></td>
            </tr>
			<tr>
				<td><label>Contact group ID</label></td>
				<td><input type="text" name="contact_group_id" value="<?php
				if ( array_key_exists ( 'contact_group_id', $campaign_data ) ) {
					echo $campaign_data['contact_group_id'];
				} ?>" /></td>
            </tr>
			<tr>
				<td><label>Verified contact group ID</label></td>
				<td><input type="text" name="verified_contact_group_id" value="<?php
				if ( array_key_exists ( 'verified_contact_group_id', $campaign_data ) ) {
					echo $campaign_data['verified_contact_group_id'];
				} ?>" /></td>
            </tr>
			<tr>
				<td><label>Capped entries contact group ID</label></td>
				<td><input type="text" name="capped_contact_group_id" value="<?php
				if ( array_key_exists ( 'capped_contact_group_id', $campaign_data ) ) {
					echo $campaign_data['capped_contact_group_id'];
				} ?>" /></td>
            </tr>
			<tr>
				<td><label>Pages</label></td>
				<td>
					<div style="overflow: auto; width: 400px; height: 300px;">
						<input type="checkbox" name="include[]" value="single" <?php
							if ( !empty( $campaign_data['include'] ) ) {
								if ( in_array( 'single', $campaign_data['include'] ) ) {
									echo ( 'checked="checked"' );
								}
							}
						?> />
						<label>Single (posts)</label><br>
						<input type="checkbox" name="include[]" value="archive" <?php
							if ( !empty( $campaign_data['include'] ) ) {
								if ( in_array( 'archive', $campaign_data['include'] ) ) {
									echo ( 'checked="checked"' );
								}
							}
						?> />
						<label>Archive</label><br>
						<?php
						$get_pages_args = array();
						$page_list = get_pages( $get_pages_args );
						foreach ( $page_list as $page ) {
						?>
						<input type="checkbox" name="include[]"
						value="<?php echo $page->{"ID"}; ?>" <?php
							if ( !empty( $campaign_data['include'] ) ) {
								if ( in_array( $page->{"ID"}, $campaign_data['include'] ) ) {
									echo ( 'checked="checked"' );
								}
							}
						?> />
						<?php echo "<label>" . $page->{"post_title"} . "</label><br>"; ?>
						<?php
						}
						/*if( is_array( $important_categories ) ) {
							if( in_array( $category->{"cat_ID"}, $important_categories ) ) {
								echo 'checked="checked"';
							}
						}*/
						?>
					</div>
				</td>
			</tr>
			<tr>
				<td><label>Type</label></td>
				<td>
					<select name="type">
						<option value="modal" <?php 
						if ( !empty( $campaign_data['type'] ) ) {
							if ( $campaign_data['type'] === 'modal' ) {
								echo( 'selected="selected"' );
							}
						}
						?>>Modal Popup</option>
						<option value="hello" <?php 
						if ( !empty( $campaign_data['type'] ) ) {
							if ( $campaign_data['type'] === 'hello' ) {
								echo( 'selected="selected"' );
							}
						}
						?>>Hello bar</option>
						<option value="goodbye" <?php 
						if ( !empty( $campaign_data['type'] ) ) {
							if ( $campaign_data['type'] === 'goodbye' ) {
								echo( 'selected="selected"' );
							}
						}
						?>>Goodbye bar</option>
						<option value="custom" <?php 
						if ( !empty( $campaign_data['type'] ) ) {
							if ( $campaign_data['type'] === 'custom' ) {
								echo( 'selected="selected"' );
							}
						}
						?>>Custom</option>
					</select>
				</td>
			</tr>
			</table>
			<button type="submit" class="button">Next</button>    
		</form>
		<?php
		echo('<p>You can edit this information again later</p>');		
	}
    function process_promotion_campaign_content( $action ) {
		// Prepare to check for form submission and merge with any saved data
		// Unless defined fields are found, do not process form data
		$form_submitted = false;
		foreach ( $this->fields['page2'] as $field ) {
			$field_name = $field['form_name'];
			$key = $field['store_name'];
			if ( isset ( $_POST[ $field_name ] ) ) {
				$_POST[ $field_name ] = urlencode( $_POST[ $field_name ] ); // Urlencode all POSTed variables for this page
				$post_array [ $key ] = $_POST[ $field_name ]; // Include found fields to be merged
				$form_submitted = true; // Flag the form as submitted since post data was found
			}
		}		
		// Check form submissions
		if ( $form_submitted )  {
			// Get any saved data
			switch( $action ) {
				case 'add':
					$temp_campaign_data = $this->get_promotion_draft(); // NOTE: data is serialized automatically
					break;
				case 'edit':
                    $campaign_slug = $_GET['campaign']; // Already filtered for all calls to this class method
    				$temp_campaign_data = $this->get_campaign_transient( $campaign_slug );
					break;
			} // Function not triggered when action is unexpected
				
			// Perform the merge if post data was submitted
			if ( $temp_campaign_data !== false ) {
				$campaign_data = array_merge( $temp_campaign_data, $post_array );
			}
			else if ( $temp_campaign_data === false ) {
				$campaign_data = $post_array;
			}			
			
			// Ensure all required on page are defined and valid
			$validation_passed = true;		
		
			// Validate $campaign_data on all required keys from current page
			// Look for presence of other keys
			$available_field_keys = array();
			// Loop through the first two pages of fields
			for ( $i = 1; $i < 3; $i++ ) {
				foreach ( $this->fields['page' . $i ] as $field ) {
					$available_field_keys[] = $field['store_name']; // Add field name to available fields key list
				}
			}
			$campaign_data_keys = array_keys( $campaign_data );
			$missing_fields = array_diff_key( $available_field_keys, $campaign_data_keys );
			if ( !empty( $missing_fields ) ) { // All optional fields are at least included even if blank
				$validation_passed = false;
			}
			// signup and confirmation class, header, body, footer, text, plus ( name, start, end, include, type )

			// Ensure all required on page are defined and valid
			$validation_passed = true;
			$campaign_data = $this->validate_fields( $campaign_data, 2 );
			if ( false === $campaign_data ) {
				$validation_passed = false;
			}

			// Save form if valid
			if ( $validation_passed ) {
				// Switch on action
				// get temp data
				// if not set, set it.
				// if set, overwrite it.
				switch ( $action ) {
					case 'add':
						$this->update_promotion_draft( $campaign_data ); // TODO: optional- use return status
						break;
                    case 'edit':
						$this->update_promotion_transient( $campaign_data, $campaign_slug ); // TODO: optional- use return status
						break;
				} // No need for a default block
				
				// Redirect to next step if passed
				$this->output_promotion_campaign_scoring( $action );
			}
			// Report failure
			else {
				echo( '<div class="updated"><p>Please ensure all fields are filled out</p></div>' );
				$this->output_promotion_campaign_content( $action );
			}
		}
		else {
			$this->output_promotion_campaign_content( $action );
		}	
	}
	function output_promotion_campaign_content( $action ) {
		// Fetch temp data based on action -- draft or transient
		switch( $action ) {
			case 'add':
				$temp_campaign_data = $this->get_promotion_draft();
				break;
			case 'edit':
                $campaign_slug = $_GET['campaign']; // Already filtered for all calls to this class method
    			$temp_campaign_data = $this->get_campaign_transient( $campaign_slug );
                // Detect if changes have been made
                $original_data = $this->get_the_raw_promotion_by_slug( $campaign_slug );
                $edited_data = array_diff_assoc( $original_data, $temp_campaign_data );
                if ( $temp_campaign_data != $original_data ) { // Does not compare references but instead checks differences recursively
					echo( '<div class="updated"><p>You have made unsaved changes to this campaign.</div>' );
				}
				break;
		} // Function not triggered when action is unexpected		

		if ( !$temp_campaign_data ) {
			$campaign_data = array();
		}
		else {
			$campaign_data = $temp_campaign_data;
		}
		?>
		<h2>Step 2 of 3</h2>
		<h3 class="nav-tab-wrapper">
		    <a class="nav-tab" href="?page=tdr_promotions">Promotions Overview</a> 
			<a class="nav-tab" href="?page=tdr_promotions&action=<?php echo $action; ?>&tab=1<?php if( $action === 'edit' ) { echo '&campaign=' . $campaign_slug; } ?>">1. Campaign Properties</a>
			<a class="nav-tab nav-tab-active" href="?page=tdr_promotions&action=<?php echo $action; ?>&tab=2<?php if( $action === 'edit' ) { echo '&campaign=' . $campaign_slug; } ?>">2. Campaign Content</a>
			<a class="nav-tab" href="?page=tdr_promotions&action=<?php echo $action; ?>&tab=3<?php if( $action === 'edit' ) { echo '&campaign=' . $campaign_slug; } ?>">3. Campaign Scoring</a>
		</h3>
        <form method="post" action="admin.php?page=tdr_promotions&action=<?php echo $action; if ( $action === 'edit' ) { echo( '&campaign=' . $campaign_slug ); } ?>&tab=2">
			<h3>Sign up values</h3>
			<table style="width: 90%;">
			<tr>
				<td style="width: 150px;"><label>Class</label></td>
				<td><input type="text" name="signup_class" value="<?php
				if ( !empty ( $campaign_data['signup_class'] ) ) {
					echo stripslashes( urldecode( $campaign_data['signup_class'] ) );
				} ?>" /></td>
			</tr>
			<tr>
				<td><label>Header</label></td>
				<td><textarea name="signup_header" class="widefat"><?php
					if ( !empty( $campaign_data['signup_header'] ) ) {
						echo stripslashes( urldecode( $campaign_data['signup_header'] ) );
					}
				?></textarea></td>
			</tr>
			<tr>
				<td><label>Body</label></td>
				<td><textarea name="signup_body" class="widefat"><?php
					if ( !empty( $campaign_data['signup_body'] ) ) {
						echo stripslashes(  urldecode( $campaign_data['signup_body'] ) );
					}
				?></textarea></td>
			</tr>
			<tr>
				<td><label>Footer</label></td>
				<td><textarea name="signup_footer" class="widefat"><?php
					if ( !empty( $campaign_data['signup_footer'] ) ) {
						echo stripslashes( urldecode( $campaign_data['signup_footer'] ) );
					}
				?></textarea></td>
			</tr>
			<tr>
				<td><label>Text (custom)</label></td>
				<td><textarea name="signup_custom_text" class="widefat"><?php
					if ( !empty( $campaign_data['signup_custom_text'] ) ) {
						echo stripslashes( urldecode( $campaign_data['signup_custom_text'] ) );
					}
				?></textarea></td>
			</tr>
			</table>
			<h3>Confirmation values</h3>
			<table style="width: 90%;">
			<tr>
				<td style="width: 150px;"><label>Class</label></td>
				<td><input type="text" name="confirmation_class" value="<?php
				if ( !empty ( $campaign_data['confirmation_class'] ) ) {
					echo stripslashes( urldecode( $campaign_data['confirmation_class'] ) );
				} ?>" /></td>
			</tr>
			<tr>
				<td><label>Header</label></td>
				<td><textarea name="confirmation_header" class="widefat"><?php
					if ( !empty( $campaign_data['confirmation_header'] ) ) {
						echo stripslashes( urldecode( $campaign_data['confirmation_header'] ) );
					}
				?></textarea></td>
			</tr>
			<tr>
				<td><label>Body</label></td>
				<td><textarea name="confirmation_body" class="widefat"><?php
					if ( !empty( $campaign_data['confirmation_body'] ) ) {
						echo stripslashes(  urldecode( $campaign_data['confirmation_body'] ) );
					}
				?></textarea></td>
			</tr>
			<tr>
				<td><label>Footer</label></td>
				<td><textarea name="confirmation_footer" class="widefat"><?php
					if ( !empty( $campaign_data['confirmation_footer'] ) ) {
						echo stripslashes( urldecode( $campaign_data['confirmation_footer'] ) );
					}
				?></textarea></td>
			</tr>
			<tr>
				<td><label>Text (custom)</label></td>
				<td><textarea name="confirmation_custom_text" class="widefat"><?php
					if ( !empty( $campaign_data['confirmation_custom_text'] ) ) {
						echo stripslashes( urldecode( $campaign_data['confirmation_custom_text'] ) );
					}
				?></textarea></td>
			</tr>
			<tr>
				<td><label>Facebook Text</label></td>
				<td><textarea name="confirmation_facebook_text" class="widefat"><?php
					if ( !empty( $campaign_data['confirmation_facebook_text'] ) ) {
						echo stripslashes( urldecode( $campaign_data['confirmation_facebook_text'] ) );
					}
				?></textarea></td>
			</tr>
			<tr>
				<td><label>Twitter Text</label></td>
				<td><textarea name="confirmation_twitter_text" class="widefat"><?php
					if ( !empty( $campaign_data['confirmation_twitter_text'] ) ) {
						echo stripslashes( urldecode( $campaign_data['confirmation_twitter_text'] ) );
					}
				?></textarea></td>
			</tr>
			<tr>
				<td><label>Email Text</label></td>
				<td><textarea name="confirmation_email_text" class="widefat"><?php
					if ( !empty( $campaign_data['confirmation_email_text'] ) ) {
						echo stripslashes( urldecode( $campaign_data['confirmation_email_text'] ) );
					}
				?></textarea></td>
			</tr>
			</table>
			<h3>Contest Page Content</h3>
			<table style="width: 90%;">
			<tr>
				<td style="width: 150px;"><label>Contest Page Signup</label></td>
				<td><textarea name="contest_page_signup" class="widefat"><?php
					if ( !empty( $campaign_data['contest_page_signup'] ) ) {
						echo stripslashes( urldecode( $campaign_data['contest_page_signup'] ) );
					}
				?></textarea></td>
			</tr>
			<tr>
				<td><label>Contest Page Confirmation</label></td>
				<td><textarea name="contest_page_confirmation" class="widefat"><?php
					if ( !empty( $campaign_data['contest_page_confirmation'] ) ) {
						echo stripslashes( urldecode( $campaign_data['contest_page_confirmation'] ) );
					}
				?></textarea></td>
			</tr>
			<tr>
				<td><label>Facebook Text</label></td>
				<td><textarea name="contest_page_facebook_text" class="widefat"><?php
					if ( !empty( $campaign_data['contest_page_facebook_text'] ) ) {
						echo stripslashes( urldecode( $campaign_data['contest_page_facebook_text'] ) );
					}
				?></textarea></td>
			</tr>
			<tr>
				<td><label>Twitter Text</label></td>
				<td><textarea name="contest_page_twitter_text" class="widefat"><?php
					if ( !empty( $campaign_data['contest_page_twitter_text'] ) ) {
						echo stripslashes( urldecode( $campaign_data['contest_page_twitter_text'] ) );
					}
				?></textarea></td>
			</tr>
			<tr>
				<td><label>Referral Text</label></td>
				<td><textarea name="contest_page_referral_text" class="widefat"><?php
					if ( !empty( $campaign_data['contest_page_referral_text'] ) ) {
						echo stripslashes( urldecode( $campaign_data['contest_page_referral_text'] ) );
					}
				?></textarea></td>
			</tr>	
			<tr>
				<td><label>Gutter (fine print)</label></td>
				<td><textarea name="contest_page_gutter" class="widefat"><?php
					if ( !empty( $campaign_data['contest_page_gutter'] ) ) {
						echo stripslashes( urldecode( $campaign_data['contest_page_gutter'] ) );
					}
				?></textarea></td>
			</tr>			
			</table>
			<h3>Contest Tracker Content</h3>
			<table style="width: 90%;">
			<tr>
				<td style="width: 150px;"><label>Body</label></td>
				<td><textarea name="contest_tracker_body" class="widefat"><?php
					if ( !empty( $campaign_data['contest_tracker_body'] ) ) {
						echo stripslashes( urldecode( $campaign_data['contest_tracker_body'] ) );
					}
				?></textarea></td>
			</tr>		
			</table>
			<h3>Contest Twitter Sharing</h3>
			<h5>Mail merge tags: {contest_name}, {contest_slug}, {contest_url}, {entry_code}, {hashtag}, {site_name}</h5>
			<h5>Twitter scraper needs {contest_url}?ref={entry_code} and {hashtag}</h5>
			<table style="width: 90%;">
			<tr>
				<td style="width: 150px;"><label>Tweet Text</label></td>
				<td><textarea name="tweet_text" class="widefat"><?php
					if ( !empty( $campaign_data['tweet_text'] ) ) {
						echo stripslashes( urldecode( $campaign_data['tweet_text'] ) );
					}
				?></textarea></td>
			</tr>		
			</table>
			<h3>Contest Email Sharing</h3>
			<h5>Mail merge tags: {contest_name}, {contest_slug}, {contest_url}, {entry_code}</h5>
			<table style="width: 90%;">
			<tr>
				<td style="width: 150px;"><label>Subject</label></td>
				<td><textarea name="email_subject" class="widefat"><?php
					if ( !empty( $campaign_data['email_subject'] ) ) {
						echo stripslashes( urldecode( $campaign_data['email_subject'] ) );
					}
				?></textarea></td>
			</tr>
			<tr>
				<td><label>Body</label></td>
				<td><textarea name="email_body" class="widefat"><?php
					if ( !empty( $campaign_data['email_body'] ) ) {
						echo stripslashes( urldecode( $campaign_data['email_body'] ) );
					}
				?></textarea></td>
			</tr>			
			</table>
			<h3>Facebook Share Popup</h3>
			<table style="width: 90%;">
			<tr>
				<td style="width: 150px;"><label>Title</label></td>
				<td><textarea name="facebook_share_title" class="widefat"><?php
					if ( !empty( $campaign_data['facebook_share_title'] ) ) {
						echo stripslashes( urldecode( $campaign_data['facebook_share_title'] ) );
					}
				?></textarea></td>
			</tr>
			<tr>
				<td><label>Summary</label></td>
				<td><textarea name="facebook_share_summary" class="widefat"><?php
					if ( !empty( $campaign_data['facebook_share_summary'] ) ) {
						echo stripslashes( urldecode( $campaign_data['facebook_share_summary'] ) );
					}
				?></textarea></td>
			</tr>
			<tr>
				<td><label>Image</label></td>
				<td><textarea name="facebook_share_image" class="widefat"><?php
					if ( !empty( $campaign_data['facebook_share_image'] ) ) {
						echo stripslashes( urldecode( $campaign_data['facebook_share_image'] ) );
					}
				?></textarea></td>
			</tr>		
			</table>
			<button type="submit" class="button">Next</button>    
		</form>

		<?php		
	}
	function process_promotion_campaign_scoring ( $action ) {
		// Prepare to check for form submission and merge with any saved data
		// Unless defined fields are found, do not process form data
		$form_submitted = false;
		foreach ( $this->fields['page3'] as $field ) {
			$field_name = $field['form_name'];
			$key = $field['store_name'];
			if ( isset ( $_POST[ $field_name ] ) ) {
				$_POST[ $field_name ] = filter_input( INPUT_POST, $field_name, FILTER_VALIDATE_INT ); // Verify all POSTed variables for this page are integers
				$post_array [ $key ] = $_POST[ $field_name ]; // Include found fields to be merged
				$form_submitted = true; // Flag the form as submitted since post data was found
			}
		}		
		
		// Check form submissions
		if ( $form_submitted )  {
			// Get any saved data
			switch( $action ) {
				case 'add':
					$temp_campaign_data = $this->get_promotion_draft(); // NOTE: data is serialized automatically
					break;
				case 'edit':
                    $campaign_slug = $_GET['campaign']; // Already filtered for all calls to this class method
    				$temp_campaign_data = $this->get_campaign_transient( $campaign_slug );
					break;
			} // Function not triggered when action is unexpected	
			// Perform the merge if post data was submitted
			if ( $temp_campaign_data !== false ) {
				$campaign_data = array_merge( $temp_campaign_data, $post_array );
			}
			else if ( $temp_campaign_data === false ) {
				$campaign_data = $post_array;
			}			
			
			// Ensure all required on page are defined and valid
			$validation_passed = true;		
		
			// Validate $campaign_data on all required keys from current page
			// Look for presence of other keys
			$available_field_keys = array();
			// Loop through the first three pages of fields
			for ( $i = 1; $i < 4; $i++ ) {
				foreach ( $this->fields['page' . $i ] as $field ) {
					$available_field_keys[] = $field['store_name']; // Add field name to available fields key list
				}
			}
			$campaign_data_keys = array_keys( $campaign_data );
			$missing_fields = array_diff_key( $available_field_keys, $campaign_data_keys );
			if ( !empty( $missing_fields ) ) { // All optional fields are at least included even if blank
				$validation_passed = false;
			}
			// signup_entries, share_facebook, share_twitter, share_google_plus, share_email, and referrals, plus (signup and confirmation class, header, body, footer, text, name, start, end, include, type )
			
			// Ensure all required on page are defined and valid
			$validation_passed = true;
			$campaign_data = $this->validate_fields( $campaign_data, 3 );
			if ( false === $campaign_data ) {
				$validation_passed = false;
			}
			
			// Save form if valid
			if ( $validation_passed ) {
				// Get current promotions
				$promotion_list = get_option( 'thunder_promotions_list', array() ); // Get saved promotions
				// Slugify current campaign
					// Disabled: Ensures suggested slug starts with a letter (wordpress allows slugs to start with a number)
					$suggested_slug = $campaign_data['campaign_slug'];
					$suggested_slug = sanitize_title( $suggested_slug ); // Already done in POST sanitation callback
					// Slug is not strictly numeric and has at least two characters
					if ( ( is_numeric( $suggested_slug ) ) || ( strlen( $suggested_slug ) < 2 ) ) {
						$suggested_slug = false;
					}
					// Disabled: No starting with numbers
						/*
						// Check to see that slug starts with a letter
						while ( !ctype_alpha( substr( $suggested_slug, 0, 1 ) ) ) {
							if ( strlen( $suggested_slug == 1 ) ) {
								$suggested_slug = false;
								break;
							}
							$suggested_slug = substr( $suggested_slug, 1 );
						}
						*/
				// Make sure slug does not exist if new or remapping
				if ( $suggested_slug ) {
                    $slug_conflict = false;
                    // Include current campaign
                    if ( $action === 'add' ) { // Not combined conditional statement in case other logic is added below
                        if ( array_key_exists( $suggested_slug, $promotion_list['campaigns'] ) ) { // New campaign has slug conflict with existing campaign
                            $slug_conflict = true;
                        }
                    }
                    else if ( $action === 'edit' ) { // Not combined conditional statement in case other logic is added below
                        if ( $suggested_slug != $campaign_slug ) { // Slug has changed on edited campaigns
							if ( array_key_exists( $suggested_slug, $promotion_list['campaigns'] ) ) { // Slug in use by existing campaign
								$slug_conflict = true;
							}  
							else {                    
								unset( $promotion_list['campaigns'][ $campaign_slug ] ); // Remove old slug
							}
                        }
                    }
                    if ( !$slug_conflict ) {
						// Winner field not in campaign data
						if( ! array_key_exists( 'winner', $campaign_data ) ) {
							$campaign_data['winner'] = ''; // Add blank winner field to campaign
						}
						// Status field not in campaign data
						if( ! array_key_exists( 'campaign_ready', $campaign_data ) ) {
							$campaign_data['campaign_ready'] = false; // Set to testing status
						}
                        $promotion_list['campaigns'][ $suggested_slug ] = $campaign_data; // Set campaign in promotions list
	    				update_option( 'thunder_promotions_list', $promotion_list );
		    			// Remove temporary draft/transient
			            switch( $action ) {
                            case 'add':
                                $this->remove_promotion_draft(); // Remove draft
                                $success_message = 'Campaign successfully created.';
                                break;
                            case 'edit':
                                $this->remove_promotion_transient( $campaign_slug ); // Remove transient (uses original slug)
                                $success_message = 'Campaign successfully updated.';
                                if ( $suggested_slug != $campaign_slug ) {
									$success_message .= ' <strong>Warning:</strong> You renamed the campaign. CSS selectors, scraper transients, campaign-specific contest template-names, contest user accounts and role, and contactology custom fields require attention.';
								}
                                break;
                        }    
    					echo( '<div class="updated"><p>' . $success_message . '</p></div>' );
	    				$this->output_promotion_overview_page();
                    }
                    else {
                        echo ( '<div class="updated"><p>There was a naming conflict with an existing campaign. Please choose a new slug for your campaign.</p></div>' );
                        $this->output_promotion_campaign_scoring( $action );
                    }
				}
				else {
                    echo( '<div class="updated"><p>Please ensure campaign slug is at least two characters and not soley a number, then try again.</p></div>' );
                    $this->output_promotion_campaign_scoring( $action );
				}
			}
			// Report failure
			else {
				echo( '<div class="updated"><p>Please ensure all fields are filled out</p></div>' );
				$this->output_promotion_campaign_scoring( $action );
			}
		}
		else {
			$this->output_promotion_campaign_scoring( $action );
		}
	}
	function output_promotion_campaign_scoring ( $action ) {
		// Fetch temp data based on action -- draft or transient
		switch( $action ) {
			case 'add':
				$temp_campaign_data = $this->get_promotion_draft();
				break;
			case 'edit':
                $campaign_slug = $_GET['campaign']; // Already filtered for all calls to this class method
    			$temp_campaign_data = $this->get_campaign_transient( $campaign_slug );
                // Detect if changes have been made
                $original_data = $this->get_the_raw_promotion_by_slug( $campaign_slug );
                $edited_data = array_diff_assoc( $original_data, $temp_campaign_data );
                if ( $temp_campaign_data != $original_data ) { // Does not compare references but instead checks differences recursively
					echo( '<div class="updated"><p>You have made unsaved changes to this campaign.</div>' );
				}
				break;
		} // Function not triggered when action is unexpected		

		if ( !$temp_campaign_data ) {
			$campaign_data = array();
		}
		else {
			$campaign_data = $temp_campaign_data;
		}
		?>
		<h2>Step 3 of 3</h2>
		<h3 class="nav-tab-wrapper">
		    <a class="nav-tab" href="?page=tdr_promotions">Promotions Overview</a> 
			<a class="nav-tab" href="?page=tdr_promotions&action=<?php echo $action; ?>&tab=1<?php if( $action === 'edit' ) { echo '&campaign=' . $campaign_slug; } ?>">1. Campaign Properties</a>
			<a class="nav-tab" href="?page=tdr_promotions&action=<?php echo $action; ?>&tab=2<?php if( $action === 'edit' ) { echo '&campaign=' . $campaign_slug; } ?>">2. Campaign Content</a>
			<a class="nav-tab nav-tab-active" href="?page=tdr_promotions&action=<?php echo $action; ?>&tab=3<?php if( $action === 'edit' ) { echo '&campaign=' . $campaign_slug; } ?>">3. Campaign Scoring</a>
		</h3>
        <form method="post" action="admin.php?page=tdr_promotions&action=<?php echo $action; if ( $action === 'edit' ) { echo( '&campaign=' . $campaign_slug ); } ?>&tab=3">
			<h3>Campaign Limits</h3>
			<table style="width: 90%;">
			<tr>
				<td style="width: 150px;"><label>Maximum Entries (per person)</label></td>
				<td><input type="text" name="entry_limit" value="<?php
				if ( is_numeric( $campaign_data['entry_limit'] ) ) {
					echo $campaign_data['entry_limit'];
				} ?>" /></td>
			</tr>
			<tr>
				<td style="width: 150px;"><label>Tweets (per day)</label></td>
				<td><input type="text"  name="tweet_limit_per_day" value="<?php
				if ( is_numeric( $campaign_data['tweet_limit_per_day'] ) ) {
					echo $campaign_data['tweet_limit_per_day'];
				} ?>" /></td>
			</tr>
			</table>
			<h3>Entrant Signup</h3>
			<table style="width: 90%;">
			<tr>
				<td style="width: 150px;"><label>Conversion</label></td>
				<td><input type="text" name="signup_entries" value="<?php
				if ( is_numeric( $campaign_data['signup_entries'] ) ) {
					echo $campaign_data['signup_entries'];
				} ?>" /></td>
			</tr>
			</table>
			<h3>Sharing</h3>
			<table style="width: 90%;">
			<tr>
				<td style="width: 150px;"><label>Facebook</label></td>
				<td><input type="text" name="share_facebook" value="<?php
				if ( is_numeric( $campaign_data['share_facebook'] ) ) {
					echo $campaign_data['share_facebook'];
				} ?>" /></td>
			</tr>
			<tr>
				<td style="width: 150px;"><label>Twitter</label></td>
				<td><input type="text"  name="share_twitter" value="<?php
				if ( is_numeric( $campaign_data['share_twitter'] ) ) {
					echo $campaign_data['share_twitter'];
				} ?>" /></td>
			</tr>
			<tr>
				<td style="width: 150px;"><label>Google+</label></td>
				<td><input type="text"  name="share_google_plus" value="<?php
				if ( is_numeric( $campaign_data['share_google_plus'] ) ) {
					echo $campaign_data['share_google_plus'];
				} ?>" /></td>
			</tr>
			<tr>
				<td style="width: 150px;"><label>Email</label></td>
				<td><input type="text"  name="share_email" value="<?php
				if ( is_numeric( $campaign_data['share_email'] ) ) {
					echo $campaign_data['share_email'];
				} ?>" /></td>
			</tr>		
			</table>
			<h3>Referrals</h3>
			<table style="width: 90%;">
			<tr>
				<td style="width: 150px;"><label>Conversion</label></td>
				<td><input type="text"  name="referrals" value="<?php
				if ( is_numeric( $campaign_data['referrals'] ) ) {
					echo $campaign_data['referrals'];
				} ?>" /></td>
			</tr>
			</table>
			<button type="submit" class="button">Save/Finish</button>    
		</form>

		<?php
	}
	function output_promotion_overview_page() {
        ?>
		<h2>Promotions Overview</h2>
		<h3 class="nav-tab-wrapper"> 
		    <a class="nav-tab" href="?page=tdr_promotions">Promotions Overview</a>
		</h3>
        <p>
            <form method="post" action="admin.php?page=tdr_promotions&action=add&tab=1">
            <button type="submit" class="button">Add new</button>    
            </form>
        </p>
        <form method="get" action="admin.php?page=tdr_promotions">
        <table>
            <thead>
                <th style="width:80px;">Selected</th>
                <th style="width:120px;">Campaign name</th>
                <th style="width:100px;">Status</th>
                <th style="width:100px;">Type</th><!--
                <th style="width:100px;">Start</th>
                <th style="width:100px;">End</th>
                -->
                <th style="width:100px;">Limit</th>
                <th style="width:100px;">Impressions</th>
                <th style="width:100px;">Entries</th>
                <th style="width:100px;">Conversion %</th>
                <th style="width:100px;">Winner</th>
            </thead>
            <tbody>
            <?php
                $promotion_list = $this->get_the_decoded_promotions();
                foreach ( $promotion_list as $promotion_slug => $promotion_data ) {
                    ?>
                    <tr>
                        <td style="text-align:center;"><input type="checkbox" name="selected[]" value="<?php echo $promotion_slug; ?>" /></td>   
                        <td style="text-align:center;"><a href="admin.php?page=tdr_promotions&action=edit&tab=1&campaign=<?php echo $promotion_slug; ?>"><?php echo $promotion_data['name']; ?></a></td>
                        <td style="text-align:center;"><?php if ( $promotion_data['campaign_ready'] ) { echo 'Production'; } else { echo 'Testing'; } ?></td>
                        <td style="text-align:center;"><?php echo $promotion_data['type']; ?></td>
						<!-- START DATE COMMENTED OUT
						<td style="text-align:center;"><?php /* if ( !empty ( $promotion_data['start'] ) ) {
							echo date('m/d/Y', (int) $promotion_data['start'] ); } else { echo '&mdash;'; } */ ?></td>
						END START DATE -->
						<!-- END DATE COMMENTED OUT
						<td style="text-align:center;"><?php /* if ( !empty ( $promotion_data['end'] ) ) {
							echo date('m/d/Y', (int) $promotion_data['end'] ); } else { echo '&mdash;'; } */ ?></td> 
						END START DATE -->
                        <td style="text-align:center;"><?php if( !empty( $promotion_data['entry_limit'] ) ) {
							echo $promotion_data['entry_limit'] . '/person'; } else { echo 'n/a'; } ?></td>
                        <td style="text-align:center;">n/a<?php /* echo $promotion_data['views']; */ ?></td>
                        <td style="text-align:center;"><?php /* echo $promotion_data['entries']; */
							$contest_user_query_args = array(
								'role' => 'promo-' . $promotion_slug,
							);
							// Perform the user query
							$contest_user_query = new WP_User_Query( $contest_user_query_args );
							// If results found, fetch them
							echo $contest_user_query->total_users; 
                        ?></td>
                        <td style="text-align:center;">n/a<?php /* echo $promotion_data['conversion']; // or just calculate based on above */ ?></td>
                        <td style="text-align:center;"><?php if( empty( $promotion_data['winner'] ) ) { echo 'TBA'; } else { echo $promotion_data['winner']; } ?> </td>
                    </tr>
                    <?php
                }
            ?>
            </tbody>
        </table>
        <select name="action">
			<option value="">Select an action</option>
            <option value="delete">Delete selected</option>
            <option value="pause">Pause selected</option>
            <option value="resume">Resume selected</option>
            <option value="judge">Judge selected</option>
        </select>
        <input type="hidden" name="page" value="tdr_promotions" />
        <button type="submit" class="button">Submit</button>
        </form>
        <?php		
	}
    // Add other helper functions, like tab detection and output
}

/* PROMOTIONS MENU SUBCLASS DEFINITION
******************************************************************************/
include_once( get_template_directory() . '/tdr_framework_promotion_subclass_functions.php' );
?>
