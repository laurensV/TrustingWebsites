 <?php
class SSLchain
{
    protected function get_certificates_info($url) {
        if ($fp = tmpfile()) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_STDERR, $fp);
            curl_setopt($ch, CURLOPT_CERTINFO, 1);
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_NOBODY, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSLVERSION, 3);
            
            $result = curl_exec($ch);
            
            // fetch errors
            $errorNumber = curl_errno($ch);
            $errorMessage = curl_error($ch);
            
            // we have errors
            if ($errorNumber != '') throw new SSLchainException($errorMessage);
            
            fseek($fp, 0);
            
            //rewind
            $str = '';
            while (strlen($str.= fread($fp, 8192)) == 8192);
            
            // close curl
            curl_close($ch);
            fclose($fp);
            
            return $str;
        }
    }
    
    protected function getInbetweenStrings($start, $end, $str) {
        $matches = array();
        $regex = "/$start(.*?)$end/";
        
        preg_match_all($regex, $str, $matches);
        return $matches[1];
    }
    
    function unique_array2($array) {
        foreach ($array as $key => $value) {
            
            //check $array array or not
            if (is_array($value)) {
                
                //check the array there is any duplicate value
                if (count($value) == count(array_unique($value))) {
                    $result[$key] = $value;
                } else {
                    $result[$key] = array_unique($value);
                }
            } else {
                $result[$key] = $value;
            }
        }
        
        //return array without duplicate value
        return $result;
    }
    
    public function getIssuers($url) {
        
        // get certificate info
        $cert_info = $this->get_certificates_info($url);
        
        //parse Issuers info
        $ca_arr = $this->getInbetweenStrings('Issuer: ', '\n', $cert_info);
        
        foreach ($ca_arr as $key => & $issuer) {
            $issuer = explode("; ", $issuer);
            $issuer_tmp = array();
            foreach ($issuer as $key => $attr) {
                
                $pieces = explode("=", $attr);
                
                if (in_array($pieces[0], array(
                    "C",
                    "ST",
                    "L"
                ))) {
                    $issuer_tmp['location'][$pieces[0]] = $pieces[1];
                } else {
                    if ($pieces[0] == "O") {
                        $issuer_tmp['name'] = $pieces[1];
                    }
                }
            }
            $issuer = $issuer_tmp;
        }
        
        return array_map("unserialize", array_unique(array_map("serialize", $ca_arr)));;
    }
}

class SSLchainException extends \Exception
{
}
?>