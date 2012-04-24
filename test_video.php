<?php

require("./inc/simple_html_dom.php");


function find_video($url){
    
    $html=file_get_contents($url);
    
    $dom = new simple_html_dom();
    $dom->load($html);
    
    $vinfo= new stdClass();
    
    // <video>
    if($dom->find('video',0)!=null){
	
	if($dom->find('video',0)->poster!==null){
	    
	    $vinfo->thumb=$dom->find('video',0)->poster;
	    $vinfo->src=$dom->find('video',0)->src;
	    $vinfo->type='html5';
	}
    }
    
    // find embed youtube, vimeo, ?
    // $dom->find('embed',0);
    
    // identify youtube in iframe->src
    $yt_src=$dom->find('iframe[src*=youtube.com]',0)->src;
    
    
    
    // example: http://www.youtube.com/embed/nz2gHlQHv4g?fs=1&feature=oembed
    
    // get the video id:
    if(preg_match("|embed/(\w+)\?|",$yt_src,$yt_code)){
	
	$vinfo->thumb= "http://i.ytimg.com/vi/".$yt_code[1]."/2.jpg";
	$vinfo->src=$yt_src;
	$vinfo->type='youtube';
    }
    
    
       
   return $vinfo;
    
}

$img_video=find_video('http://consulting.talis.com/2012/04/data-foundations-for-digital-cities-video/');
//$img_video = find_video('http://www.orebla.it/moduli/html-5/test/video_poster.html');

var_dump($img_video);