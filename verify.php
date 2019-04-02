<?php
	session_start();
	require_once('initializeConn.php');
	//sanitize the inputs
	$mysqli->query('SET NAMES utf8'); //helps protecy against sql injection
	$myUsername = $_POST['username'];//mysql_real_escape_string($_POST['username']);
	$myPassword = $_POST['password'];//mysql_real_escape_string($_POST['password']);
	//query the database (LIKE BINARY ensures cAsE sEnSiTiViTy)
	$sql = "SELECT p.personid, p.name, u.authoritylevel, u.userenabled, d.alias, d.name AS mmname
			FROM users u, person p, family f, mmlocations d
			WHERE u.username LIKE BINARY '" . $myUsername . "' 
			AND u.password LIKE BINARY '" . $myPassword . "'
			AND p.personid = u.personid
			AND p.familyid = f.familyid
			AND f.primarylocation = d.alias;";
	$result = $mysqli->query($sql);
	//'fetch' 1 result into the query. allows us to now treat it like an array.
	$user = $result->fetch_assoc();
	
	if($user['personid'] != null) {
		//make sure user is enabled
		if($user['userenabled'] == '1') {
			//set authority level (which displays/hides different functions), then the rest
			$_SESSION['authorityLevel'] = (int)$user['authoritylevel'];
			$_SESSION['username'] = $user['name'];
			$_SESSION['personid'] = $user['personid'];
			$_SESSION['locAlias'] = $user['alias'];
			$_SESSION['locName'] = $user['mmname'];
			header('location:home.php');
		}
		//user is disabled
		else {
			$_SESSION['loggedOut'] = 'disabled';
			header('location:index.php');
		}
	}
	else if($myPassword == null || $myUsername == null) {
		$_SESSION['loggedOut'] = 'connprob';
		header('location:index.php');
	}
	else {
		$_SESSION['loggedOut'] = 'invalid';
		header('location:index.php');
	}
?>