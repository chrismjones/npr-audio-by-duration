<?php
// db CONNECT
define('DB_HOST', 'localhost');
define('DB_USER', '');
define('DB_PASSWORD', '');
define('DB_NAME', '');
$status="";
if ($dbc = mysql_connect (DB_HOST, DB_USER, DB_PASSWORD)) {
    if (!mysql_select_db (DB_NAME)) $status="Could not select the database! MySQL Error: " . mysql_error();
}else{ $status="Could not connect to MySQL! MySQL Error: " . mysql_error(); }
mysql_set_charset('utf8'); 
