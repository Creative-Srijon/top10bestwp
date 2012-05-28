<?php
/*
Plugin Name: thunder-plugin-products
Plugin URI: http://path/to/page
Description: Facilitates 'product' listings and characteristics for Verticals
Version: 0.24
Author: Jeremy Hough/Digital Brands
Author URI: http://path/to/page
License: GPL2
*/

/*  Copyright 2012 Digital Brands  (email : email.goes@here)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
  * Digital Brands uses this suite of tools for creating, editing, and accessing information about products and services listed in vertical sites.
  *
  * This package includes all the necessary helper functions to initialize product/service listing functionality based on WordPress events. Please note that the native WordPress functions the_tags(), get_the_tags(), the_category(), and get_the_category() should be used because this framework does not reimplement them.
  *
  * &nbsp;
  *
  * List of meta boxes:
  *
  * &nbsp;
  *
  * Affiliate URL
  *
  * Rank
  *
  * CPA
  *
  * Conversion Rate
  *
  * EPC
  *
  * Internal Rating
  *
  * External Rating
  *
  * Window Size
  *
  * Display URL
  *
  * Price
  *
  * Price Frequency
  *
  * Custom
  *
  * Google Analytics SKU
  *
  * Affiliate Name
  *
  * Internal Notes
  *
  * Short URL
  * @package  thunder-plugin-products
  */
//Include file for automatic updates - Uncomment when update server has been configured
//require_once("product-update.php");
/**
  * Initializes an instance of the Thunder_Plugin_Products class and calls the init() method
  * @uses Thunder_Plugin_Products::init()
  * @package  thunder-plugin-products
  */
add_action( 'init', 'Thunder_Plugin_Products_Init' );
function Thunder_Plugin_Products_Init() {
	$product = new Thunder_Plugin_Products();
	$product->init();
}

add_action( 'admin_menu', 'tdr_products_section_admin_page', 10, 1 );
function tdr_products_section_admin_page() {
	$product = new Thunder_Plugin_Products();
	$product->init_section_settings_page();
}

add_action( 'admin_enqueue_scripts', 'tdr_products_load_form_control_javascript', 10, 1 );
function tdr_products_load_form_control_javascript( $hook ) {
    global $post;
    if ( ( $hook == 'post-new.php' || $hook == 'post.php' ) && ('tdr_product' === $post->post_type) ) {
        wp_register_script(  'tdr-product-form-object-control',
		plugins_url('js/tdr-product-form-object-control.js', __FILE__ ),
		array('jquery'),
		'1.0' );
		wp_enqueue_script('tdr-product-form-object-control');
    }
}

/**
  * Initializes an instance of the Thunder_Plugin_Products class and calls the formatting() method. Used for prepending all meta box information to posts as a dump
  * @uses Thunder_Plugin_Products::formatting()
  * @param object $content The content of the current post
  * @return string The content of all meta boxes used in the post
  * @package  thunder-plugin-products
  */
//add_filter( 'the_content', 'Thunder_Plugin_Products_Filter' );
function Thunder_Plugin_Products_Filter( $content ) {
	$product = new Thunder_Plugin_Products();
	return $product->formatting( $content );
}

/*
* DB Product Class
*/
/**
 * Begin DocBlox markup
 * @author Jeremy Hough <jeremy@digitalbrands.com>
 * @copyright 2012 Digital Brands Inc.
 * @license GPL2
 * @package  thunder-plugin-products
 * @version 0.24
*/
class Thunder_Plugin_Products {
	//Setup
	/**
	 * Trigger for methods linked to wordpress actions
	 * @uses Thunder_Plugin_Products::meta_fields()
	 * @uses Thunder_Plugin_Products::process_changes()
	 */
	function init() {
		add_action( 'add_meta_boxes', array( &$this, 'meta_fields' ) );
		//On save hook, pass data to process method at default priority with 2 arguments
		add_action( 'save_post', array( &$this, 'process_changes' ), 10, 2 );
	}

//	private static $local_key_value_index_counter;
//	private static $global_key_value_index_counter;
	/**
	 * Counter used by key-value pair metaboxes to generate unique names/ids for form markup
	 */
	private static $key_value_field_index_counter;
	
	/**
	 * Gets global keys from given key-value pair meta id
	 */
	function fetch_custom_keys ( $meta_id ) {
		// Get serialized list of custom comparison keys from WP options -- false if not set
		$custom_keys = get_option( $meta_id . '_keys', $default = array() );

		return $custom_keys;
	}
	/**
	 * Method for getting meta box configurations
	 * @return array Array of meta box settings
	 */
	function define_custom_meta_types() {
		// Defines product meta field properties array
//		global $product_meta_field_properties_array;
//		require_once( 'custom_meta_types.php' );
		$product_meta_field_properties_array = array (
			// unique ID, title, callback function, post-type, context (location), priority, filter-type
				'aff_url' => array( 
					'uuid' => 'thunder-plugin-products-aff-url', 
					'title' => 'Affiliate URL', 
					'desc' => 'enter url here', 
					'callback' => array( &$this, 'meta_display' ), 
					'page' => 'tdr_product', 
					'context' => 'normal', 
					'priority' => 'default', 
					'filter' => 'url', 
					'type' => 'singular' 
				),
				'best_discount_url' => array (
					'uuid' => 'thunder-plugin-products-best-disount-url',
					'title' => 'Best Discount URL',
					'desc' => 'enter url here',
					'callback' => array( &$this, 'meta_display' ),
					'page' => 'tdr_product',
					'context' => 'side',
					'priority' => 'default',
					'filter' => 'url',
					'type' => 'singular'
				),
				'rank' => array( 
					'uuid' => 'thunder-plugin-products-rank', 
					'title' => 'Product Rank', 
					'desc' => 'enter rank here', 
					'callback' => array( &$this, 'meta_display' ), 
					'page' => 'tdr_product', 
					'context' => 'side', 
					'priority' => 'default', 
					'filter' => 'html',
					'type' => 'singular' 
				),
				'cpa' => array( 
					'uuid' => 'thunder-plugin-products-cpa', 
					'title' => 'CPA Value', 
					'desc' => 'enter CPA here', 
					'callback' => array( &$this, 'meta_display' ), 
					'page' => 'tdr_product', 
					'context' => 'side', 
					'priority' => 'default', 
					'filter' => 'float', 
					'type' => 'array' /* is_array */ 
				),
				'conv' => array( 
					'uuid' => 'thunder-plugin-products-conv', 
					'title' => 'Conversion Rate' /* should be automatically generated */, 
					'desc' => 'enter conversion rate here', 
					'callback' => array( &$this, 'meta_display' ), 
					'page' => 'tdr_product', 
					'context' => 'side', 
					'priority' => 'default', 
					'filter' => 'float', 
					'type' => 'singular' 
				),
				'epc' => array( 
					'uuid' => 'thunder-plugin-products-epc', 
					'title' => 'Product EPC' /* should be automatically calculated */, 
					'desc' => 'enter EPC here', 
					'callback' => array( &$this, 'meta_display' ), 
					'page' => 'tdr_product', 
					'context' => 'side', 
					'priority' => 'default', 
					'filter' => 'float', 
					'type' => 'singular' 
				),
				'int_rating' => array( 
					'uuid' => 'thunder-plugin-products-int-rating', 
					'title' => 'Internal Rating', 
					'desc' => 'enter internal rating here', 
					'callback' => array( &$this, 'meta_display' ), 
					'page' => 'tdr_product', 
					'context' => 'side', 
					'priority' => 'default', 
					'filter' => 'html', 
					'type' => 'singular' 
				),
				'ext_rating' => array( 
					'uuid' => 'thunder-plugin-products-ext-rating', 
					'title' => 'External Rating', 
					'desc' => 'enter external rating here', 
					'callback' => array( &$this, 'meta_display' ), 
					'page' => 'tdr_product', 
					'context' => 'side', 
					'priority' => 'default', 
					'filter' => 'html', 
					'type' => 'singular' 
				),
				'win_size' => array( 
					'uuid' => 'thunder-plugin-products-win-size', 
					'title' => 'Window size', 
					'desc' => 'enter width, height eg: 300, 200', 
					'callback' => array( &$this, 'meta_display' ), 
					'page' => 'tdr_product', 
					'context' => 'side', 
					'priority' => 'default', 
					'filter' => 'stripspace', 
					'type' => 'array' /* is_array */ 
				),
				'win_offset' => array( 
					'uuid' => 'thunder-plugin-products-win-offset', 
					'title' => 'Window offset', 
					'desc' => 'enter offset x, y in pixels eg: 300, 200', 
					'callback' => array( &$this, 'meta_display' ), 
					'page' => 'tdr_product', 
					'context' => 'side', 
					'priority' => 'default', 
					'filter' => 'stripspace', 
					'type' => 'array' /* is_array */ 
				),
				'disp_url' => array( 
					'uuid' => 'thunder-plugin-products-disp-url',
					'title' => 'Display URL', 
					'desc' => 'enter url here', 
					'callback' => array( &$this, 'meta_display' ), 
					'page' => 'tdr_product', 
					'context' => 'normal', 
					'priority' => 'default', 
					'filter' => 'url', 
					'type' => 'singular' 
				),
				'freq' => array( 
					'uuid' => 'thunder-plugin-products-price-freq', 
					'title' => 'Pricing Continuum', 
					'desc' => 'Billing/Price pairs', 
					'callback' => array( &$this, 'meta_display' ), 
					'page' => 'tdr_product', 
					'context' => 'side', 
					'priority' => 'default', 
					'filter' => 'html', 
					'type' => 'local_key_value_pair' /* object-like */,
					'extra_params' => array(
						'labels' => array(
							'key' => 'Frequency',
							'value' => 'Value'
						)
					) 
				),
				'compare' => array( 
					'uuid' => 'thunder-plugin-products-comparison-points', 
					'title' => 'Comparison Points', 
					'desc' => 'Field/Value pairs', 
					'callback' => array( &$this, 'meta_display' ), 
					'page' => 'tdr_product', 
					'context' => 'normal', 
					'priority' => 'high', 
					'filter' => 'html', 
					'type' => 'global_key_value_pair' /* object-like */,
					'extra_params' => array(
						'labels' => array(
							'key' => 'Key',
							'value' => 'Value'
						)
					) 
				),
				'subratings' => array( 
					'uuid' => 'thunder-plugin-products-subratings', 
					'title' => 'Subratings', 
					'desc' => 'Field/Value pairs', 
					'callback' => array( &$this, 'meta_display' ), 
					'page' => 'tdr_product', 
					'context' => 'normal', 
					'priority' => 'high', 
					'filter' => 'html', 
					'type' => 'global_key_value_pair' /* object-like */,
					'extra_params' => array(
						'labels' => array(
							'key' => 'Key',
							'value' => 'Rating'
						)
					) 
				),
				'full_review_sections' => array( 
					'uuid' => 'thunder-plugin-products-full-review-sections', 
					'title' => 'Full Review Sections', 
					'desc' => 'The sections used in the full review pages', 
					'callback' => array( &$this, 'meta_display' ), 
					'page' => 'tdr_product', 
					'context' => 'normal', 
					'priority' => 'high', 
					'filter' => 'html', 
					'type' => 'section_listings' /* object-like */ 
				),
				//'compare' => array(
				//	'uuid' => 'thunder-plugin-products-comparison-points',
				//	'title' => 'Comparison Points',
				//	'desc' => 'Used to compare different Products',
				//	'callback' => array( &$this, 'meta_display' ),
				//	'page' => 'tdr_product',
				//	'context' => 'normal',
				//	'priority' => 'high',
				//	'filter' => 'html',
				//	'type' => 'object'
				//),
				//'custom' => array( 
				//	'uuid' => 'thunder-plugin-products-custom', 
				//	'title' => 'Custom Field', 
				//	'desc' => 'enter custom text here', 
				//	'callback' => array( &$this, 'meta_display' ), 
				//	'page' => 'tdr_product', 
				//	'context' => 'side', 
				//	'priority' => 'default', 
				//	'filter' => 'html', 
				//	'type' => 'singular' 
				//),
				'ga_sku' => array( 
					'uuid' => 'thunder-plugin-products-ga-sku', 
					'title' => 'GA Product SKU', 
					'desc' => 'enter GA SKU here', 
					'callback' => array( &$this, 'meta_display' ), 
					'page' => 'tdr_product', 
					'context' => 'side', 
					'priority' => 'default', 
					'filter' => 'html', 
					'type' => 'singular' 
				),
				'aff_name' => array( 
					'uuid' => 'thunder-plugin-products-aff-name', 
					'title' => 'Affiliate Name', 
					'desc' => 'enter affiliate here', 
					'callback' => array( &$this, 'meta_display' ), 
					'page' => 'tdr_product', 
					'context' => 'side', 
					'priority' => 'default', 
					'filter' => 'html', 
					'type' => 'singular' 
				),
				'int_notes' => array( 
					'uuid' => 'thunder-plugin-products-int-notes', 
					'title' => 'Affiliate Notes', 
					'desc' => 'enter notes here', 
					'callback' => array( &$this, 'meta_display' ), 
					'page' => 'tdr_product', 
					'context' => 'normal', 
					'priority' => 'default', 
					'filter' => 'html', 
					'type' => 'singular' 
				),
				'short_url' => array( 
					'uuid' => 'thunder-plugin-products-short-url', 
					'title' => 'Short URL', 
					'desc' => 'enter short url here', 
					'callback' => array( &$this, 'meta_display' ), 
					'page' => 'tdr_product', 
					'context' => 'side', 
					'priority' => 'default', 
					'filter' => 'url', 
					'type' => 'singular' 
				)
			);
		return $product_meta_field_properties_array;
	}
	/**
	 * Method for processing meta box configurations
	 *
	 * Loops through meta box configuration array provided by Thunder_Plugin_Products::define_custom_meta_types() and initializes each field
	 * @uses Thunder_Plugin_Products::define_custom_meta_types()
	 * @uses Thunder_Plugin_Products::meta_display()
	 * @return array Array of meta box settings
	 */
	function meta_fields() {
		$meta_array = $this->define_custom_meta_types();
		foreach ( $meta_array as $meta_entry ) {
			$hyphen_name = $meta_entry['uuid'];
			$filter_type = $meta_entry['filter'];
			$meta_desc = $meta_entry['desc'];
			$meta_type = $meta_entry['type'];
			// Allow fields to also pass unique information, defined in key 'custom' with a value of an array of unique key => value pairs
			$extra_params = array();
			// If custom key is defined, add its contents to be used below in add_meta_box
			if ( $meta_entry['extra_params'] ) {
				$extra_params = $meta_entry['extra_params'];
			}
			add_meta_box(
				$meta_entry['uuid'],					// Unique ID
				$meta_entry['title'],					// Title
				$meta_entry['callback'],				// Callback function  -- is constant [could define static]
				$meta_entry['page'],					// Admin page (or post type) -- is constant [could define static]
				$meta_entry['context'],					// Context
				$meta_entry['priority'],				// Priority -- is constant [could define static]
				array( 'name'=>$hyphen_name, 'filter'=>$filter_type, 'desc'=>$meta_desc, 'type'=>$meta_type, 'extra_params' => $extra_params )
			);
		}
	}
	/**
	 * Creates underscored ids for use in database and related methods
	 * @param string $base The id to underscore
	 * @return string Underscored id
	 */
	function underscore ( $base ) {
		return str_replace( '-', '_', $base);
	}

	/**
	 * Displays metaboxes on tdr_product editor interface
	 *
	 * Includes nonce check, form field, label, description, and current value for meta box
	 * @uses Thunder_Plugin_Products::underscore()
	 * @param object $object The post
	 * @param array $box Arguments to be used for meta id and meta box description in output
	 */
	function meta_display ( $object, $box ) {
		// Object-like meta have json values parsed and separated into different input boxes.
		// Local -- keys specific to this product
		if ( $box['args']['type'] == 'local_key_value_pair' ) {
		//	// Increase index local key value pair metaboxes for use with generating unique ids for markup
		//	$this->local_key_value_index_counter++;
			// Declare default custom variables
			$defaults = array(
				'labels' => array(
					'key' => 'Key',
					'value' => 'Value'
				)
			);
			$args = wp_parse_args( $box['args']['extra_params'], $defaults );
			extract( $args, EXTR_SKIP );

			// Nonce
			wp_nonce_field( basename( __FILE__ ), $this->underscore( $box['args']['name']) . 'nonce' );

			// INPUT NAME
			echo ( '<input type="hidden" name="' );
			echo ( $box['args']['name'] . '" id="' . $box['args']['name'] . '"' );
			// INPUT VALUE
			echo ( 'value="' );
			echo ( esc_attr( get_post_meta( $object->ID, $this->underscore($box['args']['name']), true ) ) );
			echo ( '" size="30" />' );

			// TABLE
			echo ( '<table class="tdr-object-input-container tdr-local-key-value-pair" style="width: 100%; border-collapse: collapse; margin-top: 20px;">' );
				// Get post meta
				$local_key_value_pair = get_post_meta( $object->ID, $this->underscore($box['args']['name']), true );
				// Decode resulting json into associative array
				$local_key_value_pair = json_decode( $local_key_value_pair, true );
				// Add form fields for each key-value pair
				if ( is_array( $local_key_value_pair ) ) {
					foreach ( $local_key_value_pair as $key => $value ) {
						echo ( '<tr style="width: 100%;">' );
						echo ( '<td>' );
							echo ( '<label for="key' . $this->key_value_field_index_counter . '">' . $labels['key'] . '</label>' ) ;
							echo ( '<br />' );
							echo ( '<textarea name="key' . $this->key_value_field_index_counter . '" id="key' . $this->key_value_field_index_counter . '" style="width:99%;" rows="2">' );
								echo ( urldecode( $key ) );
							echo ( '</textarea>' );
						echo ( '</td>' );
						echo ( '<td>' );
							echo ( '<label for="value' . $this->key_value_field_index_counter . '">' . $labels['value'] . '</label>' );
							echo ( '<br /><textarea name="value' . $this->key_value_field_index_counter . '" id="value' . $this->key_value_field_index_counter . '" style="width:99%;" rows="2">' );
								echo ( urldecode( $value ) );
							echo ( '</textarea>' );
						echo ( '</td>' );
						echo ('<td><div class="tagchecklist"><span><a class="ntdelbutton">X</a></span></div></td>' );
						echo ( '</tr>' );
						$this->key_value_field_index_counter++;
					}
				}
			echo ( '</table>' );
			echo ( '<div class="meta_object_pair_controls" align="right">' );
				echo ( '<button class="tdr-object-add-pair button" style="margin-right:25px; margin-top:5px;" data-label-key="' . esc_attr( $labels['key'] ) . '" data-label-value="' . esc_attr( $labels['value'] ) . '">Add pair</button>' );
				echo ( '<button class="tdr-object-do-ajax local button" style="margin-right:25px; margin-top:5px;" data-post-id="' . $object->ID . '">Save changes</button>' );
			echo ( '</div>' );
		}
		// Object-like meta have json values parsed and separated into different input boxes.
		// Global -- synchronized keys
		elseif ( $box['args']['type'] == 'global_key_value_pair' ) {
		//	// Increase index global key value pair metaboxes for use with generating unique ids for markup
		//	$this->global_key_value_index_counter++;
			// Declare default custom variables
			$defaults = array(
				'labels' => array(
					'key' => 'Key',
					'value' => 'Value'
				)
			);
			$args = wp_parse_args( $box['args']['extra_params'], $defaults );
			extract( $args, EXTR_SKIP );
			
			// Import important comparison keys from wordpress options table
			$base_key_array = $this->fetch_custom_keys( $this->underscore($box['args']['name']) );
			
			// Get Saved post meta values for comparison info
			$saved_values = get_post_meta( $object->ID, $this->underscore($box['args']['name']), true );
			// Decode saved meta value JSON
			$saved_values = json_decode( $saved_values, true );
			if ( empty( $saved_values ) ) {
				// Make an empty array if not set
				$saved_values = array();	
			}
			
			// Backwards-compatibility fallback
			if ( empty( $base_key_array ) && !empty( $saved_values ) ) {
				$base_key_array = $saved_values;
			}

			// Find saved values with important keys
			$saved_values = array_intersect_key( $saved_values, $base_key_array );
			// Add any missing important keys
			$merged_fields = $saved_values + $base_key_array;
			
			// Nonce
			wp_nonce_field( basename( __FILE__ ), $this->underscore( $box['args']['name']) . 'nonce' );

			// INPUT NAME
			echo ( '<input type="hidden" name="' );
			echo ( $box['args']['name'] . '" id="' . $box['args']['name'] . '"' );
			// INPUT VALUE
			echo ( 'value="' );
			echo ( esc_attr( get_post_meta( $object->ID, $this->underscore($box['args']['name']), true ) ) );
			echo ( '" size="30" />' );

			// INSTRUCTIONS
		    echo ( '<span style="color:#55aadd; font-size:16px;">Changes to these fields can only be saved by clicking "Save changes" below. Pressing update or publish post will have no effect. Only administrators can add/remove fields.</span>' );	
			// TABLE
			echo ( '<table class="tdr-object-input-container tdr-global-key-value-pair" style="width: 100%; border-collapse: collapse; margin-top: 20px;">' );
			
				$global_key_value_pair = $merged_fields;
				// Add form fields for each key-value pair
				if ( is_array( $global_key_value_pair ) ) {
					foreach ( $global_key_value_pair as $key => $value ) {
						echo ( '<tr style="width: 100%;">' );
						echo ( '<td style="border-bottom: 2px solid #ccc; padding: 10px 0px 5px 0px;">' );
							echo ( '<label for="key' . $this->key_value_field_index_counter . '">' . $labels['key'] . '</label>' ) ;
                            echo ( '<br />' );
                        // Disable editing the key for non-admins
                        if( !current_user_can('administrator') ) {
                            $disable_textarea = ' disabled="disabled"';
                            $disable_textarea_style = 'background-color:#f8f8f8; color:#999;';
                        }
                        else {
                            $disable_textarea = '';
                            $disable_textarea_style = '';
                        }
                            echo ( '<textarea name="key' . $this->key_value_field_index_counter . '" id="key' . $this->key_value_field_index_counter . '" style="width:99%;' . $disable_textarea_style . '" rows="2"' . $disable_textarea . ' data-original-key="' . esc_attr( urldecode( $key ) ) . '">' );
								echo ( urldecode( $key ) );
							echo ( '</textarea>' );
						echo ( '</td>' );
						echo ( '<td style="border-bottom: 2px solid #ccc; padding: 10px 0px 5px 0px;">' );
							echo ( '<label for="pricevalue' . $this->key_value_field_index_counter . '">' . $labels['value'] . '</label>' );
							echo ( '<br /><textarea name="value' . $this->key_value_field_index_counter . '" id="value' . $this->key_value_field_index_counter . '" style="width:99%;" rows="2">' );
								echo ( urldecode( $value ) );
							echo ( '</textarea>' );
						echo ( '</td>' );
                        echo ( '<td>' );
                        if( current_user_can('administrator') ) {
                            echo( '<div class="tagchecklist"><span><a class="ntdelbutton">X</a></span></div>' );
                        }
                        echo ( '</td>' );
						echo ( '</tr>' );
						$this->key_value_field_index_counter++;
					}
				}
			echo ( '</table>' );
            echo ( '<div class="meta_object_pair_controls" align="right">' );
                if ( current_user_can('administrator') ) {
                    echo ( '<button class="tdr-object-add-pair button" style="margin-right:25px; margin-top:5px;" data-label-key="' . esc_attr( $labels['key'] ) . '" data-label-value="' . esc_attr( $labels['value'] ) . '">Add pair</button>' );
                }
				echo ( '<button class="tdr-object-do-ajax global button" style="margin-right:25px; margin-top:5px;" data-post-id="' . $object->ID . '">Save changes</button>' );
			echo ( '</div>' );
		}
		else if ( $box['args']['type'] == 'section_listings' ) {
			// Get master list of sections and subsections
			$product_section_settings = get_option( 'thunder_plugin_products_section_settings', $default = array() );
			// Get saved list for current product
			$saved_values = get_post_meta( $object->ID, $this->underscore($box['args']['name']), true );
			// Decode saved section list JSON
			$saved_values = json_decode( $saved_values, true );
			if ( empty( $saved_values ) ) {
				// Make an empty array if not set
				$saved_values = array();	
			}
			// Merge together
			// First, find master top-level sections in product's saved list
			$saved_values = array_intersect_key( $saved_values, $product_section_settings ); // Stripped of extra sections not found in master list
			// Next, Remove subsections not found in master list's entry for each top-level section
			foreach ( $saved_values as $section ) {
				$saved_values[ $section['slug'] ]['subsections'] = array_intersect_key( $saved_values[ $section['slug'] ]['subsections'], $product_section_settings[ $section['slug'] ]['subsections'] );
				// Add any missing important keys -- if keys (slugs) are numeric, this will reindex them!
				$saved_values[ $section['slug'] ]['subsections'] = array_merge( $product_section_settings[ $section['slug'] ]['subsections'], $saved_values[ $section['slug'] ]['subsections'] );
			}
			// Add any missing important keys
			$merged_fields = $saved_values + $product_section_settings;
			// Missing fields have no value key/value for subsections 
			
			// Display
			echo( '<div id="tdr_product_sections" data-post-id="' . $object->ID . '" style="width:100%;">' );
			if ( !empty( $merged_fields ) ) {
				foreach ( $merged_fields as $section ) {
				// Output top-level sections
				echo( '<table class="tdr_product_section" style="width:100%; border: 2px solid #ccc; margin-bottom:15px;">' );
				echo( '<tr><th><h3 name="' . $section['slug'] . '">' . urldecode( $section['name'] ) . '</h3></th><th><h3>Value</h3></th></tr>' );
					foreach ( $section['subsections'] as $subsection ) {
					// Output sub-sections
						echo( '<tr class="tdr_product_subsection" style="width:100%;">' );
						echo( '<td style="width:15%; padding-right:20px; text-align:right;">' . urldecode( $subsection['name'] ) . '</td>' );
						echo( '<td style="width:85%;"><textarea name="' . $subsection['slug'] . '" style="width:90%;">' . urldecode( $subsection['value'] ) . '</textarea></td>' );
						echo( '</tr>' );
					}
					echo( '</table>');
				}
				
			}
			echo ( '</div>' );
			
			// Output ajax save button
			echo( '<p><button id="tdr_save_sections" class="button">Save sections</button></p>' );
		}
		// Singulars (non-objects, primitive types) have a single meta box form field generated.
		else  {
			/*
			USAGE:
			echo $box['args']['foo'];
			echo $box['args']['bar'];
			*/
			wp_nonce_field( basename( __FILE__ ), $this->underscore($box['args']['name']).'nonce' );
			echo ( '<p><label for="' . $box['args']['name'] . '">' );
				echo ( $box['args']['desc'] ); /* should be custom per meta field -- pass during callback */
			echo ( '</label><br /><input class="widefat" type="text" name="' . $box['args']['name'] . '" id="' . $box['args']['name'] . '" ' );
			echo ( 'value="' . esc_attr( get_post_meta( $object->ID, $this->underscore($box['args']['name']), true ) ) . '" size="30" /></p>' );
		}
	}
	
	/**
	 * Escaping filter for meta data fields
	 *
	 * Sanitizes input data with escaping rules for urls, integers, stripping spaces, floating point numbers, and html
	 * @param string $meta_key The meta box id
	 * @param string $filter_class The type of escaping to apply
	 * @return string Sanitized input data for meta field
	 */
	function meta_escape_filter ( $meta_key, $filter_class ) {
		//SWITCH statement for type
		switch ( $filter_class ) {
			case "url":
			$new_meta_value = esc_url( $_POST[$meta_key] );
			break;
			case "int":
			$new_meta_value = intval( $_POST[$meta_key] );
			break;
			case "stripspace":
			$new_meta_value = str_replace( ' ', '', $_POST[$meta_key] );
			break;
			case "float":
			$new_meta_value = filter_var( $_POST[$meta_key], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
			break;
			default:
			$new_meta_value = esc_html( $_POST[$meta_key] );
		}
		return ( isset( $_POST[$meta_key] ) ? $new_meta_value : '' ); 
	}
	
	/**
	 * Processes data in meta boxes
	 *
	 * Performs validation, sanitation, and relevant CRUD operation to database
	 * @uses Thunder_Plugin_Products::check_post_type()
	 * @uses Thunder_Plugin_Products::perm_check()
	 * @uses Thunder_Plugin_Products::define_custom_meta_types()
	 * @uses Thunder_Plugin_Products::nonce_check()
	 * @uses Thunder_Plugin_Products::meta_escape_filter()
	 * @uses Thunder_Plugin_Products::underscore()
	 * @uses Thunder_Plugin_Products::get_post_meta()
	 * @uses Thunder_Plugin_Products::meta_check()
	 * @param int $post_id post identification number
	 * @param object $post the post
	 */
	function process_changes ( $post_id, $post ) {
		// Check if this is an autosave, doesn't save custom post meta
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		//Perform once
		$post_type = $this->check_post_type( $post );
		$this->perm_check( $post_type, $post_id );
		//Perform loop
		$meta_array = $this->define_custom_meta_types();
		foreach ( $meta_array as $meta_entry ) {
			if ( ( $meta_entry['type'] == 'global_key_value_pair' ) || ( $meta_entry['type'] == 'local_key_value_pair' ) || ( $meta_entry['type'] == 'section_listings' ) ) {
				continue;
			}
			$this->nonce_check( $meta_entry['uuid'] ); //Ensure has purpose
			//Meta escape filter stage
			//Example code:
			//$this->filter_meta( $post, $field_type );
			/*
			$new_meta_value = ( isset( $_POST['smashing-post-class'] ) ? sanitize_html_class( $_POST['smashing-post-class'] ) : '' );
			*/
			$new_meta_value = $this->meta_escape_filter( $meta_entry['uuid'], $meta_entry['filter'] );
			$meta_key = $this->underscore($meta_entry['uuid']);
			$meta_value = get_post_meta( $post_id, $meta_key, true );
			$this->meta_check( $meta_key, $meta_value, $new_meta_value, $post_id );	
		}
	}
	/**
	 * Performs database CRUD operations for meta boxes
	 * @uses Thunder_Plugin_Products::update_key()
	 * @uses Thunder_Plugin_Products::rm_key()
	 * @param string $meta_key The meta box name
	 * @param string $meta_value The previous meta field value, if any
	 * @param string $new_meta_value The current meta field value
	 * @param int $post_id The post identification number
	 */
	function meta_check( $meta_key, $meta_value, $new_meta_value, $post_id ) {

		/* If there the new meta value is blank but an old value existed, delete it. */
		if ( '' == $new_meta_value && $meta_value )
			$this->rm_key( $post_id, $meta_key, $meta_value );	
	
		/* Updates meta value if it exists, Else Adds it to the post. */
		else
			$this->update_key( $post_id, $meta_key, $new_meta_value );
			
	}
	/**
	 * Permission check for posting rights
	 * @param object $post_type The post type object
	 * @param int $post_id The post identification number
	 * @return int $post_id The post identification number, if the test passes
	 * TODO: use continue statement on loop when test fails during Thunder_Plugin_Products::process_changes() method
	 */
	function perm_check( $post_type, $post_id ) {
		//make die on failure
		if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
				return $post_id;
	}
	/**
	 * Finds the type of the current post
	 * @param object $post The current post
	 * @returns object The post type
	 */
	function check_post_type( $post ) {
		return get_post_type_object( $post->post_type );
	}
	/**
	 * Nonce check for maintaining form security to WordPress standards
	 * @param string $meta_key The meta box name
	 * @return int $post_id The post identification number, if the test passes
	 * TODO: pass the $meta_key value from Thunder_Plugin_Products::process_changes() method
	 */
	function nonce_check ( $meta_key ) {
		//make die on failure
		if ( !isset( $_POST['Thunder_Plugin_Products_aff_url_nonce'] ) || !wp_verify_nonce( $_POST['Thunder_Plugin_Products_aff_url_nonce'], basename( __FILE__ ) ) )
			return $post_id;
	}
	/**
	 * Edits data for meta field if present, Adds to post if new
	 * @param int $post_id The post identification number
	 * @param string $meta_key The meta box name
	 * @param string $new_meta_value The current meta field value
	 */
	function update_key ( $post_id, $meta_key, $new_meta_value ) {
		update_post_meta( $post_id, $meta_key, $new_meta_value );
	}
	/**
	 * Removes data for meta field
	 * @param int $post_id The post identification number
	 * @param string $meta_key The meta box name
	 * @param string $new_meta_value The current meta field value
	 */
	function rm_key ( $post_id, $meta_key, $meta_value ) {
		delete_post_meta( $post_id, $meta_key, $meta_value );
	}
	/**
	 * Gets data from meta field
	 * @param int $post_id The post identification number
	 * @param string $meta_key The meta box name
	 * @param string $new_meta_value The current meta field value
	 */
	function get_key ( $post_id, $meta_key ) {
		return get_post_meta( $post_id, $meta_key, true );
	}

	/** Returns the ID of the current Product
	 * @var $post Global post variable
	 * @return int $id The post ID.
	 */
	function get_the_ID() {
		global $post;
		$id = $post->ID;
		return $id;
	}

	function get_the_affiliate_image() {
		global $post;
		$image = get_the_post_thumbnail();
		return $image;
	}

	/**
	 * Prepares get_the_{meta_box} methods
	 * @var $post Global post variable 
	 * @return int $id The post identification number
	 */
	private function get_the_setup () {
		global $post;
		$id = isset( $post->ID ) ? $post->ID : ( int ) $id;
		return $id;
	}
	/**
	 * Prepares the_{meta_box} methods by converting to html entities
	 * @param string $content The meta box content that will be prepared
	 * @return string $content The safe meta box content
	 */
	private function the_setup ( $content ) {
		return $content = str_replace(']]>', ']]&gt;', $content);
	}
	/**
	 * Echos out the content of the affiliate url meta box
	 * @var string $content Holds the content of the meta box while processed within the method
	 * @uses Thunder_Plugin_Products::get_the_affiliate_url()
	 * @uses Thunder_Plugin_Products::the_setup()
	 */
	function the_affiliate_url () {
		$content = $this->get_the_affiliate_url();
		$content = $this->the_setup( $content );		
		echo $content;
	}
	/**
	 * Fetches the content of the affiliate url meta box
	 * @var int $id The post identification number
	 * @uses Thunder_Plugin_Products::get_the_setup()
	 * @uses Thunder_Plugin_Products::get_key()	 
	 * @return string The affiliate url meta box content
	 */
	function get_the_affiliate_url () {
		$id = $this->get_the_setup();
		return $this->get_key( $id, $this->underscore( 'thunder-plugin-products-aff-url' ) );
	}

	function get_the_best_offer_url () {
		$id = $this->get_the_setup();
		return $this->get_key( $id, $this->underscore( 'thunder-plugin-products-best-disount-url' ) );
	}
	/**
	 * Echos out the content of the rank meta box
	 * @var string $content Holds the content of the meta box while processed within the method
	 * @uses Thunder_Plugin_Products::get_the_rank()
	 * @uses Thunder_Plugin_Products::the_setup()
	 */
	function the_rank () {
		$content = $this->get_the_rank();
		$content = $this->the_setup( $content );		
		echo $content;
	}
	/**
	 * Fetches the content of the rank meta box
	 * @var int $id The post identification number
	 * @uses Thunder_Plugin_Products::get_the_setup()
	 * @uses Thunder_Plugin_Products::get_key()	 
	 * @return string The rank meta box content
	 */
	function get_the_rank () {
		$id = $this->get_the_setup();
		return $this->get_key( $id, $this->underscore( 'thunder-plugin-products-rank' ) );
	}
	/**
	 * Fetches the content of the CPA meta box
	 * @var int $id The post identification number
	 * @uses Thunder_Plugin_Products::get_the_setup()
	 * @uses Thunder_Plugin_Products::get_key()	 
	 * @return string The CPA meta box content
	 */
	function get_the_cpa () {
		$id = $this->get_the_setup();
		return $this->get_key( $id, $this->underscore( 'thunder-plugin-products-cpa' ) );
	}
	/**
	 * Fetches the content of the conversion rate meta box
	 * @var int $id The post identification number
	 * @uses Thunder_Plugin_Products::get_the_setup()
	 * @uses Thunder_Plugin_Products::get_key()	 
	 * @return string The conversion rate meta box content
	 */
	function get_the_conversion_rate () {
		$id = $this->get_the_setup();
		return $this->get_key( $id, $this->underscore( 'thunder-plugin-products-conv' ) );
	}
	/**
	 * Fetches the content of the EPC meta box
	 * @var int $id The post identification number
	 * @uses Thunder_Plugin_Products::get_the_setup()
	 * @uses Thunder_Plugin_Products::get_key()	 
	 * @return string The EPC meta box content
	 */
	function get_the_epc () {
		$id = $this->get_the_setup();
		return $this->get_key( $id, $this->underscore( 'thunder-plugin-products-epc' ) );
	}
	/**
	 * Fetches the content of the internal rating meta box
	 * @var int $id The post identification number
	 * @uses Thunder_Plugin_Products::get_the_setup()
	 * @uses Thunder_Plugin_Products::get_key()	 
	 * @return string The internal rating meta box content
	 */
	function get_the_internal_rating () {
		$id = $this->get_the_setup();
		return $this->get_key( $id, $this->underscore( 'thunder-plugin-products-int-rating' ) );
	}
	/**
	 * Fetches the content of the external rating meta box
	 * @var int $id The post identification number
	 * @uses Thunder_Plugin_Products::get_the_setup()
	 * @uses Thunder_Plugin_Products::get_key()	 
	 * @return string The external rating meta box content
	 */
	function get_the_external_rating () {
		$id = $this->get_the_setup();
		return $this->get_key( $id, $this->underscore( 'thunder-plugin-products-ext-rating' ) );
	}
	/**
	 * Fetches the content of the window size rating meta box
	 * @var int $id The post identification number
	 * @uses Thunder_Plugin_Products::get_the_setup()
	 * @uses Thunder_Plugin_Products::get_key()	 
	 * @return array The window size meta box content: [width, height]
	 */
	function get_the_window_size () {
		$id = $this->get_the_setup();
		return explode( ',', $this->get_key( $id, $this->underscore(' thunder-plugin-products-win-size' ), 2 )); //TODO: return as array
	}
	/**
	 * Fetches the positioning offset of the affiliate offer window
	 * @var int $id The post identification number
	 * @uses Thunder_Plugin_Products::get_the_setup()
	 * @uses Thunder_Plugin_Products::get_key()   
	 * @return array The window size meta box content: [width, height]
	 */
	function get_the_window_offset () {
		$id = $this->get_the_setup();
		return explode( ',', $this->get_key( $id, $this->underscore(' thunder-plugin-products-win-offset' ), 2 )); //TODO: return as array
	}
	/**
	 * Echos out the content of the display url meta box
	 * @var string $content Holds the content of the meta box while processed within the method
	 * @uses Thunder_Plugin_Products::get_the_display_url()
	 * @uses Thunder_Plugin_Products::the_setup()
	 */
	function the_display_url () {
		$content = $this->get_the_display_url();
		$content = $this->the_setup( $content );		
		echo $content;
	}
	/**
	 * Fetches the content of the display url meta box
	 * @var int $id The post identification number
	 * @uses Thunder_Plugin_Products::get_the_setup()
	 * @uses Thunder_Plugin_Products::get_key()	 
	 * @return string The display url meta box content
	 */
	function get_the_display_url () {
		$id = $this->get_the_setup();
		return $this->get_key( $id, $this->underscore( 'thunder-plugin-products-disp-url' ) );
	}
	/**
	 * Echos out the content of the price meta box
	 * @var string $content Holds the content of the meta box while processed within the method
	 * @uses Thunder_Plugin_Products::get_the_price()
	 * @uses Thunder_Plugin_Products::the_setup()
	 */
	function the_price () {
		$content = $this->get_the_price();
		$content = $this->the_setup( $content );		
		echo $content;
	}
	/**
	 * Echos out the content of the price-frequency continuum meta box
	 * @var string $content Holds the content of the meta box while processed within the method
	 * @uses Thunder_Plugin_Products::get_the_price_frequency()
	 * @uses Thunder_Plugin_Products::the_setup()
	 */
	function the_price_frequency () {
		$pricing_object = $this->get_the_price_frequency();
		$content = "<table class=\"tdr-product-pricing-table\"><thead><tr><td>Term</td><td>Price</td></tr></thead><tbody>";
			// Display each frequency-price pair
			foreach ( $pricing_object as $frequency => $price ) {
			$content .= "<tr><td>".esc_attr( urldecode( $frequency ) )."</td><td>".esc_attr( urldecode( $price ) )."</td></tr>";
			}
		$content .=	"</tbody></table>";
		$content = $this->the_setup( $content );		
		echo $content;
	}
	/**
	 * Fetches the content of the price-frequency continuum meta box, used to denote if a product/service is a recurring or one time charge and different plans offered for each frequency
	 * @var int $id The post identification number
	 * @uses Thunder_Plugin_Products::get_the_setup()
	 * @uses Thunder_Plugin_Products::get_key()	 
	 * @return array The price frequency meta box content with key-value pairs
	 */
	function get_the_price_frequency () {
		$id = $this->get_the_setup();
		$pricing_array = $this->get_key( $id, $this->underscore( 'thunder-plugin-products-price-freq' ) );
		$pricing_array = json_decode( $pricing_array, true );
		$return_array = array();
		foreach ( $pricing_array as $key => $value ) {
			$return_array[ urldecode( $key ) ] = urldecode( $value );
		}
		return $return_array;
	}
	/**
	 * Echos out the content of the comparison points meta box
	 * @var string $content Holds the content of the meta box while processed within the method
	 * @uses Thunder_Plugin_Products::get_the_price_frequency()
	 * @uses Thunder_Plugin_Products::the_setup()
	 */
	function the_comparison_points () {
		$comparison_array = $this->get_the_price_frequency();
		$content = "<table class=\"tdr-comparison-points-table\"><thead><tr><td>Key</td><td>Value</td></tr></thead><tbody>";
			// Display each key-value pair
			foreach ( $comparison_array as $key => $value ) {
			$content .= "<tr><td>" . esc_attr( urldecode( $key ) ) . "</td><td>" . esc_attr( urldecode( $value ) ) . "</td></tr>";
			}
		$content .=	"</tbody></table>";
		$content = $this->the_setup( $content );		
		echo $content;
	}
	/**
	 * Fetches the content of the comparison points meta box, common to all posts of type tdr-product
	 * @var int $id The post identification number
	 * @uses Thunder_Plugin_Products::get_the_setup()
	 * @uses Thunder_Plugin_Products::get_key()	 
	 * @return array The comparison points meta box content with key-value pairs
	 */
	function get_the_comparison_points () {
		$id = $this->get_the_setup();
		$comparison_array = $this->get_key( $id, $this->underscore( 'thunder-plugin-products-comparison-points' ) );
		$comparison_array = json_decode( $comparison_array, true );
		$return_array = array();
		foreach ( $comparison_array as $key => $value ) {
			$return_array[ urldecode( $key ) ] = urldecode( $value );
		}
		return $return_array;
	}
	/**
	 * Fetches the content of the subratings meta box, common to all posts of type tdr-product
	 * @var int $id The post identification number
	 * @uses Thunder_Plugin_Products::get_the_setup()
	 * @uses Thunder_Plugin_Products::get_key()	 
	 * @return array The comparison points meta box content with key-value pairs
	 */
	function get_the_subratings () {
		$id = $this->get_the_setup();
		$subrating_array = $this->get_key( $id, $this->underscore( 'thunder-plugin-products-subratings' ) );
		$subrating_array = json_decode( $subrating_array, true );
		$return_array = array();
		foreach ( $subrating_array as $key => $value ) {
			$return_array[ urldecode( $key ) ] = urldecode( $value );
		}
		return $return_array;
	}	
	/**
	 * Fetches the content of the full review sections meta, common to all posts of type tdr-product
	 * @var int $id The post identification number
	 * @uses Thunder_Plugin_Products::get_the_setup()
	 * @uses Thunder_Plugin_Products::get_key()	 
	 * @return array The full review sections meta box content with key-value pairs
	 */
	function get_the_full_review () {
		$id = $this->get_the_setup();
		$full_review_sections_array = $this->get_key( $id, $this->underscore( 'thunder-plugin-products-full-review-sections' ) );
		$full_review_sections_array = json_decode( $full_review_sections_array, true );
		$return_array = array();
		foreach ( $full_review_sections_array as $key => $value ) {
			// Put whole section in return array
			$return_array[ $key ] = $value;
			$return_array[ $key ]['name'] = urldecode( $return_array[ $key ]['name'] );
			// Go into each subsection
			foreach ( $full_review_sections_array[ $key ]['subsections'] as $subsection => $subsection_value ) {
				// Decode they name and value fields
				$return_array[ $key ]['subsections'][ $subsection ]['name'] = urldecode( $full_review_sections_array[ $key ]['subsections'][ $subsection ]['name'] );
				$return_array[ $key ]['subsections'][ $subsection ]['value'] = urldecode( $full_review_sections_array[ $key ]['subsections'][ $subsection ]['value'] );
			}
		}
		return $return_array;
	}
	/**
	 * Fetches the content of a section from the full review sections meta, common to all posts of type tdr-product
	 * @param string $section The parent section slug
	 * @param string $subsection the slug for the desired subsection
	 * @param string $flag (optional) the value to get (name, value, all- including slug)
	 * @var int $id The post identification number
	 * @uses Thunder_Plugin_Products::get_the_setup()
	 * @uses Thunder_Plugin_Products::get_key()	 
	 * @return false on failure || string or array on success: A subsection or derived value (name, value) from the full review sections meta box content with key-value pairs
	 */
	function get_the_subsection ( $section, $subsection, $flag = '' ) {
		$id = $this->get_the_setup();
		$full_review_sections_array = $this->get_key( $id, $this->underscore( 'thunder-plugin-products-full-review-sections' ) );
		$full_review_sections_array = json_decode( $full_review_sections_array, true );
		$return_array = array();
		// Look for section
		if ( array_key_exists( $section, $full_review_sections_array ) ) {
			// Look for subsection
			if ( array_key_exists( $subsection, $full_review_sections_array[ $section ]['subsections'] ) ) {
				// Decode they name and value fields
				$name = urldecode( $full_review_sections_array[ $section ]['subsections'][ $subsection ]['name'] );
				$value = urldecode( $full_review_sections_array[ $section ]['subsections'][ $subsection ]['value'] );
				// Determine which values to return
				switch ( $flag ) {
					case "name":
						return $name;
						break;
					case "value":
						return $value;
						break;
					default:
						$return_array['slug'] = $subsection;
						$return_array['name'] = $name;
						$return_array['value'] = $value;
						return $return_array;
				}
			}
		}
		return false;
    }
    /**
     * Fetches the human-readable name of a full review section given its slug
     * @param string $section_slug The top-level section slug
     * @var int $id The post identification number
     * @uses Thunder_Plugin_Products::get_the_setup()
     * @uses Thunder_Plugin_Products::get_key()
     * @return false on failure || string name of section on success
     */
    function get_the_section_name ( $section_slug ) {
        $id = $this->get_the_setup();
        $full_review_sections_array = $this->get_key( $id, $this->underscore( 'thunder-plugin-products-full-review-sections' ) );
        $full_review_sections_array = json_decode( $full_review_sections_array, true );
        $return_array = array();
        // Look for section
        if ( array_key_exists( $section_slug, $full_review_sections_array ) ) {
            // Decode section name
            $section_name = urldecode( $full_review_sections_array[ $section_slug ]['name'] );
            return $section_name;
        }
        // Return false if section not found
        else {
            return false;
        }
    }
	/**
	 * Echos out the content of the custom meta box
	 * @var string $content Holds the content of the meta box while processed within the method
	 * @uses Thunder_Plugin_Products::get_the_custom()
	 * @uses Thunder_Plugin_Products::the_setup()
	 */
	function the_custom () {
		$content = $this->get_the_custom();
		$content = $this->the_setup( $content );		
		echo $content;
	}
	/**
	 * Fetches the content of the custom meta box
	 * @var int $id The post identification number
	 * @uses Thunder_Plugin_Products::get_the_setup()
	 * @uses Thunder_Plugin_Products::get_key()	 
	 * @return string The custom meta box content
	 */
	function get_the_custom () {
		$id = $this->get_the_setup();
		return $this->get_key( $id, $this->underscore( 'thunder-plugin-products-custom' ) );
	}
	/**
	 * Echos out the content of the Google Analytics Stock Keeping Unit meta box
	 * @var string $content Holds the content of the meta box while processed within the method
	 * @uses Thunder_Plugin_Products::get_the_ga_sku()
	 * @uses Thunder_Plugin_Products::the_setup()
	 */
	function the_ga_sku () {
		$content = $this->get_the_ga_sku();
		$content = $this->the_setup( $content );		
		echo $content;
	}
	/**
	 * Fetches the content of the Google Analytics Stock Keeping Unit meta box
	 * @var int $id The post identification number
	 * @uses Thunder_Plugin_Products::get_the_setup()
	 * @uses Thunder_Plugin_Products::get_key()	 
	 * @return string The Stock Keeping Unit meta box content
	 */
	function get_the_ga_sku () {
		$id = $this->get_the_setup();
		return $this->get_key( $id, $this->underscore( 'thunder-plugin-products-ga-sku' ) );
	}
	/**
	 * Echos out the content of the affiliate name meta box
	 * @var string $content Holds the content of the meta box while processed within the method
	 * @uses Thunder_Plugin_Products::get_the_affiliate_name()
	 * @uses Thunder_Plugin_Products::the_setup()
	 */
	function the_affiliate_name () {
		$content = $this->get_the_affiliate_name();
		$content = $this->the_setup( $content );		
		echo $content;
	}
	/**
	 * Fetches the content of the affiliate name meta box
	 * @var int $id The post identification number
	 * @uses Thunder_Plugin_Products::get_the_setup()
	 * @uses Thunder_Plugin_Products::get_key()	 
	 * @return string The affiliate name meta box content
	 */
	function get_the_affiliate_name () {
		$id = $this->get_the_setup();
		return $this->get_key( $id, $this->underscore( 'thunder-plugin-products-aff-name' ) );
	}
	/**
	 * Fetches the content of the internal notes meta box
	 * @var int $id The post identification number
	 * @uses Thunder_Plugin_Products::get_the_setup()
	 * @uses Thunder_Plugin_Products::get_key()	 
	 * @return string The internal notes meta box content
	 */
	function get_the_internal_notes () {
		$id = $this->get_the_setup();
		return $this->get_key( $id, $this->underscore( 'thunder-plugin-products-int-notes' ) );
	}
	/**
	 * Echos out the content of the short url meta box
	 * @var string $content Holds the content of the meta box while processed within the method
	 * @uses Thunder_Plugin_Products::get_the_short_url()
	 * @uses Thunder_Plugin_Products::the_setup()
	 */
	function the_short_url () {
		$content = $this->get_the_short_url();
		$content = $this->the_setup( $content );		
		echo $content;
	}
	/**
	 * Fetches the content of the short url meta box
	 * @var int $id The post identification number
	 * @uses Thunder_Plugin_Products::get_the_setup()
	 * @uses Thunder_Plugin_Products::get_key()	 
	 * @return string The short url meta box content
	 */
	function get_the_short_url () {
		$id = $this->get_the_setup();
		return $this->get_key( $id, $this->underscore( 'thunder-plugin-products-short-url' ) );
	}
	/**
	 * Displays meta information for posts as a dump
	 * @param object $content The content of the current post
	 * @var $wp_query Global WordPress query variable
	 * @return string The post content with the meta information prepended as a dump
	 */
	function formatting ( $content ) {
		global $wp_query;
		return $content.(the_meta())/*($this->get_key($wp_query->post->ID, 'Thunder_Plugin_Products_aff_url'))*/;
	}
	function init_section_settings_page () {
		$page_title = 'Manage Product Sections';
		$menu_title = 'Manage Sections';
		$capability = 'manage_options';
		$menu_slug = 'product-section-settings';
		$function = array( &$this, 'output_section_settings_page' );	
		add_submenu_page( 'edit.php?post_type=tdr_product', $page_title, $menu_title, $capability, $menu_slug, $function );
	}
	function output_section_settings_page () {
		// Fetch current settings
		$product_section_settings = get_option( 'thunder_plugin_products_section_settings', $default = array() );
		
		// Output control javascript
		?>
		<script type="text/javascript">
			// JSON stringify Polyfill
			var JSON;
			if( !JSON) {
				JSON = {};
			}
			JSON.stringify = JSON.stringify || function (obj) {  
				var t = typeof (obj);  
				if (t != "object" || obj === null) {  
					// simple data type  
					if (t == "string") obj = '"'+obj+'"';  
					return String(obj);  
				}  
				else {  
					// recurse array or object  
					var n, v, json = [], arr = (obj && obj.constructor == Array);  
					for (n in obj) {  
						v = obj[n]; t = typeof(v);  
						if (t == "string") v = '"'+v+'"';  
						else if (t == "object" && v !== null) v = JSON.stringify(v);  
						json.push((arr ? "" : '"' + n + '":') + String(v));  
					}  
					return (arr ? "[" : "{") + String(json) + (arr ? "]" : "}");  
				}  
			};
			function tdrPrepareSlug( name ) {
				slug = name.toLowerCase();
				if ( ( slug.match( /[a-z]/g ) ) == null ) { // Look for at least one alphabetical character
					return null
				}
				else {
					slug = slug.replace( /\s/g, "-" ); // Replace spaces
					slug = slug.replace( /[^a-z0-9\-]/g, "-"); // Replace non alpha-numeric or hyphen characters
					slug = slug.replace( /-+/g, "-" ); // Replace multiple hyphens in a row
					while ( ( slug.charAt(0).match( /[a-z]/ ) ) == null ) { // Ensure does not start with number
						slug = slug.substring(1);
					}
					slug = slug.charAt(0).replace( /-/, "" ) + slug.substring(1); // Ensure does not start with hyphen - join first character with rest of string
					slug = slug.substring(0, slug.length-1) + slug.charAt(slug.length-1).replace( /-/, "" ); // Ensure does not end with hyphen - join string with last character
					return slug;
				}
			}
			function tdrSlugifyName( name, suggestedSlug ) {
				// Finds slug version of supplied name
				slugifiedName = tdrPrepareSlug( name );
				slugifiedSuggestedSlug = tdrPrepareSlug( suggestedSlug );
				// Validate suggested slug
				if ( slugifiedSuggestedSlug == suggestedSlug && suggestedSlug.length > 0 ) {
					slug = suggestedSlug;
				}
				// On failure fallback to auto-generated slug
				else {
					slug = slugifiedName;
				}
				return slug;
			}
			jQuery( document ).ready( function() {
				// Add section handler
				jQuery( '.tdr_add_section' ).on( 'click', function() {
					var section = jQuery( '<tr><td class="tdr_product_section" data-original-slug=""><table><tr><td>Name</td><td><input type="text" value="Value" /></td><td>Slug</td><td><input type="text" value="" /></td><td><span><a class="tdr_remove_section" href="#">&times; Remove section</a> (top-level)</span></td></tr></table><table class="tdr_product_subsections" style="margin-left:50px;"></table><p style="margin-left:45px;"><span><a class="tdr_add_subsection" href="#">+ Add subsection</a></span></p></td></tr>' );
					jQuery( '#tdr_product_sections' ).append( section );
					// Need to bind the remove handler on new section due to the way .on delegates
					jQuery( section ).find( '.tdr_remove_section' ).on( 'click', function() {
						if( confirm( 'Are you sure you really want to remove this section?' ) ) {
							jQuery( this ).parents('tr').fadeOut( 'slow', function() { jQuery( this ).remove(); } );
						}
						else {
							alert( 'Remove cancelled.' );
						}
						return false;	
					});
					// Add subsection handler
					jQuery( section ).find( '.tdr_add_subsection' ).on( 'click', function() {
						var subsection = jQuery( '<tr><td class="tdr_product_subsection" data-original-slug=""><table><tr><td>Name</td><td><input type="text" value="Value" /></td><td>Slug</td><td><input type="text" value="" /></td><td><span><a class="tdr_remove_subsection" href="#">&times; Remove subsection</a></span></td></tr></table></td></tr>' );
						// Add subsection
						jQuery( this ).parents('td').find('.tdr_product_subsections').append( subsection );
						// Add removal handler
						jQuery( subsection ).find( '.tdr_remove_subsection' ).on( 'click', function() {
							if( confirm( 'Are you sure you really want to remove this subsection?' ) ) {
								jQuery( this ).parentsUntil('tr').parent('tr').parentsUntil('tr').parent('tr').fadeOut( 'slow', function() { jQuery( this ).remove(); } );
							}
							else {
								alert( 'Remove cancelled.' );
							}
							return false;
						});
						return false;
					});	
					return false;
				});
				// Remove section handler
				jQuery( '.tdr_remove_section' ).on( 'click', function() {
					if( confirm( 'Are you sure you really want to remove this section?' ) ) {
						jQuery( this ).parents('tr').fadeOut( 'slow', function() { jQuery( this ).remove(); } );
					}
					else {
						alert( 'Remove cancelled.' );
					}
					return false;
				});
				// Remove subsection handler
				jQuery( '.tdr_remove_subsection' ).on( 'click', function() {
					if( confirm( 'Are you sure you really want to remove this subsection?' ) ) {
						jQuery( this ).parentsUntil('tr').parent('tr').parentsUntil('tr').parent('tr').fadeOut( 'slow', function() { jQuery( this ).remove(); } );
					}
					else {
						alert( 'Remove cancelled.' );
					}
					return false;
				});
				// Add subsection handler
				jQuery( '.tdr_add_subsection' ).on( 'click', function() {
					var subsection = jQuery( '<tr><td class="tdr_product_subsection" data-original-slug=""><table><tr><td>Name</td><td><input type="text" value="Value" /></td><td>Slug</td><td><input type="text" value="" /></td><td><span><a class="tdr_remove_subsection" href="#">&times; Remove subsection</a></span></td></tr></table></td></tr>' );
					// Add subsection
					jQuery( this ).parents('td').find('.tdr_product_subsections').append( subsection );
					// Add removal handler
					jQuery( subsection ).find( '.tdr_remove_subsection' ).on( 'click', function() {
						if( confirm( 'Are you sure you really want to remove this subsection?' ) ) {
							jQuery( this ).parentsUntil('tr').parent('tr').parentsUntil('tr').parent('tr').fadeOut( 'slow', function() { jQuery( this ).remove(); } );
						}
						else {
							alert( 'Remove cancelled.' );
						}
						return false;
					});
					return false;
				});	
				// Save section settings handler
				jQuery( '#tdr_save_sections' ).on( 'click', function() {
					var objValues = {};
					var slugChanges = {};
					// Track top-level section slugs
					var duplicateSlugs = {
						found: false,
						duplicate: null,
						type: null,
						parent: null,
					}
					var sectionSlugs = [];
					// Find each regular section
					jQuery( '#tdr_product_sections' ).find( '.tdr_product_section' ).each( function(i) {
						if ( duplicateSlugs.found ) {
							return false;
						}
						sectionName = jQuery( this ).find('table:first').find('td:eq(1)').children('input').val();
						sectionSlug = jQuery( this ).find('table:first').find('td:eq(3)').children('input').val();
						sectionSlug = tdrSlugifyName( sectionName, sectionSlug );
						// Proceed adding section if its slug is unique
						if ( sectionSlugs.indexOf( sectionSlug ) == -1 ) {
							objValues [ sectionSlug ] = {
								name: encodeURIComponent( sectionName ),
								slug: sectionSlug,
								subsections: {}
							};
							sectionSlugs.push( sectionSlug );
							// Find original section slug
							var originalSlug = jQuery(this).attr('data-original-slug');
							originalSlug = encodeURIComponent( originalSlug );
							// Push non-empty slugs which have changed to object slugChanges
							if ( originalSlug != '' && originalSlug != sectionSlug) {
								slugChanges[ originalSlug ] = {
									new_name: sectionSlug, // include new slug name
									subsections: {} // include container for subsection changes
								};
							}
							// Track subsection slugs
							var subSectionSlugs = [];
							// Find each subsection
							jQuery( this ).find('.tdr_product_subsection').each( function(j) {
								subSectionName = jQuery( this ).find('td:eq(1)').children('input').val();
								subSectionSlug = jQuery( this ).find('td:eq(3)').children('input').val();
								subSectionSlug = tdrSlugifyName( subSectionName, subSectionSlug );
								if ( subSectionSlugs.indexOf( subSectionSlug ) == -1 ) {
									objValues[sectionSlug].subsections[subSectionSlug] = {
											name: encodeURIComponent( subSectionName ),
											slug: subSectionSlug
									};
									subSectionSlugs.push( subSectionSlug );
								}
								// Otherwise report error and break;
								else {
									duplicateSlugs.found = true;
									duplicateSlugs.duplicate = subSectionSlug;
									duplicateSlugs.type = 'subsection';
									duplicateSlugs.parent = sectionSlug;
									return false;
								}
								// Find original subsection slug
								var originalSubSectionSlug = jQuery(this).attr('data-original-slug');
								originalSubSectionSlug = encodeURIComponent( originalSubSectionSlug );
								// Push non-empty slugs which have changed to object slugChanges
								if ( originalSubSectionSlug != '' && originalSubSectionSlug != subSectionSlug) {
									if ( slugChanges[ originalSlug ] == undefined ) {
										slugChanges[ originalSlug ] = {
											subsections: {}
										};
									}
									slugChanges[ originalSlug ].subsections[ originalSubSectionSlug ] = subSectionSlug; // include new slug name
								}
							});
						}
						// Otherwise report error and break
						else {
							duplicateSlugs.found = true;
							duplicateSlugs.duplicate = sectionSlug;
							duplicateSlugs.type = 'top-level section';
							return false;
						}
					});
					if ( duplicateSlugs.found ) {
						if ( duplicateSlugs.type == 'subsection' ) {
							slugmsg = 'the subsection "' + duplicateSlugs.duplicate + '" in top-level section "' + duplicateSlugs.parent + '"';
						}
						else {
							slugmsg = 'the top-level section "' + duplicateSlugs.duplicate + '"';
						}
						alert( 'At least one duplicate slug was found. First duplicate was ' + slugmsg + '. Please rename and try again.' );
					}
					// Else no duplicates found-- continue request
					else {
						var data = {
							action: 'tdr_save_section_settings',
							key_value_json: JSON.stringify( objValues ),
							slug_changes: JSON.stringify( slugChanges ),
							client_version: 0.1
						};
						jQuery.post(ajaxurl, data, function(response) {
							response = jQuery.parseJSON(response);
							if ( response.error ) { 
							   alert( 'ERROR: ' + response.error );
							}
							else if ( response.confirm_request ) {
								if( confirm( response.confirm_message ) ) {
									d = new Date();
									data.confirmation_key = d.getTime();
									jQuery.post(ajaxurl, data, function(response) {
										response = jQuery.parseJSON(response);
										if ( response.error ) {
											alert( 'ERROR: ' + response.error );
										}
										else {
											alert( 'SUCCESS: ' + response.message );
											// On success after changing slugs, update 'original-slug' data attributes
											jQuery( '#tdr_product_sections' ).find( '.tdr_product_section' ).each( function(i) {
												// Find newest slug from input field
												sectionName = jQuery( this ).find('table:first').find('td:eq(1)').children('input').val();
												sectionSlug = jQuery( this ).find('table:first').find('td:eq(3)').children('input').val();
												sectionSlug = tdrSlugifyName( sectionName, sectionSlug );
												// Set original slug attribute						
												jQuery(this).attr('data-original-slug', sectionSlug);
												// Find each subsection
												jQuery( this ).find('.tdr_product_subsection').each( function(j) {
													subSectionName = jQuery( this ).find('td:eq(1)').children('input').val();
													subSectionSlug = jQuery( this ).find('td:eq(3)').children('input').val();
													subSectionSlug = tdrSlugifyName( subSectionName, subSectionSlug );
													// Set original slug attribute
													jQuery( this ).attr('data-original-slug', subSectionSlug);
												});
											});
										}
									}).error( function() {
										alert( 'There was a problem resubmitting your request.');
									});
								}
								else {
									alert( 'Save cancelled.' );
								}			
							}
						}).error( function() {
							alert( 'There was a problem sending the data. Please try again' );
						});
					}
				});					
			});
		</script>
		
		<?php
		// Header
		echo( '<h3>Manage Product Sections</h3>' );
		
		echo( '<table id="tdr_product_sections">' );
		if ( !empty( $product_section_settings ) ) {
			foreach ( $product_section_settings as $section ) {
			// Output top-level sections
			echo( '<tr><td class="tdr_product_section" data-original-slug="' . $section['slug'] .'">' );
			echo( '<table><tr><td>Name</td><td><input type="text" value="' . urldecode( $section['name'] ) . '" /></td><td>Slug</td><td><input type="text" value="' . $section['slug'] . '" /></td><td><span><a class="tdr_remove_section" href="#">&times; Remove section</a> (top-level)</span></td></tr></table>' );
			echo( '<table class="tdr_product_subsections" style="margin-left:50px;">' );
				foreach ( $section['subsections'] as $subsection ) {
				// Output sub-sections
					echo( '<tr><td class="tdr_product_subsection" data-original-slug="' . $subsection['slug'] .'">' );
					echo( '<table><tr>' );
					echo( '<td>Name</td><td><input type="text" value="' . urldecode( $subsection['name'] ) . '" /></td><td>Slug</td><td><input type="text" value="' . $subsection['slug'] . '" /></td><td><span><a class="tdr_remove_subsection" href="#">&times; Remove subsection</a></span></td>' );
					echo( '</tr></table>' );
					echo( '</td></tr>' );
				}
				// Output sub-section controls
				echo( '</table>' );
				echo( '<p style="margin-left:45px;"><span><a class="tdr_add_subsection" href="#">+ Add subsection</a></span></p>' );
				echo( '</td></tr>');
			}
		}
		echo ( '</table>' );
		// Output top-level controls
		echo( '<div class="controls"><span><a class="tdr_add_section" href="#">+ Add section</a> (top-level)</span></div>' );
		
		// Output ajax save button
		echo( '<p><button id="tdr_save_sections" class="button">Save sections</button></p>' );
	}
}

add_action( 'init', 'tdr_plugin_products_create_post_type' );
/**
  * Registers the post type tdr_product in WordPress. Called on WordPress init event
  * @package  thunder-plugin-products
  */
function tdr_plugin_products_create_post_type() {
	$labels = array(
		'name' => 'Products',
		'singular_name' => 'Product',
		'add_new' => 'Add New Product',
		'add_new_item' => 'Add New Product',
		'edit_item' => 'Edit Product',
		'new_item' => 'New Product',
		'all_items' => 'All Products',
		'view_items' => 'View Products',
		'search_items' => 'Search Products',
		'not_found' => 'No Products found',
		'not_found_in_trash' => 'No Products found in Trash',
		'parent_item_colon' => '',
		'menu_name' => 'Products'
	);

	$args = array(
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'query_var' => true,
		//'rewrite' => array( 'slug' => 'reviews' ),
		'capability_type' => 'post',
		//'has_archive' => false, // put string slug to use for archive page so /reviews is not overriden
		'hierarchical' => false,
		'menu_position' => null,
		'menu_icon' => plugins_url('images/icon-products.png', __FILE__ ),
		'taxonomies' => array( 'offer-category' ),
		'supports' => array(
			'title',
			'editor', //content
			'author',
			'thumbnail',
			'excerpt',
			//'custom-fields',  // *CAN NOT BE INCLUDED BECAUSE AJAX FUNCTIONALITY OF COMPARISON POINTS!!
			'comments',
			'revisions'
		)
	);
	register_post_type( 'tdr_product', $args );
	remove_post_type_support( 'tdr_product', 'editor' );
}

/* Create a custom taxonomy: Order Category
 * ***************************************************************************/
add_action( 'init', 'tdr_product_create_offer_category_tax' );
function tdr_product_create_offer_category_tax() {
	$labels = array(
		'name' => 'Offer Category',
	    'singular_name' => 'Offer Category',
	    'search_items' => 'Search Offer Categories',
	    'all_items' => 'All Offer Categories',
	    'parent_item' => 'Parent Offer Category',
	    'parent_item_colon' => 'Parent Offer Category:',
		'edit_item' => 'Edit Offer Category', 
	    'update_item' => 'Update Offer Category',
	    'add_new_item' => 'Add New Offer Category',
		'new_item_name' => 'New Offer Category Name',
		'menu_name' => 'Offer Category',
	);
	$args = array(
		'hierarchical' => true,
		'labels' => $labels,
	    'show_ui' => true,
		'query_var' => 'offer-category',
		'rewrite' => array( 'slug' => 'offer-category' ),
	);

	register_taxonomy( 'offer-category', 'tdr_product', $args );
	register_taxonomy_for_object_type( 'offer-category', 'page' );
	add_post_type_support( 'page','category' );
}

add_action('wp_ajax_tdr_save_section_values', 'tdr_save_section_values');
function tdr_save_section_values() {
    // Setup request return info for client callback
    $return_array = array(
        'message' => '',
        'error' => ''
    );
    // Check javascript client script version
    if ( $_POST['client_version'] != 0.1 ) {
        $return_array['error'] = 'Your browser has an old version of the page loaded. Please try refreshing your page or clearing your browser cache, then try again.';
    }
	else {
		// Parse Data
		$key_value_pairs = $_POST['key_value_json'];
		$key_value_pairs = stripslashes( $key_value_pairs );
		$key_value_pairs = json_decode( $key_value_pairs, true );
		if ( empty( $key_value_pairs ) ) {
			$return_array['error'] = 'There was a problem processing any data. Unless you intended to remove all fields and their values, this is bad.';
		}
		else {
			// Need to get post id for ajax calling post
			$post_id = $_POST['post_id'];
			$meta_value = get_post_meta( $post_id, 'thunder_plugin_products_full_review_sections', true );
			
			// Make sure no slugs or names have been added/removed/renamed
				$section_changes_detected = false;
				$saved_meta_pairs = json_decode( $meta_value, true );
				
				// Check for top-level slug changes ----- No section names not in saved list -- same number of sections
				// Array_diff_key cannot be directly placed into empty()
				$difference_of_section_slugs = array_diff_key( $key_value_pairs, $saved_meta_pairs );
				if ( !empty( $difference_of_section_slugs ) || count( $key_value_pairs ) != count( $saved_meta_pairs ) ) {
					// Report tampering
					$section_changes_detected = true;
				}
				// Else no top-level slug problems
				else {
					foreach ( $saved_meta_pairs as $section => $section_values ) {
						// Check for top-level name changes
						if ( $key_value_pairs[ $section ]['name'] != $saved_meta_pairs[ $section ]['name'] ) {
							// Report tampering
							$section_changes_detected = true;
							break;
						}
						// Check for subsection slug changes
						// Array_diff_key cannot be directly placed into empty()
						$difference_of_subsection_slugs = array_diff_key( $key_value_pairs[ $section ]['subsections'], $saved_meta_pairs[ $section ]['subsections'] );
						if ( !empty( $difference_of_subsection_slugs ) || count( $key_value_pairs[ $section ]['subsections'] ) != count( $saved_meta_pairs[ $section ]['subsections'] ) ) {
							// Report tampering
							$section_changes_detected = true;
							break;
						}
						foreach ( $saved_meta_pairs[ $section ]['subsections'] as $subsection => $subsection_values ) {
							// Check for subsection name changes
							if ( $key_value_pairs[ $section]['subsections'][ $subsection ]['name'] != $saved_meta_pairs[ $section ]['subsections'][ $subsection ]['name'] ) {
								// Report tampering
								$section_changes_detected = true;
								break;
							}
						}
						if ( $section_changes_detected ) {
							break;
						}
					}
				}
			// END slug and name check
			// If any change detected, (sub)sections were likely changed since the page was laoded (or less likely, the form was tampered with).
			if ( $section_changes_detected ) {
				$return_array['error'] = 'Sections or subsections have changed since the page was last loaded. Rejecting changes to avoid losing data. Please reload the settings page.';
			}
			else {
				// JSON Encode the meta
				$new_meta_value = json_encode( $key_value_pairs );
				
				/* If there the new meta value is blank but an old value existed, delete it. */
				if ( '' == $new_meta_value && $meta_value )
					delete_post_meta( $post_id, $meta_id, $meta_value );	

				/* Updates meta value if it exists, Else Adds it to the post. */
				else
					update_post_meta( $post_id, 'thunder_plugin_products_full_review_sections', $new_meta_value );
				$return_array['message'] = 'Your changes have been saved';
			}
		}
	}
    $return_json = json_encode( $return_array );
    echo $return_json;
	die(); // this is required to return a proper result	
}

add_action('wp_ajax_tdr_save_section_settings', 'tdr_save_section_settings');
function tdr_save_section_settings() {
    // Setup request return info for client callback
    $return_array = array(
        'message' => '',
        'confirm_message' => '',
        'confirm_request' => false,
        'error' => '',
        'confirmation_key' => ''
    );
    // Check javascript client script version
    if ( $_POST['client_version'] != 0.1 ) {
        $return_array['error'] = 'Your browser has an old version of the page loaded. Please try refreshing your page or clearing your browser cache, then try again.';
    }
	else {
		// Parse Data
		$key_value_pairs = $_POST['key_value_json'];
		$key_value_pairs = stripslashes( $key_value_pairs );
		$key_value_pairs = json_decode( $key_value_pairs, true );
		if ( empty( $key_value_pairs ) ) {
			$return_array['error'] = 'There was a problem processing any data. Unless you intended to remove all fields and their values, this is bad.';
		}
		else {
			// TODO:
			// verify slugs are valid -- sanitize_title()
			// could use array_filter and compare size/length
			$valid_slugs = true;
			foreach ( $key_value_pairs as $section ) {
				// Checks that the provided slug matches wordpress's suggested slug and starts with a letter (wordpress allows slugs to start with a number)
				if ( ( $section['slug'] != sanitize_title( $section['slug'] ) ) || ( !ctype_alpha( substr( $section['slug'], 0, 1 ) ) ) ) {
					$valid_slugs = false;
					break;
				}
				foreach ( $section['subsections'] as $subsection ) {
					if ( ( $subsection['slug'] != sanitize_title( $subsection['slug'] ) ) || ( !ctype_alpha( substr( $subsection['slug'], 0, 1 ) ) ) ) {
						$valid_slugs = false;
						break;
					}
				}
				if ( !$valid_slugs ) {
					break;
				}
			}
			if ( !$valid_slugs ) {
				$return_array['error'] = 'It seems the section/subsection names returned unacceptable "slug names". Usually this is caused by data tampering.';
			}
			else {
				// Check for confirmation key
				if ( !isset( $_POST['confirmation_key'] ) ) {
					$return_array['confirm_request'] = true;
					$return_array['confirm_message'] = 'You requested to save section settings. This will synchronize across all products. Are you sure you want to continue?';
				}
				else {
					update_option( 'thunder_plugin_products_section_settings', $key_value_pairs );
					$return_array['message'] .= 'Saved sections settings';
					// Get information about slug changes
					$slug_changes_list = $_POST['slug_changes'];
					$slug_changes_list = stripslashes( $slug_changes_list);
					$slug_changes_list = json_decode( $slug_changes_list, true );
					// List of key changes will be empty if all keys are new or all old keys are removed
					
					// START Synchronize sections to other saved products

						// The Query
						$args = array( 'post_type' => 'tdr_product', 'posts_per_page'=>-1 );
						$the_query = new WP_Query( $args );

						// Import master section list from wordpress options table
						$product_section_settings = get_option( 'thunder_plugin_products_section_settings', $default = array() );

						// The Loop
						while ( $the_query->have_posts() ) : $the_query->the_post();
								global $post;
								// Fetch current serialized section list for tdr_product in the loop			
								$saved_sections_meta = get_post_meta( $post->ID, 'thunder_plugin_products_full_review_sections', true );

								// Deserialize
								$saved_sections_meta = json_decode( $saved_sections_meta, true );

								if ( empty( $saved_sections_meta ) ) {
									// Make an empty array if not set
									$saved_sections_meta = array();	
								}
								
								// Change names of saved slugs that were renamed
								foreach ( $slug_changes_list as $original_slug => $renamed_slug ) {
									// Rename any subsections first
									if ( !empty( $slug_changes_list[ $original_slug ]['subsections'] ) ) {
										foreach ( $slug_changes_list[ $original_slug ]['subsections'] as $original_subsection => $renamed_subsection ) {
											// Assign old subsection's value to renamed slug
											$saved_sections_meta[ $original_slug ]['subsections'][ $renamed_subsection ] = $saved_sections_meta[ $original_slug ]['subsections'][ $original_subsection ];
											// Change the name and slug values within that subsection
											/*if ( empty( $slug_changes_list[ $original_slug ]['new_name'] ) ) {
												$section_slug = $original_slug;
											}
											else {
												$section_slug = $renamed_slug['new_name'];
											}
											$saved_sections_meta[ $original_slug ]['subsections'][ $renamed_subsection ]['name'] = $product_section_settings[ $section_slug ]['subsections'][ $renamed_subsection ]['name'];*/ // Unneeded because names are synchronized below
											$saved_sections_meta[ $original_slug ]['subsections'][ $renamed_subsection ]['slug'] = $renamed_subsection; 
											// Remove original slug
											unset( $saved_sections_meta[ $original_slug ]['subsections'][ $original_subsection ] );
										}
									}
									// Finish by renaming section if necessary
									if ( !empty( $slug_changes_list[ $original_slug ]['new_name'] ) ) {
										// Assign old slug's value to renamed slug
										$saved_sections_meta [ $renamed_slug['new_name'] ] = $saved_sections_meta[ $original_slug ];
										// Change the name and slug values for section
										/*$saved_sections_meta [ $renamed_slug['new_name'] ]['name'] = $product_section_settings[ $renamed_slug['new_name'] ]['name'];*/ // Unneeded because names are synchronized below
										$saved_sections_meta [ $renamed_slug['new_name'] ]['slug'] = $renamed_slug['new_name'];
										// Remove original slug
										unset( $saved_sections_meta[ $original_slug ] );
									}
								}	
								// Merge together
								// First, find master top-level sections in product's saved list
								$saved_sections_meta = array_intersect_key( $saved_sections_meta, $product_section_settings ); // Stripped of extra sections not found in master list
								// Next, Remove subsections not found in master list's entry for each top-level section
								foreach ( $saved_sections_meta as $section ) {
									$saved_sections_meta[ $section['slug'] ]['subsections'] = array_intersect_key( $saved_sections_meta[ $section['slug'] ]['subsections'], $product_section_settings[ $section['slug'] ]['subsections'] );
									// Add any missing important keys -- if keys (slugs) are numeric, this will reindex them!
									$saved_sections_meta[ $section['slug'] ]['subsections'] = array_merge( $product_section_settings[ $section['slug'] ]['subsections'], $saved_sections_meta[ $section['slug'] ]['subsections'] );
								}
								
								// Loop through all present saved sections and subsections, synchronizing names -- separated from above loop for readability
								foreach ( $saved_sections_meta as $section ) {
									// Change name of top-level section if not current
									if ( $saved_sections_meta[ $section['slug'] ]['name'] != $product_section_settings[ $section['slug'] ]['name'] ) {
										$saved_sections_meta[ $section['slug'] ]['name'] = $product_section_settings[ $section['slug'] ]['name'];
									}
									foreach ( $section['subsections'] as $subsection ) {
										// Change name of subsection if not current
										if ( $saved_sections_meta[ $section['slug'] ]['subsections'][ $subsection['slug'] ]['name'] != $product_section_settings[ $section['slug'] ]['subsections'][ $subsection['slug'] ]['name'] ) {
											$saved_sections_meta[ $section['slug'] ]['subsections'][ $subsection['slug'] ]['name'] = $product_section_settings[ $section['slug'] ]['subsections'][ $subsection['slug'] ]['name'];
										}
									}
								}
								
								// Add any missing important keys
								$merged_fields = $saved_sections_meta + $product_section_settings;
								// Missing fields have no value key/value for subsections 
								// Reserialize
								$merged_fields = json_encode( $merged_fields );
								// Save
								update_post_meta( $post->ID, 'thunder_plugin_products_full_review_sections', $merged_fields );
						endwhile;
						
						// Reset Post Data
						wp_reset_postdata();
					$return_array['message'] .= ' and synchronized to '.$the_query->post_count.' products';
					// END Synchronize fields to other saved products
				}
			}
		}
	}
    $return_json = json_encode( $return_array );
    echo $return_json;
	die(); // this is required to return a proper result	
}

add_action('wp_ajax_tdr_save_global_key_value_pair', 'tdr_save_global_key_value_pair');
function tdr_save_global_key_value_pair() {
	// Initialize
	$tdr_product = New Thunder_Plugin_Products();
		
    // Setup request return info for client callback
    $return_array = array(
        'message' => '',
        'confirm_message' => '',
        'confirm_request' => false,
        'error' => '',
        'confirmation_key' => ''
    );
    // Set other defaults
		$keys_changed = false;
		$save_changes = false;
		$synch_changes = false;
		
    // Check javascript client script version
    if ( $_POST['client_version'] != 0.1 ) {
        $return_array['error'] = 'Your browser has an old version of the page loaded. Please try refreshing your page or clearing your browser cache, then try again.';
    }
    else {
		// Data is already decoded
		// Do any wanted filtering
		$key_value_pairs = $_POST['key_value_json'];
		$key_value_pairs = stripslashes( $key_value_pairs);
		$key_value_pairs = json_decode( $key_value_pairs, true );
		if ( empty( $key_value_pairs ) ) {
			$return_array['error'] = 'There was a problem processing any data. Unless you intended to remove all fields and their values, this is bad.';
		}
		else {
			// Ensure meta_id is valid (exists)
			$meta_id = esc_html( $_POST['meta_id'] );
			$meta_array = $tdr_product->define_custom_meta_types();
			$meta_id_valid = false;
			foreach ( $meta_array as $meta_entry ) {
				if ( $tdr_product->underscore( $meta_entry['uuid'] ) == $meta_id ) {
					$meta_id_valid = true;
				}
			}		
			if ( !$meta_id_valid )  {
				$return_array['error'] = 'The submission request contained tampered information.';
			}
			else {	
				// Need to get post id for ajax calling post
				$post_id = $_POST['post_id'];
				$meta_value = get_post_meta( $post_id, $meta_id, true );

				// Check if number or names of fields have changed
				$original_keys = get_option( $meta_id . '_keys' );
				$original_keys = array_keys( $original_keys );
				$newest_keys = array_keys( $key_value_pairs );
				if ( count( $original_keys ) == count( $newest_keys ) ) {
					$key_difference = array_diff( $newest_keys, $original_keys );
					if ( !empty( $key_difference ) ) {
						$keys_changed = true;
					}
				}
				else {
					$keys_changed = true;
				}
				if ( $keys_changed ) {
					// Check for admin powers
					if ( !current_user_can( 'administrator' ) ) {
						$return_array['error'] = 'You are not authorized to add/remove fields. Your changes will be discarded.';
					}
					else {
						// Check for confirmation key
						if ( !isset( $_POST['confirmation_key'] ) ) {
							$return_array['confirm_request'] = true;
							$return_array['confirm_message'] = 'You have changed the name/number of fields. This will synchronize across other products. Are you sure you want to continue?';
						}
						// Enable save and synchronization
						else {
							$save_changes = true;
							$synch_changes = true;
						}
					}
				}
				else {
					// Proceed to save
					$save_changes = true;
				}
				/**
				 * Save comparison points for current product
				 */
				if ( $save_changes ) {
					// JSON Encode the meta
					$new_meta_value = json_encode( $key_value_pairs );
					
					/* If there the new meta value is blank but an old value existed, delete it. */
					if ( '' == $new_meta_value && $meta_value )
						delete_post_meta( $post_id, $meta_id, $meta_value );	

					/* Updates meta value if it exists, Else Adds it to the post. */
					else
						update_post_meta( $post_id, $meta_id, $new_meta_value );
				
					$return_array['message'] = 'Your changes to this product have been saved';
				}
				/**
				 * Synchronize changes to fields to other products
				 */
				if ( !$synch_changes ) {
					
				}
				else {	
					$new_option_value = array_keys( $key_value_pairs );
					$new_option_value = array_map("strval", $new_option_value);
					$new_option_array = array();
					foreach ( $new_option_value as $key ) {
						// Keys mapped to empty string
						$new_option_array[$key] = ""; 
					}
					update_option( $meta_id . '_keys', $new_option_array );
					
					// Get information about key changes
					$key_changes_list = $_POST['key_changes'];
					$key_changes_list = stripslashes( $key_changes_list);
					$key_changes_list = json_decode( $key_changes_list, true );
					// List of key changes will be empty if all keys are new or all old keys are removed

					// START Synchronize fields to other saved products

						// The Query
						$args = array( 'post_type' => 'tdr_product', 'posts_per_page'=>-1, 'post__not_in' => array( $post_id ) );
						$the_query = new WP_Query( $args );

						// Import important comparison keys from wordpress options table
						// Get serialized list of custom comparison keys from WP options -- false if not set
						$comparison_keys = get_option( $meta_id . '_keys', $default = array() );

						// The Loop
						while ( $the_query->have_posts() ) : $the_query->the_post();
								global $post;
								// Fetch current serialized key-value pair for tdr_product in the loop			
								$product_comparison_meta = get_post_meta( $post->ID, $meta_id, true );

								// Deserialize
								$product_comparison_meta = json_decode( $product_comparison_meta, true );

								if ( empty( $product_comparison_meta ) ) {
									// Make an empty array if not set
									$product_comparison_meta = array();	
								}

								// Access important keys from class attribute
								$base_key_array = $tdr_product->fetch_custom_keys( $meta_id );
								
								// Change names of saved keys that were renamed
								foreach ( $key_changes_list as $original_key => $renamed_key ) {
									// Assign old key's value to renamed key
									$product_comparison_meta [ $renamed_key ] = $product_comparison_meta[ $original_key ];
									// Remove original key
									unset( $product_comparison_meta[ $original_key ] );
								}
								
								// Find saved values with important keys
								$product_comparison_meta = array_intersect_key( $product_comparison_meta, $base_key_array );
								// Add any missing important keys
								$merged_fields = $product_comparison_meta + $base_key_array;
								// Because they are originally serialized by javascript, resaving them here will make them a bit different: e.g., no HTML entities
								// Reserialize
								$merged_fields = json_encode( $merged_fields );
								// Save
								update_post_meta( $post->ID, $meta_id, $merged_fields );
						endwhile;
						
						// Reset Post Data
						wp_reset_postdata();
					$return_array['message'] .= ' and synchronized to '.$the_query->post_count.' other products';
					// END Synchronize fields to other saved products
				}
			}
		}
    }
    $return_json = json_encode( $return_array );
    echo $return_json;
	die(); // this is required to return a proper result
}
add_action('wp_ajax_tdr_save_local_key_value_pair', 'tdr_save_local_key_value_pair');

function tdr_save_local_key_value_pair() {
	// Initialize
	$tdr_product = New Thunder_Plugin_Products();
	
    // Setup request return info for client callback
    $return_array = array(
        'message' => '',
        'confirm_message' => '',
        'confirm_request' => false,
        'error' => '',
        'confirmation_key' => ''
    );

    // Check javascript client script version
    if ( $_POST['client_version'] != 0.1 ) {
        $return_array['error'] = 'Your browser has an old version of the page loaded. Please try refreshing your page or clearing your browser cache, then try again.';
    }
	else {
		// Parse Data
		$key_value_pairs = $_POST['key_value_json'];
		$key_value_pairs = stripslashes( $key_value_pairs );
		$key_value_pairs = json_decode( $key_value_pairs, true );
		$save_changes = true; // Save changes unless revoked below (no data &&& no confirmation)
		if ( empty( $key_value_pairs ) ) {
			// Check for confirmation key
			if ( !isset( $_POST['confirmation_key'] ) ) {
				$return_array['confirm_request'] = true;
				$return_array['confirm_message'] = 'No data was received. If you wanted to clear the data in this section, please confirm.';
				$save_changes = false;
			}
			// Else will proceed
		}
		if ( $save_changes ) {		
			// Ensure meta_id is valid (exists)
			$meta_id = esc_html( $_POST['meta_id'] );
			$meta_array = $tdr_product->define_custom_meta_types();
			$meta_id_valid = false;
			foreach ( $meta_array as $meta_entry ) {
				if ( $tdr_product->underscore( $meta_entry['uuid'] ) == $meta_id ) {
					$meta_id_valid = true;
				}
			}
			if ( !$meta_id_valid )  {
				$return_array['error'] = 'The submission request contained tampered information.';
			}
			else {
				// Need to get post id for ajax calling post
				$post_id = $_POST['post_id'];
				$meta_value = get_post_meta( $post_id, $meta_id, true );
				
				// JSON Encode the meta
				$new_meta_value = json_encode( $key_value_pairs );
				
				/* If there the new meta value is blank but an old value existed, delete it. */
				if ( '' == $new_meta_value && $meta_value )
					delete_post_meta( $post_id, $meta_id, $meta_value );	

				/* Updates meta value if it exists, Else Adds it to the post. */
				else
					update_post_meta( $post_id, $meta_id, $new_meta_value );

			//	echo('Nice. We have found json that has ' . count( $key_value_json ) . ' pairs');

			//	echo('Nice. You are on post ' . $post_id);

				$return_array['message'] = 'Your changes have been saved';
			}
		}
	}
    $return_json = json_encode( $return_array );
    echo $return_json;
	die(); // this is required to return a proper result
}
?>
