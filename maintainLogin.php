<?php
	session_start();
	//ensure that their session is valid
	if(!isset($_SESSION['username'])) {
		session_destroy();
		session_start();
		$_SESSION['loggedOut'] = 'notin';
		header('location:index.php');
		exit;
	}
	//and that it hasn't expired (60 * 30 = half hour)
	else if(isset($_SESSION['lastActivity']) && (time() - $_SESSION['lastActivity'] > 60 * 30)) { 
		session_destroy();
		session_start();
		$_SESSION['loggedOut'] = 'expired';
		header('location:index.php');
		exit;
	}
	//of if you're trying to poke in somewhere you shouldn't be
	else if($requiredAuthorityLevel < $_SESSION['authorityLevel']) {
		header('location:forms.php');
		exit;
	}
	//update last activity time stamp
	$_SESSION['lastActivity'] = time();
?>