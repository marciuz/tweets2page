<?php

$db1=array(
    'database' => '',
    'username' => '',
    'password' => '',
    'host' => '',
    'port' => '',
    );

$mysqli = @new mysqli($db1['host'], $db1['username'], $db1['password'], $db1['database']);

/* check connection */
if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}

// Load a stop list
$stoplist=explode("\n",file_get_contents(dirname(__FILE__)."/stoplist.dat"));
