<?php
class ip2loc
{
    
    // refer to http://ip-api.com/docs/api:returned_values#field_generator
    protected $fields = 49369;
    
    protected $api = "http://ip-api.com/php/";
    
    public function query($q) {
        $data = $this->communicate($q);
        
        return $data;
    }
    
    private function communicate($q) {
        
        if (is_callable('curl_init')) {
            $c = curl_init();
            
            curl_setopt($c, CURLOPT_URL, $this->api . $q . '?fields=' . $this->fields);
            
            curl_setopt($c, CURLOPT_HEADER, false);
            curl_setopt($c, CURLOPT_TIMEOUT, 30);
            curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
            $result_array = unserialize(curl_exec($c));
            curl_close($c);
        } else {
            $result_array = unserialize(file_get_contents($this->api . $q . '?fields=' . $this->fields));
        }
        
        return $result_array;
    }
}
?>