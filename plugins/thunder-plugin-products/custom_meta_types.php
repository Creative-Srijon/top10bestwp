<?php
$product_meta_field_properties_array = array (
	// unique ID, title, callback function, post-type, context (location), priority, filter-type
		'aff_url' => array( 
			'uuid' => 'thunder-plugin-products-aff-url', 
			'title' => esc_html( 'Affiliate URL' ), 
			'desc' => esc_html( 'enter url here' ), 
			'callback' => array( &$this, 'meta_display' ), 
			'page' => 'tdr_product', 
			'context' => 'side', 
			'priority' => 'default', 
			'filter' => 'url', 
			'type' => 'singular' 
		),
		'rank' => array( 
			'uuid' => 'thunder-plugin-products-rank', 
			'title' => esc_html( 'Product Rank' ), 
			'desc' => esc_html( 'enter rank here' ), 
			'callback' => array( &$this, 'meta_display' ), 
			'page' => 'tdr_product', 
			'context' => 'side', 
			'priority' => 'default', 
			'filter' => 'html',
			'type' => 'singular' 
		),
		'cpa' => array( 
			'uuid' => 'thunder-plugin-products-cpa', 
			'title' => esc_html( 'CPA Value' ), 
			'desc' => esc_html( 'enter CPA here' ), 
			'callback' => array( &$this, 'meta_display' ), 
			'page' => 'tdr_product', 
			'context' => 'side', 
			'priority' => 'default', 
			'filter' => 'float', 
			'type' => 'array' /* is_array */ 
		),
		'conv' => array( 
			'uuid' => 'thunder-plugin-products-conv', 
			'title' => esc_html( 'Conversion Rate' /* should be automatically generated */ ), 
			'desc' => esc_html( 'enter conversion rate here' ), 
			'callback' => array( &$this, 'meta_display' ), 
			'page' => 'tdr_product', 
			'context' => 'side', 
			'priority' => 'default', 
			'filter' => 'float', 
			'type' => 'singular' 
		),
		'epc' => array( 
			'uuid' => 'thunder-plugin-products-epc', 
			'title' => esc_html( 'Product EPC' /* should be automatically calculated */ ), 
			'desc' => esc_html( 'enter EPC here' ), 
			'callback' => array( &$this, 'meta_display' ), 
			'page' => 'tdr_product', 
			'context' => 'side', 
			'priority' => 'default', 
			'filter' => 'float', 
			'type' => 'singular' 
		),
		'int_rating' => array( 
			'uuid' => 'thunder-plugin-products-int-rating', 
			'title' => esc_html( 'Internal Rating' ), 
			'desc' => esc_html( 'enter internal rating here' ), 
			'callback' => array( &$this, 'meta_display' ), 
			'page' => 'tdr_product', 
			'context' => 'side', 
			'priority' => 'default', 
			'filter' => 'html', 
			'type' => 'singular' 
		),
		'ext_rating' => array( 
			'uuid' => 'thunder-plugin-products-ext-rating', 
			'title' => esc_html( 'External Rating' ), 
			'desc' => esc_html( 'enter external rating here' ), 
			'callback' => array( &$this, 'meta_display' ), 
			'page' => 'tdr_product', 
			'context' => 'side', 
			'priority' => 'default', 
			'filter' => 'html', 
			'type' => 'singular' 
		),
		'win_size' => array( 
			'uuid' => 'thunder-plugin-products-win-size', 
			'title' => esc_html( 'Window size' ), 
			'desc' => esc_html( 'enter width, height eg: 300, 200' ), 
			'callback' => array( &$this, 'meta_display' ), 
			'page' => 'tdr_product', 
			'context' => 'side', 
			'priority' => 'default', 
			'filter' => 'stripspace', 
			'type' => 'array' /* is_array */ 
		),
		'win_offset' => array( 
			'uuid' => 'thunder-plugin-products-win-offset', 
			'title' => esc_html( 'Window offset' ), 
			'desc' => esc_html( 'enter offset x, y in pixels eg: 300, 200' ), 
			'callback' => array( &$this, 'meta_display' ), 
			'page' => 'tdr_product', 
			'context' => 'side', 
			'priority' => 'default', 
			'filter' => 'stripspace', 
			'type' => 'array' /* is_array */ 
		),
		'disp_url' => array( 
			'uuid' => 'thunder-plugin-products-disp-url',
			'title' => esc_html( 'Display URL' ), 
			'desc' => esc_html( 'enter url here' ), 
			'callback' => array( &$this, 'meta_display' ), 
			'page' => 'tdr_product', 
			'context' => 'normal', 
			'priority' => 'default', 
			'filter' => 'url', 
			'type' => 'singular' 
		),
		'freq' => array( 
			'uuid' => 'thunder-plugin-products-price-freq', 
			'title' => esc_html( 'Pricing Continuum' ), 
			'desc' => esc_html( 'Billing/Price pairs' ), 
			'callback' => array( &$this, 'meta_display' ), 
			'page' => 'tdr_product', 
			'context' => 'side', 
			'priority' => 'default', 
			'filter' => 'html', 
			'type' => 'local_key_value_pair' /* object */ 
		),
		'compare' => array( 
			'uuid' => 'thunder-plugin-products-comparison-points', 
			'title' => esc_html( 'Comparison Points' ), 
			'desc' => esc_html( 'Field/Value pairs' ), 
			'callback' => array( &$this, 'meta_display' ), 
			'page' => 'tdr_product', 
			'context' => 'side', 
			'priority' => 'default', 
			'filter' => 'html', 
			'type' => 'global_key_value_pair' /* object */ 
		),
		//'compare' => array(
		//	'uuid' => 'thunder-plugin-products-comparison-points',
		//	'title' => esc_html__( 'Comparison Points' ),
		//	'desc' => esc_html__( 'Used to compare different Products' ),
		//	'callback' => array( &$this, 'meta_display' ),
		//	'page' => 'tdr_product',
		//	'context' => 'normal',
		//	'priority' => 'high',
		//	'filter' => 'html',
		//	'type' => 'object'
		//),
		//'custom' => array( 
		//	'uuid' => 'thunder-plugin-products-custom', 
		//	'title' => esc_html__( 'Custom Field' ), 
		//	'desc' => esc_html__( 'enter custom text here' ), 
		//	'callback' => array( &$this, 'meta_display' ), 
		//	'page' => 'tdr_product', 
		//	'context' => 'side', 
		//	'priority' => 'default', 
		//	'filter' => 'html', 
		//	'type' => 'singular' 
		//),
		'ga-sku' => array( 
			'uuid' => 'thunder-plugin-products-ga-sku', 
			'title' => esc_html( 'GA Product SKU' ), 
			'desc' => esc_html( 'enter GA SKU here' ), 
			'callback' => array( &$this, 'meta_display' ), 
			'page' => 'tdr_product', 
			'context' => 'side', 
			'priority' => 'default', 
			'filter' => 'html', 
			'type' => 'singular' 
		),
		'aff_name' => array( 
			'uuid' => 'thunder-plugin-products-aff-name', 
			'title' => esc_html( 'Affiliate Name' ), 
			'desc' => esc_html( 'enter affiliate here' ), 
			'callback' => array( &$this, 'meta_display' ), 
			'page' => 'tdr_product', 
			'context' => 'side', 
			'priority' => 'default', 
			'filter' => 'html', 
			'type' => 'singular' 
		),
		'int_notes' => array( 
			'uuid' => 'thunder-plugin-products-int-notes', 
			'title' => esc_html( 'Affiliate Notes' ), 
			'desc' => esc_html( 'enter notes here' ), 
			'callback' => array( &$this, 'meta_display' ), 
			'page' => 'tdr_product', 
			'context' => 'normal', 
			'priority' => 'default', 
			'filter' => 'html', 
			'type' => 'singular' 
		),
		'short_url' => array( 
			'uuid' => 'thunder-plugin-products-short-url', 
			'title' => esc_html( 'Short URL' ), 
			'desc' => esc_html( 'enter short url here' ), 
			'callback' => array( &$this, 'meta_display' ), 
			'page' => 'tdr_product', 
			'context' => 'side', 
			'priority' => 'default', 
			'filter' => 'url', 
			'type' => 'singular' 
		)
	);
?>
