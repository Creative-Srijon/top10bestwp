<?php

/**
* Action: Widget Init
*/
add_action( 'widgets_init', create_function('', 'return register_widget("tdr_top_choices_widget");') );

if( get_bloginfo('version') >= '2.8' ) {
	class tdr_top_choices_widget extends WP_Widget {

		/* CONSTRUCTOR
		 * *******************************************************************/
		function tdr_top_choices_widget() {
			parent::WP_Widget( FALSE, $name = 'Thunder Plugin - Top Choices', array( 'description' => 'Displays our top Product choices.'  ) );
		}

		/* DISPLAY 
		 * *******************************************************************/
		function widget($args, $opts){
			// Before Widget Output
			echo $args['before_widget'];
		?>

					<div class="widget-title">
						<?php echo $args['before_title'] . $opts['widget_title'] . $args['after_title']; ?>
					</div> <!-- .widget-title -->

					<!-- NAVIGATION -->
					<div class="our_top_menu" style="text-align: center;">
					<?php
							$main_offer_cat_id = $opts['main_offer_cat_id'];
							echo ( "<span class='our_top_menu_item' id='$main_offer_cat_id'><span id='selected'>All</span></span>" );
							foreach ( $opts['which_offer_cats'] as $offer_cat_id ) {
								$term = get_term_by( 'id', $offer_cat_id, 'offer-category', OBJECT );
								echo ( "<span class='our_top_menu_divider' > | </span><span class='our_top_menu_item' id='$term->term_id'><span>$term->name</span></span>" );
							}
						?>
					</div>

					<!-- OUTPUT THE TABLE -->
					<?php 
						// Get data
						$args = array(
							'number_to_show' => $opts['number_to_show'],
							'number_to_query' => 10,
							//'offer_cat_terms' => array( $opts['main_offer_cat_id'] )
						);
						echo '<section class="our_top_choices_section">';
						echo tdr_top_choices_table( $args );
						echo '</section><!-- end .our_top_choices_section -->';
					?>

					
				<?php
				
				// After the Widget
				echo $args['after_widget'];
		}
		
		/** 
		* Update the Options when the Admin form is submitted 
		*/
		function update($new_opts, $old_opts) {
			return $new_opts;
		}

		/**
		* The Backend Admin form
		*/
		function form($opts) {
			/* SETUP FOR TITLE */
			if ( isset( $opts['widget_title'] ) ) {
				$title = $opts['widget_title'];
			} else {
				$title = 'New title';
			}
			$title_id = $this->get_field_id( 'widget_title' );
			$title_name = $this->get_field_name( 'widget_title' );
			$title_esc = esc_attr( $title );

			/* SETUP FOR NUMBER TO SHOW */
			if ( isset( $opts['number_to_show'] ) ) {
				$number = $opts['number_to_show'];
			} else {
				$number = 'Enter the number to show.';
			}
			$number_id = $this->get_field_id( 'number_to_show' );
			$number_name = $this->get_field_name( 'number_to_show' );
			$number_esc = esc_attr( $number );

			/* SETUP FOR OFFER CATEGORIES CHECKBOXES */
			if ( isset( $opts['which_offer_cats'] ) ) {
				$which_offer_cats = $opts['which_offer_cats'];
			} else {
				$which_offer_cats = array();
			}
			$which_offer_cats_id = $this->get_field_id( 'which_offer_cats' );
			$which_offer_cats_name = $this->get_field_name( 'which_offer_cats' );
			$which_offer_cats_esc = esc_attr( $which_offer_cats );

			/* MAIN OFFER-CATEGORY ID
			 * ****************************************************************/
			if ( isset( $opts['main_offer_cat_id'] ) ) {
				$main_offer_cat_id = $opts['main_offer_cat_id'];
			} else {
				$main_offer_cat_id = 'Enter the ID of the Main offer-category.';
			}
			$main_offer_cat_id_id = $this->get_field_id( 'main_offer_cat_id' );
			$main_offer_cat_id_name = $this->get_field_name( 'main_offer_cat_id' );
			$main_offer_cat_id_esc = esc_attr( $main_offer_cat_id );

?>
		<!-- TITLE INPUT FIELD -->
		<p>
		<label for="<?php echo $title_id; ?>">Title</label>		
		<input class="widefat" id="<?php echo $title_id; ?>" name="<?php echo $title_name; ?>" type="text" value="<?php echo $title_esc; ?>" />
		</p>

		<!-- NUMBER INPUT FIELD -->
		<p>
		<label for="<?php echo $number_id; ?>">Number to Show</label><br />
		<input class="widefat" id="<?php echo $number_id; ?>" name="<?php echo $number_name; ?>" type="text" value="<?php echo $number_esc; ?>" />
		</p>

		<?php
			// OFFER CATEGORIES
			$offer_cats = get_terms( 'offer-category' );
			foreach ( $offer_cats as $offer_cat ) {
				$option = '<input type="checkbox" id="'. $this->get_field_id('which_offer_cats') . '[]" name="'. $this->get_field_name( 'which_offer_cats' ) . '[]"';
				if ( is_array ( $opts['which_offer_cats'] ) ) {
					foreach ( $opts['which_offer_cats'] as $term ) {
						if ( $term == $offer_cat->term_id ) {
							$option = $option . ' checked="checked"';
						}
					}
				}
				$option .= ' value="' . $offer_cat->term_id . '" />';
				$option .= $offer_cat->name;
				$option .= '<br />';
				echo $option;
			}
?>
		<br />.
		<!-- MAIN OFFER CAT ID -->
		<p>
		<label for="<?php echo $main_offer_cat_id; ?>">Main Offer Category ID</label><br />
		<input class="widefat" id="<?php echo $main_offer_cat_id_id; ?>" name="<?php echo $main_offer_cat_id_name; ?>" type="text" value="<?php echo $main_offer_cat_id_esc; ?>" />
		</p>
<?php
		}
	}
}

add_action( 'wp_ajax_nopriv_tdr_top_choices_widget_ajax', 'tdr_top_choices_widget_ajax' );
add_action( 'wp_ajax_tdr_top_choices_widget_ajax', 'tdr_top_choices_widget_ajax' );
function tdr_top_choices_widget_ajax() {
	if ( isset( $_POST ) ) {
		$offer_cat_id = (int)$_POST['offer_cat_id'];
	}

	$term_meta = get_option( "taxonomy_term_$offer_cat_id" );
	if ( isset( $term_meta['sort_meta_key'] ) && !empty( $term_meta['sort_meta_key'] ) ) {
		$rank_key = $term_meta['sort_meta_key'];
	} else {
		$rank_key = '';
	}

	$args = array (
		'offer_cat_terms' => array( $offer_cat_id ),
		'rank_meta_key' => $rank_key,
		'number_to_query' => 10
	);
	$new_table = tdr_top_choices_table( $args );

	echo $new_table;
	exit;	
}

function tdr_top_choices_table( $args ) {
	$defaults = array(
		'number_to_show' => 3,
		'number_to_query' => 3,
		'offer_cat_terms' => get_terms( 'offer-category', array( 'fields' => 'ids' ) ),
		'rank_meta_key' => ''
	);

	$args = wp_parse_args( $args, $defaults );
	extract( $args, EXTR_SKIP );

	/* Set up the Query */

	/* Taxonomy for the Query
	 * ***************************************************************/
		// Set up the taxonomy
		$offer_cat_args = array (
			array (
				'taxonomy' => 'offer-category',
				'field' => 'id',
				'terms' => $offer_cat_terms
			)	
		);

	/* Create the query 
	 * ***************************************************************/
	$basic_query = new WP_Query(
		array (
			'tax_query' => $offer_cat_args,
			'post_type' => 'tdr_product',
			'posts_per_page' => (int)$number_to_query,
			'order' => 'ASC',
			'orderby' => 'meta_value_num',
			'meta_key' => 'thunder_plugin_products_rank'
		)
	);

	// If a Rank Key is defined, reorder the Products
	if ( !empty( $rank_meta_key ) ) {
		global $tdr_rank_key;
		$tdr_rank_key = $rank_meta_key;
		usort( &$basic_query->{'posts'}, 'tdr_sort_by_offer_category_rank' );
		$basic_query->rewind_posts();
	}

	// Update to only show the $number_to_show
	$basic_query->post_count = $number_to_show;
	
	$returner = '<div class="row">';
		$returner .= '<div class="span">';
			
			// Set up tdr_product
			$product = new Thunder_Plugin_Products();

			while ( $basic_query->have_posts() ) : $basic_query->the_post();
				// Get the ID for the Rating Bar, this determines how the stars appear
				$internal_rating = floatval( $product->get_the_internal_rating() );
				if ( $internal_rating < 2.2 ) {
					$internal_rating_id = 'two_zero';
				} elseif ( $internal_rating < 2.7 ) {
					$internal_rating_id = 'two_five';
				} elseif ( $internal_rating < 3.2 ) {
					$internal_rating_id = 'three_zero';
				} elseif ( $internal_rating < 3.7 ) {
					$internal_rating_id = 'three_five';
				} elseif ( $internal_rating < 4.2 ) {
					$internal_rating_id = 'four_zero';
				} elseif ( $internal_rating < 4.7 ) {
					$internal_rating_id = 'four_five';
				} elseif ( $internal_rating >= 4.7 ) {
					$internal_rating_id = 'five_zero';
				} else {
					$internal_rating_id = $internal_rating;
				}

				// Force display of non-significant digits for the internal rating
				$internal_rating = sprintf( "%1.1f", $internal_rating );

				// Output a row for this Product, and get its name
				$returner .= '<div class="row"><div class="span4" style="border-top: 1px solid #eee; padding: 10px 0;">';
				$product_name = $product->get_the_affiliate_name();

				// Get the logo, and set up link
				add_image_size( 'our_top_choices_thumb', 120, 30 );

				$review_link = get_permalink();  
				$attr['alt'] = $product_name . ' Reviews';
				$attr['title'] = $product_name . ' Reviews';
				if ( class_exists( 'MultiPostThumbnails' )
				  && MultiPostThumbnails::has_post_thumbnail( 'tdr_product', 'thumbnail-affiliate-logo' ) ) {
						$affiliate_img = MultiPostThumbnails::get_the_post_thumbnail('tdr_product', 'thumbnail-affiliate-logo', NULL , 'our_top_choices_thumb', $attr);
				  }
				
				
				$returner .= "<div class='our_top_logos'>";
					$returner .= "<a href='$review_link'>";
						$returner .= $affiliate_img;
					$returner .= '</a>';
				$returner .= "</div><!-- end .our_top_logos -->";

				$returner .= "<div class='our_top_ratings' style=''>";
					$returner .= "<div id='$internal_rating_id' class='rating-bar' style='position: relative; left: 5px;'></div>";
					$returner .=  "<span style='font-size: 24px; font-weight: bold; line-height: 24px; '>$internal_rating</span>" . " <span class='review-link'>(<a href='$review_link' style=''>Review</a>)</span>";
				$returner .= "</div>";

				$returner .= "<div class='our_top_visit' style=''>";
					$product_id = $product->get_the_id();
					$jump_page_url = get_home_url() . "/visit?site=$product_id&t=sidebar";
					$returner .= "<a target=_blank' href='$jump_page_url'><span style='font-size:10px;'>&#9654;</span> Visit Site</a>";
				$returner .= "</div>";

				$returner .= '</div><!-- end .span --></div><!-- end .row -->';	
			endwhile;

		$returner .= "</div><!-- end .span -->";
	$returner .= "</div><!-- end .row -->";

	
	$returner .= '<div class="read_more_articles">';
		$offer_category_permalink = get_home_url() . '/reviews';
		$returner .= "<a href='$offer_category_permalink' id='review_page_link'>+ View All Reviews</a>";
	$returner .= '</div><!-- end .read_more_articles -->';

	return $returner;
}
?>
