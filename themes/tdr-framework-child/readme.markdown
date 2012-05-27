# Files Needed
You need both the **tdr-framework-core** and **tdr-framework-child** in your ./theme directory.  Also, you must Active the _Thunder Framework Child_

# Don't Forget to Add these Files to the **Child Theme**
These files are not included in the repo specifically so they are not overwritten on any updates.  These files must be added manually for the Framework to function properly.

* ./[Child-Theme-Dir]
	* index.php
	* header.php
	* footer.php
	* functions.php
* ./[Child-Theme-Dir/js]
	* tdr-child-custom.js

# To Build the CSS File
1. Change variables.less to your preferences.
1. Add any custom styling you want in custom.less.
1. If you are using media queries you can add them to layout.less.
1. At the command line run makefile-css.py (Python).  To minify the output, pass `-m` to the makefile.

# To Build the Javascript File
1. Run makefile-js.py (Python) that will build ./js/tdr-framework.js.  To minify the output, pass `-m` to the makefile.
1. Add any custom javascript to ./js/tdr-child-custom.js.  this will automatically be loaded by the child theme.

# A Typical Menu Example

	$args = array(
		'theme_location' => 'main-bootstrap-menu',
		'depth' => 2,
		'container' => false,
		'menu_class' => 'nav',
		'items_wrap' => '
			<a class="brand" href="#">Project Name</a>
			<ul id="%1$s" class="%2$s">%3$s</ul>' .
			get_search_form( $echo = false ),
		'walker' => new Bootstrap_Walker_Nav_Menu()
	);

	wp_nav_menu($args);

* Enable *'CSS Classes'* in the back-end under *'Screen Options'*.  This will let you set classes and is useful for making **Dividers**
* Here the *'items_wrap'* plays an important role.   For the **Project Name** and **Search** functionality this is the best place to include this.


