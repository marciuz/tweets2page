<?php
/**
 *
 * 
 * 
 * 
 * 
 *  
 */



/**
 * Main class Tweets2Page
 *  
 */
class Tweets2Page {
    
    public $debug=false;
    
    public $debug_obj;
    
    public $max_tweets=10;
    
    private $res;
    
    private $result_type='mixed';
    
    public $skip_sites=array();
    
    protected $T0;
    
    
    
    public function __construct(){
	
	$this->T0=microtime(true);
	
	$this->debug_obj=new stdClass();
	
	$this->res=new stdClass();
	
	$this->res->pages=array();
	
    }
    
    
    public function parseTweets($search, $max=0, $type=''){
	
	
	// unique array to prevent duplicates
	// the parser should investigate on single page once time
	$url_to_check=array();
	
	// override paramethers
	if(intval($max)>0){
	    
	    $this->max_tweets=$max;
	}
	
	// override paramethers
	if($type==''){
	    
	    $this->result_type=$type;
	}
	
	
	//  Get the Tweets!
	$tweets= $this->getTweets($search);
	
	// Tweets to be parsed
	$tw_2_be_parsed=array();
	
	// cycle on the tweets
	foreach($tweets->results as $tw){
	    
	    
	    // conditions to skip:
	    
	    // 0: the tweets has a link?
	    // 
	    // if there is no url in this tweet...
	    if(!is_array($tw->entities->urls) || count($tw->entities->urls)==0){
		
		continue;
	    }
	    else{
		
		//$url= $this->find_url($tw->text);
		$url= $tw->entities->urls[0]->expanded_url;
	    }
	    
	    
	    
	    // 1: the url has already been analyzed?
	    // 
	    // check session duplicates in url
	    if(in_array($url, $url_to_check)){
		
		continue;
	    }
	    else {
		$url_to_check[]=$url;
	    }

	    
	    // 2: is a PDF?
	    // 
	    // if pdf, continue
	    if(strtolower(substr($url,-4,4))=='.pdf') {
		continue;
	    }
	    
	    
	    // 3: Is in the black list?
	    // 
	    // skip the specified websites
	    if(count($this->skip_sites)>0){
		
		$found=false;
		
		for($i=0;$i<count($this->skip_sites);$i++){
		    
		    if(strpos($url, $this->skip_sites[$i])!==false){
			
			$found=true;
		    }
		}
		
		if($found) continue;
		
	    }
	    
	 
	    // 4: is in DB?
	    // TODO
	    
	    
	    $tw_2_be_parsed[]=$tw;
	    
	}
	
	
	
	
	
	// cycle on the tweets
	foreach($tw_2_be_parsed as $tw){
	    
	    // Create a Single page Parser
	    $obj = new SinglePageParser($url, $this->debug);
	    
	    
	    // Start print results
	    $page=new stdClass();
	    $page->title = $obj->get_title();
	    $page->img	 = $obj->post_image;
	    $page->description = trim($obj->description);
	    $page->url = $url;
	    
	    // add original object
	    $page->tweet=$tw;

	    $this->res->pages[]=$page;

	}
	
	// set the exec time
	$this->res->exec_time=round( (microtime(true) - $this->T0), 3);
	
	return $this->res;
    }
    
    
    
    protected function getTweets($search){
	
	$url="http://search.twitter.com/search.json?"
	    ."q=".urlencode($search)
	    ."&rpp=".$this->max_tweets
	    ."&include_entities=true"
	    ."&with_twitter_user_id=true"
	    ."&result_type=$this->result_type";
	
	$json=file_get_contents($url);
	
	$json=preg_replace('/"id" ?:(\d+)/', '"id":"${1}"', $json);
	
	return json_decode($json);
	
    }
    
    
    
    protected function find_url($text){
	
	$reg_exUrl = "#\bhttps?://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#";
        
	return  (preg_match($reg_exUrl, $text, $url)) ? $url[0] : false;
    }
    
}




class SinglePageParser {
    
    public $debug;
    public $debug_obj;
    
    public $headers;
    
    public $title;
    public $h1;
    public $description;
    
    public $url;
    
    public $min_img_width=220;
    public $min_img_height=220;
    
    private $loop2_allowed_formats=array('.jpg');
    
    private $T0;
    
    public $_exec_time;
    
    
    
    public function __construct($url, $debug=false) {
	
	$this->T0 = microtime(true);
	
	$this->debug=$debug;
	
	$this->url=$url;
	
	$this->parse($this->url);
    }
    
    /**
     * Set real location (not tw.co/abcd)
     * 
     * @param type $http_response_header 
     
    private function header_code( $rh ){
	       
        // Display easy-to-read valid PHP array code
        $this->headers =  $rh;
	
	$tmp=$this->headers;
	
	krsort($tmp);
	
	foreach($tmp as $hhh){
	    
	    if(strpos($hhh,":")!==false){
		list($h,$url)=explode(":",$hhh,2);

		if($h=="Location"){

		    return trim($url);
		}
	    }
	}
	
	return null;
    }*/
    
    
    
    /**
     * Get <title> content or if not exists, get <h1> content
     * @return type 
     */
    public function get_title(){
	
	if($this->title!='') 
	    	    
	    return trim($this->title);
	else 
	    return trim($this->h1);
    }
    
    
    
    /**
     * Parse and find contents from URL
     * 
     */
    public function parse() {
	
        // Make first request
	/*
        $html = @file_get_contents($this->url);
	
	// test alternative method with curl: is better?
	if($html=='' || $html==null){
	    
	    // Test with CURL
	    $curl = new Curl();
	    
	    $html=$curl->get($this->url);
	}
	 * 
	 */
	
	list($html,$cinfo)=mycurl($this->url);
	
	// Load class simple dom for page parsing
	$dom = new simple_html_dom();
	$dom->load($html);
	
	
	// set title
	$this->title=@$dom->find('title',0)->innertext;
	
	// set h1
	$h1=@$dom->find('h1',0)->innertext;
	$this->h1=(is_string($h1)) ? strip_tags($h1) : '';
	
	// set description
	$this->description=@$dom->find('meta[name=description]',0)->content;

	// Search for tag <img> 
	$images = $dom->find('img');
	
	// Set imgs for debug
	if($this->debug){
	    
	    $this->debug_obj->img=$images;
	    $this->debug_obj->img_count=count($images);
	}
	
	
	
	/*
	 *  IMG PARSING --------------------------------------------------------
	 */
	
	// set img_src as null
	$img_src=null;
	
	// First:
	$img_src=$this->heuristic_image_1($images);
	
	// Second loop heuristic if first fails
	if($img_src===null){
	    
	    $img_src=$this->heuristic_image_2($images);
	}

	$this->post_image= ($img_src===null) ? null : $this->myUrlEncode($img_src);
	
	// End IMG Parsing -----------------------------------------------------
	
	
	$this->_exec_time=round((microtime(true) - $this->T0), 3);
	
	return $this;

    }
    
    
    
    
    
    
    /**
     * The images can be as http://domain.com/image.xx or /relative/img.xxx
     * The case "../relative/img.xxx is not covered
     * @param type $path
     * @return type 
     * @todo Create case for relative paths like "../foo/bar.jpg"
     */
    protected function path_image($path){
	
	if(strpos($path,'http')===false){
	    
	    $pp=parse_url($this->url);

	    if($path{0}=="/"){

		return $pp['scheme']."://".$pp['host'].$path;
	    }
	    else if(substr($path,0,3)=='../'){
		
		return $this->relative_image($this->url, $path);
	    }
	}
	else{
	    
	    return $path;
	}
	
    }
    
    
    protected function relative_image($url, $path_img){

	$tk0=parse_url($url);

	$path=explode("/",$tk0['path']);

	preg_match_all("|(\.\./)|",$path_img,$backs);

	$xx=array_slice($path, 0, count($backs[0]) * -1);

	$abs_path_img=$tk0['scheme']."://".$tk0['host'].implode("/",$xx)."/".  str_replace("../", "", $path_img);

	return $abs_path_img;
    }
    
    
    protected function myUrlEncode($string) {
	$entities = array('%21', '%2A', '%27', '%28', '%29', '%3B', '%3A', '%40', '%26', '%3D', '%2B', '%24', '%2C', '%2F', '%3F', '%25', '%23', '%5B', '%5D');
	$replacements = array('!', '*', "'", "(", ")", ";", ":", "@", "&", "=", "+", "$", ",", "/", "?", "%", "#", "[", "]");
	return str_replace($entities, $replacements, rawurlencode($string));
    }
    
    
    
    /** 
     * First heuristic to choice the image
     * Basend on explicit size.
     * 
     * 
     * @param type $images 
     */
    protected function heuristic_image_1($images){
	
	if(!is_array($images)) {
	    
	    return null;
	}
	else{
	    
	    $img_src=null;
	
	    // first loop heuristic: search for images with attributes
	    foreach($images as $i=>$imgs){

		if((!empty($imgs->width) && $imgs->width > $this->min_img_width)
		   && (!empty($imgs->height) && $imgs->height > $this->min_img_height)) {

		    // set the image!
		    $img_src=$imgs->src;

		    // debug 
		    if($this->debug){

			$this->debug_obj->found_img_n=$i;
			$this->debug_obj->heuristic=1;
		    }

		    break;
		}
	    }
	
	}
	
	return $img_src;
    }
    
    
    
    
    protected function heuristic_image_2($images){
	
	if(!is_array($images)) {
	    
	    return null;
	}
	else{
	    
	    $img_src=null;
	
	    foreach($images as $i=>$imgs){

		// Change path to absolute?
		$img_tmp_src=  $this->path_image($imgs->src);

		// filter on formats: if is not in allowed skip and continue
		if(!in_array(strtolower(substr($img_tmp_src, -4, 4)), $this->loop2_allowed_formats )){

		    continue;
		}

		// CRITICAL: get image info with a image request
		$img_size= @getimagesize($img_tmp_src);

		if($img_size[0]>$this->min_img_width && $img_size[1]>$this->min_img_height){

		    if($this->debug){
			$this->debug_obj->found_img_n=$i;
			$this->debug_obj->heuristic=2;
		    }
		    
		    $img_src=$img_tmp_src;
		    
		    break;
		}

	    }
	    
	    return $img_src;
	}
    }
    
    
    
    
    
}






















