<?php
	//if $title = 'Login' then ignore most parts
	if($title != 'Login') {
		require_once('maintainLogin.php'); //initializes session, ensures you're actually logged in
		require_once('initializeConn.php'); //connect to database	
	
		//set menu classes, because depending on page, one of the higher <li>s needs the class 'active'
		$clsHome = $clsLog = $clsAdd = $clsAdmin = '';
		switch($menuArea) {
			case 'home': $clsHome = 'active'; break;
			case 'log': $clsLog = 'active'; break;
			case 'add': $clsAdd = 'active'; break;
			case 'admin': $clsAdmin = 'active'; break;
		}
		//then add the class has-sub, necessary for the css menu. $clsHome doesn't get it because Home doesn't have subs
		$clsLog .= ' has-sub';
		$clsAdd .= ' has-sub';
		$clsAdmin .= ' has-sub';
	}
?>
<!--  -->
<!DOCTYPE html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<meta name="viewport" content="width=device-width">
	<link href="http://fonts.googleapis.com/css?family=Roboto:400,500,700,700italic,300,100italic,400italic" rel="stylesheet" type="text/css">
	<link rel="stylesheet" href="resources/styles.css" type="text/css"/>
	<!-- Jquery Accordian  style navigation -->
	<link rel="stylesheet" href="resources/nav-styles.css" type="text/css">
   	<script src="http://code.jquery.com/jquery-latest.min.js" type="text/javascript"></script>
   	<script type="text/javascript">
   		//gives <select>s a highlight
   		$(document).ready(function (event) {   
		    $('select').on('mouseenter', 'option', function (e) {
		        this.style.background = '#aaa';
		    });
		    $('select').on('mouseleave', 'option', function (e) {
		        this.style.background = 'none';
		    });
		});
   	</script>
   	<script src="resources/nav-script.js" type="text/javascript"></script>
	<!-- http://www.mattkruse.com/javascript/calendarpopup/ -->
	<link href="resources/calendarPopup.css" rel="stylesheet" type="text/css"/>
	<script src="resources/calendarPopup.js"></script>
	<title>MMAPP - <?php echo $title; ?></title>
</head>
<body>
	<div id="wrapper">
		<div id="contentwrapper"><!---->
			<div id="sideBar">
				<div id="logo">
					<img class="logo" src="resources/pLogo.png" alt="logo">
				</div>
				
				<?php if($title != 'Login') { //if you're not logged in, the menu is hidden ?>
				<div class="nav" id='cssmenu'><!-- Navigation Menu -->
				<ul>
					<li class="<?php echo $clsHome; ?>"><a href="home.php"><span>Home</span></a></li>
					<li class="<?php echo $clsLog; ?>"><a href="#"><span>Log</span></a>
					<ul>
						<li><a class="sub" href="logGuests.php"><span>Guests</span></a></li>
						<li><a class="sub" href="logVolunteers.php"><span>Volunteers</span></a></li>
						<?php 
							if($_SESSION['authorityLevel'] <= 2) {
								echo "\n" . '<li><a class="sub" href="logFood.php"><span>Food Pickup</span></a></li>';
							} 
						?>
					</ul>
					</li>
					<li class="<?php echo $clsAdd; ?>"><a href="#"><span>Add<?php if($_SESSION['authorityLevel'] <= 2) {echo ' or Edit';} ?></span></a>
					<ul>
						<li><a class="sub" href="addHousehold.php"><span>Households</span></a></li>
						<li><a class="sub" href="addVolunteers.php"><span>Volunteers</span></a></li>
						<?php 
							if($_SESSION['authorityLevel'] <= 2) {
								echo "\n" . '<li><a class="sub" href="addUsers.php"><span>Users</span></a></li>';
							}
						?>
					</ul>
					</li>
					<?php 
						if($_SESSION['authorityLevel'] <= 2) {
							echo "\n" . '<li class="' . $clsAdmin . '"><a href="#"><span>Admin</span></a>';
							echo "\n" . '<ul>';
							echo "\n" . '<li><a class="sub" href="reports.php"><span>Reports</span></a></li>';
							if($_SESSION['authorityLevel'] == 1) {
								echo "\n" . '<li><a class="sub" href="alterDonations.php"><span>Alter Donations</span></a></li>';
								echo "\n" . '<li><a class="sub" href="donationLocations.php"><span>Donation Locations</span></a></li>';
								echo "\n" . '<li><a class="sub" href="mmLocations.php"><span>MM Locations</span></a></li>';
							}
							echo "\n" . '</ul>';
							echo "\n" . '</li>';
						} 
					?>
				</ul>
				</div> <!--end CSS Menu-->
				<?php } //end menu hiding ?>
				
				<div class="clear"></div>
			</div> <!--end sideBar-->
			<div id="main">
				<div id="mainHeader">
					<div id="title">
						<h1><?php echo $title; ?></h1>
					</div>
					<?php if($title != 'Login') { //if you're not logged in, greeting is hidden ?> 
					<div id="greeting">
						<span>
							Welcome <?php echo $_SESSION['username']; ?>
							<p class="message">Not You?&nbsp;<a href="logout.php">Logout</a></p>
						</span>
					</div>
					<?php } ?>
				</div>
				<div id="mainContent">

