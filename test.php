<?php

require_once("./inc/class.curl.php");
require_once("./inc/simple_html_dom.php");
require_once("./class.tweets2page.php");


abstract class T2P_Formatter {
    
    
    
    
}


class TP2_FormatterXML {
    
    
    public function main($TAG, $results, $path_xml_cached="", $print_results=false){
	
	$xml_cached= $path_xml_cached."/".$TAG.".xml";
	
	$XML="<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
	$XML.="<tagosphere>\n";
	$XML.="<tags>".$TAG."</tags>\n";
	$XML.="<date_creation time=\"".time()."\">".date('c')."</date_creation>\n";
	$XML.="<n_items>".count($results)."</n_items>\n";
	$XML.="<rows>\n";

	foreach($results as $obj){

	    $XML.=self::node($obj,$id_group,$TAG);
	}

	$XML.="</rows>\n";

	$XML.="</tagosphere>\n";

	$fp=fopen($xml_cached,"w");
	$feedback=fwrite($fp,$XML);
	fclose($fp);

	return array($XML, $feedback);
    }
    
    
    
    
    private function node($obj, $id_group, $TAG){

	$XMLROW='<row>';
	$XMLROW.="<icon>http://twitter.com/phoenix/favicon.ico</icon>\n";
	$XMLROW.="<permalink><![CDATA[".$obj->page->real_url."]]></permalink>\n";
	$XMLROW.="<url><![CDATA[".$obj->page->url."]]></url>\n";
	$XMLROW.="<title><![CDATA[".trim($obj->page->title)."]]></title>\n";
	$XMLROW.="<content><![CDATA[".trim($obj->page->description)."]]></content>\n";
	$XMLROW.="<date>". date("Y-m-d H:i",$obj->tweet->time)."</date>\n";
	$XMLROW.="<uuid>". $obj->tweet->id."</uuid>\n";
	$XMLROW.="<dae_gid>". $id_group ."</dae_gid>\n";
	$XMLROW.="<ht>". $TAG ."</ht>\n";
	$XMLROW.="<image>".$obj->page->img."</image>\n";
	$XMLROW.="</row>";

	return $XMLROW;
    }

    
}


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
	$ROW.="<p><a href=\"".$obj->url."\">".$obj->url."</a></p>\n";
	$ROW.="<p><img src=\"".$obj->tweet->profile_image_url."\" width=\"25\" />"
		." Posted by @".$obj->tweet->from_user." (".$obj->tweet->from_user_name.") </p>\n";
	$ROW.="<p>Tweet: <code>".$obj->tweet->text."</code></p>\n";
	$ROW.="</div>\n";


	return $ROW;
    }

    
}




// TEST: #da12

$s="#da12data";

$Pages = new Tweets2Page();

$Pages->debug=true;

$Pages->skip_sites[]="daa.ec.europa.eu";

$results=$Pages->parseTweets($s, 10);



// Formatters:

//$F = new T2P_FormatterXML($results);
$F = new T2P_FormatterHTML($results);

print $F->__print();

print $results->exec_time;

