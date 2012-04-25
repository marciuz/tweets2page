<?php
/**
 *
 * 
 * --
-- Struttura della tabella `__blacklist`
--

CREATE TABLE IF NOT EXISTS `__blacklist` (
  `h` char(32) NOT NULL,
  `l` text COMMENT 'Url',
  `al` text COMMENT 'Alias url',
  PRIMARY KEY (`h`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struttura della tabella `__feedparser`
--

CREATE TABLE IF NOT EXISTS `__feedparser` (
  `h` char(32) NOT NULL,
  `tag` varchar(25) NOT NULL,
  `t` datetime NOT NULL,
  `o` blob,
  PRIMARY KEY (`h`,`tag`),
  KEY `i_hash` (`h`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
 * 
 * 
 *  
 */


class dbcache {
    
    public function store($permalink, $date, $o, $tag=''){
	
	global $mysqli;
	
	$o=$mysqli->real_escape_string(json_encode($o));
	$tag=$mysqli->real_escape_string($tag);
	
	$q=$mysqli->query("INSERT INTO __feedparser (h,t,o,tag) 
		VALUES ('".md5($permalink)."', '".$date."','$o', '$tag')");
	
	return $mysqli->affected_rows;
    }
    
    public function get($permalink){
	
	global $mysqli;
	
	$q=$mysqli->query("SELECT o FROM __feedparser WHERE h='".md5($permalink)."'");
	
	$RS= $q->fetch_assoc();
	
	$o= json_decode($RS['o']);
	
	return $o;
    }
    
    public function clear(){
	
	global $mysqli;
	
	$q=$mysqli->query("DELETE FROM __feedparser");
	
	return $mysqli->affected_rows;
    }
    
    public function exists($permalink, $tag=''){
	
	global $mysqli;
	
	if($tag!=''){
	    $add=" AND tag='".$mysqli->real_escape_string($tag)."'";
	}
	else{
	    $add='';
	}
	
	$q=$mysqli->query("SELECT 1 FROM __feedparser WHERE h='".md5($permalink)."' $add LIMIT 1");
	
	return (bool) $q->num_rows;
    }
    
    public function get_last_datetime(){
	
	global $mysqli;
	
	$q=$mysqli->query("SELECT MAX(d) FROM __feedparser");
	
	list($max_date)=$q->fetch_row();
	
	return $max_date;
	
    }
    
    public function add_to_blacklist($permalink, $real_url){
	
	global $mysqli;
	
	$l=$mysqli->real_escape_string($permalink);
	$al=$mysqli->real_escape_string($real_url);
	
	$q=$mysqli->query("INSERT IGNORE INTO __blacklist (h,l, al) 
			    VALUES ('".md5($permalink)."', '".$l."', '".$al."')");
	
	return $mysqli->affected_rows;
    }
    
    public function get_blacklist(){
	
	global $mysqli;
	
	$q= $mysqli->query("SELECT l FROM __blacklist");
	
	$blacklist=array();
	
	while($RS=$q->fetch_row()){
	    
	    $blacklist[]=$RS[0];
	}
	
	return $blacklist;
    }
    
    public function in_blacklist($permalink){
	
	global $mysqli;
	
	$q=$mysqli->query("SELECT 1 FROM __blacklist WHERE h='".md5($permalink)."' LIMIT 1");
	
	return (bool) $q->num_rows;
    }
    
    
    public function clear_blacklist(){
	
	global $mysqli;
	
	$q=$mysqli->query("DELETE FROM __blacklist");
	
	return $mysqli->affected_rows;
    }
    
    
    public function __destruct() {
	
	global $mysqli;
	
	$mysqli->close();
    }
    
    
  
    
}