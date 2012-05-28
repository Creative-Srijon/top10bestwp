/*

*/
// implement JSON.stringify serialization  
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
jQuery(document).ready(function(){
	pairCount = jQuery('.tdr-object-input-container').find('textarea').size()/2;
	jQuery('.tdr-object-add-pair').on('click', function() {
		keyLabel = jQuery(this).attr('data-label-key');
		valueLabel = jQuery(this).attr('data-label-value');
		newKeyValuePair = jQuery('<tr style="width:100%;"><td><label for="key'+pairCount+'">'+keyLabel+'</label><br /><textarea name="key'+pairCount+'" id="key'+pairCount+'" style="width:99%;" rows="2" data-original-key=""></textarea></td><td><label for="value'+pairCount+'">'+valueLabel+'</label><br /><textarea name="value'+pairCount+'" id="value'+pairCount+'" style="width:99%;" rows="2"></textarea></td><td><div class="tagchecklist"><span><a class="ntdelbutton">X</a></span></div></td></tr>')
		jQuery(this).parent().siblings('.tdr-object-input-container').append(newKeyValuePair);
		pairCount++;
		// Add remove handler for newly added remove button
		jQuery( newKeyValuePair ).find('.ntdelbutton').on('click', function() {
			if( confirm( 'Are you sure you really want to remove this field?' ) ) {
				jQuery(this).parent().parent().parent().parent().fadeTo(500, 0, function(){ jQuery(this).remove(); });
			}
			else {
				alert( 'Remove cancelled.' );
			}
			return false;
		});
		return false;
	});
	jQuery(".tdr-object-input-container .tagchecklist .ntdelbutton").on("click", function() {
		if( confirm( 'Are you sure you really want to remove this field?' ) ) {
			jQuery(this).parent().parent().parent().parent().fadeTo(500, 0, function(){ jQuery(this).remove(); });
		}
		else {
			alert( 'Remove cancelled.' );
		}
		return false;
	});

	jQuery( '.tdr-object-do-ajax' ).on( 'click', function() {
		if ( jQuery( this ).hasClass( 'global' ) ) {
			jQuery(this).parent().siblings( '.tdr-global-key-value-pair' ).each( function() {
				globalKeyValuePair = this;
				var objValues = {};
				var keyChangeMap = {};
				jQuery( this ).find( 'tr' ).each( function() {
					// Find key and value
					var key = jQuery(this).find('textarea:first').val();
					var value = jQuery(this).find('textarea:eq(1)').val();
					// Urlencode to avoid escaping problems
                    key = encodeURIComponent(key);
                    // Assign key and value to object objValues
                    value = encodeURIComponent(value);
					objValues[key] = value;
					// Find original key
					var originalKey = jQuery(this).find('textarea:first').attr('data-original-key');
					originalKey = encodeURIComponent( originalKey );
					// Push non-empty keys which have changed to object keyChangeMap
					if ( originalKey != '' && originalKey != key) {
						keyChangeMap[ originalKey ] = key;
					}
				});
				//serialize
				jQuery(this).siblings('input:last').val(JSON.stringify(objValues));
				//return false;
				
				var data = {
					action: 'tdr_save_global_key_value_pair',
					post_id: jQuery(this).siblings('.meta_object_pair_controls').children('.tdr-object-do-ajax').attr('data-post-id'),
					meta_id: jQuery(this).siblings('input:last').attr('id').replace(/\-/g,'_'),
					key_value_json: JSON.stringify( objValues ),
					key_changes: JSON.stringify( keyChangeMap ),
                    client_version: 0.1
				};
				
				jQuery.post(ajaxurl, data, function(response) {
                    response = jQuery.parseJSON(response);
                    if ( response.error ) { 
                       alert( 'ERROR: ' + response.error );
                    }
					else {
                        if ( response.message ) {
                            alert( 'SUCCESS: ' + response.message );
                        }
                        if( response.confirm_request ) {
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
                                        // On success after changing keys, update 'original-key' data attributes
                                        jQuery( globalKeyValuePair ).find( 'tr' ).each( function() {
											// Find newest key from textarea
											var key = jQuery(this).find('textarea:first').val();
											// Set original key attribute						
											jQuery(this).find('textarea:first').attr('data-original-key', key);
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
					    //alert('Saved. Reloading the page for you now.');
    					//window.location.reload();
                    }
				}).error( function() {
                    alert( 'There was a problem sending the data. Please try again' );
                });			
			});
		}
		else if ( jQuery(this).hasClass('local') ) {
			jQuery(".tdr-local-key-value-pair").each(function() {
				var objValues = {};
				jQuery(this).find("tr").each(function() {
					var key = jQuery(this).find("textarea:first").val();
					var value = jQuery(this).find("textarea:eq(1)").val();
                    key = encodeURIComponent(key);
                    value = encodeURIComponent(value);
                    objValues[key] = value;
				});
				//serialize
				jQuery(this).siblings("input:last").val(JSON.stringify(objValues));
				//return false;
				
				var data = {
					action: 'tdr_save_local_key_value_pair',
					post_id: jQuery(this).siblings('.meta_object_pair_controls').children('.tdr-object-do-ajax').attr('data-post-id'),
					meta_id: jQuery(this).siblings('input:last').attr('id').replace(/\-/g,"_"),
					key_value_json: JSON.stringify(objValues),
                    client_version: 0.1
				};
				
				jQuery.post(ajaxurl, data, function(response) {
                    response = jQuery.parseJSON(response);
                    if ( response.error ) { 
                       alert( 'ERROR: ' + response.error );
                    }
					if ( response.message ) {
                            alert( 'SUCCESS: ' + response.message );
                    }
					if( response.confirm_request ) {
						if( confirm( response.confirm_message ) ) {
							d = new Date();
							data.confirmation_key = d.getTime();
							jQuery.post(ajaxurl, data, function(response) {
								response = jQuery.parseJSON(response);
								if ( response.error ) {
									alert( response.error );
								}
								else {
									alert( response.message );
								}
							}).error( function() {
								alert( 'There was a problem sending the data. Please try again');
							});
						}
						else {
							alert( 'Save cancelled.' );
						}
					}
				}).error( function() {
                    alert( 'There was a problem sending the data. Please try again' );
                });			
			});
		}
		return false;
	});
	jQuery( '#tdr_save_sections' ).on( 'click', function() {
		var objValues = {};
		// Find each regular section
		jQuery( '#tdr_product_sections' ).find( '.tdr_product_section' ).each( function(i) {
			// echo the value to be sure
			sectionName = jQuery( this ).find('th:first').find('h3').text();
			sectionSlug = jQuery( this ).find('th:first').find('h3').attr('name');
			objValues [ sectionSlug ] = {
				name: encodeURIComponent( sectionName ),
				slug: sectionSlug,
				subsections: {}
			}
			// Find each subsection
			jQuery( this ).find('.tdr_product_subsection').each( function(j) {
				subSectionName = jQuery( this ).find('td:eq(0)').text();
				subSectionSlug = jQuery( this ).find('td:eq(1)').children('textarea').attr('name');
				subSectionValue = jQuery( this ).find('td:eq(1)').children('textarea').val();
				objValues[sectionSlug].subsections[subSectionSlug] = {
						name: encodeURIComponent( subSectionName ),
						slug: subSectionSlug,
						value: encodeURIComponent( subSectionValue )
				};
			});
		});
		var data = {
			action: 'tdr_save_section_values',
			key_value_json: JSON.stringify( objValues ),
			post_id: jQuery( '#tdr_product_sections' ).attr('data-post-id'),
			client_version: 0.1
		};
		jQuery.post(ajaxurl, data, function(response) {
			response = jQuery.parseJSON(response);
			if ( response.error ) { 
			   alert( 'ERROR: ' + response.error );
			}
			else if ( response.message ) {
				alert( 'SUCCESS: ' + response.message );
			}
		}).error( function() {
			alert( 'There was a problem sending the data. Please try again' );
		});
		return false;		
	});
});
