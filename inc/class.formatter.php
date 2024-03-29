<?php

/**
 *
 * Tagosphere formatter
 *  
 */
class T2P_FormatterXML {
    
    
    private $XML;
    
    private $TAG;
    
    private $id_group;
    
    
    public function __construct($results, $TAG, $id_group) {
	
	$this->TAG=str_replace("#", "", $TAG);
	
	$this->id_group=$id_group;
	
	$this->main($results);
	
    }
    
    
    public function __print($header=true){
	
	if($header)
	    header("Content-type: text/xml");
	
	print $this->XML;
	
    }
    
    
    public function __save($path=''){
	
	if($path!='' && substr($path,-1,1)!='/'){
	    $path.="/";
	}
	
	$xml_cached= $path . $this->TAG.".xml";
	
	if(is_writable($path)){

	    $fp=fopen($xml_cached,"w");
	    $feedback=fwrite($fp,$this->XML);
	    fclose($fp);
	}
	else{
	    error_log("File $xml_cached not writable!"
		, 1
                ,MAIL_ERROR_LOG);
	    
	    trigger_error("File $xml_cached not writable!", E_USER_ERROR);
	}
	
	return $feedback;
	
    }
    
    
    public function main($results){
	
	
	
	$XML="<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
	$XML.="<tagosphere>\n";
	$XML.="<tags>".$this->TAG."</tags>\n";
	$XML.="<date_creation time=\"".time()."\">".date('c')."</date_creation>\n";
	$XML.="<n_items>".count($results->pages)."</n_items>\n";
	$XML.="<rows>\n";

	foreach($results->pages as $obj){
	    
	    $XML.=$this->node($obj,$this->id_group,$this->TAG);
	}

	$XML.="</rows>\n";

	$XML.="</tagosphere>\n";

	$this->XML=$XML;

    }
    
    
    
    
    private function node($obj){
	
	$XMLROW='<row>';
	$XMLROW.="<icon>http://twitter.com/phoenix/favicon.ico</icon>\n";
	$XMLROW.="<permalink><![CDATA[".$obj->real_url."]]></permalink>\n";
	$XMLROW.="<url><![CDATA[".$obj->url."]]></url>\n";
	$XMLROW.="<title><![CDATA[".trim($obj->title)."]]></title>\n";
	$XMLROW.="<content><![CDATA[".trim($obj->description)."]]></content>\n";
	$XMLROW.="<date>". date("Y-m-d H:i",  strtotime($obj->tweet->created_at))."</date>\n";
	$XMLROW.="<uuid>". $obj->tweet->id."</uuid>\n";
	$XMLROW.="<dae_gid>". $this->id_group ."</dae_gid>\n";
	$XMLROW.="<ht>". $this->TAG ."</ht>\n";
	$XMLROW.="<image>".$obj->img."</image>\n";
	$XMLROW.="</row>";

	return $XMLROW;
    }

    
}






/**
 * HTML Formatter
 * 
 * For debug 
 */
class T2P_FormatterHTML {
    
    
    private $output;
    
    public function __construct($results) {
	
	$this->output = $this->main($results);
	
    }
    
    public function __print(){
	
	return $this->output;
    }
    
    
    private function main($results){
	
	$HTML='';
	
	// <!DOCTYPE ...
	// <html>
	// ... <body>...
	
	$HTML.="<div class=\"results\">\n";

	foreach($results->pages as $obj){

	    $HTML.=$this->node($obj);
	}

	$HTML.="</div>\n";

	return $HTML;
    }
    
    private function node($obj){
	
	
	$ROW="<div class=\"result\" id=\"".$obj->tweet->id."\">\n";
	$ROW.="<h2>".utf8_decode($obj->title)."</h2>\n";
	$ROW.="<p>".$obj->description."</p>\n";
	$ROW.="<img src=\"".$obj->img."\" alt=\"\"/>\n";
	$ROW.="<p><a href=\"".$obj->real_url."\">".$obj->real_url."</a> (".$obj->url.")</p>\n";
	$ROW.="<p><img src=\"".$obj->tweet->profile_image_url."\" width=\"25\" />"
		." Posted by @".$obj->tweet->from_user." (".$obj->tweet->from_user_name.") </p>\n";
	$ROW.="<p>Tweet: <code>".$obj->tweet->text."</code></p>\n";
	$ROW.="</div>\n";


	return $ROW;
    }

    
}
