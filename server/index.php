<?php

/* show documentation when no url is given */
if (!isset($_GET['url'])) {
    require ('pages/fields_generator.html');
    return;
}

/* include functions */
require ('include/functions.php');
require ('include/getLocations.php');

/* parse url */
$url = $_GET['url'];
$domain = parse_url($url);
$protocol = $domain['scheme'];
$domain = $domain['host'];

//remove www from domain
if (substr(strtolower($domain) , 0, 4) == "www.") $domain = substr($domain, 4);

/* check which information the user wants */
if (isset($_GET['fields'])) {
    $code = intval($_GET['fields']);
    if ($code < 0 || $code > 1023) {
        echo 'invalid fields code';
        die();
    }
    $bin = decbin($code);
    $code = substr("0000000000", 0, 10 - strlen($bin)) . $bin;
    $code = strrev($code);
} else {
    /* when fields code is not provided, show all fields */
    $code = "1111111111";
}


/* Get User info */
if ($code[0] == "1") {
    $user_ip = getUserIP();
    $user = getIPInfo($user_ip);
}

/* Get Hosts info */
if ($code[1] == "1" || $code[2] == "1") {
    $hosts = array();
    $firsthost = true;
    $host_records = dns_get_record($domain, DNS_A);
    foreach ($host_records as $host_record) {
        if ($firsthost || $code[2] == "1") {
            /* check if owner needs to be found for first host */
            if ($code[3] == "0") $firsthost = false;
            
            /* get host info */
            $host = getIPInfo($host_record['ip'], $host_record['host'], $firsthost);
            
            /* add host to list of hosts */
            $hosts[] = $host;

            $firsthost = false;
        }
    }
}

/* Get Mail server info */
if ($code[4] == "1") {
    $mservers = array();
    $mserver_records = dns_get_record($domain, DNS_MX);
    foreach ($mserver_records as $mserver_record) {
        $mserver_ip = gethostbyname($mserver_record['target']);
        $mserver = getIPInfo($mserver_ip, $mserver_record['target']);
        $mservers[] = $mserver;
    }
}

/* Get Domain info */

if ($code[5] == "1") {

    $tld = false;
    if ($code[6] == "1") $tld = true;
    $domainwhois = getDomainInfo($domain, $tld);
}

/* Get CA's info */
if ($code[7] == "1" && $protocol == 'https') {
    $chain = false;
    /* check if whole chain needs to be found */
    if ($code[8] == "1") $chain = true;

    $ca_arr = getCAsInfo($url, $chain);
}

$output = array(
    'hosts' => $hosts,
    'mservers' => $mservers,
    'domain' => $domainwhois,
    'cas' => $ca_arr,
);
$output = array_filter_recursive($output);

/* Get midpoint */
if ($code[9] == "1") {
    $midpoint = getMidPoint($output);
}

$output['name'] = $protocol . "://" . $domain;
$output['user'] = $user;
$output['midpoint'] = $midpoint;
$output = array_filter_recursive($output);

// output this array json encoded..
if (isset($_GET['output'])) {
    $type = $_GET['output'];
} else {
    $type = 'json';
}
switch ($type) {
    case "json":
        
        /* only for PHP 5.4 or higher */
        
        //echo '[' . json_encode($output, JSON_UNESCAPED_SLASHES) . ']';
        
        echo '[' . str_replace('\/', '/', json_encode($output)) . ']';
        break;

    case "array":
        print_r($output);
        break;

    default:
        echo "Output type not valid";
}

/* close the cache database */
close_db();
?>