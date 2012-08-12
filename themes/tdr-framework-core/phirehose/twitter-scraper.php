<?php
if( PHP_SAPI != 'cli' ) { // Prevent execution by apache
exit();
}
require_once('Phirehose.php');
/**
 * Listen for tweets that match active contest slug hashtags and send them to Wordpress for processing
 */
class FilterTrackConsumer extends Phirehose
{
  protected $wp_ajax_url;

  /**
   * Overidden constructor to take class-specific parameters
   * 
   * @param string $username
   * @param string $password
   * @param integer $filter_refresh_interval (defaults to 12 hours)
   */
  public function __construct($username, $password, $filter_refresh_interval = 43200 )
  {
    // Get command line options/arguments
    $options = getopt("a:", array("url:") );
    // Define Wordpress AJAXURL from options
    $this->wp_ajax_url = $options['url'];
    // Get tracking terms
    $this->checkFilterPredicates();
    // Sanity check for filter update
      if ( $filter_refresh_interval < 60*60 ) { // Filter refresh check set less than hourly:
        $filter_refresh_interval = 60*60*12; // Force to every 12 hours
      }
    $this->filterCheckMin = $filter_refresh_interval; // Set filter refresh rate
    // Call parent constructor
    return parent::__construct($username, $password, Phirehose::METHOD_FILTER);
  }
  /**
   * AJAX POST Handler
   */
  private function doPostRequest( $action, $data = '' ) {
      // Post AJAX request
      $request_details = array(
        'action' => $action // Values [may] need to be urlencoded
      );
      if ( !empty( $data ) ) {
        $request_details['data'] = $data;
      }
      $curl = curl_init( $this->wp_ajax_url ); // Setup curl with AJAX URL
      curl_setopt( $curl, CURLOPT_POST, 1 ); // Enable post fields
      curl_setopt( $curl, CURLOPT_POSTFIELDS, $request_details ); // Set post fields
      curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true ); // Return output rather than echoing
      $response = curl_exec( $curl ); // Process CURL request
      return $response; // AJAX response
  }
  /**
   * Update filter list periodically
   */
  public function checkFilterPredicates()
  {
      // Request list of active campaign slugs from Wordpress
      $response = $this->doPostRequest( 'tdr_promotions_list_active_campaign_hashtags' );
      $contest_scrape_terms = json_decode( $response ); // Decode JSON response
      if ( $contest_scrape_terms != $this->getTrack() ) { // Update filter terms used for scrape if they have changed
          $this->setTrack( $contest_scrape_terms );
      }
  }
  /**
   * Enqueue each status
   *
   * @param string $status
   */
  public function enqueueStatus($status)
  {
    // Send collected tweet to Wordpress for processing
    // print 'status found' . "\n"; // DEBUGGING: show when tweets are found
    $this->doPostRequest( 'tdr_promotions_twitter_scrape', $status ); 
  }
}

// Start streaming
// Get twitter username and password from Command line arguments
$options = getopt("u:p:", array("username:","password:") );
$twitter_username = $options['username'];
$twitter_password = $options['password'];
// Setup Scraper
$sc = new FilterTrackConsumer($twitter_username, $twitter_password, 60*60*12); // Set twitter credentials & filter refresh interval
// Begin Scraping
$sc->consume();
