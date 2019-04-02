<?php
	$title = 'MM Locations';
	$requiredAuthorityLevel = 1;
	$menuArea = 'admin';
	include('appHeader.php'); 
	
	$a = true; //used to see if there's a conflict with the alias when making a new mm location
	$info = ''; //message to user on success or error
	
	//if form is [re]submitted
	if(isset($_POST['viewstate'])) {
		//insert new location entry
		if($_POST['viewstate'] == 'new') {
			//check to see if the alias is unique, because it needs to be
			$aliasList = $mysqli->query('SELECT alias FROM mmlocations');
			while($AL = $aliasList->fetch_assoc()) {
				if($AL['alias'] == $_POST['alias']) {
					$a = false;
				}
			}
			if(trim($_POST['alias']) == '') {$a = false;} //if alias was blank, trigger repopulation
			
			if($a) { //no conflict with alias
				//begin the prepared statement
				if(!($stmt = $mysqli->prepare('INSERT INTO mmlocations VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)'))) {
		     		echo 'Prepare failed: (' . $mysqli->errno . ') ' . $mysqli->error;
				}
				
				$chk = isset($_POST['mealserved']) ? 1 : 0;
				
				//bind the parameters in the prepared statement
				if(!$stmt->bind_param('ssssssssssssis'
					, $_POST['alias']
					, $_POST['name']
					, $_POST['address']
					, $_POST['city']
					, $_POST['state']
					, $_POST['zip']
					, $_POST['contactname']
					, $_POST['phone']
					, $_POST['website']
					, $_POST['distday']
					, $_POST['registration']
					, $_POST['distribution']
					, $chk
					, $_POST['areaserved'])) {
				    echo 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error;
				}
				
				//execute the prepared statement
				if(!$stmt->execute()) {
				    echo 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error;
				}
				else {
					$info = 'Insert Successful';
				}
			}
			else { //alias is not unique, repopulate data
				$info = 'Alias is not unique';
				//since $a is now false, it will trigger the repopulation below
			}
		}
		//update existing info
		else if($_POST['viewstate'] == 'update') {
			//begin the prepared statement
			if(!($stmt = $mysqli->prepare('	UPDATE mmlocations
											SET name = ?,
											address = ?,
											city = ?,
											state = ?,
											zip = ?,
											contactname = ?,
											phone = ?,
											website = ?,
											distday = ?,
											registration = ?,
											distribution = ?,
											mealserved = ?,
											areaserved = ?
											WHERE alias = "' . $_POST['locationName'] . '"'))) {
	     		echo 'Prepare failed: (' . $mysqli->errno . ') ' . $mysqli->error;
			}
			
			//checkbox = bit/boolean
			$chk = isset($_POST['mealserved']) ? 1 : 0;
			
			//bind the parameters in the prepared statement
			if(!$stmt->bind_param('sssssssssssis'
				, $_POST['name']
				, $_POST['address']
				, $_POST['city']
				, $_POST['state']
				, $_POST['zip']
				, $_POST['contactname']
				, $_POST['phone']
				, $_POST['website']
				, $_POST['distday']
				, $_POST['registration']
				, $_POST['distribution']
				, $chk
				, $_POST['areaserved'])) {
			    echo 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error;
			}
			
			//execute the prepared statement
			if(!$stmt->execute()) {
			    echo 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error;
			}
			else {
				//everything worked, so tell user
				$info = 'Congratulations! Update Successful';
				//requery so data gets repopulated
				$result = $mysqli->query('SELECT * FROM mmlocations WHERE alias = "' . $_POST['locationName'] . '"');
				$loc = $result->fetch_assoc();
			}
		}
		elseif($_POST['viewstate'] == 'cycle') {
			$result = $mysqli->query('SELECT * FROM mmlocations WHERE alias = "' . $_POST['locationName'] . '"');
			$loc = $result->fetch_assoc();
		}
	}
	
	$details = array();
	if(isset($loc)) { // = the query happened, and there's data
		foreach($loc as $key => $value) {
			$details[$key] = $value;
		}
	}
	elseif(!$a) { // = alias was not unique or was blank, repopulate the data
		$chk = isset($_POST['mealserved']) ? 1 : 0;
		$details = array(	'name' => $_POST['name'],
							'address' => $_POST['address'],
							'city' => $_POST['city'],
							'state' => $_POST['state'],
							'zip' => $_POST['zip'],
							'contactname' => $_POST['contactname'],
							'phone' => $_POST['phone'],
							'website' => $_POST['website'],
							'registration' => $_POST['registration'],
							'distribution' => $_POST['distribution'],
							'mealserved' => $chk,
							'areaserved' => $_POST['areaserved']);
	}
	else { // = there's no query, so blank the fields. Yes they're blank by default, but there are set statements down there that expect data.
		$details = array(	'name' => '',
							'address' => '',
							'city' => '',
							'state' => 'MN',
							'zip' => '',
							'contactname' => '',
							'phone' => '',
							'website' => '',
							'registration' => '',
							'distribution' => '',
							'mealserved' => 0,
							'areaserved' => '');
	}
?>

<form id="mmLocations" name="mmLocations" method="post" action="mmLocations.php">
	<input type="hidden" name="viewstate" id="viewstate" value="" />
	<table>
		<tr>
			<td colspan="2"><span id="sqlInfo"><?php echo $info ?>.</span></td>
		</tr>
		<tr>
			<th>MM Location</th>
			<td>
				<select id="locationName" name="locationName" onchange="cycleForm('cycle');">
					<option value="newLocation">-- Add New Location --</option>
	        		<?php 
						$result = $mysqli->query('SELECT alias, name FROM mmlocations');
						while($row = $result->fetch_assoc()) {
							echo '<option value="' . $row['alias'] . '">' . $row['name'] . '</option>' . "\n";
						}
					?>
	        	</select>
        	</td>
		</tr>
		<tr>
			<th>Alias</th>
			<td>
				<input type="text" id="alias" name="alias" maxlength="4" <?php if(isset($_POST['locationName']) && $_POST['locationName'] != 'newLocation') {echo 'value="' . $_POST['locationName'] . '" disabled';} ?> />
				<img src="resources/questionMark.png" title="This is the 4 character ID for the MM location entry.">
			</td>
		</tr>
		<tr>
			<th>Name</th>
			<td><input type="text" id="name" name="name" value="<?php echo $details['name']; ?>" size="40" maxlength="50" /></td>
		</tr>
		<tr>
			<th>Address</th>
			<td><input type="text" id="address" name="address" value="<?php echo $details['address']; ?>" size="40" maxlength="50" /></td>
		</tr>
		<tr>
			<th>City</th>
			<td><input type="text" id="city" name="city" value="<?php echo $details['city']; ?>" maxlength="20" /></td>
		</tr>
		<tr>
			<th>State</th>
			<td><input type="text" id="state" name="state" value="<?php echo $details['state']; ?>" maxlength="2" /></td>
		</tr>
		<tr>
			<th>Zip</th>
			<td><input type="text" id="zip" name="zip" value="<?php echo $details['zip']; ?>" maxlength="5" /></td>
		</tr>
		<tr>
			<th>Contact Name</th>
			<td><input type="text" id="contactname" name="contactname" value="<?php echo $details['contactname']; ?>" maxlength="50" /></td>
		</tr>
		<tr>
			<th>Phone</th>
			<td><input type="text" id="phone" name="phone" value="<?php echo $details['phone']; ?>" maxlength="20" /></td>
		</tr>
		<tr>
			<th>Website</th>
			<td><input type="text" id="website" name="website" value="<?php echo $details['website']; ?>" maxlength="100" /></td>
		</tr>
		<tr>
			<th>Distribution Day</th>
			<td>
				<select id="distday" name="distday">
					<option value="Sunday">Sunday</option>
					<option value="Monday">Monday</option>
					<option value="Tuesday">Tuesday</option>
					<option value="Wednesday">Wednesday</option>
					<option value="Thursday">Thursday</option>
					<option value="Friday">Friday</option>
					<option value="Saturday">Saturday</option>
	        	</select>
			</td>
		</tr>
		<tr>
			<th>Registration Time</th>
			<td><input type="text" id="registration" name="registration" value="<?php echo $details['registration']; ?>" maxlength="20" /></td>
		</tr>
		<tr>
			<th>Distribution Time</th>
			<td><input type="text" id="distribution" name="distribution" value="<?php echo $details['distribution']; ?>" maxlength="20" /></td>
		</tr>
		<tr>
			<th>Meal Served</th>
			<td>
				<input type="checkbox" class="css-checkbox" id="mealserved" name="mealserved" />
				<label class="css-label" for="mealserved"></label>
			</td>
		</tr>
		<tr>
			<th>Area Served</th>
			<td><textarea cols="40" rows="3" id="areaserved" name="areaserved" ><?php echo $details['areaserved']; ?></textarea></td>
		</tr>
		<tr>
			<td></td>
			<td><input type="button" class="btn" value="Save" onclick="trySubmit();" /></td>
		</tr>
	</table>
</form>

<script type="text/javascript">
	window.onload = function() {
		//set posted options
		document.getElementById('locationName').value = "<?php echo (isset($_POST['locationName']) ? $_POST['locationName'] : 'newLocation'); ?>";
		document.getElementById('distday').value = "<?php echo (isset($loc['distday']) ? $loc['distday'] : 'Sunday'); ?>";
		document.getElementById('mealserved').checked = <?php echo (($details['mealserved'] == 1) ? 'true' : 'false'); ?>;
		//fade $info reply
		setTimeout(fadeMessage, 3000);
	}

	function cycleForm(state) {
		document.getElementById('viewstate').value = state;
		document.getElementById('mmLocations').submit();
	}
	
	function trySubmit() {
		//see if required text fields are filled out
		var ids = ['areaserved', 'distribution', 'registration', 'zip', 'phone', 'state', 'city', 'address', 'name', 'alias'];
		var success = true;
		var lastWrong = '';
		for(var i = 0; i < ids.length; i++) {
			if(document.getElementById(ids[i]).value.trim() == '') {
				success = false;
				lastWrong = ids[i];
			}
		}
		
		if(document.getElementById('alias').value.length != 4) {
			alert('The alias entry must be 4 characters in length');
			document.getElementById('alias').focus();
		}
		else if(success) {
			//decide the viewstate
			if(document.getElementById('locationName').value == 'newLocation') {cycleForm('new');}
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
