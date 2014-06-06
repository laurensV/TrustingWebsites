<?php
class Geolocation
{
    
    // API URL
    protected $API_URL = 'https://maps.googleapis.com/maps/api/geocode/json';
    protected $API_KEY = 'AIzaSyClOelTQO4kVSRmEuRRd9fbfQ042IIWA_8';
    protected $latest_formatted_address = null;
    protected $latest_bounds = null;
    
    /**
     * Do call
     *
     * @return object
     * @param  array  $parameters
     */
    protected function doCall($parameters = array()) {
        
        // check if curl is available
        if (!function_exists('curl_init')) {
            
            // throw error
            throw new GeolocationException('This method requires cURL (http://php.net/curl), it seems like the extension isn\'t installed.');
        }
        
        // define url
        $url = $this->API_URL . '?';
        
        // add every parameter to the url
        foreach ($parameters as $key => $value) $url.= $key . '=' . urlencode($value) . '&';
        
        // trim last &
        $url.= 'key=' . $this->API_KEY;
        
        // init curl
        $curl = curl_init();
        
        // set options
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        
        // execute
        $response = curl_exec($curl);
        
        // fetch errors
        $errorNumber = curl_errno($curl);
        $errorMessage = curl_error($curl);
        
        // close curl
        curl_close($curl);
        
        // we have errors
        if ($errorNumber != '') throw new GeolocationException($errorMessage);
        
        // redefine response as json decoded
        $response = json_decode($response);
        
        // return the content
        return $response->results;
    }
    
    /**
     * Get latest address
     */
    public function getLatestAddress() {
        
        $return = $this->latest_formatted_address;
        $this->latest_formatted_address = null;
        return $return;
    }
    
    /**
     * Get latest bounds
     */
    public function getLatestBounds() {
        $return = $this->latest_bounds;
        $this->latest_bounds = null;
        return $return;
    }
    
    /**
     * Set latest bounds
     */
    public function setLatestBounds($bounds) {
        $this->latest_bounds = array();
        
        $this->latest_bounds[] = array(
            'latitude' => (float)$bounds->northeast->lat,
            'longitude' => (float)$bounds->northeast->lng
        );
        $this->latest_bounds[] = array(
            'latitude' => (float)$bounds->southwest->lat,
            'longitude' => (float)$bounds->southwest->lng
        );
    }
    
    /**
     * Get coordinates latitude/longitude
     */
    public function getCoordinates($address) {
        
        // define result
        $results = $this->doCall(array(
            'address' => $address,
            'sensor' => 'false'
        ));
        
        // store formatted address in latest adres
        $this->latest_formatted_address = array_key_exists(0, $results) ? $results[0]->formatted_address : null;
        
        // store bounds in latest bounds
        if (array_key_exists(0, $results)) {
            $this->setLatestBounds($results[0]->geometry->viewport);
        }
        
        // return coordinates latitude/longitude
        return array(
            'latitude' => array_key_exists(0, $results) ? (float)$results[0]->geometry->location->lat : null,
            'longitude' => array_key_exists(0, $results) ? (float)$results[0]->geometry->location->lng : null
        );
    }
}

class GeolocationException extends \Exception
{
}
