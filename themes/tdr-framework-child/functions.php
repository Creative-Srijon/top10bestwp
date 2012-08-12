<?php
register_nav_menu( 'main-bootstrap-menu', 'Main Bootstrap Menu' );

if (class_exists('MultiPostThumbnails')) {
    new MultiPostThumbnails(array(
        'label' => 'Affiliate Logo - 150x100 px',
        'id' => 'affiliate-logo',
        'post_type' => 'tdr_product'
    ) );
}

?>
