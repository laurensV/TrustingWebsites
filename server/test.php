<?php
//globals on or off ? 
$register_globals = (bool) ini_get('register_gobals'); 
$system = ini_get('system'); 
// 
If ($register_globals) 
{ 
   $ip = getenv(REMOTE_ADDR); 
   $self = $PHP_SELF; 
}  
else  
{ 
   $submit = $_GET['submit']; 
   $host   = $_GET['host']; 
   $ip     = $_SERVER['REMOTE_ADDR']; 
   $self   = $_SERVER['PHP_SELF']; 
}; 
// form submitted ? 
If ($submit == "Analyse")  
{ 
      // replace bad chars 
      //$host= preg_replace ("/[^A-Za-z0-9.]/","",$host); 
      echo '<body bgcolor="#FFFFFF" text="#000000"></body>';
      $result = dns_get_record($host, DNS_A, $authns, $addtl);
      echo 'DNS Records:<br><pre>';
      echo "Result = ";
      print_r($result);
      echo "Auth NS = ";
      print_r($authns);
      echo "Additional = ";
      print_r($addtl);
      echo '</pre>';

      echo("Trace Output:<br>");  
      echo '<pre>';            
      //check target IP or domain 
         system ("traceroute $host"); 
         system("killall -q traceroute");// kill all traceroute processes in case there are some stalled ones or use echo 'traceroute' to execute without shell 
      echo '</pre>';
      echo "Dig host:<br>";
      echo '<pre>';
         system ("dig $host any"); 
         system ("nslookup $host");
      echo '</pre>';  
      echo 'done ...';   
}  
else  
{ 
    echo '<body bgcolor="#FFFFFF" text="#000000"></body>'; 
    echo '<p><font size="2">Your IP is: '.$ip.'</font></p>'; 
    echo '<form methode="post" action="'.$self.'">'; 
    echo '   Enter IP or Host <input type="text" name="host" value="'.$ip.'"></input>'; 
    echo '   <input type="submit" name="submit" value="Analyse"></input>'; 
    echo '</form>'; 
    echo '<br><b>'.$system.'</b>'; 
    echo '</body></html>'; 
} 
?> 