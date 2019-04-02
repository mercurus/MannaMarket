<?php
	include('connexion/dbSettings.php');  
	$mysqli = new mysqli('127.0.0.1', $db_user, $db_pass, $db_name);
	unset($db_user, $db_pass, $db_name);
	if($mysqli->connect_errno) {
    	echo 'Failed to connect to MySQL: (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error;
		exit; 
	}
?>