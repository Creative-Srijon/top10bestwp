/* =============================================================
 * Thunder Core Custom Functions
 * ============================================================= */
/* TOOLTIPS
 * ***************************************************************************/
jQuery(document).ready(function() {
    jQuery('*[rel="tooltip"]').tooltip();
});

/* ------------------------------------------------------------
 * COOKIES
 * ------------------------------------------------------------*/
/*!
 * jQuery Cookie Plugin
 * https://github.com/carhartl/jquery-cookie
 *
 * Copyright 2011, Klaus Hartl
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.opensource.org/licenses/GPL-2.0
 */
(function($) {
    $.cookie = function(key, value, options) {

        // key and at least value given, set cookie...
        if (arguments.length > 1 && (!/Object/.test(Object.prototype.toString.call(value)) || value === null || value === undefined)) {
            options = $.extend({}, options);

            if (value === null || value === undefined) {
                options.expires = -1;
            }

            if (typeof options.expires === 'number') {
                var days = options.expires, t = options.expires = new Date();
                t.setDate(t.getDate() + days);
            }

            value = String(value);

            return (document.cookie = [
                encodeURIComponent(key), '=', options.raw ? value : encodeURIComponent(value),
                options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
                options.path    ? '; path=' + options.path : '',
                options.domain  ? '; domain=' + options.domain : '',
                options.secure  ? '; secure' : ''
            ].join(''));
        }

        // key and possibly options given, get cookie...
        options = value || {};
        var decode = options.raw ? function(s) { return s; } : decodeURIComponent;

        var pairs = document.cookie.split('; ');
        for (var i = 0, pair; pair = pairs[i] && pairs[i].split('='); i++) {
            if (decode(pair[0]) === key) return decode(pair[1] || ''); // IE saves cookies with empty string as "c; ", e.g. without "=" as opposed to EOMB, thus pair[1] may be undefined
        }
        return null;
    };
})(jQuery);

/* -------------------------------------------------------------
 PLACEHOLDERS
--------------------------------------------------------------*/
jQuery(document).ready(function() {
	jQuery('input, textarea').placeholder();
});

/*! http://mths.be/placeholder v2.0.4 by @mathias */
;(function(window, document, jQuery) {

	var isInputSupported = 'placeholder' in document.createElement('input'),
	    isTextareaSupported = 'placeholder' in document.createElement('textarea'),
	    prototype = jQuery.fn,
	    valHooks = jQuery.valHooks,
	    hooks,
	    placeholder;

	if (isInputSupported && isTextareaSupported) {

		placeholder = prototype.placeholder = function() {
			return this;
		};

		placeholder.input = placeholder.textarea = true;

	} else {

		placeholder = prototype.placeholder = function() {
			return this
				.filter((isInputSupported ? 'textarea' : ':input') + '[placeholder]')
				.not('.placeholder')
				.bind({
					'focus.placeholder': clearPlaceholder,
					'blur.placeholder': setPlaceholder
				})
				.data('placeholder-enabled', true)
				.trigger('blur.placeholder').end();
		};

		placeholder.input = isInputSupported;
		placeholder.textarea = isTextareaSupported;

		hooks = {
			'get': function(element) {
				var jQueryelement = jQuery(element);
				return jQueryelement.data('placeholder-enabled') && jQueryelement.hasClass('placeholder') ? '' : element.value;
			},
			'set': function(element, value) {
				var jQueryelement = jQuery(element);
				if (!jQueryelement.data('placeholder-enabled')) {
					return element.value = value;
				}
				if (value == '') {
					element.value = value;
					// Issue #56: Setting the placeholder causes problems if the element continues to have focus.
					if (element != document.activeElement) {
						// We can’t use `triggerHandler` here because of dummy text/password inputs :(
						setPlaceholder.call(element);
					}
				} else if (jQueryelement.hasClass('placeholder')) {
					clearPlaceholder.call(element, true, value) || (element.value = value);
				} else {
					element.value = value;
				}
				// `set` can not return `undefined`; see http://jsapi.info/jquery/1.7.1/val#L2363
				return jQueryelement;
			}
		};

		isInputSupported || (valHooks.input = hooks);
		isTextareaSupported || (valHooks.textarea = hooks);

		jQuery(function() {
			// Look for forms
			jQuery(document).delegate('form', 'submit.placeholder', function() {
				// Clear the placeholder values so they don’t get submitted
				var jQueryinputs = jQuery('.placeholder', this).each(clearPlaceholder);
				setTimeout(function() {
					jQueryinputs.each(setPlaceholder);
				}, 10);
			});
		});

		// Clear placeholder values upon page reload
		jQuery(window).bind('beforeunload.placeholder', function() {
			jQuery('.placeholder').each(function() {
				this.value = '';
			});
		});

	}

	function args(elem) {
		// Return an object of element attributes
		var newAttrs = {},
		    rinlinejQuery = /^jQuery\d+jQuery/;
		jQuery.each(elem.attributes, function(i, attr) {
			if (attr.specified && !rinlinejQuery.test(attr.name)) {
				newAttrs[attr.name] = attr.value;
			}
		});
		return newAttrs;
	}

	function clearPlaceholder(event, value) {
		var input = this,
		    jQueryinput = jQuery(input);
		if (input.value == jQueryinput.attr('placeholder') && jQueryinput.hasClass('placeholder')) {
			if (jQueryinput.data('placeholder-password')) {
				jQueryinput = jQueryinput.hide().next().show().attr('id', jQueryinput.removeAttr('id').data('placeholder-id'));
				// If `clearPlaceholder` was called from `jQuery.valHooks.input.set`
				if (event === true) {
					return jQueryinput[0].value = value;
				}
				jQueryinput.focus();
			} else {
				input.value = '';
				jQueryinput.removeClass('placeholder');
			}
		}
	}

	function setPlaceholder() {
		var jQueryreplacement,
		    input = this,
		    jQueryinput = jQuery(input),
		    jQueryorigInput = jQueryinput,
		    id = this.id;
		if (input.value == '') {
			if (input.type == 'password') {
				if (!jQueryinput.data('placeholder-textinput')) {
					try {
						jQueryreplacement = jQueryinput.clone().attr({ 'type': 'text' });
					} catch(e) {
						jQueryreplacement = jQuery('<input>').attr(jQuery.extend(args(this), { 'type': 'text' }));
					}
					jQueryreplacement
						.removeAttr('name')
						.data({
							'placeholder-password': true,
							'placeholder-id': id
						})
						.bind('focus.placeholder', clearPlaceholder);
					jQueryinput
						.data({
							'placeholder-textinput': jQueryreplacement,
							'placeholder-id': id
						})
						.before(jQueryreplacement);
				}
				jQueryinput = jQueryinput.removeAttr('id').hide().prev().attr('id', id).show();
				// Note: `jQueryinput[0] != input` now!
			}
			jQueryinput.addClass('placeholder');
			jQueryinput[0].value = jQueryinput.attr('placeholder');
		} else {
			jQueryinput.removeClass('placeholder');
		}
	}

}(this, document, jQuery));
/* ============================================================
 * bootstrap-dropdown.js v2.0.0
 * http://twitter.github.com/bootstrap/javascript.html#dropdowns
 * ============================================================
 * Copyright 2012 Twitter, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ============================================================ */


!function( $ ){

  "use strict"

 /* DROPDOWN CLASS DEFINITION
  * ========================= */

  var toggle = '[data-toggle="dropdown"]'
    , Dropdown = function ( element ) {
        var jQueryel = jQuery(element).on('click.dropdown.data-api', this.toggle)
        jQuery('html').on('mouseover.dropdown.data-api', function () {
          jQueryel.parent().removeClass('open')
        })
      }
  Dropdown.prototype = {

    constructor: Dropdown

  , toggle: function ( e ) {
      var jQuerythis = jQuery(this)
        , selector = jQuerythis.attr('data-target')
        , jQueryparent
        , isActive

      if (!selector) {
        selector = jQuerythis.attr('href')
        selector = selector && selector.replace(/.*(?=#[^\s]*jQuery)/, '') //strip for ie7
      }

      jQueryparent = jQuery(selector)
      jQueryparent.length || (jQueryparent = jQuerythis.parent())

      isActive = jQueryparent.hasClass("open:not("+this+")")
	  jQuery(toggle).parent(":not("+this+")").removeClass('open');
      //waitBeforeClear()
      !isActive && jQueryparent.toggleClass('open')

      return false
    }

  }

  function clearMenus() {
    jQuery(toggle).parent().removeClass('open')
  }

  function waitBeforeClear() {
    var leave = 1;
    jQuery('.dropdown, .dropdown-menu').bind('hover.dropdown.data-api', function() { 
        leave = 0; 
    }).delay(200, "menuQueue").queue("menuQueue", function(){
	  jQuery(this).unbind('mouseover.dropdown.data-api');
	  if(leave==1) {
		clearMenus();
	  }
	}).dequeue("menuQueue");
    leave = 1;
  }

  /* DROPDOWN PLUGIN DEFINITION
   * ========================== */

  jQuery.fn.dropdown = function ( option ) {
    return this.each(function () {
      var jQuerythis = jQuery(this)
        , data = jQuerythis.data('dropdown')
      if (!data) jQuerythis.data('dropdown', (data = new Dropdown(this)))
      if (typeof option == 'string') data[option].call(jQuerythis)
    })
  }

  jQuery.fn.dropdown.Constructor = Dropdown


  /* APPLY TO STANDARD DROPDOWN ELEMENTS
   * =================================== */

  jQuery(function () {
    jQuery('.dropdown').on('mouseleave.dropdown.data-api', waitBeforeClear)
    jQuery('body').on('mouseover.dropdown.data-api', toggle, Dropdown.prototype.toggle)
  })

}( window.jQuery )

/* JSON.stringify Polyfill for ie7 and older browsers
 * ***************************************************************************/
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
/* EMAIL CAPTURE & WORDPRESS SUBSCRIBER REGISTRATION AJAX HANDLER
 * ***************************************************************************/
jQuery( document ).ready (function() {
	jQuery ( '.tdr_register_email' ).parents('form').on( 'submit', function( event ) {
		// Define submit button
		submit_button = jQuery( this ).find('.tdr_register_email');
		// Define the form
		form = this;
		// Define status containers
		error_container = jQuery( form ).siblings( '.tdr_register_email_error' );
		duplicate_error_container = jQuery( form ).siblings( '.tdr_register_email_duplicate_error' );
        validation_error_container = jQuery( form ).siblings( '.tdr_register_email_validation_error' );
		success_container = jQuery( form ).siblings( '.tdr_register_email_success' );
		// Toggle ajax-in-working-state class
		jQuery( form ).find( '.tdr_register_email_working' ).ajaxStart( function() {
			jQuery( this ).show();
		}).ajaxStop( function() {
			jQuery( this ).hide();
		});
		jQuery( submit_button ).ajaxStart( function() {
			jQuery( this ).hide();
		}).ajaxStop( function() {
			jQuery( this ).show();
		});
		// Hide status on each submission
		jQuery( error_container).add( duplicate_error_container ).add( validation_error_container ).add( success_container ).hide();
		// Get user information from form
		var user_information = {};
		jQuery( form ).find( 'input' ).each(function() {
            //Remove any error styling
            jQuery(this).parents( '.control-group' ).removeClass( 'error' );
            // Get key and value
			var key = jQuery(this).attr( 'name' );
			var value = jQuery(this).val();
            // Encode value
			value = encodeURIComponent(value);
            // Set key and value
			user_information[key] = value;
		});
		var request_details = {
			list_id: jQuery( form ).attr('data-tdr-list-id'),
            group_id: jQuery ( form ).attr('data-tdr-group-id'),
			opt_in_source: jQuery( form ).attr('data-tdr-opt-in-source'),
			password_length: jQuery( form ).attr('data-tdr-pw-length'),
			user_registration: jQuery( form ).attr('data-tdr-user-registration'),
			email_capture: jQuery( form ).attr('data-tdr-email-capture'),
			is_promotion: jQuery( form ).attr('data-tdr-promotion'),
			promotion_name: jQuery( form ).attr('data-tdr-promotion-name'),
			referrer_id: jQuery( form ).attr('data-tdr-promotion-referrer')
		}
		// IE FIX for undefined fields breaking requests
		for ( field in request_details ) { // Look through fields in request details
			if ( request_details[ field ] == undefined ) {
				request_details[ field ] = ''; // Set undefined fields to an empty string
			}
		}
		var post_data = {
			request_details: request_details,
			user_information: user_information
		}
		var data = {
			action: 'tdr_email_list_subscribe',
			data: JSON.stringify(post_data)
		};
		jQuery.post(ajaxurl, data, function(response) {
			response = jQuery.parseJSON(response);
			if ( response.error ) {
				if ( response.duplicate ) {
					jQuery( duplicate_error_container ).show();
				}
				else {
					jQuery( error_container ).show();
				}
			}
			else if ( response.invalid.length > 0 ) {
                jQuery( validation_error_container ).show();
                for ( fieldName in response.invalid ) {
					jQuery( form ).find('input[name|="' + response.invalid[fieldName]  + '"]').parents('.control-group').addClass( 'error' );
                }				
			}
			else if ( response.message ) {
				jQuery( success_container ).show().delay(10000).fadeOut('slow');
				if ( request_details.is_promotion === 'true' ) {
					jQuery( form ).data('referral_id', response.referral_id ); // Give access to the referral id for the user
					jQuery( form ).trigger('tdr_promo_success');
				}
			}
		}).error( function(error) {
			jQuery( error_container ).show();
		});
		event.preventDefault();
		return false;
	});
});
/* CONTESTS: USER SOCIAL MEDIA ACCOUNT BINDING TO USER PROFILE
 * ***************************************************************************/
jQuery( document ).ready (function() {
	jQuery ( '.tdr_bind_social_media_accounts' ).parents('form').on( 'submit', function( event ) {
		// Define submit button
		submit_button = jQuery( this ).find('.tdr_bind_social_media_accounts');
		// Define the form
		form = this;
		// Define status containers
		error_container = jQuery( form ).siblings( '.tdr_bind_social_media_error' );
        validation_error_container = jQuery( form ).siblings( '.tdr_bind_social_media_validation_error' );
		success_container = jQuery( form ).siblings( '.tdr_bind_social_media_success' );
		// Toggle ajax-in-working-state class
		jQuery( form ).find( '.tdr_bind_social_media_working' ).ajaxStart( function() {
			jQuery( this ).show();
		}).ajaxStop( function() {
			jQuery( this ).hide();
		});
		jQuery( submit_button ).ajaxStart( function() {
			jQuery( this ).hide();
		}).ajaxStop( function() {
			jQuery( this ).show();
		});
		// Hide status on each submission
		jQuery( error_container).add( validation_error_container ).add( success_container ).hide();
		// Get user information from form
		var user_information = {};
		jQuery( form ).find( 'input' ).each(function() {
            //Remove any error styling
            jQuery(this).parents( '.control-group' ).removeClass( 'error' );
            // Get key and value
			var key = jQuery(this).attr( 'name' );
			var value = jQuery(this).val();
            // Encode value
			value = encodeURIComponent(value);
            // Set key and value
			user_information[key] = value;
		});
		var request_details = {
			promotion_name: jQuery( form ).attr('data-tdr-promotion-name'),
			referrer_id: jQuery( form ).attr('data-tdr-promotion-referrer')
		}
		var post_data = {
			request_details: request_details,
			user_information: user_information
		}
		var data = {
			action: 'tdr_bind_social_media_accounts',
			data: JSON.stringify(post_data)
		};
		jQuery.post(ajaxurl, data, function(response) {
			response = jQuery.parseJSON(response);
			if ( response.error ) {
				jQuery( error_container ).show();
			}
			else if ( response.invalid.length > 0 ) {
                jQuery( validation_error_container ).show();
                for ( fieldName in response.invalid ) {
					jQuery( form ).find('input[name|="' + response.invalid[fieldName]  + '"]').parents('.control-group').addClass( 'error' );
                }				
			}
			else if ( response.message ) {
				jQuery( success_container ).show().delay(10000).fadeOut('slow');
				jQuery( form ).trigger('tdr_bind_social_media_success');
			}
		}).error( function(error) {
			jQuery( error_container ).show();
		});
		event.preventDefault();
		return false;
	});
});

/* HELLO BAR AND GOODBYE BAR DISMISS HANDLERS
 * ***************************************************************************/
 jQuery( document ).ready( function() {
	jQuery( '[data-dismiss="hello"]' ).on( 'click', function() {
		jQuery( this ).parents('.hello').slideUp('300');
	});
	jQuery( '[data-dismiss="goodbye"]' ).on( 'click', function() {
		jQuery( this ).parents('.goodbye').slideUp('300');
	});
});

/* OUR TOP CHOICES WIDGET 
 * ***************************************************************************/
jQuery( document ).ready( function() {
	jQuery( '.our_top_menu_item' ).on( 'click', function() {
		// Get the ID of this 
		var offerCategory = jQuery( this ).attr('id');

		// Edit the Menu
		jQuery( this ).children().attr( 'id', 'selected' );
		jQuery( this ).siblings().children().attr( 'id', '' );

		// Animate out the section
		jQuery( '.our_top_choices_section' ).animate( { opacity: 0 }, 'fast');

		// set up the data for AJAX call
		var data = {
			action: 'tdr_top_choices_widget_ajax',
			offer_cat_id: offerCategory
		};

		// Make the AJAX call
		jQuery.post( ajaxurl, data, function( response ) {
			// Display the response in the section and animate in
			jQuery( '.our_top_choices_section' ).html( response ).animate( { opacity: 1 }, 'fast');
		});
	});
});

jQuery( document ).ready( function() {
	// Jump Page Controller
	jQuery(document).ready( function() {
		var windowCount = 1;
		jQuery( document ).on('click', '.our_top_visit a', function( event ) {
			
			// Create Jump Page and Open it
			var url = jQuery( this ).attr('href');
			var windowName = "popUp" + windowCount;
			var top = (screen.height/2)-(800/2); // offset by window height/2
			var left = (screen.width/2)-(1000/2); // offset by window width/2
			var windowSize = "width=1000,height=800,resizable=yes,location=yes,top=" + top + ",left=" + left;
			window.open( url, windowName, windowSize );
			windowCount++;
			
			// Prevent Default Action
			event.preventDefault();
			return false;
		});	
	});
});
