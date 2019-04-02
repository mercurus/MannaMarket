<?php 
	session_destroy();
	session_start();
	$_SESSION['loggedOut'] = 'logout';
	header('location:index.php');
	exit;
?>