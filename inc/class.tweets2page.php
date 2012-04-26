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
    
    public $max_tweets=10;
    
    private $res;
    
    private $result_type='mixed';
    
    private $skip_sites=array();
    
    private $T0;
    
    private $heuristic=2;
    
    private $cache_db=true;
    
    private $log="-------------------------------\n";
    
    private $log_level=5;
    
    
    /**
     * Set the T0, initialize output class, load blackist
     */
    public function __construct(){
	
	$this->T0=microtime(true);
	
	$this->res=new stdClass();
	
	$this->res->pages=array();
	
	// load blacklist
	if($this->cache_db) 
	    $this->skip_sites=dbcache::get_blacklist();
	
    }
    
    public function set_log_level($level){
	
	$this->log_level=$level;
    }
    
    
    /**
     * Add a skyp site to blacklist
     * @param string $url 
     */
    public function add_skyp_site($url){
	
	$this->skip_sites[]=$url;
    }
    
    
    /**
     * Set the cache (default=>true)
     * @param bool $bool 
     */
    public function set_cache($bool){
	
	$this->cache_db = (bool) $bool;
    }
    
    
    /**
     *
     * @param int $heu 1|2|3 default=>2
     */
    public function set_heuristic($heu){
	
	$this->heuristic = (intval($heu)>3) ? 3 : $heu;
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
	
	
	$this->add_log("Search for twitter: $search", 1);
	
	//  Get the Tweets!
	$tweets= $this->getTweets($search);
	
	$this->add_log("Found: ".count($tweets->results), 1);
	
	
	// Tweets to be parsed
	$tw_2_be_parsed=array();
	
	// cycle on the tweets
	foreach($tweets->results as $tw){
	    
	    
	    // conditions to skip:
	    
	    // 0: the tweets has a link?
	    // 
	    // if there is no url in this tweet...
	    if(!is_array($tw->entities->urls) || count($tw->entities->urls)==0){
		
		$this->add_log("No url, skip", 7);
		continue;
	    }
	    else{
		
		//$url= $this->find_url($tw->text);
		$url= $tw->entities->urls[0]->expanded_url;
		$this->add_log("Analyze: $url", 7);
	    }
	    
	    
	    
	    // 1: the url has already been analyzed?
	    // 
	    // check session duplicates in url
	    if(in_array($url, $url_to_check)){
		
		$this->add_log("Skip (duplicate): $url", 8);
		continue;
	    }
	    else {
		
		// put in the list
		$url_to_check[]=$url;
	    }

	    
	    // 2: is a PDF?
	    // 
	    // if pdf, continue
	    if(strtolower(substr($url,-4,4))=='.pdf') {
		
		$this->add_log("Skip (pdf): $url", 8);
		continue;
	    }
	    
	    
	    // 3: Is in skyp urls? (or in DB black list?)
	    // skip the specified websites
	    //
	    if($this->is_in_skyp_urls($url)){
		
		$this->add_log("Skip (in blacklist): $url", 8);
		continue;
	    }
	    
	    
	    // 4: is in the stoplist?
	    if(in_stoplist($url)){
		
		$this->add_log("Skip (in stoplist): $url", 8);
		continue;
	    }
	    
	   
	 
	    // ELSE:
	    $tw_2_be_parsed[]=$tw;
	    
	}
	
	
	$this->add_log("Tweets to be parsed: ".count($tw_2_be_parsed), 3);
	
	
	
	$in_cache=0;
	
	// cycle on the tweets
	foreach($tw_2_be_parsed as $tw){
	    
	    
	    $url= $tw->entities->urls[0]->expanded_url;
	    
	    if($this->cache_db && dbcache::exists($url)){
		
		$this->res->pages[]=dbcache::get($url);
		$this->add_log("Taked from cache: ".$url, 7);
		$in_cache++;
	    }
	    else{
	    
		$this->add_log("Start parsing: $url", 5);
		
		// Create a Single page Parser
		$obj = new SinglePageParser($url, array(), $this->debug, $this->heuristic);
		
		$this->add_log("Finishing parsing: $url", 5);
		
		

		// Is possible there are alias url (eb bit.ly)
		// This is blacklisted if is alias of skypurl
		
		if($this->is_in_skyp_urls($obj->real_url) || in_stoplist($obj->real_url)){
		    
		    dbcache::add_to_blacklist($url, $obj->real_url);
		    $this->add_log("Real URL added to blacklist: $url, alias of ".$obj->real_url, 7);
		    continue;
		}
		
		
		
		// add info heuristic for debug
		$obj->heuristic=$this->heuristic;

		// Start print results
		$page=new stdClass();
		$page->title = $obj->get_title();
		$page->img	 = $obj->post_image;
		$page->description = trim($obj->description);
		$page->url = $url;
		$page->real_url = $obj->real_url;
		$page->heuristic = $obj->real_url;

		// add original object
		$page->tweet=$tw;

		$this->res->pages[]=$page;
		
		if($this->cache_db) {
		    dbcache::store($url,date('c'), $page, $search);
		    $this->add_log("Add to cache: $url", 7);
		}
	    }

	}
	
	// set the exec time
	$this->res->exec_time=round( (microtime(true) - $this->T0), 3);
	$this->add_log("Finish in: ".$this->res->exec_time, 1);
	$this->add_log("Found: ".count($tweets->results)
		.", Parsed: ".count($tw_2_be_parsed)
		.", In cache: ". $in_cache, 1);
	
	return $this->res;
    }
    
    
    public function is_in_skyp_urls($url){
	
	$found=false;
	
	// skip the specified websites
	if(count($this->skip_sites)>0){

	    for($i=0;$i<count($this->skip_sites);$i++){

		if(strpos($url, $this->skip_sites[$i])!==false){

		    $found=true;
		    break;
		}
	    }
	}
	
	return $found;
    }




    protected function getTweets($search){
	
	$url="http://search.twitter.com/search.json?"
	    ."q=".urlencode($search)
	    ."&rpp=".$this->max_tweets
	    ."&include_entities=true"
	    ."&with_twitter_user_id=true"
	    ."&result_type=$this->result_type";
	
	//$json=file_get_contents($url);
	list($json,$info)=mycurl($url);
	
	$json=preg_replace('/"id" ?:(\d+)/', '"id":"${1}"', $json);
	
	return json_decode($json);
	
    }
    
    
    
    protected function find_url($text){
	
	$reg_exUrl = "#\bhttps?://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#";
        
	return  (preg_match($reg_exUrl, $text, $url)) ? $url[0] : false;
    }
    
    private function add_log($string, $level=5){
	
	if($this->log_level >= $level){
	    $this->log  .=round(microtime(true) -$this->T0, 4)."\t"
			.memory_get_usage()."\t"
			.str_replace(array("\n","\r","\t")," ",$string)."\n";

	}
    }
    
    public function log(){
	
	return $this->log;
    }
    
}







/**
 * Class SIngle Parser 
 */
class SinglePageParser {
    
    public $debug;
    public $debug_obj;
    
    public $headers;
    
    public $title;
    public $h1;
    public $description;
    
    public $url;
    
    public $real_url;
    
    public $min_img_width=220;
    public $min_img_height=220;
    
    public $post_image;
    
    
    /**
     * Min imag size for heuristic 3
     * @var int 
     */
    public $min_img_size=20000;
    
    private $loop2_allowed_formats=array('.jpg', 'jpeg');
    
    private $T0;
    
    public $_exec_time;
    
    public $heuristic=2;
    
    
    
    public function __construct($url, $contents=array(), $debug=false, $heuristic=2) {
	
	$this->T0 = microtime(true);
	
	$this->debug=$debug;
	
	$this->url=$url;
	
	$this->heuristic=$heuristic;
	
	if(!is_array($contents) || count($contents)==0){
	    
	    list($html,$cinfo)=$this->get_content($url);
	}
	else{
	    list($html,$cinfo)=$contents;
	}
	
	$this->parse($html, $cinfo);
    }
    
    
    
    
    
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
    
    
    public function get_content(){
	
	
	return mycurl($this->url);
    }
    
    /**
     * Parse and find contents from URL
     * 
     */
    public function parse($html, $cinfo) {
	
        // Make first request
		
	
	if($cinfo['http_code']=='200'){
	
	    // Load class simple dom for page parsing
	    $dom = new simple_html_dom();
	    $dom->load($html);

	    $this->real_url=$cinfo['url'];
	    
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

		if($this->heuristic==2){
		    $img_src=$this->heuristic_image_2($images);
		}
		else if($this->heuristic==3){
		    $img_src=$this->heuristic_image_3($images);
		}
	    }

	    $this->post_image= ($img_src===null) ? null : $this->myUrlEncode($img_src);

	    // End IMG Parsing -----------------------------------------------------
	
	}
	
	
	$this->_exec_time=round((microtime(true) - $this->T0), 3);
	
	return $this;

    }
    
    
    
    
    
    
    /**
     * The images can be as http://domain.com/image.xx or /relative/img.xxx
     * @param type $path
     * @return type 
     */
    protected function path_image($path){
	
	if(strpos($path,'http')===false){
	    
	    $pp=parse_url($this->real_url);

	    if($path{0}=="/"){

		return $pp['scheme']."://".$pp['host'].$path;
	    }
	    else if(substr($path,0,3)=='../'){
		
		return $this->relative_image($this->real_url, $path);
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
		
		// is domain in stoplist?
		if(in_stoplist($img_tmp_src)){
		    
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
    
    
    
    
    /**
     * Heuristic method 3, based on CURL multi
     * 
     * 
     * @param type $images
     * @return null 
     */
    protected function heuristic_image_3($images){
	
	if(!is_array($images)) {
	    
	    return null;
	}
	else{
	    
	    $url_img_for_parsing=array();
	    
	    $img_src=null;
	
	    foreach($images as $i=>$imgs){

		// Change path to absolute?
		$img_tmp_src=  $this->path_image($imgs->src);

		// filter on formats: if is not in allowed skip and continue
		if(!in_array(strtolower(substr($img_tmp_src, -4, 4)), $this->loop2_allowed_formats )){

		    continue;
		}
		
		// is domain in stoplist?
		else if(in_stoplist($img_tmp_src)){
		    
		    continue;
		}
		
		else{
		    
		    $url_img_for_parsing[]=$img_tmp_src;
		}
	    }

	    $mh = curl_multi_init();

	    $handles = array();

	    for($i=0;$i< count($url_img_for_parsing);$i++){

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url_img_for_parsing[$i]);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		//curl_setopt($ch, CURLOPT_FOLLOWLOCATION,true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);

		curl_setopt($ch, CURLOPT_TIMEOUT,5);

		curl_multi_add_handle($mh,$ch);

		$handles[] = $ch;
	    }

	    $running=null;
	    
	    do {
		
		curl_multi_exec($mh,$running);
		
	    } while ($running > 0);
	    
	    

	    for($i=0;$i< count($handles);$i++){
		
		curl_multi_getcontent($handles[$i]);

		$ii=curl_getinfo($handles[$i]);
		
		// print_r($ii);
		
		// is image?
		if(strpos($ii['content_type'],'image/')!==false){
		    
		    if($ii['download_content_length']>$this->min_img_size) {
			
			$img_src=$ii['url'];
			
			curl_multi_remove_handle($mh,$handles[$i]);
			curl_multi_close($mh);
			
			if($this->debug){
			    $this->debug_obj->found_img_n=$i;
			    $this->debug_obj->heuristic=3;
			}
			
			return $img_src;
		    }
		}

		curl_multi_remove_handle($mh,$handles[$i]);
	    }


	    curl_multi_close($mh);
	    
	    return null;
	}
    }
    
    

}






function mycurl($url){
    
    $ch = curl_init();
    
    $a=array();

    curl_setopt($ch,CURLOPT_URL, $url);
    curl_setopt($ch,CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION,true);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch,CURLOPT_TIMEOUT,10);
    // curl_setopt($ch, CURLOPT_HEADERFUNCTION, 'curlHeaderCallback'); 
    //curl_setopt($ch, CURLINFO_HEADER_OUT, true); 


    $a[0]= @curl_exec($ch);
    $a[1]=null;

    if(!curl_errno($ch)){

	$a[1]=curl_getinfo($ch);
    }
    
    return $a;
}









function in_stoplist($url){
    
    global $stoplist;
    
    $n=count($stoplist);
    
    $out=false;
    
    for($i=0;$i<$n;$i++){
	if(stristr($url,$stoplist[$i])!==false){
	    
	    $out=true;
	    break;
	}
    }

    return $out;
}






