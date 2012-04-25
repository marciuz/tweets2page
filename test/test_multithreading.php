<?php


function test_multi($urls){
    
  $T0=microtime(true);

  $mh = curl_multi_init();
  
  $handles = array();
  
  $output='';
 
  for($i=0;$i< count($urls);$i++){
      
    $ch = curl_init();
 
    curl_setopt($ch,CURLOPT_URL, $urls[$i]);
    curl_setopt($ch,CURLOPT_HEADER,0);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch,CURLOPT_TIMEOUT,30);
 
    curl_multi_add_handle($mh,$ch);
 
    $handles[] = $ch;
  }
 
  $running=null;
  do 
  {
    curl_multi_exec($mh,$running);
  } while ($running > 0);
 
  for($i=0;$i< count($handles);$i++)
  {
    $output.= curl_multi_getcontent($handles[$i]);
 
    curl_multi_remove_handle($mh,$handles[$i]);
  }
 
  
  
  // echo $output;
  
  curl_multi_close($mh);
  
  $T=round(microtime(true) - $T0, 5);
  
  return $T;
}
  
  


function test_single($urls){
    
   $T0=microtime(true);
   
   $output='';
     
    for($i=0;$i< count($urls);$i++){
     
	$ch = curl_init();

	curl_setopt($ch,CURLOPT_URL, $urls[$i]);
	curl_setopt($ch,CURLOPT_HEADER,0);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch,CURLOPT_TIMEOUT,30);
	
	$output.=curl_exec($ch);
	
	
    }
    
    // echo $output;
  
    curl_close($ch);
    
    $T=round(microtime(true) - $T0, 5);
    
    return $T;
}


// BIG SITES
$urls=array(
      
    'http://www.repubblica.it/',  
    'http://www.corriere.it/',  
    'http://www.lastampa.it/', 
    'http://www.guardian.co.uk/',
    'http://www.tiscali.it/',
);


$T1=  test_multi($urls);
$T2=  test_single($urls);


echo "\n\n\n$T1 <-> $T2\n";

echo round(1-($T1/($T2/100)/100), 2) ."%";