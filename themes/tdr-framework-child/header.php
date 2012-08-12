<!DOCTYPE html>

<!-- BROWSER CONDITIONAL STATEMENTS
------------------------------------------------------------------------------>
<!--[if lt IE 7]> <html class="no-js le-ie10 lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie10 lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js le-ie10 lt-ie9" lang="en"> <![endif]-->
<!--[if IE 9]>    <html class="no-js lt-ie10" lang="en"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en"> <!--<![endif]-->

<!-- HEAD
------------------------------------------------------------------------------>
<head>
    <!-- Charset -->
    <meta charset="<?php bloginfo( 'charset' ); ?>" />
    <!-- -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

                            
    <!-- Page Title -->
    <title><?php
        wp_title('', $echo = true, 'left');
        echo( " | " . get_bloginfo('name') );
    ?></title>
    
    <!-- Site Favicon -->
    <link rel="shortcut icon" href="<?php bloginfo('stylesheet_directory'); ?>/images/favicon/favicon.ico" />
    
    <!-- Basic Stylesheet for Theme -->
    <link rel="stylesheet" type="text/css" media="all" href="<?php echo get_stylesheet_directory_uri(); ?>/style.css" />

    <!-- WordPress Header Insert (for js, stylesheets, etc) -->
    <?php wp_head(); ?>
</head>

    <!-- BODY
------------------------------------------------------------------------------>
<body <?php body_class(); ?>>
    <!-- Prompt IE 6 users to install Chrome Frame. Remove this if you support IE 6.
         http://chromium.org/developers/how-tos/chrome-frame-getting-started -->
    <!--[if lt IE 7]><p class=chromeframe>Your browser is <em>ancient!</em> 
        <a href="http://browsehappy.com/">Upgrade to a different browser</a> or 
        <a href="http://www.google.com/chromeframe/?redirect=true">install 
        Google Chrome Frame</a> to experience this site.</p><![endif]-->

    <!-- MAIN CONTENT CONTAINER 
    ------------------------------------------------------------------------------>
    
        <!-- NAVBAR
        ---------------------------------------------------------------------->
        <div class="navbar navbar-fixed-top">
            <div class="navbar-inner">
                <div class="container">
                <?php
        $home_menu_item = '<li class="menu-item-home"><a id="logo" href="' . get_home_url()  . '"><i class="icon-home icon-white"></i></a></li><li class="divider-vertical"></li>';
                    $items_wrap = '<ul id="%1$s" class="%2$s">' . $home_menu_item  . '%3$s</ul>';
                    $items_wrap .= get_search_form( $echo = false );

                    $menu_args = array(
                        'theme_location' => 'main-bootstrap-menu',
                        'depth'          => 2,
                        'container'      => false,
                        'menu_class'     => 'nav',
                        'items_wrap'     => $items_wrap,
                        'walker'         => new Bootstrap_Walker_Nav_Menu()
                    );

                    wp_nav_menu( $menu_args );
                    ?>
                </div><!-- end .container -->
            </div><!-- end .navbar-inner -->
        </div><!-- end .navbar -->

        <div class="container" id="main-container">
        <!-- HEADER
        --------------------------------------------------------------------------->
        <header>
            <?php if ( false ) { ?>
            <div id="header">
                <div class="row">
                    <div class="span12">
                    </div><!-- end .span12 -->
                </div><!-- end .row -->
            </div><!-- end #header -->
            <?php } ?>

        </header>
