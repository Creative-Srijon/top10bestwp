<?php
/*
 * Template Name: Comparison
 * */
?>

<?php
    /* Build the taxonomy query of offer-category
    * * ***********************************************************************/
    global $post;   
    $offer_terms = get_the_terms( $post->ID, 'offer-category' );
    $offer_cats_array = array();

    foreach( $offer_terms as $offer_cat ) {
    $offer_cats_array[] = $offer_cat->slug;
    }

    /* What is the category: Assuming only one chosen
     * * ***********************************************************************/
    $offer_category = $offer_cats_array[0];
    $tax_query = array(
        array (
            'taxonomy' => 'offer-category',
            'field' => 'slug',
            'terms' => $offer_cats_array,
        )
    );

    $logo_src = get_stylesheet_directory_uri() . '/images/top10logo.png';
?>

<?php get_header(); ?>

<div class="row">
    <div class="span12">
        <div id="main-content">

        <!-- Page Title and Description 
        ----------------------------------------------------------------------->
        <?php while ( have_posts() ) : the_post(); ?>
            <div class="row">
                <div class="span10 offset1">
                    <div class="page-header">
                        <h1>
                            <img src="<?php echo $logo_src; ?>" /> <?php echo get_the_title(); ?>
                        </h1>
                    </div><!-- end .page-header -->
                </div><!-- end .span10 -->
            </div><!-- end .row -->
    
            <div class="row">
                <div class="span10 offset1">
                    <div class="page-content">
                        <?php echo get_the_content(); ?>
                    </div><!-- end .page-content -->
                </div><!-- end .span10 -->
            </div><!-- end .row -->

        <?php endwhile; ?>

        <!-- Comparisons
        ---------------------------------------------------------------------->
<?php
	    $product = new Thunder_Plugin_Products();

        $comparison_query_args = array( 
            'post_type' => 'tdr_product',
            'meta_key' => 'thunder_plugin_products_rank',
            'orderby' => 'meta_value_num',
            'order' => 'ASC',
            'tax_query' => $tax_query
        );
        $comparison_query = new WP_Query( $comparison_query_args ); 
        $comp_order = 1;
        while ( $comparison_query->have_posts() ) : $comparison_query->the_post();
            $affiliate_name = $product->get_the_affiliate_name();
            $internal_rating = floatval( $product->get_the_internal_rating() );
            $internal_rating_id = ttwph_get_rating_id( $internal_rating );
            $internal_rating = sprintf( "%1.1f", $internal_rating );
            $comparison_points = $product->get_the_comparison_points();
?>
        <div class="row">
            <div class="span12">
                <section class='comparison-table'>
                    <!-- Rating -->
                    <div class="span-comp-order">
                        <span class='comp-order'><?php echo $comp_order; ?></span>
                    </div><!-- end span1 -->

                    <!-- Logo -->
                    <div class="span-aff-logo">
                        <?php
                        $attr['alt'] = $affiliate_name . ' Reviews';
                        $attr['title'] = 'Visit ' . $affiliate_name;
                        $attr['width'] = '150px';
                        $affiliate_logo = MultiPostThumbnails::get_the_post_thumbnail( 'tdr_product', 'affiliate-logo', NULL, 'post-thumbnail', $attr ); 
                        ?>
                        <a href='#' id="affiliate-logo"><?php echo $affiliate_logo; ?></a>
                        <a href='#' class="btn btn-warning" id="visit-site-button"><i class="icon-play icon-white"></i> Visit Site</a>
                    </div><!-- end .aff-logo -->

                    <!-- Review -->
                    <div class="span7" id="review">
                        <div class="excerpt">
                            <strong>Our Take: </strong><?php the_excerpt(); ?>
                        </div><!-- end .excerpt -->
                        <div class="sub-ratings">
                            <div id="support"><h6><i class="icon-comment"></i> Customer Support</h6><?php echo( $comparison_points['customer-service'] ); ?></div>
                            <div id="one-click"><h6><i class="icon-cog"></i> One-Click Install?</h6><?php echo( $comparison_points['one-click'] ); ?></div>
                            <div id="price"><h6><i class="icon-shopping-cart"></i> Price</h6><?php echo ( $comparison_points['cheap-price'] ); ?><br /><a href="#">Visit Site</a></div>
                            <div id="bandwidth"><h6><i class="icon-signal"></i> Bandwidth</h6><?php echo ( $comparison_points['cheap-bandwidth'] ); ?></div>
                        </div><!-- end .sub-ratings -->
                    </div><!-- end .our-review -->

                    <!-- Visit Site Button -->
                    <div class="span" id="rating">
                        <div class="rating-number"><span><?php echo $internal_rating; ?></span>/5.0</div>
                        <div class="rating-stars" id="<?php echo $internal_rating_id; ?>"></div>
                        <div class="rating-link"><a href='#'>Visit Site</a></div>
                    </div>
                </section>
            </div><!-- end .span12 -->
        </div><!-- end .row -->
        <?php
        $comp_order++;
        endwhile;
        ?> 

        </div><!-- end #main-content -->
    </div><!-- end .span12 -->
</div><!-- end .row -->


<?php get_footer(); ?>

<?php 
function ttwph_get_rating_id( $internal_rating ) {
/* Get the ID for the Rating Bar, this determines how the stars appear
    * **********************************************************************/
    if ( $internal_rating < 0.2 ) {
        $internal_rating_id = 'zero';
    } elseif ( $internal_rating < 0.7 ) {
        $internal_rating_id = 'zero-five';
    } elseif ( $internal_rating < 1.2 ) {
        $internal_rating_id = 'one-zero';
    } elseif ( $internal_rating < 1.7 ) {
        $internal_rating_id = 'one-five';
    } elseif ( $internal_rating < 2.2 ) {
        $internal_rating_id = 'two-zero';
    } elseif ( $internal_rating < 2.7 ) {
        $internal_rating_id = 'two-five';
    } elseif ( $internal_rating < 3.2 ) {
        $internal_rating_id = 'three-zero';
    } elseif ( $internal_rating < 3.7 ) {
        $internal_rating_id = 'three-five';
    } elseif ( $internal_rating < 4.2 ) {
        $internal_rating_id = 'four-zero';
    } elseif ( $internal_rating < 4.7 ) {
        $internal_rating_id = 'four-five';
    } elseif ( $internal_rating >= 4.7 ) {
        $internal_rating_id = 'five-zero';
    } else {
        $internal_rating_id = $internal_rating;
    }
    return $internal_rating_id;
}
?>
