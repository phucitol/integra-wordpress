<?php

function aasgnn_first_last( $author_id ) {
	$user_info = get_userdata( $author_id );

	// return the name
	return $user_info->first_name . " " . $user_info->last_name;
}

function aasgnn_image( $filename, $ext ) {
    $image = "";

    // Path to the "override" folder in the active theme
    $theme_path = get_template_directory() . '/aa-sign-in-img/';

    $files = glob( $theme_path . $filename . ".*" );

    if ( $files ) {
        $file_info = pathinfo( $files[0] );
        $image = get_template_directory_uri()  . '/aa-sign-in-img/' . $file_info['filename'] . '.' . $file_info['extension'];
    } else {
        // Path to the "img" folder in the current plugin
        $image = SIGN_IN_PLUGIN_URL . 'img/' . $filename . '.' . $ext;
    }

   return $image;
}

function aasgnn_utc_from_local( $start_time, $local_time ) {
    
    $local_timestamp = strtotime( $local_time );
    $start_timestamp = strtotime( $start_time );
    $utc_timestamp = time();
    $diff = $utc_timestamp - $local_timestamp;
    $diff -=  $diff % 60;
    $start_time_utc = date( 'Y-m-d H:i:s', $start_timestamp + $diff );
    
    return $start_time_utc;
}

function sortable_link( $orderby ) {
	$parameters = array();
	$query = "";

	if ( !empty( $_GET['s'] ) ) {
		$parameters['s'] = $_GET['s'];
	}
	
	if ( !empty( $_GET['page'] ) ) {
		$parameters['page'] = $_GET['page'];
	}

	$parameters['orderby'] = $orderby;
	
	if ( !empty( $_GET['orderby'] ) && $_GET['orderby'] === $orderby ) {
		if ( !empty( $_GET['sort'] ) && $_GET['sort'] == 'ASC' ) {
			$parameters['sort'] = 'DESC';
		} else {
			$parameters['sort'] = 'ASC';
		}
	} else {
		$parameters['sort'] = 'ASC';
	}

	if ( !empty( $parameters ) ) {
		$query = "?";
		$count = 0;
		foreach ( $parameters as $key => $value ) {
			$query .= $key . "=" . $value;
			if ( ++$count < count( $parameters ) ) {
				$query .= "&";
			}
		}
	}

	return get_site_url() . "/" . $query;
}

function aasgnnInsertAfterKey($array, $key, $newElementKey, $newElementValue) {
    $newArray = [];
    foreach ($array as $k => $v) {
        $newArray[$k] = $v;
        if ($k === $key) {
            $newArray[$newElementKey] = $newElementValue;
        }
    }
    return $newArray;
}

function work_country_code_options( $key, $parameters ) {

    $countries = array(
        "Select your country" => "",
        "USA" => "US",
        "Canada" => "CA",
        "Afghanistan" => "AF",
        "Åland Islands" => "AX",
        "Albania" => "AL",
        "Algeria" => "DZ",
        "American Samoa" => "AS",
        "Andorra" => "AD",
        "Angola" => "AO",
        "Anguilla" => "AI",
        "Antarctica" => "AQ",
        "Antigua and Barbuda" => "AG",
        "Argentina" => "AR",
        "Armenia" => "AM",
        "Aruba" => "AW",
        "Australia" => "AU",
        "Austria" => "AT",
        "Azerbaijan" => "AZ",
        "Bahamas" => "BS",
        "Bahrain" => "BH",
        "Bangladesh" => "BD",
        "Barbados" => "BB",
        "Belarus" => "BY",
        "Belgium" => "BE",
        "Belize" => "BZ",
        "Benin" => "BJ",
        "Bermuda" => "BM",
        "Bhutan" => "BT",
        "Bolivia" => "BO",
        "Bonaire, Saint Eustatius and Saba" => "BQ",
        "Bosnia and Herzegovina" => "BA",
        "Botswana" => "BW",
        "Bouvet Island" => "BV",
        "Brazil" => "BR",
        "British Indian Ocean Terr." => "IO",
        "British Virgin Islands" => "VG",
        "Brunei Darussalam" => "BN",
        "Bulgaria" => "BG",
        "Burkina Faso" => "BF",
        "Burundi" => "BI",
        "Cambodia" => "KH",
        "Cameroon" => "CM",
        "Cape Verde" => "CV",
        "Cayman Islands" => "KY",
        "Central African Republic" => "CF",
        "Chad" => "TD",
        "Chile" => "CL",
        "China" => "CN",
        "Christmas Island" => "CX",
        "Cocos (Keeling) Islands" => "CC",
        "Colombia" => "CO",
        "Comoros" => "KM",
        "Congo (Democratic Republic)" => "CD",
        "Congo (Republic)" => "CG",
        "Cook Islands" => "CK",
        "Costa Rica" => "CR",
        "Cote d'Ivoire" => "CI",
        "Croatia" => "HR",
        "Cuba" => "CU",
        "Curaçao" => "CW",
        "Cyprus" => "CY",
        "Czech Republic" => "CZ",
        "Denmark" => "DK",
        "Djibouti" => "DJ",
        "Dominica" => "DM",
        "Dominican Republic" => "DO",
        "Ecuador" => "EC",
        "Egypt" => "EG",
        "El Salvador" => "SV",
        "England" => "GB1",
        "Equatorial Guinea" => "GQ",
        "Eritrea" => "ER",
        "Estonia" => "EE",
        "Ethiopia" => "ET",
        "Falkland Islands (Malvinas)" => "FK",
        "Faroe Islands" => "FO",
        "Fiji" => "FJ",
        "Finland" => "FI",
        "France" => "FR",
        "French Guiana" => "GF",
        "French Polynesia" => "PF",
        "French Southern Territories" => "TF",
        "Gabon" => "GA",
        "Gambia" => "GM",
        "Georgia" => "GE",
        "Germany" => "DE",
        "Ghana" => "GH",
        "Gibraltar" => "GI",
        "Greece" => "GR",
        "Greenland" => "GL",
        "Grenada" => "GD",
        "Guadeloupe" => "GP",
        "Guam" => "GU",
        "Guatemala" => "GT",
        "Guernsey" => "GG",
        "Guinea" => "GN",
        "Guinea-Bissau" => "GW",
        "Guyana" => "GY",
        "Haiti" => "HT",
        "Heard &amp; McDonald Islands" => "HM",
        "Honduras" => "HN",
        "Hong Kong (China)" => "HK",
        "Hungary" => "HU",
        "Iceland" => "IS",
        "India" => "IN",
        "Indonesia" => "ID",
        "Iran" => "IR",
        "Iraq" => "IQ",
        "Ireland" => "IE",
        "Isle of Man" => "IM",
        "Israel" => "IL",
        "Italy" => "IT",
        "Jamaica" => "JM",
        "Japan" => "JP",
        "Jersey" => "JE",
        "Jordan" => "JO",
        "Kazakhstan" => "KZ",
        "Kenya" => "KE",
        "Kiribati" => "KI",
        "Kosovo" => "KO",
        "Kuwait" => "KW",
        "Kyrgyzstan" => "KG",
        "Laos" => "LA",
        "Latvia" => "LV",
        "Lebanon" => "LB",
        "Lesotho" => "LS",
        "Liberia" => "LR",
        "Libya" => "LY",
        "Liechtenstein" => "LI",
        "Lithuania" => "LT",
        "Luxembourg" => "LU",
        "Macau (China)" => "MO",
        "Madagascar" => "MG",
        "Malawi" => "MW",
        "Malaysia" => "MY",
        "Maldives" => "MV",
        "Mali" => "ML",
        "Malta" => "MT",
        "Marshall Islands" => "MH",
        "Martinique" => "MQ",
        "Mauritania" => "MR",
        "Mauritius" => "MU",
        "Mayotte" => "YT",
        "Mexico" => "MX",
        "Micronesia" => "FM",
        "Moldova" => "MD",
        "Monaco" => "MC",
        "Mongolia" => "MN",
        "Montenegro" => "ME",
        "Montserrat" => "MS",
        "Morocco" => "MA",
        "Mozambique" => "MZ",
        "Myanmar" => "MM",
        "Namibia" => "NA",
        "Nauru" => "NR",
        "Nepal" => "NP",
        "Netherlands" => "NL",
        "Netherlands Antilles" => "AN",
        "New Caledonia" => "NC",
        "New Zealand" => "NZ",
        "Nicaragua" => "NI",
        "Niger" => "NE",
        "Nigeria" => "NG",
        "Niue" => "NU",
        "Norfolk Island" => "NF",
        "North Korea" => "KP",
        "Northern Ireland" => "GB4",
        "Northern Mariana Islands" => "MP",
        "Norway" => "NO",
        "Oman" => "OM",
        "Pakistan" => "PK",
        "Palau" => "PW",
        "Palestine, State of" => "PS",
        "Panama" => "PA",
        "Papua New Guinea" => "PG",
        "Paraguay" => "PY",
        "Peru" => "PE",
        "Philippines" => "PH",
        "Pitcairn" => "PN",
        "Poland" => "PL",
        "Portugal" => "PT",
        "Puerto Rico" => "PR",
        "Qatar" => "QA",
        "Republic of North Macedonia" => "MK",
        "Reunion" => "RE",
        "Romania" => "RO",
        "Russian Federation" => "RU",
        "Rwanda" => "RW",
        "Saint Barthélemy" => "BL",
        "Saint Helena" => "SH",
        "Saint Kitts And Nevis" => "KN",
        "Saint Lucia" => "LC",
        "Saint Martin" => "MF",
        "Saint Pierre and Miquelon" => "PM",
        "Saint Vincent" => "VC",
        "Samoa" => "WS",
        "San Marino" => "SM",
        "Sao Tome and Principe" => "ST",
        "Saudi Arabia" => "SA",
        "Scotland" => "GB2",
        "Senegal" => "SN",
        "Serbia" => "RS",
        "Seychelles" => "SC",
        "Sierra Leone" => "SL",
        "Singapore" => "SG",
        "Sint Maarten (Dutch part)" => "SX",
        "Slovakia" => "SK",
        "Slovenia" => "SI",
        "Solomon Islands" => "SB",
        "Somalia" => "SO",
        "South Africa" => "ZA",
        "South Georgia and the South Sandwich Islands" => "GS",
        "South Korea" => "KR",
        "South Sudan" => "SS",
        "Spain" => "ES",
        "Sri Lanka" => "LK",
        "Sudan" => "SD",
        "Suriname" => "SR",
        "Svalbard And Jan Mayen" => "SJ",
        "Swaziland" => "SZ",
        "Sweden" => "SE",
        "Switzerland" => "CH",
        "Syria" => "SY",
        "Taiwan (Republic of China)" => "TW",
        "Tajikistan" => "TJ",
        "Tanzania" => "TZ",
        "Thailand" => "TH",
        "Timor-Leste" => "TL",
        "Togo" => "TG",
        "Tokelau" => "TK",
        "Tonga" => "TO",
        "Trinidad and Tobago" => "TT",
        "Tunisia" => "TN",
        "Turkey" => "TR",
        "Turkmenistan" => "TM",
        "Turks and Caicos Islands" => "TC",
        "Tuvalu" => "TV",
        "U.S. Virgin Islands" => "VI",
        "Uganda" => "UG",
        "Ukraine" => "UA",
        "United Arab Emirates" => "AE",
        "United Kingdom" => "GB",
        "United States Minor Outlying Islands" => "UM",
        "Uruguay" => "UY",
        "Uzbekistan" => "UZ",
        "Vanuatu" => "VU",
        "Vatican City State (Holy See)" => "VA",
        "Venezuela" => "VE",
        "Vietnam" => "VN",
        "Wales" => "GB3",
        "Wallis and Futuna" => "WF",
        "Western Sahara" => "EH",
        "Yemen" => "YE",
        "Zambia" => "ZM",
        "Zimbabwe" => "ZW" 
    );
    
    $html = "";

    foreach ( $countries as $name => $code ) {
        if ( !empty( $_POST[$key] ) ) {
            $selected = ( $name === $_POST[$key] ) ? ' selected' : '';
        } else if ( $name === "Select your country" ) {
            $selected = ' selected';
        } else {
            $selected = '';
        }
        $disabled = ( empty( $code ) ) ? ' disabled' : '';

        $html .= "<option value='{$code}'{$selected}{$disabled}>{$name}</option>";
    }

    return $html;
}

function signature_options( $key, $parameters, $selections ) {
    $html = "";

    $html .= "<input type='checkbox' name='{$key}[]' value='true'> I understand that checking this box constitutes a legal signature confirming that I am <span id='print_signature'>__________________</span>.";

    return $html;
}
