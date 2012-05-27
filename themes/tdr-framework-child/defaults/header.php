<!DOCTYPE html>
<!-- paulirish.com/2008/conditional-stylesheets-vs-css-hacks-answer-neither/ -->
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" lang="en"> <![endif]-->
<!-- Consider adding a manifest.appcache: h5bp.com/d/Offline -->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en"> <!--<![endif]-->
<head>

<meta charset="<?php bloginfo( 'charset' ); ?>" />

<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

<title>
<?php
  global $page, $paged;
  wp_title('|', true, 'right');
  bloginfo('name');

  $site_description = get_bloginfo('description', 'display');
  if ($site_description && (is_home() || is_front_page() )) {
    echo " | $site_description";
  }

  if ($paged >= 2 || $page >= 2) {
    echo ' | ' . sprintf(__('Page %s', 'tdr-framework-child'), max($paged, $page));
  }
?>
</title>

<!-- Basic Stylesheet for Theme -->
<link rel="stylesheet" type="text/css" media="all" href="<?php echo get_stylesheet_directory_uri(); ?>/style.css" />

<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

	<!-- Prompt IE 6 users to install Chrome Frame. Remove this if you support IE 6.
    	 http://chromium.org/developers/how-tos/chrome-frame-getting-started -->
	<!--[if lt IE 7]><p class=chromeframe>Your browser is <em>ancient!</em> <a href="http://browsehappy.com/">Upgrade to a different browser</a> or <a href="http://www.google.com/chromeframe/?redirect=true">install Google Chrome Frame</a> to experience this site.</p><![endif]-->
<div class="container">
	<header>
	<?php
    	// Create the 'items_wrap' to be used in the wp_nav_menu() args.
	    //$items_wrap  = '<a class="brand" href="' . site_url() . '">' . get_bloginfo('name') . '</a>';
    	$items_wrap = '<ul id="%1$s" class="%2$s">%3$s</ul>';
	    $items_wrap .= get_search_form( $echo = false );

		$menu_args = array(
			'theme_location' => 'main-bootstrap-menu',
			'depth'          => 2,
			'container'      => false,
			'menu_class'     => 'nav',
			'items_wrap'     => $items_wrap,
			'walker'         => new Bootstrap_Walker_Nav_Menu()
		);
		?>

		<div class="navbar">
			<div class="navbar-inner">
				<div class="container">
						<?php wp_nav_menu($menu_args); ?>
				</div> <!-- end .container -->
			</div> <!-- end .navbar-inner -->
		</div> <!-- end .navbar -->
</header>
