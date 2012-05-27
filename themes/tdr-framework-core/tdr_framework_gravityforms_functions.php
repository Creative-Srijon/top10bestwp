<?php
if(!is_admin()) {
	/**
	 * Adjusting the HTML of the submit button to match bootstrap design
	 *
	 *
	 * @param $button string  required  The text string of the button we're editing
	 * @param $form   array   required  The whole form object
	 *
	 * @return string The new HTML for the button
	 */
	add_filter( 'gform_submit_button', 'theme_t_wp_submit_button', 10, 2 );
	function theme_t_wp_submit_button( $button, $form ){
		if ( $form['cssClass'] = 'bigsubmit' ) {
			$optional_class = " span5";
		}
		else {
			$optional_class = "";
		}
		return '<input type="submit" id="gform_submit_button_'.$form["id"].'" value="'. $form["button"]["text"] .'" class="gform_button btn btn-primary ' . $optional_class . '" />';
	}
	
	/**
	 * Adjusting the HTML of the submit button to match bootstrap design
	 *
	 *
	 * @param	$classes	string	required	The text string of the classes we're editing
	 * @param	$field		array	required	The whole field object
	 * @param	$form		array	required	The whole form object
	 *
	 * @return string The new css for the parent list item element for the form field
	 */
	add_action("gform_field_css_class", "custom_class", 10, 3);
	function custom_class($classes, $field, $form){
	if( $field["cssClass"] != "" ){
		if( strpos($field["cssClass"], "tdr-inline") != false ) {
			$classes = str_replace($field["cssClass"], "tdr-inline", $classes);
		}
		else {
			$classes = str_replace($field["cssClass"], "", $classes);
		}
	}
	return $classes;
}
	/**
	 * Adjusting the HTML of the text input and textarea fields to match bootstrap design
	 *
	 *
	 * @param $field_content array  required  The whole content for the form field
	 * @param $field   array   required  The whole field object
	 * @param $value	array	required The default/initial value that the field should be pre-populated with
	 * @param $lead_id int required When executed from the entry detail screen, $lead_id will be populated with the Entry ID. Otherwise, it will be 0.
	 * @param $form_id int required The current Form ID
	 *
	 * @return string The new HTML for the text input/textarea field
	 */

	add_filter( 'gform_field_content', 'theme_t_wp_text_input', 10, 5 );
	function theme_t_wp_text_input( $field_content, $field, $value, $lead_id, $form_id ){
		if ( $field["cssClass"] != "" ) {
			$custom_classes = " " . $field['cssClass'];
		}
        else $custom_classes = " span3";
		switch ( $field["type"] ) {
		case "text": case "number": {
			if ( $field["isRequired"] == 1 ) {
				$required = '<span class="gfield_required">*</span>';
			}
			else $required = '';
			$field_content = '<label class="gfield_label" for="input_'.$form_id.'_'.$field["id"].'">'.$field["label"].$required.'</label><div class="ginput_container"><input id="input_'.$form_id.'_'.$field["id"].'" class="'.$field["size"]. $custom_classes . '" placeholder="'.$field["defaultValue"].'" type="'.$field["type"].'" tabindex="" maxlength="'.$field["maxLength"].'" name="input_'.$field["id"].'"><span class="help-inline gfield_description">'.$field["description"].'</span></div>';
			return $field_content;
		}
		case "name": {
            if ( strstr( $field["cssClass"], "last-initial" ) != -1 ) {
                $last_text = "Last Initial";
                $last_length = 1;
            }
            else {
                $last_text = $field["inputs"][1]["label"];
                $last_length = $field["maxLength"];
            }
            if ( $field["isRequired"] == 1 ) {
				$required = '<span class="gfield_required">*</span>';
			}
			else $required = '';
			$name1 = $field["inputs"][0]["id"];
			$name2 = $field["inputs"][1]["id"];
			$id1 = str_replace(".","_", $name1);
			$id2 = str_replace(".","_", $name2);
			$field_content = '<label class="gfield_label" for="input_'.$form_id.'_'.$id1.'">'.$field["label"].$required.'</label><div id="input_'.$form_id.'_'.$field["id"].'" class="ginput_complex ginput_container"><span id="input_'.$form_id.'_'.$id1.'_container" class="ginput_left"><input id="input_'.$form_id.'_'.$id1.'" class="'.$field["size"]. $custom_classes . ' span3" placeholder="'.$field["inputs"][0]["label"].'" type="text" tabindex="" maxlength="'.$field["maxLength"].'" name="input_'.$name1.'"></span> <span id="input_'.$form_id.'_'.$id2.'_container" class="ginput_right"><input id="input_'.$form_id.'_'.$id2.'" class="'.$field["size"]. $custom_classes . ' span2" placeholder="'.$last_text.'" type="text" tabindex="" maxlength="'.$last_length.'" name="input_'.$name2.'"></span></div>';
			return $field_content;		
		}
		case "date": {
			if ( $field["isRequired"] == 1 ) {
				$required = '<span class="gfield_required">*</span>';
			}
			else $required = '';
			$field_content = '<label class="gfield_label" for="input_'.$form_id.'_'.$field["id"].'">'.$field["label"].$required.'</label><div class="ginput_container"><input id="input_'.$form_id.'_'.$field["id"].'" class="datepicker mdy datepicker_with_icon '.$field["size"]. $custom_classes . '" placeholder="'.$field["defaultValue"].'" type="text" tabindex="" maxlength="'.$field["maxLength"].'" name="input_'.$field["id"].'"> <input id="gforms_calendar_icon_input_'.$form_id.'_'.$field["id"].'" class="gform_hidden" type="hidden" value="'.plugins_url().'/gravityforms/images/calendar.png"><span class="help-inline gfield_description">'.$field["description"].'</span></div>';
			return $field_content;		
		}
		case "time": {
			if ( $field["isRequired"] == 1 ) {
				$required = '<span class="gfield_required">*</span>';
			}
			else $required = '';
			$field_content = '<label class="gfield_label" for="input_'.$form_id.'_'.$field["id"].'">'.$field["label"].$required.'</label><div class="clear-multi"><span id="input_'.$form_id.'_'.$field["id"].'" class="gfield_time_hour ginput_container"><input id="input_'.$form_id.'_'.$field["id"].'_1" class="'.$field["size"]. $custom_classes . ' span1" placeholder="HH" type="text" tabindex="" maxlength="2" name="input_'.$field["id"].'[]"></span> <span class="gfield_time_minute ginput_container"><input id="input_'.$form_id.'_'.$field["id"].'_2" class="'.$field["size"]. $custom_classes . ' span1" placeholder="MM" type="text" tabindex="" maxlength="2" name="input_'.$field["id"].'[]"></span> <span class="gfield_time_ampm ginput_container"><select id="input_'.$form_id.'_'.$field["id"].'_3" class="span1'. $custom_classes . '" tabindex="" name="input_'.$field["id"].'[]"><option selected="selected" value="am">AM</option><option value="pm">PM</option></select></span></div>';
			return $field_content;	
		}
		case "address": {
			if ( $field["isRequired"] == 1 ) {
				$required = '<span class="gfield_required">*</span>';
			}
			else $required = '';
			$name1 = $field["inputs"][0]["id"];
			$name2 = $field["inputs"][1]["id"];
			$name3 = $field["inputs"][2]["id"];
			$name4 = $field["inputs"][3]["id"];
			$name5 = $field["inputs"][4]["id"];
			$name6 = $field["inputs"][5]["id"];
			$id1 = str_replace(".","_", $name1);
			$id2 = str_replace(".","_", $name2);
			$id3 = str_replace(".","_", $name3);
			$id4 = str_replace(".","_", $name4);
			$id5 = str_replace(".","_", $name5);
			$id6 = str_replace(".","_", $name6);
			$field_content = '<label class="gfield_label" for="input_'.$form_id.'_'.$id1.'">'.$field["label"].$required.'</label><div id="input_'.$form_id.'_'.$field["id"].'" class="ginput_complex ginput_container"><span id="input_'.$form_id.'_'.$id1.'_container" class="ginput_full"><input id="input_'.$form_id.'_'.$id1.'" class="'.$field["size"]. $custom_classes . '" placeholder="'.$field["inputs"][0]["label"].'" type="text" tabindex="" maxlength="'.$field["maxLength"].'" name="input_'.$name1.'"></span> <div id="input_'.$form_id.'_'.$id2.'_container" class="ginput_full"><input id="input_'.$form_id.'_'.$id2.'" class="'.$field["size"]. $custom_classes . '" placeholder="'.$field["inputs"][1]["label"].'" type="text" tabindex="" maxlength="'.$field["maxLength"].'" name="input_'.$name2.'"></div><span id="input_'.$form_id.'_'.$id3.'_container" class="ginput_left"><input id="input_'.$form_id.'_'.$id3.'" class="'.$field["size"]. $custom_classes . ' span2" placeholder="'.$field["inputs"][2]["label"].'" type="text" tabindex="" maxlength="'.$field["maxLength"].'" name="input_'.$name3.'"></span> <span id="input_'.$form_id.'_'.$id4.'_container" class="ginput_center"><input id="input_'.$form_id.'_'.$id4.'" class="'.$field["size"]. $custom_classes . ' span1" placeholder="'.$field["inputs"][3]["label"].'" type="text" tabindex="" maxlength="'.$field["maxLength"].'" name="input_'.$name4.'"></span> <span id="input_'.$form_id.'_'.$id5.'_container" class="ginput_right"><input id="input_'.$form_id.'_'.$id5.'" class="'.$field["size"]. $custom_classes . ' span1" placeholder="'.$field["inputs"][4]["label"].'" type="text" tabindex="" maxlength="'.$field["maxLength"].'" name="input_'.$name5.'"></span><div id="input_'.$form_id.'_'.$id6.'_container" class="ginput_full"><select id="input_'.$form_id.'_'.$id6.'" tabindex="" name="input_'.$name6.'" class="'. $custom_classes . '"><option selected="selected" value=""></option> <option value="Afghanistan">Afghanistan</option> <option value="Albania">Albania</option> <option value="Algeria">Algeria</option> <option value="American Samoa">American Samoa</option> <option value="Andorra">Andorra</option> <option value="Angola">Angola</option> <option value="Antigua and Barbuda">Antigua and Barbuda</option> <option value="Argentina">Argentina</option> <option value="Armenia">Armenia</option> <option value="Australia">Australia</option> <option value="Austria">Austria</option> <option value="Azerbaijan">Azerbaijan</option> <option value="Bahamas">Bahamas</option> <option value="Bahrain">Bahrain</option> <option value="Bangladesh">Bangladesh</option> <option value="Barbados">Barbados</option> <option value="Belarus">Belarus</option> <option value="Belgium">Belgium</option> <option value="Belize">Belize</option> <option value="Benin">Benin</option> <option value="Bermuda">Bermuda</option> <option value="Bhutan">Bhutan</option> <option value="Bolivia">Bolivia</option> <option value="Bosnia and Herzegovina">Bosnia and Herzegovina</option> <option value="Botswana">Botswana</option> <option value="Brazil">Brazil</option> <option value="Brunei">Brunei</option> <option value="Bulgaria">Bulgaria</option> <option value="Burkina Faso">Burkina Faso</option> <option value="Burundi">Burundi</option> <option value="Cambodia">Cambodia</option> <option value="Cameroon">Cameroon</option> <option value="Canada">Canada</option> <option value="Cape Verde">Cape Verde</option> <option value="Central African Republic">Central African Republic</option> <option value="Chad">Chad</option> <option value="Chile">Chile</option> <option value="China">China</option> <option value="Colombia">Colombia</option> <option value="Comoros">Comoros</option> <option value="Congo, Democratic Republic of the">Congo, Democratic Republic of the</option> <option value="Congo, Republic of the">Congo, Republic of the</option> <option value="Costa Rica">Costa Rica</option> <option value="C?te d\'Ivoire">C&#244;te d\'Ivoire</option> <option value="Croatia">Croatia</option> <option value="Cuba">Cuba</option> <option value="Cyprus">Cyprus</option> <option value="Czech Republic">Czech Republic</option> <option value="Denmark">Denmark</option> <option value="Djibouti">Djibouti</option> <option value="Dominica">Dominica</option> <option value="Dominican Republic">Dominican Republic</option> <option value="East Timor">East Timor</option> <option value="Ecuador">Ecuador</option> <option value="Egypt">Egypt</option> <option value="El Salvador">El Salvador</option> <option value="Equatorial Guinea">Equatorial Guinea</option> <option value="Eritrea">Eritrea</option> <option value="Estonia">Estonia</option> <option value="Ethiopia">Ethiopia</option> <option value="Fiji">Fiji</option> <option value="Finland">Finland</option> <option value="France">France</option> <option value="Gabon">Gabon</option> <option value="Gambia">Gambia</option> <option value="Georgia">Georgia</option> <option value="Germany">Germany</option> <option value="Ghana">Ghana</option> <option value="Greece">Greece</option> <option value="Greenland">Greenland</option> <option value="Grenada">Grenada</option> <option value="Guam">Guam</option> <option value="Guatemala">Guatemala</option> <option value="Guinea">Guinea</option> <option value="Guinea-Bissau">Guinea-Bissau</option> <option value="Guyana">Guyana</option> <option value="Haiti">Haiti</option> <option value="Honduras">Honduras</option> <option value="Hong Kong">Hong Kong</option> <option value="Hungary">Hungary</option> <option value="Iceland">Iceland</option> <option value="India">India</option> <option value="Indonesia">Indonesia</option> <option value="Iran">Iran</option> <option value="Iraq">Iraq</option> <option value="Ireland">Ireland</option> <option value="Israel">Israel</option> <option value="Italy">Italy</option> <option value="Jamaica">Jamaica</option> <option value="Japan">Japan</option> <option value="Jordan">Jordan</option> <option value="Kazakhstan">Kazakhstan</option> <option value="Kenya">Kenya</option> <option value="Kiribati">Kiribati</option> <option value="North Korea">North Korea</option> <option value="South Korea">South Korea</option> <option value="Kuwait">Kuwait</option> <option value="Kyrgyzstan">Kyrgyzstan</option> <option value="Laos">Laos</option> <option value="Latvia">Latvia</option> <option value="Lebanon">Lebanon</option> <option value="Lesotho">Lesotho</option> <option value="Liberia">Liberia</option> <option value="Libya">Libya</option> <option value="Liechtenstein">Liechtenstein</option> <option value="Lithuania">Lithuania</option> <option value="Luxembourg">Luxembourg</option> <option value="Macedonia">Macedonia</option> <option value="Madagascar">Madagascar</option> <option value="Malawi">Malawi</option> <option value="Malaysia">Malaysia</option> <option value="Maldives">Maldives</option> <option value="Mali">Mali</option> <option value="Malta">Malta</option> <option value="Marshall Islands">Marshall Islands</option> <option value="Mauritania">Mauritania</option> <option value="Mauritius">Mauritius</option> <option value="Mexico">Mexico</option> <option value="Micronesia">Micronesia</option> <option value="Moldova">Moldova</option> <option value="Monaco">Monaco</option> <option value="Mongolia">Mongolia</option> <option value="Montenegro">Montenegro</option> <option value="Morocco">Morocco</option> <option value="Mozambique">Mozambique</option> <option value="Myanmar">Myanmar</option> <option value="Namibia">Namibia</option> <option value="Nauru">Nauru</option> <option value="Nepal">Nepal</option> <option value="Netherlands">Netherlands</option> <option value="New Zealand">New Zealand</option> <option value="Nicaragua">Nicaragua</option> <option value="Niger">Niger</option> <option value="Nigeria">Nigeria</option> <option value="Norway">Norway</option> <option value="Northern Mariana Islands">Northern Mariana Islands</option> <option value="Oman">Oman</option> <option value="Pakistan">Pakistan</option> <option value="Palau">Palau</option> <option value="Palestine">Palestine</option> <option value="Panama">Panama</option> <option value="Papua New Guinea">Papua New Guinea</option> <option value="Paraguay">Paraguay</option> <option value="Peru">Peru</option> <option value="Philippines">Philippines</option> <option value="Poland">Poland</option> <option value="Portugal">Portugal</option> <option value="Puerto Rico">Puerto Rico</option> <option value="Qatar">Qatar</option> <option value="Romania">Romania</option> <option value="Russia">Russia</option> <option value="Rwanda">Rwanda</option> <option value="Saint Kitts and Nevis">Saint Kitts and Nevis</option> <option value="Saint Lucia">Saint Lucia</option> <option value="Saint Vincent and the Grenadines">Saint Vincent and the Grenadines</option> <option value="Samoa">Samoa</option> <option value="San Marino">San Marino</option> <option value="Sao Tome and Principe">Sao Tome and Principe</option> <option value="Saudi Arabia">Saudi Arabia</option> <option value="Senegal">Senegal</option> <option value="Serbia and Montenegro">Serbia and Montenegro</option> <option value="Seychelles">Seychelles</option> <option value="Sierra Leone">Sierra Leone</option> <option value="Singapore">Singapore</option> <option value="Slovakia">Slovakia</option> <option value="Slovenia">Slovenia</option> <option value="Solomon Islands">Solomon Islands</option> <option value="Somalia">Somalia</option> <option value="South Africa">South Africa</option> <option value="Spain">Spain</option> <option value="Sri Lanka">Sri Lanka</option> <option value="Sudan">Sudan</option> <option value="Sudan, South">Sudan, South</option> <option value="Suriname">Suriname</option> <option value="Swaziland">Swaziland</option> <option value="Sweden">Sweden</option> <option value="Switzerland">Switzerland</option> <option value="Syria">Syria</option> <option value="Taiwan">Taiwan</option> <option value="Tajikistan">Tajikistan</option> <option value="Tanzania">Tanzania</option> <option value="Thailand">Thailand</option> <option value="Togo">Togo</option> <option value="Tonga">Tonga</option> <option value="Trinidad and Tobago">Trinidad and Tobago</option> <option value="Tunisia">Tunisia</option> <option value="Turkey">Turkey</option> <option value="Turkmenistan">Turkmenistan</option> <option value="Tuvalu">Tuvalu</option> <option value="Uganda">Uganda</option> <option value="Ukraine">Ukraine</option> <option value="United Arab Emirates">United Arab Emirates</option> <option value="United Kingdom">United Kingdom</option> <option value="United States">United States</option> <option value="Uruguay">Uruguay</option> <option value="Uzbekistan">Uzbekistan</option> <option value="Vanuatu">Vanuatu</option> <option value="Vatican City">Vatican City</option> <option value="Venezuela">Venezuela</option> <option value="Vietnam">Vietnam</option> <option value="Virgin Islands, British">Virgin Islands, British</option> <option value="Virgin Islands, U.S.">Virgin Islands, U.S.</option> <option value="Yemen">Yemen</option> <option value="Zambia">Zambia</option> <option value="Zimbabwe">Zimbabwe</option></select><span class="help-inline gfield_description">'.$field["inputs"][5]["label"].'</span></div></div>';
			return $field_content;			
		}
		case "phone": {
			if ( $field["isRequired"] == 1 ) {
				$required = '<span class="gfield_required">*</span>';
			}
			else $required = '';
			$field_content = '<label class="gfield_label" for="input_'.$form_id.'_'.$field["id"].'">'.$field["label"].$required.'</label><div class="ginput_container"><input id="input_'.$form_id.'_'.$field["id"].'" class="'.$field["size"]. $custom_classes . '" placeholder="'.$field["defaultValue"].'" type="tel" tabindex="" maxlength="'.$field["maxLength"].'" name="input_'.$field["id"].'"><span class="help-inline gfield_description">'.$field["description"].'</span></div>';
			return $field_content;		
		}
		case "website": {
			if ( $field["isRequired"] == 1 ) {
				$required = '<span class="gfield_required">*</span>';
			}
			else $required = '';
			$field_content = '<label class="gfield_label" for="input_'.$form_id.'_'.$field["id"].'">'.$field["label"].$required.'</label><div class="ginput_container"><input id="input_'.$form_id.'_'.$field["id"].'" class="'.$field["size"]. $custom_classes . '" placeholder="http://" type="url" tabindex="" maxlength="'.$field["maxLength"].'" name="input_'.$field["id"].'"><span class="help-inline gfield_description">'.$field["description"].'</span></div>';
			return $field_content;		
		}
		case "email": {
			if ( $field["isRequired"] == 1 ) {
				$required = '<span class="gfield_required">*</span>';
			}
			else $required = '';
			$field_content = '<label class="gfield_label" for="input_'.$form_id.'_'.$field["id"].'">'.$field["label"].$required.'</label><div class="ginput_container"><input id="input_'.$form_id.'_'.$field["id"].'" class="'.$field["size"]. $custom_classes . '" placeholder="'.$field["defaultValue"].'" type="'.$field["type"].'" tabindex="" maxlength="'.$field["maxLength"].'" name="input_'.$field["id"].'"><span class="help-inline gfield_description">'.$field["description"].'</span></div>';
			return $field_content;		
		}
		case "textarea": {
			if ( $field["isRequired"] == 1 ) {
				$required = '<span class="gfield_required">*</span>';
			}
			else $required = '';
			$field_content = '<label class="gfield_label" for="input_'.$form_id.'_'.$field["id"].'">'.$field["label"].$required.'</label><div class="ginput_container"><textarea id="input_'.$form_id.'_'.$field["id"].'" class="textarea '.$field["size"]. $custom_classes . '" placeholder="'.$field["defaultValue"].'" tabindex="" name="input_'.$field["id"].'"></textarea><span class="help-inline gfield_description">'.$field["description"].'</span></div>';
			return $field_content;	
		}
		case "checkbox": {
			if ( $field["isRequired"] == 1 ) {
				$required = '<span class="gfield_required">*</span>';
			}
			else $required = '';
			$field_content = '<label class="gfield_label">'.$field["label"].$required.'</label><div class="ginput_container"><ul id="input_'.$form_id.'_'.$field["id"].'" class="gfield_checkbox">';
			foreach ( $field["choices"] as $choice_id => $choice_info ) {
				$field_content .= '<li class="gchoice_'.$field["id"].'_'.($choice_id+1).'"><label class="gfield_label ginput_container checkbox" for="choice_'.$field["id"].'_'.($choice_id+1).'"><input id="choice_'.$field["id"].'_'.($choice_id+1).'" value="'.$choice_info["value"].'" class="'.$field["size"]. $custom_classes . '" type="'.$field["type"].'" tabindex="" name="input_'.$field["id"].'.'.($choice_id+1).'"> '.$choice_info["text"].'</label></li>';
			}
			$field_content .= '</ul></div>';
			return $field_content;	
		}
		case "select": {
			if ( $field["isRequired"] == 1 ) {
				$required = '<span class="gfield_required">*</span>';
			}
			else $required = '';
			$field_content = '<label class="gfield_label">'.$field["label"].$required.'</label><div class="ginput_container"><select id="input_'.$form_id.'_'.$field["id"].'" class="gfield_select '.$field["size"]. $custom_classes .'" tabindex="" name="input_' . $field["id"] . '">';
			foreach ( $field["choices"] as $choice_id => $choice_info ) {
				if ( $choice_info["isSelected"] == true ) {
					$selected = ' selected="selected"';
				}
				else {
					$selected = "";
				}
				$field_content .= '<option value="'.$choice_info["value"].'"' . $selected . '> '.$choice_info["text"].'</option>';
			}
			$field_content .= '</select></div>';
			return $field_content;	
		}
		case "radio": {
			if ( $field["isRequired"] == 1 ) {
				$required = '<span class="gfield_required">*</span>';
			}
			else $required = '';
			$field_content = '<label class="gfield_label">'.$field["label"].$required.'</label><div class="ginput_container"><ul id="input_'.$form_id.'_'.$field["id"].'" class="gfield_radio">';
			foreach ( $field["choices"] as $choice_id => $choice_info ) {
				$field_content .= '<li class="gchoice_'.$field["id"].'_'.($choice_id).'"><label class="gfield_label ginput_container radio" for="choice_'.$field["id"].'_'.($choice_id).'"><input id="choice_'.$field["id"].'_'.($choice_id).'" value="'.$choice_info["value"].'" class="'.$field["size"]. $custom_classes . '" type="'.$field["type"].'" tabindex="" name="input_'.$field["id"].'"> '.$choice_info["text"].'</label></li>';
			}
			$field_content .= '</ul></div>';
			return $field_content;	
		}
		default: return $field_content;
		}
	}
}
?>
