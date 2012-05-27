<?php
Class Tdr_Social {
    //Attributes for tracking javascript loading and global settings
    //FB-like and Email not included
    private $size = 'small';
	private $twitter = true;
	//$fb = true;
	private $google = true;
	private $fb_share = true;
	private $twitter_handle;
	// JS
    private $twitter_js = 'test';
	//$fb_js;
	private $google_js = 'test';
	private $fb_share_js = 'test';
	// JS load states
	private $twitter_loaded = false;
	//private $fb_loaded = false;
	private $google_loaded = false;
    private $fb_share_loaded = false;
    private $email_modal_inserted = false;
	
	public function insert_social_icons ( $args ) {
		$defaults = array(
				'size' => 'small',
				'twitter' => true,
				'fb' => true,
				'google' => true,
                'fb_share' => true,
                'email' => true,
                'email_form_id' => null,
                'email_form_title' => "Email this",
                'insert_row' => false,
                'twitter_handle' => null,
                'section_class' => ''
		);
		$args = wp_parse_args( $args, $defaults );
		extract( $args, EXTR_SKIP );
		
		if ( $size != "large" ) {
			$size = $defaults["size"];
        }
        // Disable email icon if gravityforms is not enabled
        if ( $email && is_numeric($email_form_id) && class_exists('RGForms') ) {
            $email_form_id = (int)$email_form_id;
            $email_markup = do_shortcode('[gravityform id="' . $email_form_id  . '" name="Email this" ajax="true"]');
            if ( $this->{"email_modal_inserted"} == false ) {
                $modal_markup = '<div class="modal fade" id="tdr-email-this-form" style="display:none;"><div class="modal-header"><a class="close" data-dismiss="modal">x</a><h3>' . $email_form_title . '</h3></div><div class="modal-body">' . $email_markup  . '</div></div>';
            }
            else {
                $modal_markup = "";
            }
            $this->{"email_modal_inserted"} = true;
        }
        else {
            $modal_markup = "";
            $email = false;
        }

        if ( !empty( $section_class ) ) {
            $section_class = " " . $section_class;
        }

        $row = array(
            'open' => '',
            'close' => ''
        );

        if ( $insert_row ) {
            $row['open'] = '<div class="row row-open">';
            $row['close'] = '</div>';
        }

		// Set properties to parsed args
		$this->size = $size;
		$this->twitter = $twitter;
		//$this->fb = $fb;
		$this->google = $google;
        $this->fb_share = $fb_share;
        //$this->email = $email;
		$this->twitter_handle = $twitter_handle;
		// Consider making constructor
		
		// If $twitter_handle is not blank, include '{data-}via' and '{data-}related' attributes
		if ( $twitter_hanlde != null ) {
			$twitter_attributes = ' data-via="' . $twitter_handle . '" data-related="' . $twitter_handle . '"';
		}
		else {
			$twitter_attributes = '';
		}
		
		// Structure
		$social = array(
			'twitter' => array(
				'container_open' => '<div class="social-twitter">', // Ensure check for empty ( $twitter_handle ) to do via and recommend
				'markup' => array(
					'small' => '<a href="https://twitter.com/share" class="twitter-share-button"' . $twitter_attributes . '>Tweet</a>',
					'large' => '<a href="https://twitter.com/share" class="twitter-share-button"' . $twitter_attributes . ' data-count="vertical">Tweet</a>'			
				),
				'container_close' => '</div>',
				'javascript' => '!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");'
			),
			'fb' => array(
				'container_open' => '<div class="social-fb-like">',
				'markup' => array(
					'small' => '<iframe src="//www.facebook.com/plugins/like.php?href=' . urlencode( get_permalink() ) . '&amp;send=false&amp;layout=button_count&amp;width=100&amp;show_faces=false&amp;action=like&amp;colorscheme=light&amp;font&amp;height=21" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:100px; height:21px;" allowTransparency="true"></iframe>',
					'large' => '<iframe src="//www.facebook.com/plugins/like.php?href=' . urlencode( get_permalink() ) . '&amp;send=false&amp;layout=box_count&amp;width=450&amp;show_faces=false&amp;action=like&amp;colorscheme=light&amp;font&amp;height=90" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:450px; height:90px;" allowTransparency="true"></iframe>'
				),
				'container_close' => '</div>'/*,
				'javascript' => ''*/
			),
			'google' => array(
				'container_open' => '<div class="social-g-plus">',
				'markup' => array(
					'small' => '<div class="g-plusone" data-size="medium"></div>',
					'large' => '<div class="g-plusone" data-size="tall"></div>'			
				),
				'container_close' => '</div>',
				'javascript' => '(function() {var po = document.createElement("script"); po.type = "text/javascript"; po.async = true;po.src = "https://apis.google.com/js/plusone.js"; var s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(po, s);})();'
			),
			'fb_share' => array(
				'container_open' => '<div class="social-fb-share">', // remember to position at bottom w/ CSS
				'markup' => array(
					'small' => '<a name="fb_share" type="button"></a>',
					'large' => '<a name="fb_share" type="button"></a>'			
				),
				'container_close' => '</div>',
                'javascript' => '(function() {var po = document.createElement("script"); po.type = "text/javascript"; po.async = true;po.src = "http://static.ak.fbcdn.net/connect.php/js/FB.Share"; var s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(po, s);})();'),
            'email' => array(
                'container_open' => '<div class="social-email-this" data-toggle="modal" data-target="#tdr-email-this-form">',
                'markup' => array(
                    'small' => '<div class="social-email-small"></div>',
                    'large' => '<div class="social-email-large"></div>',
                    'modal' => $modal_markup
                ),
                'container_close' => '</div>'
            ),
			'container_open' => array(
				'small' => $row['open'] . '<div class="social-container social-small' . $section_class  . '">',
				'large' => $row['open'] . '<div class="social-container social-large' . $section_class  . '">'
			),
            'container_close' => '</div>' . $row['close']
		);
		$this->twitter_js = $social["twitter"]["javascript"];
		$this->google_js = $social["google"]["javascript"];
		$this->fb_share_js = $social["fb_share"]["javascript"];
		
		add_action( 'wp_footer', array( &$this, 'social_javascript' ));

		// Based on social networks TRUE, enqueue [TODO] registered scripts
			$returner = $social["container_open"][$size];
			if ( $twitter ) {
				$returner .= $social["twitter"]["container_open"];
					$returner .= $social["twitter"]["markup"][$size];
				$returner .= $social["twitter"]["container_close"];
			}
			if ( $fb ) {
				$returner .= $social["fb"]["container_open"];
					$returner .= $social["fb"]["markup"][$size];
				$returner .= $social["fb"]["container_close"];
			}
			if ( $google ) {
				$returner .= $social["google"]["container_open"];
					$returner .= $social["google"]["markup"][$size];
				$returner .= $social["google"]["container_close"];
			}
			if ( $fb_share ) {
				$returner .= $social["fb_share"]["container_open"];
					$returner .= $social["fb_share"]["markup"][$size];
				$returner .= $social["fb_share"]["container_close"];
            }
            if ( $email ) {
                $returner .= $social["email"]["container_open"];
                    $returner .= $social["email"]["markup"][$size];
                $returner .= $social["email"]["container_close"];
                $returner .= $social["email"]["markup"]["modal"];
            }
			$returner .= $social["container_close"];
		return $returner;
	}
	// Setup javascript enqueue
	public function social_javascript() {
		// Check to see that we need to output anything at all
		if ( ( $this->twitter && !$this->twitter_loaded ) || /* ( $this->fb && !$this->fb_loaded ) || */ ( $this->google && !$this->google_loaded ) || ( $this->fb_share && !$this->fb_share_loaded ) ) {
			echo '<script type="text/javascript">';
			// Start asynchronous call
			echo '(function(){function async_load(){';
			if ( $this->twitter && !$this->twitter_loaded ) {
					$this->twitter_loaded = true;
					echo $this->twitter_js;
			}
	/*		if ( $this->fb ) {
				// Not needed - using iframe
			}
	*/
			if ( $this->google && !$this->google_loaded ) {
					$this->google_loaded = true;
					echo $this->google_js;
			}
			if ( $this->fb_share && !$this->fb_share_loaded ) {
					$this->fb_share_loaded = true;
					echo $this->fb_share_js;
			}
			// Finish asynchronous call
			echo '} if (window.attachEvent) window.attachEvent("onload", async_load); else window.addEventListener("load", async_load, false);})();';
			echo '</script>';
		}
	} 
}

// Gravity forms dynamic content hooks

if ( class_exists('RGForms') ) {
	
	/* GRAVITY FORMS POST EXCERPT HOOK
	 * ***************************************************************************/
	add_filter('gform_field_value_post_content', 'tdr_form_post_content');
	function tdr_form_post_content($value){
		global $post;
		$content = get_the_content();
		return $content;
	}
	
	/* GRAVITY FORMS POST EXCERPT HOOK
	 * ***************************************************************************/
	add_filter('gform_field_value_post_excerpt', 'tdr_form_post_excerpt');
	function tdr_form_post_excerpt($value){
		global $post;
		$content = get_the_content();
		$content = tdr_trim_by_word_limit( $content, 55 ) . "...";
		return $content;
	}
}
?>
