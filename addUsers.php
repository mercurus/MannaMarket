<?php
	$title = 'Users';
	$requiredAuthorityLevel = 2;
	$menuArea = 'add';
	include('appHeader.php'); 
	
	$info = '';
	$repopulatePost = false; //could be changed later in the nested ifs
	$entryType = isset($_POST['entryType']) ? $_POST['entryType'] : 'new'; //used to set correct radio button, and change checkbox
	$personid = isset($_POST['personList']) ? (int)$_POST['personList'] : -1; //only used to reset personList <select>
	$userEnabled = isset($_POST['userEnabled']) ? 1 : 0; //used for the column in users of the same name
	
	//if page has been [re]submitted
	if(isset($_POST['viewstate'])) {
		//new entry
		if($_POST['viewstate'] == 'new') {
			//make sure passwords match
			if($_POST['password'] == $_POST['verifyPassword']) {
				//begin the prepared statement
				if(!($stmt = $mysqli->prepare('INSERT INTO users VALUES (?,?,?,?,?)'))) {
		     		echo 'Prepare failed: (' . $mysqli->errno . ') ' . $mysqli->error;
				}
				
				//bind the parameters in the prepared statement
				if(!($stmt->bind_param('ssiii'
					, $_POST['username']
					, $_POST['password']
					, $_POST['authorityLevel']
					, $_POST['personList']
					, $userEnabled))) {
				    echo 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error;
				}
				
				//execute the prepared statement
				if(!$stmt->execute()) {
				    echo 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error;
				}
				else {
					//everything was successful, so tell the user
					$info = 'Insert Successful';
				}
			}
			//if passwords don't match
			else {
				$info = 'Passwords do not match';
				$repopulatePost = true;
			}
		}
		//update existing info
		elseif($_POST['viewstate'] == 'update') {
			//make sure passwords match
			if($_POST['password'] == $_POST['verifyPassword']) {
				//begin the prepared statement
				if(!($stmt = $mysqli->prepare('	UPDATE users
												SET username = ?,
												password = ?,
												authoritylevel = ?,
												userenabled = ?
												WHERE personid = ' . $_POST['personList']))) {
		     		echo 'Prepare failed: (' . $mysqli->errno . ') ' . $mysqli->error;
				}
				
				//bind the parameters in the prepared statement
				if(!($stmt->bind_param('ssii'
					, $_POST['username']
					, $_POST['password']
					, $_POST['authorityLevel']
					, $userEnabled))) {
				    echo 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error;
				}
				
				//execute the prepared statement
				if(!$stmt->execute()) {
				    echo 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error;
				}
				else {
					//everything was successful, so tell the user
					$info = 'Update Successful';
					//requery to get person info again
					$result = $mysqli->query('SELECT * FROM users WHERE personid = ' . $_POST['personList']);
					$person = $result->fetch_assoc();
				}
			}
			//if passwords don't macth
			else {
				$info = 'Passwords do not match';
				$repopulatePost = true;
			}
		}
		//a particular person has been selected
		else if($_POST['viewstate'] == 'editPerson') {
			$result = $mysqli->query('SELECT * FROM users WHERE personid = ' . $_POST['personList']);
			$person = $result->fetch_assoc();
		}
	}
	
	$details = array();
	if(isset($person)) { // = the query happened, and there's data
		foreach($person as $key => $value) {
			$details[$key] = (string)$value;
		}
	}
	elseif($repopulatePost) {
		$details = array(	'username' => $_POST['username'],
							'password' => '',
							'authoritylevel' => $_POST['authorityLevel'],
							'userenabled' => (string)$userEnabled);
	}
	else { // = there's no query, so blank the fields. Yes they're blank by default, but there are set statements down there that expect data.
		$details = array(	'username' => '',
							'password' => '',
							'authoritylevel' => '3',
							'userenabled' => '1');
	}
?>

<form id="addUsers" name="addUsers" method="post" action="addUsers.php">
	<input type="hidden" name="viewstate" id="viewstate" value="" />
	<table>
		<tr>
			<td colspan="2"><span id="sqlInfo"><?php echo $info ?>.</span></td>
		</tr>
		<tr>
			<th>Entry</th>
			<td>
				<input type="radio" class="css-checkboxR" name="entryType" id="new" value="new" onclick="cycleForm('newEntry');" /><label for="new" class="css-labelR">New</label>
				<?php if($_SESSION['authorityLevel'] == 1) { ?>
				<input type="radio" class="css-checkboxR" name="entryType" id="edit" value="edit" onclick="cycleForm('editEntry');" /><label for="edit" class="css-labelR">Edit</label>
				<?php } ?>
			</td>
		</tr>
		<tr>
			<th>
				Volunteer List
				<br />
				<span class="note">
					Note: Users need to be<br />
					logged as a volunteer<br />
					<a href="addVolunteers.php">Add Voulnteers</a>
				</span>	
			</th>
			<td>
				<select id="personList" name="personList" size="7" onchange="if(document.getElementById('edit').checked == true) {cycleForm('editPerson');}">
					<?php
						$sql = 'SELECT p.personid, p.name
								FROM person p
								WHERE p.personid';
						//choose whether we're including or excluding users in the existing list
						if(!isset($_POST['entryType']) || $_POST['entryType'] == 'new') {$sql .= ' NOT';} 
						$sql .= ' IN
								(SELECT u.personid
								FROM users u)
								ORDER BY p.name';
						$result = $mysqli->query($sql);
						while($row = $result->fetch_assoc()) {
							echo '<option value="' . $row['personid'] . '">' . $row['name'] . '</option>' ."\n";
						}
					?>
				</select>
			</td>
		</tr>
		<tr>
			<th>Username</th>
			<td><input type="text" id="username" name="username" value="<?php echo $details['username']; ?>" maxlength="12" /></td>
		</tr>
		<tr>
			<th>Password</th>
			<td><input type="password" id="password" name="password" value="<?php echo $details['password']; ?>" maxlength="20" /></td>
		</tr>
		<tr>
			<th>Verify Password</th>
			<td><input type="password" id="verifyPassword" name="verifyPassword" value="<?php if($details['password'] != '') {echo $details['password'];} ?>" maxlength="20" /></td>
		</tr>
		<tr>
			<th>Authority Level</th>
			<td>
				<select id="authorityLevel" name="authorityLevel">
					<option value="3">3</option>
					<?php if($_SESSION['authorityLevel'] == 1) { //essentially, level 2s can only create level 3s ?>
					<option value="2">2</option>
					<option value="1">1</option>
					<?php } ?>
				</select>
			</td>
		</tr>
		<tr>
			<th>User Enabled</th>
			<td><input type="checkbox" class="css-checkbox" id="userEnabled" name="userEnabled" /><label class="css-label" for="userEnabled"></label>
			</td>
		</tr>
		<tr>
			<td></td>
			<td><input type="button" class="btn" value="Save" onclick="trySubmit();" /></td>
		</tr>
	</table>
</form>

<script type="text/javascript">
	window.onload = function() {
		//set comboboxes to posted/default value
		document.getElementById('personList').value = '<?php echo $personid; ?>';
		document.getElementById('authorityLevel').value = '<?php echo $details['authoritylevel']; ?>';	
		//set proper radio/check button
		document.getElementById('<?php echo $entryType; ?>').checked = true;
		document.getElementById('userEnabled').checked = <?php echo ($details['userenabled'] == 1 ? 'true' : 'false'); ?>;
		//fade $info reply
		setTimeout(fadeMessage, 3000);
	}

	function cycleForm(state) {
		document.getElementById('viewstate').value = state;
		document.getElementById('addUsers').submit();
	}
	
	function trySubmit() {
		//Error Checking
		
		//if you haven't selected something on the list
		if(document.getElementById('personList').selectedIndex == -1) {
			alert('Please select a volunteer');
			return;
		}
		
		//see if required text fields are filled out
		var ids = ['verifyPassword', 'password', 'username'];
		var success = true;
		var lastWrong = '';
		for(var i = 0; i < ids.length; i++) {
			if(document.getElementById(ids[i]).value.trim() == '') {
				success = false;
				lastWrong = ids[i];
			}
		}
		
		if(success) {
			//decide the viewstate
			if(document.getElementById('new').checked == true) {cycleForm('new');}
			else {cycleForm('update');}
		}
		else {
			alert('Please fill out ' + lastWrong);
			document.getElementById(lastWrong).focus();
		}
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
