<?php
	$title = 'Donation Locations';
	$requiredAuthorityLevel = 2;
	$menuArea = 'admin';
	include('appHeader.php'); 
	
	$info = '';
	
	//if page has been [re]submitted
	if(isset($_POST['viewstate'])) {
		//new entry
		if($_POST['viewstate'] == 'new') {
			//begin the prepared statement
			if(!($stmt = $mysqli->prepare('INSERT INTO pickuplocations VALUES (null,?,?,?)'))) {
	     		echo 'Prepare failed: (' . $mysqli->errno . ') ' . $mysqli->error;
			}
			
			//bind the parameters in the prepared statement
			if(!($stmt->bind_param('sss'
				, $_POST['mmLocation']
				, $_POST['locationName']
				, $_POST['notes']))) {
			    echo 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error;
			}
			
			//execute the prepared statement
			if(!$stmt->execute()) {
			    echo 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error;
			}
			else {
				//everything worked, so tell the user
				$info = 'Insert Successful';
			}
		}
		//update existing info
		elseif($_POST['viewstate'] == 'update') {
			//begin the prepared statement
			if(!($stmt = $mysqli->prepare('	UPDATE pickuplocations
											SET primarylocation = ?
											, name = ?
											, notes = ?
											WHERE locationid = ' . $_POST['dLocation']))) {
	     		echo 'Prepare failed: (' . $mysqli->errno . ') ' . $mysqli->error;
			}
			
			//bind the parameters in the prepared statement
			if(!($stmt->bind_param('sss'
				, $_POST['mmLocation']
				, $_POST['locationName']
				, $_POST['notes']))) {
			    echo 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error;
			}
			
			//execute the prepared statement
			if(!$stmt->execute()) {
			    echo 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error;
			}
			else {
				//everything worked, so tell the user
				$info = 'Update Successful';
			}
		}
	} 
	
	//set locations
	if(isset($_POST['dLocation']) && $_POST['dLocation'] != 'newLocation') {
		$result = $mysqli->query('SELECT * FROM pickuplocations WHERE locationid = "' . $_POST['dLocation'] . '";');
		$loc = $result->fetch_assoc();
		$details = array(	'dLocation' => $_POST['dLocation'],
							'mmLocation' => $loc['primarylocation'],
							'locName' => $loc['name'],
							'notes' => $loc['notes']);
	}
	else {
		$details = array(	'dLocation' => 'newLocation',
							'mmLocation' => isset($_POST['mmLocation']) ? $_POST['mmLocation'] : $_SESSION['locAlias'],
							'locName' => '',
							'notes' => '');
	}	
?>

<form id="donationLocations" name="donationLocations" method="post" action="donationLocations.php">
	<input type="hidden" name="viewstate" id="viewstate" value="" />
	<table>
		<tr>
			<td colspan="2"><span id="sqlInfo"><?php echo $info ?>.</span></td>
		</tr>
		<tr>
			<th>Donation Location</th>
			<td>
				<select id="dLocation" name="dLocation" onchange="cycleForm('cycle');">
					<option value="newLocation">-- Add New Location --</option>
	        		<?php 
						$result = $mysqli->query('SELECT locationid, name FROM pickuplocations WHERE primarylocation IS NOT NULL');
						while($row = $result->fetch_assoc()) {
							echo '<option value="' . $row['locationid'] . '">' . $row['name'] . '</option>' . "\n";
						}
					?>
	        	</select>
        	</td>
		</tr>
		<tr>
			<th>Primary MM Location</th>
			<td>
				<select id="mmLocation" name="mmLocation">
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
			<th>Name</th>
			<td><input type="text" id="locationName" name="locationName" value="<?php echo $details['locName']; ?>" maxlength="50" size="40" /></td>
		</tr>
		<tr>
	        <th>Notes</th>
	        <td><textarea cols="30" rows="2" name="notes" maxlength="255"><?php echo $details['notes']; ?></textarea></td>
	    </tr>
		<tr>
			<td></td>
			<td><input type="button" class="btn" value="Save" onclick="trySubmit();" /></td>
		</tr>
	</table>
</form>

<script type="text/javascript">
	window.onload = function() {
		//set location comboboxes to posted or default instead of none
		document.getElementById('dLocation').value = "<?php echo $details['dLocation']; ?>";
		document.getElementById('mmLocation').value = "<?php echo $details['mmLocation']; ?>";
		//fade $info reply
		setTimeout(fadeMessage, 3000);
	}

	function cycleForm(state) {
		document.getElementById('viewstate').value = state;
		document.getElementById('donationLocations').submit();
	}
	
	function trySubmit() {
		if(document.getElementById('locationName').value.trim() == '') {
			alert('Please enter a name for the location');
			return;
		}
		
		if(document.getElementById('dLocation').value == 'newLocation') {
			cycleForm('new');
		}
		else {
			cycleForm('update');
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
