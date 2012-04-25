<?php

require_once("./inc/conf.php");
require_once("./inc/class.dbcache.php");
require_once("./inc/simple_html_dom.php");
require_once("./inc/class.tweets2page.php");
require_once("./inc/class.formatter.php");








// TEST: #da12

$s="#ijf12";

$Pages = new Tweets2Page();

$Pages->set_cache(false);

$Pages->set_heuristic(2);

$Pages->add_skyp_site("daa.ec.europa.eu");

$results=$Pages->parseTweets($s, 20);

print "<pre>\n";
print $Pages->log();




$Pages2 = new Tweets2Page();

$Pages2->set_cache(false);

$Pages2->set_heuristic(3);

$Pages2->add_skyp_site("daa.ec.europa.eu");

$results2=$Pages2->parseTweets($s, 20);

print $Pages2->log();


print "</pre>\n";



// Formatters:

echo "<hr />\n";

//$F = new T2P_FormatterXML($results, $s, 4);
$F = new T2P_FormatterHTML($results);

print $F->__print();

echo "<hr />\n";
 
 //$F = new T2P_FormatterXML($results, $s, 4);
$F = new T2P_FormatterHTML($results2);

print $F->__print();

// $F->__save("/tmp/xml/");



