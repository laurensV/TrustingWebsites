<?php
require ('classes/sslchain.class.php');
require ('classes/ip2loc.class.php');
require ('classes/geolocation.class.php');
require ('classes/whois.class.php');

//Load the classes
$ip2loc = new ip2loc;
$geolocation = new Geolocation;
$whois = new Whois;
$cache_lifetime_seconds = 60 * 60 * 24;
 //24 hours

/* load/create the database */
$dbhandle = sqlite_open('db/cache.db', 0666, $error);
if (!$dbhandle) die($error);

// Create cache table if it doesn't exists
$stm = "CREATE TABLE cache(name text, result text, time int)";
$ok = @sqlite_exec($dbhandle, $stm, $error);

function getCAsInfo($url, $chain) {
    global $geolocation, $dbhandle, $cache_lifetime_seconds;
    
    /* check if information is available in the cache */
    $query = "SELECT result, time FROM cache WHERE name = 'CAs:" . $url . ":" . $chain . "' LIMIT 1";
    $result = sqlite_query($dbhandle, $query);
    if (!$result) die("Cannot execute query.");
    if (sqlite_num_rows($result)) {
        $row = sqlite_fetch_array($result, SQLITE_ASSOC);
        
        /* check if cache is older than cache lifetime */
        if (time() - $row['time'] > $cache_lifetime_seconds) {
            $query = "DELETE FROM cache WHERE name = 'CAs:" . $url . ":" . $chain . "' LIMIT 1";
            $result = sqlite_exec($dbhandle, $query);
            if (!$result) die("Cannot delete old data from cache.");
        } else {
            
            /* return the results from the cache */
            $info = unserialize($row['result']);
            return $info;
        }
    }
    
    /* only load sslchain class if this function gets called */
    $sslchain = new SSLchain;
    $ca_arr = array();
    
    $issuers = $sslchain->getIssuers($url);
    
    foreach ($issuers as $issuer) {
        $address = "";
        if (is_array($issuer['location'])) {
            foreach ($issuer['location'] as $location) {
                $address.= $location . " ";
            }
            $ca_location_coord = $geolocation->getCoordinates($address);
            $ca_address = $geolocation->getLatestAddress();
            $ca_bounds = $geolocation->getLatestBounds();
            $ca_radius = calculateRadius($ca_bounds);
            $ca_name = $issuer['name'];
            $ca_arr[] = array(
                'coord' => $ca_location_coord,
                'address' => $ca_address,
                'name' => $ca_name,
                'radius' => $ca_radius
            );
        }
        if (!$chain) break;
    }
    
    /* store result in cache */
    $stm = "INSERT INTO cache VALUES('CAs:" . $url . ":" . $chain . "','" . serialize($ca_arr) . "','" . time() . "')";
    $result = sqlite_exec($dbhandle, $stm);
    if (!$result) die("Cannot store ca's in cache.");
    
    return $ca_arr;
}

function getIPInfo($ip, $name = "", $findowner = false) {
    global $ip2loc, $geolocation, $whois, $dbhandle, $cache_lifetime_seconds;
    
    /* check if information is available in the cache */
    $query = "SELECT result, time FROM cache WHERE name = 'IP:" . $ip . ":" . $name . ":" . $findowner . "' LIMIT 1";
    $result = sqlite_query($dbhandle, $query);
    if (!$result) die("Cannot execute query.");
    if (sqlite_num_rows($result)) {
        $row = sqlite_fetch_array($result, SQLITE_ASSOC);
        
        /* check if cache is older than cache lifetime */
        if (time() - $row['time'] > $cache_lifetime_seconds) {
            $query = "DELETE FROM cache WHERE name = 'IP:" . $ip . ":" . $name . ":" . $findowner . "' LIMIT 1";
            $result = sqlite_exec($dbhandle, $query);
            if (!$result) die("Cannot delete old data from cache.");
        } else {
            
            /* return the results from the cache */
            $info = unserialize($row['result']);
            return $info;
        }
    }
    
    /* info not yet in cache or outdated, so fetch new info */
    $location = $ip2loc->query($ip);
    $address = $location['city'] . " " . $location['regionName'] . " " . $location['country'];
    $coord = $geolocation->getCoordinates($address);
    $address = $geolocation->getLatestAddress();
    $bounds = $geolocation->getLatestBounds();
    $radius = calculateRadius($bounds);
    
    $info = array(
        'name' => $name,
        'ip' => $ip,
        'address' => $address,
        'coord' => $coord,
        'radius' => $radius
    );
    if ($findowner) {
        $res = $whois->whoislookup($ip);
        
        $address = $res['location'];
        $coord = $geolocation->getCoordinates($address);
        $address = $geolocation->getLatestAddress();
        $bounds = $geolocation->getLatestBounds();
        $radius = calculateRadius($bounds);
        $owner = array(
            'name' => $res['name'],
            'address' => $address,
            'coord' => $coord,
            'radius' => $radius
        );
        $info['owner'] = $owner;
    }
    
    /* store result in cache */
    $stm = "INSERT INTO cache VALUES('IP:" . $ip . ":" . $name . ":" . $findowner . "','" . serialize($info) . "','" . time() . "')";
    $result = sqlite_exec($dbhandle, $stm);
    if (!$result) die("Cannot store ip info in cache.");
    
    return $info;
}
function getDomainInfo($domain, $getTld) {
    global $whois, $geolocation, $ip2loc, $dbhandle, $cache_lifetime_seconds;
    
    /* check if information is available in the cache */
    $query = "SELECT result, time FROM cache WHERE name = 'Domain:" . $domain . ":" . $getTld . "' LIMIT 1";
    $result = sqlite_query($dbhandle, $query);
    if (!$result) die("Cannot execute query.");
    if (sqlite_num_rows($result)) {
        $row = sqlite_fetch_array($result, SQLITE_ASSOC);
        
        /* check if cache is older than cache lifetime */
        if (time() - $row['time'] > $cache_lifetime_seconds) {
            $query = "DELETE FROM cache WHERE name = 'Domain:" . $domain . ":" . $getTld . "' LIMIT 1";
            $result = sqlite_exec($dbhandle, $query);
            if (!$result) die("Cannot delete old data from cache.");
        } else {
            
            /* return the results from the cache */
            $info = unserialize($row['result']);
            return $info;
        }
    }
    
    /* whois lookup for domain */
    $domainwhois_res = $whois->whoislookup($domain);
    
    /* Registrant */
    if (isset($domainwhois_res['registrant']['location']) && !empty($domainwhois_res['registrant']['location'])) {
        foreach ($domainwhois_res['registrant']['location'] as $location) {
            $registrant_address.= $location . " ";
        }
        $registrant_coord = $geolocation->getCoordinates($registrant_address);
        $registrant_address = $geolocation->getLatestAddress();
        $registrant_bounds = $geolocation->getLatestBounds();
        $registrant_radius = calculateRadius($registrant_bounds);
        
        $registrant_name = $domainwhois_res['registrant']['name'];
    }
    
    /* Registrar */
    if (isset($domainwhois_res['registrar']['location']) && !empty($domainwhois_res['registrar']['location'])) {
        foreach ($domainwhois_res['registrar']['location'] as $location) {
            $registrar_address.= $location . " ";
        }
        $registrar_coord = $geolocation->getCoordinates($registrar_address);
        $registrar_address = $geolocation->getLatestAddress();
        $registrar_bounds = $geolocation->getLatestBounds();
        $registrar_radius = calculateRadius($registrar_bounds);
        $registrar_name = $domainwhois_res['registrar']['name'];
    } else if (isset($domainwhois_res['registrar']['url'])) {
        $registrar_name = $domainwhois_res['registrar']['url'];
        $registrar_domain = parse_url($registrar_name);
        $host = $registrar_domain['host'];
        if (substr($host, -1) == "_") {
            $host = substr_replace($host, "", -1);
        }
        
        $registrar_location = $ip2loc->query($host);
        
        $registrar_address = $registrar_location['city'] . " " . $registrar_location['regionName'] . " " . $registrar_location['country'];
        
        $registrar_coord = $geolocation->getCoordinates($registrar_address);
        $registrar_address = $geolocation->getLatestAddress();
        $registrar_bounds = $geolocation->getLatestBounds();
        $registrar_radius = calculateRadius($registrar_bounds);
    }
    
    /* TLD */
    if ($getTld) {
        $tld = getTLD($domain);
        
        $tld_countries = getTLDCountries();
        if (isset($tld_countries[$tld])) {
            $tld_address = $tld_countries[$tld];
            
            $tld_coord = $geolocation->getCoordinates($tld_address);
            $tld_address = $geolocation->getLatestAddress();
            $tld_bounds = $geolocation->getLatestBounds();
            $tld_radius = calculateRadius($tld_bounds);
        }
    }
    
    $info = array(
        'registrant' => array(
            'name' => $registrant_name,
            'address' => $registrant_address,
            'coord' => $registrant_coord,
            'radius' => $registrant_radius
        ) ,
        'registrar' => array(
            'name' => $registrar_name,
            'address' => $registrar_address,
            'coord' => $registrar_coord,
            'radius' => $registrar_radius
        ) ,
        'tld' => array(
            'name' => $tld,
            'address' => $tld_address,
            'coord' => $tld_coord,
            'radius' => $tld_radius
        )
    );
    
    /* store result in cache */
    $stm = "INSERT INTO cache VALUES('Domain:" . $domain . ":" . $getTld . "','" . serialize($info) . "','" . time() . "')";
    $result = sqlite_exec($dbhandle, $stm);
    if (!$result) die("Cannot store ip info in cache.");
    
    return $info;
}

function close_db() {
    global $dbhandle;
    sqlite_close($dbhandle);
}
?>