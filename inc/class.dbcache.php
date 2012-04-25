<?php



class dbcache {
    
    public function store($permalink, $date, $o){
	
	global $mysqli;
	
	$o=$mysqli->real_escape_string(json_encode($o));
	
	$q=$mysqli->query("INSERT INTO __feedparser (h,t,o) VALUES ('".md5($permalink)."', '".$date."','$o')");
	
	return $mysqli->affected_rows;
    }
    
    public function get($permalink){
	
	global $mysqli;
	
	$q=$mysqli->query("SELECT o FROM __feedparser WHERE h='".md5($permalink)."'");
	
	$RS= $q->fetch_assoc();
	
	$o= json_decode($RS['o']);
	
	return $o;
    }
    
    public function exists($permalink){
	
	global $mysqli;
	
	$q=$mysqli->query("SELECT 1 FROM __feedparser WHERE h='".md5($permalink)."' LIMIT 1");
	
	return (bool) $q->num_rows;
    }
    
    public function get_last_datetime(){
	
	global $mysqli;
	
	$q=$mysqli->query("SELECT MAX(d) FROM __feedparser");
	
	list($max_date)=$q->fetch_row();
	
	return $max_date;
	
    }
    
    public function __destruct() {
	
	global $mysqli;
	
	$mysqli->close();
    }
    
  
    
}