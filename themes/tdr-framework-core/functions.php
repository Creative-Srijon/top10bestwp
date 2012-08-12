<?php 
/* ADD THEME SUPPORT FOR THUMBNAILS
******************************************************************************/
if ( function_exists( 'add_theme_support' ) ) {
	add_theme_support( 'post-thumbnails' );
}

/* INCLUDE COMPILED AND CUSTOM JAVASCRIPT FROM CHILD
******************************************************************************/
add_action( 'wp_enqueue_scripts', 'tdr_framework_child_includes' );
function tdr_framework_child_includes() {
	// Include jQuery
	wp_enqueue_script( 'jquery' );
	
	// Register the main compiled Javascript (jQuery) script
	wp_register_script( 
		'tdr-framework-jquery-plugins', 
		get_stylesheet_directory_uri( __FILE__ ) . '/js/tdr-framework.js',
		array( 'jquery' ),
		'0.2',
		false
	);
	wp_enqueue_script( 'tdr-framework-jquery-plugins' );
	
	// Register the custom theme based Javascript
	//wp_register_script(
	//	'tdr-framework-custom-js',
	//	get_stylesheet_directory_uri( __FILE__ ) . '/js/tdr-child-custom.js',
	//	array( 'jquery', 'tdr-framework-jquery-plugins'),
	//	'0.2',
	//	true
	//);
	//wp_enqueue_script( 'tdr-framework-custom-js' );
}

/* FIX FOR MISSED SCHEDULE
 * ***************************************************************************/
define( 'WPMS_DELAY',5 );
define( 'WPMS_OPTION','wp_missed_schedule' );

register_deactivation_hook( __FILE__, 'wpms_replace' );
function wpms_replace() {
    delete_option( WPMS_OPTION );
}

add_action( 'init', 'wpms_init', 0 );
function wpms_init() {
    remove_action( 'publish_future_post','check_and_publish_future_post' );
    $last = get_option( WPMS_OPTION, false );
    if( ( $last !== false ) && ( $last > ( time() - ( WPMS_DELAY*60 ) ) ) ) {
        return;
    }
    update_option( WPMS_OPTION, time() );
    global $wpdb;
    $scheduledIDs = $wpdb->get_col("SELECT`ID`FROM`{$wpdb->posts}`"."WHERE("."((`post_date`>0)&&(`post_date`<=CURRENT_TIMESTAMP()))OR"."((`post_date_gmt`>0)&&(`post_date_gmt`<=UTC_TIMESTAMP()))".")AND`post_status`='future'LIMIT 0,5");
    if( !count( $scheduledIDs ) ) {
        return;
    }
    
    foreach( $scheduledIDs as $scheduledID ) {
        if( !$scheduledID ) {
            continue;
        }
        wp_publish_post($scheduledID);
    }
}

/* SHORTCODES FOR BOOTSTRAP
******************************************************************************/

add_shortcode( 'tdr_framework_alert', 'tdr_framework_alert_func' );
function tdr_framework_alert_func( $atts, $content = null ) {
	extract( shortcode_atts( array(
			'block' => 'false',
			'heading' => 'Alert',
			'close' => 'true',
			'fade' => 'true',
			'type' => ''
		), $atts ) );
		switch ( $type ) {
			case "error":
			$type_class = " alert-error";
			break;
			case "success":
			$type_class = " alert-success";
			break;
			case "info":
			$type_class = " alert-info";
			break;
			default: $type_class = "";
		}
		$heading_class = "";
		$fade_class = " fade in";
		$fade_block = '<a class="close" data-dismiss="alert">&times;</a>';
		if ( $fade == 'false' ) {
			$fade_class = "";
			$fade_block = '<a class="close" data-dismiss="alert">&times;</a>';
		}
		if ( $close == 'false' ) {
			$fade_class = "";
			$fade_block = "";
		}
		if ( !empty($fade_class) && !empty($heading_class) )
			$heading_class .= " ";
		if ( $block == 'true' ) {
			$block_content = '<h1 class="alert-heading">'.$heading.'</h1>';
			$heading_class = " alert-block";
			}
		else $block_content = '<strong>'.$heading.'</strong> ';
		return '<div class="alert'.$type_class.$heading_class.$fade_class.'">'.$fade_block.$block_content.$content.'</div>';
}

add_shortcode( 'tdr_framework_label', 'tdr_framework_label_func' );
function tdr_framework_label_func( $atts, $content = null ) {
	extract( shortcode_atts( array(
			'type' => ''
		), $atts ) );
		switch ( $type ) {
			case "success":
			$type_class = " label-success";
			break;
			case "warning":
			$type_class = " label-warning";
			break;
			case "important":
			$type_class = " label-important";
			break;
			case "info":
			$type_class = " label-info";
			break;
			default: $type_class = "";
		}
		return '<span class="label'.$type_class.'">'.$content.'</span>';
}

add_shortcode( 'tdr_framework_popover', 'tdr_framework_popover_func' );
function tdr_framework_popover_func( $atts, $content = null ) {
	extract( shortcode_atts( array(
			'title' => '',
			'popover' => ''
		), $atts ) );
		return '<span class="popover" data-content="'.esc_attr($popover).'" rel="popover" href="#" data-original-title="'.esc_attr($title).'">'.$content.'</span>';
}

add_shortcode( 'tdr_framework_tooltip', 'tdr_framework_tooltip_func' );
function tdr_framework_tooltip_func( $atts, $content = null ) {
	extract( shortcode_atts( array(
			'title' => ''
		), $atts ) );
		return '<a href="#" rel="tooltip" title="'.esc_attr($title).'">'.$content.'</a>';
}

/* BOOTSTRAP MENU SYSTEM
******************************************************************************/
class Bootstrap_Walker_Nav_Menu extends Walker_Nav_Menu {
	function start_lvl( &$output, $depth ) {

		$indent = str_repeat( "\t", $depth );
		$output	   .= "\n$indent<ul class=\"dropdown-menu\">\n";
		
	}

	function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
		
		$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

		$li_attributes = '';
		$class_names = $value = '';

		$classes = empty( $item->classes ) ? array() : (array) $item->classes;
		$classes[] = ($args->has_children) ? 'dropdown' : '';
		$classes[] = ($item->current) ? 'active' : '';
		$classes[] = 'menu-item-' . $item->ID;


		$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args ) );
		$class_names = ' class="' . esc_attr( $class_names ) . '"';

		$id = apply_filters( 'nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args );
		$id = strlen( $id ) ? ' id="' . esc_attr( $id ) . '"' : '';

		$output .= $indent . '<li' . $id . $value . $class_names . $li_attributes . '>';

		$attributes  = ! empty( $item->attr_title ) ? ' title="'  . esc_attr( $item->attr_title ) .'"' : '';
		$attributes .= ! empty( $item->target )     ? ' target="' . esc_attr( $item->target     ) .'"' : '';
		$attributes .= ! empty( $item->xfn )        ? ' rel="'    . esc_attr( $item->xfn        ) .'"' : '';
		$attributes .= ! empty( $item->url )        ? ' href="'   . esc_attr( $item->url        ) .'"' : '';
		$attributes .= ($args->has_children) 	    ? ' class="dropdown-toggle" data-toggle="dropdown"' : '';

		$item_output = $args->before;
		$item_output .= '<a'. $attributes .'>';
		$item_output .= $args->link_before . apply_filters( 'the_title', $item->title, $item->ID ) . $args->link_after;
		$item_output .= ($args->has_children) ? ' <b class="caret"></b></a>' : '</a>';
		$item_output .= $args->after;

		$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
	}

	function display_element( $element, &$children_elements, $max_depth, $depth=0, $args, &$output ) {
		
		if ( !$element )
			return;
		
		$id_field = $this->db_fields['id'];

		//display this element
		if ( is_array( $args[0] ) ) 
			$args[0]['has_children'] = ! empty( $children_elements[$element->$id_field] );
		else if ( is_object( $args[0] ) ) 
			$args[0]->has_children = ! empty( $children_elements[$element->$id_field] ); 
		$cb_args = array_merge( array(&$output, $element, $depth), $args);
		call_user_func_array(array(&$this, 'start_el'), $cb_args);

		$id = $element->$id_field;

		// descend only when the depth is right and there are childrens for this element
		if ( ($max_depth == 0 || $max_depth > $depth+1 ) && isset( $children_elements[$id]) ) {

			foreach( $children_elements[ $id ] as $child ){

				if ( !isset($newlevel) ) {
					$newlevel = true;
					//start the child delimiter
					$cb_args = array_merge( array(&$output, $depth), $args);
					call_user_func_array(array(&$this, 'start_lvl'), $cb_args);
				}
				$this->display_element( $child, $children_elements, $max_depth, $depth + 1, $args, $output );
			}
				unset( $children_elements[ $id ] );
		}

		if ( isset($newlevel) && $newlevel ){
			//end the child delimiter
			$cb_args = array_merge( array(&$output, $depth), $args);
			call_user_func_array(array(&$this, 'end_lvl'), $cb_args);
		}

		//end this element
		$cb_args = array_merge( array(&$output, $element, $depth), $args);
		call_user_func_array(array(&$this, 'end_el'), $cb_args);
		
	}
}

/* PAGINATION BOOTSTRAP INTEGRATION
******************************************************************************/
/**
 * Modification of wp_link_pages() to produce bootstrap-style pagination markup. Supports link_before, link_after, nextpagelink, previouspagelink, pagelink, and echo settings; Overrides before and after settings
 *
 * @param  array $args
 * @return void || string
 */
function tdr_link_pages( $args = array () )
{
	// Declare defaults
    $defaults = array(
		'link_before'      => '',
		'link_after'       => '',
		'nextpagelink'     => 'Next &raquo;',
		'previouspagelink' => '&laquo; Previous',
		'pagelink'         => '%',
		'echo'             => 1
    );
	// Merge defaults with supplied arguments
    $args = wp_parse_args( $args, $defaults );
    $args = apply_filters( 'wp_link_pages_args', $args );
    // Pull settings into variables
    extract( $args, EXTR_SKIP );

	// Force before and after content
    $before = '<div class="pagination"><ul>';
    $after = '</ul></div>';
    
	// Pull global variables into scope
    global $page, $numpages, $multipage, $more, $pagenow;

	// Don't paginate if there is not more than one page
    if ( ! $multipage )
    {
        return;
    }
	// DETERMINE PAGE RANGE
		// Show two pages back, bounded by 1
		$start = max ( 1, $page - 2 );
		// Show two pages forward, bounded by number of pages
		$end = min( $page+3, $numpages+1 );
	// END PAGE RANGE
	
    // Start output with "before" content
    $output = $before;

    // Display "previous" link if page is not first page
	if ( $page != 1 ) {
		$output .= '<li>' . _wp_link_page( $page-1 ) . $link_before . $previouspagelink . $link_after .'</a></li>';
	}

	// Display first page link if not inside range
	if ( $start > 1 ) {
		$output .= '<li>' . _wp_link_page( 1 ) . $link_before . str_replace( '%', 1, $pagelink ) . $link_after . '</a></li>';
	}
	
	// If first page is not adjacent to or inclusive of page 1, display ellipsis (...) at beginning of primary nav links
	if ( $start > 2 ) {
		$output .= '<li class="disabled"><a href="#">...</a></li>';
	}
	// Show navigation links from start to end of range
    for ( $i = $start; $i < $end; $i++ )
    {
        $link_format_text = str_replace( '%', $i, $pagelink );

        if ( $i != $page || ( ! $more && 1 == $page ) )
        {
            $output .= '<li>' . _wp_link_page( $i ) . $link_before . $link_format_text . $link_after . '</a></li>';
        }
        else
        {
			// Mark current page link with active class
            $output .= '<li class="active"><a href="#">' . $link_before . $link_format_text . $link_after . '</a></li>';
        }
    }
    // If last page is not adjacent to or inclusive of range cutoff, display ellipsis (...) at end of primary nav links
	if ( $end < $numpages ) {
		$output .= '<li class="disabled"><a href="#">...</a></li>';
	}
	// Display last page link if not inside range
	if ( $end != $numpages+1 ) {
		$output .= '<li>' . _wp_link_page( $numpages ) . $link_before . str_replace( '%', $numpages, $pagelink ) . $link_after . '</a></li>';
	}
	// Display "next" link if not on last page
	if ( $page != $numpages ) {
		$output .= '<li>' . _wp_link_page( $page+1 ) . $link_before . $nextpagelink . $link_after . '</a></li>';
	}
	//End output with "after" content
    $output .= $after;
    
    // Echo output if set to true
    if ( $echo ) {
		print $output;
	}
	// Else return output
	else {
		return $output;
	}
}

/* CUSTOM EXCERPT LENGTH
******************************************************************************/
function tdr_get_the_excerpt( $limit ) {
    // Get the Global post content.  This must be used within the Loop.
    global $post;
    $content = $post->post_content;

    // Set a word limit
	if ( !is_int( $limit ) ) {
		$limit = 25;
    }

    // Make sure to take out all HTML elements and truncate to word limit
    $content = tdr_trim_by_word_limit( $content, $limit ) . apply_filters( 'excerpt_more', '' );
	return $content;
}

function tdr_the_excerpt ( $limit ) {
	echo tdr_get_the_excerpt( $limit );
}

function tdr_get_the_excerpt_char ( $char_limit, $use_readmore_newline = false, $use_space_before_readmore = false ) {
    // This function returns a string with the given number of characters but 
    // split at a word boundary instead of in the middle of a word.  May only
    // work for English

    // Get the Global post content
    global $post;
    $content = $post->post_content;

    // Set a character limit if not already set
    //if ( !is_int( $char_limit )) {
    //    $char_limit = 200;
    //}

    // Strip out all html elements and truncates the content
    $content = tdr_trim_by_char( $content, $char_limit );

    // Add the excerpt_more filter
    if ( $use_readmore_newline ) {
        $ellipses = '...<br />';
	} else {
		if ( $use_space_before_readmore ) {
			$ellipses = '... ';
		} else {
	        $ellipses = '...';
		}
    }

    $content = $content . $ellipses . apply_filters( 'excerpt_more', '' );
    
    return $content;

}

function tdr_the_excerpt_char( $char_limit, $use_readmore_newline = false) {
    echo tdr_get_the_excerpt_char( $char_limit, $use_radmore_newline );
}

function tdr_trim_by_char ( $content = '', $char_limit = 55, $after_content = '' ) {
	$content = strip_tags( $content );
    $temp_content = substr( $content, 0, strpos( wordwrap( $content, $char_limit ), "\n" ) );

    $content = ( empty( $temp_content ) ) ? $content : $temp_content . $after_content ;
    return $content;
}

function tdr_trim_by_word_limit ( $content = '', $word_limit = 25 ) {
    // Make sure to take out all HTML elements
    $content = strip_tags( $content );

    // Split the content into words up to the number of words wanted
    $content = preg_split( "/\s/", $content, $word_limit+1 );

    // Pop off the unwanted content
	if ( count( $content ) == $word_limit+1 ) {
		array_pop( $content );
    }

    // Join back the words and add the excerpt more filter.
	$content = join( " ", $content );
    return $content;
}

/* CUSTOM TITLE FUNCTIONS
******************************************************************************/
/**
  * Returns the title for a post, with SEO Titles getting precedence over the regular title
  * 
  * @param int $id (optional) The id of the post to fetch the title from
  * @return string The title to use for the post
  */
function tdr_get_the_title ( $id = '' ) {
    // Verify that id has been passed, if not use current post in the Loop
    if ( empty( $id ) ) {
        global $post;
        $id = $post->ID;
    }

	// Try to get the SEO title from the postmeta table
	$seo_title = get_post_meta( $id, '_yoast_wpseo_title', true );
	// Return the SEO Title if it was found
	if( !empty( $seo_title ) ) {
		return $seo_title;
	}
	// Otherwise return the regular post title
	else {
		return get_the_title( $id );
	}
}
/**
  * Echoes (or optionally returns) the title for the current post, with SEO Titles getting precedence over the regular title
  * 
  * @param string $before (optional) Text to place before the title. Defaults to '' 
  * @param string $after (optional)  Text to place after the title. Defaults to ''
  * @param boolean $echo (optional)  Display the title (true) or return it for use in PHP (false). Defaults to true. 
  * @return string The title to use for the post
  */
function tdr_the_title ( $before = '', $after = '', $echo = true ) {
	// Construct the title
	$title = $before . tdr_get_the_title() . $after;
	
	// If echo flag is true, echo it out and return nothing
	if ( $echo ) {
		echo $title;
		return;
	}
	// Otherwise, just return the title
	else {
		return $title;
	}
}

/* GRAVITY FORMS BOOTSTRAP INTEGRATION
******************************************************************************/
require_once( get_template_directory() . '/tdr_framework_gravityforms_functions.php' );

/* SOCIAL ICONS
******************************************************************************/
require_once( get_template_directory() . '/tdr_framework_social_functions.php' );

/* DEFINE AJAX URL FOR FRONT END
******************************************************************************/
add_action('wp_head','tdr_frontend_insert_ajaxurl');

function tdr_frontend_insert_ajaxurl() {
?>
<script type="text/javascript">
var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
</script>
<?php
}

/* CONTACTOLOGY EMAIL LIST
******************************************************************************/
include_once( get_template_directory() . '/tdr_framework_contactology_functions.php' );

/* PROMOTIONS AND CONTESTS
******************************************************************************/
include_once( get_template_directory() . '/tdr_framework_promotion_functions.php' );

/* OUR TOP CHOICES WIDGET 
 * ***************************************************************************/
include_once( get_template_directory() . '/tdr_top_choices_widget.php' );

/* ADD SUPPORT FOR PAGE CATEGORIES
******************************************************************************/
add_action('admin_init', 'tdr_add_page_categories');
function tdr_add_page_categories() {
    register_taxonomy_for_object_type( 'category', 'page' );
    add_post_type_support( 'page','category' );
}

/* ADD FUNCTION FOR PAGINATING LINK
 * ***************************************************************************/
function tdr_paginate_links( $args = array() ) {
	global $wp_query;
	
	$total_pages = $wp_query->max_num_pages;  
	if ($total_pages > 1) {
		$current_page = max(1, get_query_var('paged'));  
	}

	// Get the defaults and parse with $args
	$defaults = array(
		'base'         => get_pagenum_link(1) . '%_%',
	    'format'       => '/page/%#%',
	    'total'        => $total_pages,
	    'current'      => $current_page,
	    'show_all'     => False,
	    'end_size'     => 1,
	    'mid_size'     => 2,
	    'prev_next'    => True,
	    'prev_text'    => '&laquo; Previous',
	    'next_text'    => 'Next &raquo;',
	    'add_args'     => False,
	    'add_fragment' => ''
	);
	$args = wp_parse_args( $args, $defaults );

	// Set the 'type' equalt to 'array' (can't be changed)
	$args['type'] = 'array';

	// Run paginate links
	$links = paginate_links( $args );  

	// Build the necessary markup
	echo "<div class='pagination'><ul>";
	foreach ( $links as $link ) {
		if ( strpos( $link, 'current' ) ) {
			echo "<li class='active'><a href='#'>$link</a></li>";
		} elseif ( strpos( $link, 'dots' ) ) {
		   echo "<li class='disabled'><a href='#'>$link</a></li>";	
		} else {
			echo "<li>$link</li>";
		}
	}
	  echo "</ul></div><!-- end .pagination -->";
}

?>
