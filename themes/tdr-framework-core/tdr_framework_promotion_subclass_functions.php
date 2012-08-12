<?php
Class tdr_promotions_menus extends tdr_promotions {
    function init() {
        $this->setup_admin_menus();
    }
    function setup_admin_menus() {
        // Add menu entry for Promotions for wp-admin
        $page_title = 'Promotions';
        $menu_title = 'Promotions';
        $capability = 'manage_options'; // Only available to administrators
        $menu_slug = 'tdr_promotions';
        $function = array( $this, 'output_promotion_admin_settings_page' );
        /*
        $icon_url = ''; // Define at a later time
        $position = ''; // Define at a later time
         */
        add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function );
		
        // Add submenu item Contests
        $parent_slug = 'tdr_promotions';
        $page_title = 'Contests';
        $menu_title = 'Contests';
        $capability = 'manage_options'; // Only available to administrators
        $menu_slug = 'tdr_contests';
        $function = array( $this, 'output_contest_admin_settings_page' );
        add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
        
        // Add settings section
        $id = 'tdr_contests_settings';
        $title = 'General Contest Settings';
        $function = array( $this, 'output_contest_settings_general_section' );
        $page = 'tdr_contests';
        add_settings_section($id, $title, $function, $page);
        
        // Add settings field
        
        $setting_id = 'tdr_promotions_site_title_function';
        $setting_name = 'Site Title Function';
        $function = array( $this, 'output_contest_settings_site_title_function' );
        $menu_slug = 'tdr_contests';
        $section_id = 'tdr_contests_settings';
		add_settings_field($setting_id, $setting_name, $function, $menu_slug, $section_id );
		
        // Register settings
        $option_group = 'tdr_promotions_general_contest_settings';
        $option_name = 'tdr_promotions_site_title_function';
        register_setting( $option_group, $option_name, array( $this, 'validate_options' ) );
    }
    function validate_options( $input ) {
		$valid_input = array();
		/*foreach ( $input as $input_key => $input_value ) {			
			switch( $input_key ) {
				case 'tdr_promotions_site_title_function':
					$valid_input[ $input_key ] = sanitize_text_field( $input_value );
					break;
			}
		}
		*/
		$valid_input = sanitize_text_field( $input );
		return $valid_input;
	}
    function output_contest_settings_general_section () {
		echo '<p>These settings affect all contests side-wide.</p>';
	}
	function output_contest_settings_site_title_function() {
		echo '<input name="tdr_promotions_site_title_function" id="tdr_promotions_site_title_function" type="text" value="' . esc_attr( get_option('tdr_promotions_site_title_function') ) . '" class="code" style="width: 400px;" />';
	}
    function output_contest_admin_settings_page() {
	?>  
		<div class="wrap">  
			<div class="icon32" id="icon-options-general"></div>  
			<h2>Contests Settings</h2>
	  
			<form action="options.php" method="post">
				<?php				
				settings_fields( 'tdr_promotions_general_contest_settings' );   

				do_settings_sections( 'tdr_contests' );  				
				?>
				<p class="submit">  
					<input name="Submit" type="submit" class="button-primary" value="Save Changes" />  
				</p>  
	  
			</form>  
		</div><!-- wrap -->  
	<?php
    }	
}
?>
