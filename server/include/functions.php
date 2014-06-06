<?php
function getUserIP() {
    $ipaddress = '';
    if (getenv('HTTP_CLIENT_IP')) $ipaddress = getenv('HTTP_CLIENT_IP');
    else if (getenv('HTTP_X_FORWARDED_FOR')) $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
    else if (getenv('HTTP_X_FORWARDED')) $ipaddress = getenv('HTTP_X_FORWARDED');
    else if (getenv('HTTP_FORWARDED_FOR')) $ipaddress = getenv('HTTP_FORWARDED_FOR');
    else if (getenv('HTTP_FORWARDED')) $ipaddress = getenv('HTTP_FORWARDED');
    else if (getenv('REMOTE_ADDR')) $ipaddress = getenv('REMOTE_ADDR');
    else $ipaddress = 'UNKNOWN';
    
    return $ipaddress;
}
$points = array();
function check_points_recursive($array) {
    global $points;
    
    foreach ($array as $value) {
        if (is_array($value)) {
            if (array_key_exists("coord", $value) && floatval($value['radius']) > 0 && is_array($value['coord'])) {
                $value['coord']['weight'] = 1 / floatval($value['radius']);
                $points[] = $value['coord'];
            } else {
                check_points_recursive($value);
            }
        }
    }
}

function array_filter_recursive($input) {
    foreach ($input as & $value) {
        if (is_array($value)) {
            $value = array_filter_recursive($value);
        }
    }
    
    return array_filter($input);
}

function getMidPoint($input) {
    
    global $points;
    
    check_points_recursive($input);
    
    $combined['x'] = 0;
    $combined['y'] = 0;
    $combined['z'] = 0;
    $totweight = 0;
    
    foreach ($points as & $point) {
        
        /* convert to radians */
        $point['latitude'] = $point['latitude'] * (M_PI / 180.0);
        $point['longitude'] = $point['longitude'] * (M_PI / 180.0);
        
        $point['X'] = cos($point['latitude']) * cos($point['longitude']);
        $point['Y'] = cos($point['latitude']) * sin($point['longitude']);
        $point['Z'] = sin($point['latitude']);
        
        $weight = 1;
         //floatval($point['weight']);
        $totweight+= $weight;
        
        $combined['x']+= $point['X'] * $weight;
        $combined['y']+= $point['Y'] * $weight;
        $combined['z']+= $point['Z'] * $weight;
    }
    if ($totweight == 0) return;
    
    $combined['x'] = $combined['x'] / $totweight;
    $combined['y'] = $combined['y'] / $totweight;
    $combined['z'] = $combined['z'] / $totweight;
    
    $lon = atan2($combined['y'], $combined['x']);
    $hyp = sqrt($combined['x'] * $combined['x'] + $combined['y'] * $combined['y']);
    $lat = atan2($combined['z'], $hyp);
    
    /* convert back to degrees */
    $lat = $lat * 180.0 / M_PI;
    $lon = $lon * 180.0 / M_PI;
    
    $midpoint['latitude'] = $lat;
    $midpoint['longitude'] = $lon;
    
    return $midpoint;
}

function distanceCalculation($point1_lat, $point1_long, $point2_lat, $point2_long, $decimals = 2) {
    
    // Calculate the distance in degrees
    $degrees = rad2deg(acos((sin(deg2rad($point1_lat)) * sin(deg2rad($point2_lat))) + (cos(deg2rad($point1_lat)) * cos(deg2rad($point2_lat)) * cos(deg2rad($point1_long - $point2_long)))));
    
    // Convert the distance in degrees to kilometres
    // 1 degree = 111.13384 km, based on the average diameter of the Earth (12,735 km)
    $distance = $degrees * 111.13384;
    
    return round($distance, $decimals);
}

function calculateRadius($bounds) {
    $radius = distanceCalculation($bounds[0]['latitude'], $bounds[0]['longitude'], $bounds[1]['latitude'], $bounds[1]['longitude']) / 2;
    $radius = $radius / sqrt(2);
    
    return $radius;
}

function getTLD($domain) {
    
    // exceptions of tld's with 2 parts
    $x = array(
        'uk' => 'co'
    );
    
    // split host on dot
    $parts = explode('.', $domain);
    
    // create tld
    $t = array_pop($parts);
    
    // add to tld for the exceptions
    if (isset($x[$t]) && end($parts) == $x[$t]) {
        $t = array_pop($parts) . '.' . $t;
    }
    
    return $t;
}
function getTLDCountries() {
    return array(
        "ac" => "Ascension Island",
        "ad" => "Andorra",
        "ae" => "United Arab Emirates",
        "af" => "Afghanistan",
        "ag" => "Antigua and Barbuda",
        "ai" => "Anguilla",
        "al" => "Albania",
        "am" => "Armenia",
        "an" => "Netherlands Antilles",
        "ao" => "Angola",
        "aq" => "Antarctica",
        "ar" => "Argentina",
        "as" => "American Samoa",
        "at" => "Austria",
        "au" => "Australia",
        "aw" => "Aruba",
        "ax" => "Åland",
        "az" => "Azerbaijan",
        "ba" => "Bosnia and Herzegovina",
        "bb" => "Barbados",
        "bd" => "Bangladesh",
        "be" => "Belgium",
        "bf" => "Burkina Faso",
        "bg" => "Bulgaria",
        "bh" => "Bahrain",
        "bi" => "Burundi",
        "bj" => "Benin",
        "bm" => "Bermuda",
        "bn" => "Brunei",
        "bo" => "Bolivia",
        "br" => "Brazil",
        "bs" => "Bahamas",
        "bt" => "Bhutan",
        "bv" => "Bouvet Island",
        "bw" => "Botswana",
        "by" => "Belarus",
        "bz" => "Belize",
        "ca" => "Canada",
        "cc" => "Cocos (Keeling) Islands",
        "cd" => "Democratic Republic of the Congo",
        "cf" => "Central African Republic",
        "cg" => "Republic of the Congo",
        "ch" => "Switzerland",
        "ci" => "Côte d'Ivoire",
        "ck" => "Cook Islands",
        "cl" => "Chile",
        "cm" => "Cameroon",
        "cn" => "People's Republic of China",
        "co" => "Colombia",
        "cr" => "Costa Rica",
        "cs" => "Czechoslovakia",
        "cu" => "Cuba",
        "cv" => "Cape Verde",
        "cw" => "Curaçao",
        "cx" => "Christmas Island",
        "cy" => "Cyprus",
        "cz" => "Czech Republic",
        "dd" => "East Germany",
        "de" => "Germany",
        "dj" => "Djibouti",
        "dk" => "Denmark",
        "dm" => "Dominica",
        "do" => "Dominican Republic",
        "dz" => "Algeria",
        "ec" => "Ecuador",
        "ee" => "Estonia",
        "eg" => "Egypt",
        "eh" => "Western Sahara",
        "er" => "Eritrea",
        "es" => "Spain",
        "et" => "Ethiopia",
        "eu" => "European Union",
        "fi" => "Finland",
        "fj" => "Fiji",
        "fk" => "Falkland Islands",
        "fm" => "Federated States of Micronesia",
        "fo" => "Faroe Islands",
        "fr" => "France",
        "ga" => "Gabon",
        "gb" => "United Kingdom",
        "gd" => "Grenada",
        "ge" => "Georgia",
        "gf" => "French Guiana",
        "gg" => "Guernsey",
        "gh" => "Ghana",
        "gi" => "Gibraltar",
        "gl" => "Greenland",
        "gm" => "The Gambia",
        "gn" => "Guinea",
        "gp" => "Guadeloupe",
        "gq" => "Equatorial Guinea",
        "gr" => "Greece",
        "gs" => "South Georgia and the South Sandwich Islands",
        "gt" => "Guatemala",
        "gu" => "Guam",
        "gw" => "Guinea-Bissau",
        "gy" => "Guyana",
        "hk" => "Hong Kong",
        "hm" => "Heard Island and McDonald Islands",
        "hn" => "Honduras",
        "hr" => "Croatia",
        "ht" => "Haiti",
        "hu" => "Hungary",
        "id" => "Indonesia",
        "ie" => "Ireland",
        "il" => "Israel",
        "im" => "Isle of Man",
        "in" => "India",
        "io" => "British Indian Ocean Territory",
        "iq" => "Iraq",
        "ir" => "Iran",
        "is" => "Iceland",
        "it" => "Italy",
        "je" => "Jersey",
        "jm" => "Jamaica",
        "jo" => "Jordan",
        "jp" => "Japan",
        "ke" => "Kenya",
        "kg" => "Kyrgyzstan",
        "kh" => "Cambodia",
        "ki" => "Kiribati",
        "km" => "Comoros",
        "kn" => "Saint Kitts and Nevis",
        "kp" => "Democratic People's Republic of Korea",
        "kr" => "Republic of Korea",
        "kw" => "Kuwait",
        "ky" => "Cayman Islands",
        "kz" => "Kazakhstan",
        "la" => "Laos",
        "lb" => "Lebanon",
        "lc" => "Saint Lucia",
        "li" => "Liechtenstein",
        "lk" => "Sri Lanka",
        "lr" => "Liberia",
        "ls" => "Lesotho",
        "lt" => "Lithuania",
        "lu" => "Luxembourg",
        "lv" => "Latvia",
        "ly" => "Libya",
        "ma" => "Morocco",
        "mc" => "Monaco",
        "md" => "Moldova",
        "me" => "Montenegro",
        "mg" => "Madagascar",
        "mh" => "Marshall Islands",
        "mk" => "Macedonia",
        "ml" => "Mali",
        "mm" => "Myanmar",
        "mn" => "Mongolia",
        "mo" => "Macau",
        "mp" => "Northern Mariana Islands",
        "mq" => "Martinique",
        "mr" => "Mauritania",
        "ms" => "Montserrat",
        "mt" => "Malta",
        "mu" => "Mauritius",
        "mv" => "Maldives",
        "mw" => "Malawi",
        "mx" => "Mexico",
        "my" => "Malaysia",
        "mz" => "Mozambique",
        "na" => "Namibia",
        "nc" => "New Caledonia",
        "ne" => "Niger",
        "nf" => "Norfolk Island",
        "ng" => "Nigeria",
        "ni" => "Nicaragua",
        "nl" => "Netherlands",
        "no" => "Norway",
        "np" => "Nepal",
        "nr" => "Nauru",
        "nu" => "Niue",
        "nz" => "New Zealand",
        "om" => "Oman",
        "pa" => "Panama",
        "pe" => "Peru",
        "pf" => "French Polynesia",
        "pg" => "Papua New Guinea",
        "ph" => "Philippines",
        "pk" => "Pakistan",
        "pl" => "Poland",
        "pm" => "Saint-Pierre and Miquelon",
        "pn" => "Pitcairn Islands",
        "pr" => "Puerto Rico",
        "ps" => "State of Palestine",
        "pt" => "Portugal",
        "pw" => "Palau",
        "py" => "Paraguay",
        "qa" => "Qatar",
        "re" => "Réunion",
        "ro" => "Romania",
        "rs" => "Serbia",
        "ru" => "Russia",
        "rw" => "Rwanda",
        "sa" => "Saudi Arabia",
        "sb" => "Solomon Islands",
        "sc" => "Seychelles",
        "sd" => "Sudan",
        "se" => "Sweden",
        "sg" => "Singapore",
        "sh" => "Saint Helena",
        "si" => "Slovenia",
        "sk" => "Slovakia",
        "sl" => "Sierra Leone",
        "sm" => "San Marino",
        "sn" => "Senegal",
        "so" => "Somalia",
        "sr" => "Suriname",
        "ss" => "South Sudan",
        "st" => "São Tomé and Príncipe",
        "su" => "Soviet Union",
        "sv" => "El Salvador",
        "sx" => "Sint Maarten",
        "sy" => "Syria",
        "sz" => "Swaziland",
        "tc" => "Turks and Caicos Islands",
        "td" => "Chad",
        "tf" => "French Southern and Antarctic Lands",
        "tg" => "Togo",
        "th" => "Thailand",
        "tj" => "Tajikistan",
        "tk" => "Tokelau",
        "tl" => "East Timor",
        "tm" => "Turkmenistan",
        "tn" => "Tunisia",
        "to" => "Tonga",
        "tp" => "East Timor",
        "tr" => "Turkey",
        "tt" => "Trinidad and Tobago",
        "tv" => "Tuvalu",
        "tw" => "Taiwan",
        "tz" => "Tanzania",
        "ua" => "Ukraine",
        "ug" => "Uganda",
        "uk" => "United Kingdom",
        "us" => "United States of America",
        "uy" => "Uruguay",
        "uz" => "Uzbekistan",
        "va" => "Vatican City",
        "vc" => "Saint Vincent and the Grenadines",
        "ve" => "Venezuela",
        "vg" => "British Virgin Islands",
        "vi" => "United States Virgin Islands",
        "vn" => "Vietnam",
        "vu" => "Vanuatu",
        "wf" => "Wallis and Futuna",
        "ws" => "Samoa",
        "ye" => "Yemen",
        "yt" => "Mayotte",
        "za" => "South Africa",
        "zm" => "Zambia",
        "zw" => "Zimbabwe"
    );
}
?>