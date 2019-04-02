<?php
	session_start(); //Start the session
	$info = '';
	if(isset($_SESSION['loggedOut'])){
		if($_SESSION['loggedOut'] == 'invalid') {
			$info = 'Incorrect username or password';
		}
		else if($_SESSION['loggedOut'] == 'expired') {
			$info = 'Session expired';
		}
		else if($_SESSION['loggedOut'] == 'logout') {
			$info = 'You have logged out';
		}
		else if($_SESSION['loggedOut'] == 'connprob') {
			$info = 'Problem connecting with database';
		}
		else if($_SESSION['loggedOut'] == 'notin') {
			$info = 'Please log in';
		}
		else if($_SESSION['loggedOut'] == 'disabled') {
			$info = 'Your account has been disabled';
		}
	} 
	session_destroy();
	
	$title = 'Login';
	include('appHeader.php'); 
?>

<form action="verify.php" method="post">
	<table align="center">
    	<h2 style="text-align: center;">Please enter your Username and Password</h2>
        <p style="text-align: center;">(For Staff and Volunteers)</p>
		<tr>
			<td>Username</td>
			<td><input type="text" name="username" maxlength="12" value="dev" required autofocus /></td>
		</tr>
		<tr>
			<td>Password</td>
			<td><input type="password" name="password" maxlength="20" value="dev" required /></td>
		</tr>
		<tr>
			<td colspan="2"><center><input type="submit" class="btn" value="Login" ></center></td>
		</tr>
		<tr>
			<td colspan="2" ><span id="sqlInfo"><?php echo $info; ?></span></td>
		</tr>
	</table>
</form>
        
<script type="text/javascript">
	window.onload = function() {
		//fade $info reply
		setTimeout(fadeMessage, 3000);
	}

	var opacity = 100;
	function fadeMessage() {
		if(opacity > 0) {
			opacity -= 10;
			document.getElementById('sqlInfo').style.opacity = opacity / 100;
			setTimeout(fadeMessage, 100);
		}
	}
</script>

<?php include('appFooter.php'); ?>

