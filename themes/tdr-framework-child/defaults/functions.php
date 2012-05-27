<?php
register_nav_menu('main-bootstrap-menu', 'Main Bootstrap Menu');

add_action('widgets_init', 'tdr_sidebars_init');
function tdr_sidebars_init() {
  register_sidebar(array(
    'name'          => __('Right Sidebar'),
    'id'            => 'right-sidebar',
    'before_widget' => '<aside id="%1$s" class="widget %2$s">',
    'after_widget'  => '</aside>',
    'before_title'  => '<h3 class="widget-title">',
    'after_title'   => '</h3>',
  ));
}

?>
