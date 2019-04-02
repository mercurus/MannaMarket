<?php
	$title = 'Volunteers';
	$requiredAuthorityLevel = 3;
	$menuArea = 'add';
	include('appHeader.php'); 
	
	$info = '';
	$mmLocation = isset($_POST['mmLocation']) ? $_POST['mmLocation'] : $_SESSION['locAlias'];
	$entryType = isset($_POST['entryType']) ? $_POST['entryType'] : 'new';
	
	//if page has been [re]submitted
	if(isset($_POST['viewstate'])) { 
		//new entry
		if($_POST['viewstate'] == 'new') {
			//begin the prepared statement
			if(!($stmt = $mysqli->prepare('INSERT INTO person VALUES (null,?,?,?,?,?,?)'))) {
	     		echo 'Prepare failed: (' . $mysqli->errno . ') ' . $mysqli->error;
			}
			
			//bind the parameters in the prepared statement
			if(!($stmt->bind_param('isdsss'
				, $_POST['familyList']
				, $_POST['name']
				, $_POST['avgvolhrs']
				, $_POST['emergencycontact']
				, $_POST['emergencyphone']
				, $_POST['theDate']))) {
			    echo 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error;
			}
			
			//execute the prepared statement
			if(!$stmt->execute()) {
			    echo 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error;
			}
			else {
				//everything was successful, so tell user
				$info = 'Insert Successful';
			}
		}
		//update existing info
		elseif($_POST['viewstate'] == 'update') {
			//begin the prepared statement
			if(!($stmt = $mysqli->prepare('	UPDATE person
											SET name = ?,
											avgvolhrs = ?,
											emergencycontact = ?,
											emergencyphone = ?,
											dob = ?
											WHERE personid = ' . $_POST['personList']))) {
	     		echo 'Prepare failed: (' . $mysqli->errno . ') ' . $mysqli->error;
			}
			//bind the parameters in the prepared statement
			if(!($stmt->bind_param('sdsss'
				, $_POST['name']
				, $_POST['avgvolhrs']
				, $_POST['emergencycontact']
				, $_POST['emergencyphone']
				, $_POST['theDate']))) {
			    echo 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error;
			}
			
			//execute the prepared statement
			if(!$stmt->execute()) {
			    echo 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error;
			}
			else {
				//everything was successful, so tell user
				$info = 'Update Successful';
				//requery so data gets repopulated
				$result = $mysqli->query('SELECT * FROM person WHERE personid = "' . $_POST['personList'] . '"');
				$person = $result->fetch_assoc();
			}
		}
		//a particular person has been selected
		else if($_POST['viewstate'] == 'newPerson') {
			$result = $mysqli->query('SELECT * FROM person WHERE personid = "' . $_POST['personList'] . '"');
			$person = $result->fetch_assoc();
		}
	}
	
	$details = array();
	if(isset($person)) { // = the query happened, and there's data
		foreach($person as $key => $value) {
			$details[$key] = $value;
		}
	}
	else { // = there's no query, so blank the fields. Yes they're blank by default, but there are set statements down there that expect data.
		$details = array(	'name' => '',
							'avgvolhrs' => 1.0,
							'emergencycontact' => '',
							'emergencyphone' => '',
							'dob' => '1970-01-01');
	}
?>

<form id="addVolunteers" name="addVolunteers" method="post" action="addVolunteers.php">
	<input type="hidden" name="viewstate" id="viewstate" value="" />
	<table>
		<tr>
			<td colspan="2"><span id="sqlInfo"><?php echo $info ?>.</span></td>
		</tr>
		<tr>
			<th>Entry</th>
			<td>
				<input type="radio" class="css-checkboxR" name="entryType" id="new" value="new" onclick="cycleForm('newEntry');" /><label for="new" class="css-labelR">New</label>
				<?php if($_SESSION['authorityLevel'] <= 2) { ?>
				<input type="radio" class="css-checkboxR" name="entryType" id="edit" value="edit" onclick="cycleForm('editEntry');" /><label for="edit" class="css-labelR">Edit</label>
				<?php } ?>
			</td>
		</tr>
		<tr>
			<th>Primary Location</th>
			<td>
				<select id="mmLocation" name="mmLocation" onchange="cycleForm('repopulate');"> 
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
			<th>
				<?php echo ($entryType == 'new' ? 'Household List' : 'Volunteer List'); //display correct header ?>
				<br />
				<span class="note">
					Note: Even if a volunteer is<br />
					not a MM guest, they still need<br />
					to be part of a Household<br />
					<a href="addHousehold.php">Add Household</a>
				</span>	
			</th>
			<td>
				<?php
					if($entryType == 'new') {
						$sql = 'SELECT familyid, primaryshopper, address1 
								FROM family
								WHERE primarylocation = "'. $mmLocation .'"
								ORDER BY address1';
						$result = $mysqli->query($sql);
						echo '<select id="familyList" name="familyList" size="7">';
						while($row = $result->fetch_assoc()) {
							echo '<option value="' . $row['familyid'] . '">' . $row['primaryshopper'] . ' ' . $row['address1'] . '</option>' ."\n";
						}
					}
					else { //entryType is 'edit', so list volunteers instead
						$sql = 'SELECT p.personid, p.name
								FROM person p, family f 
								WHERE p.familyid = f.familyid
								AND primarylocation = "'. $mmLocation .'"
								ORDER BY p.name';
						$result = $mysqli->query($sql);
						echo '<select id="personList" name="personList" size="7" onchange="cycleForm(' . "'newPerson'" .');">';
						while($row = $result->fetch_assoc()) {
							echo '<option value="' . $row['personid'] . '">' . $row['name'] . '</option>' ."\n";
						}
					}
				?>
				</select>
			</td>
		<tr>
			<th>Name</th>
			<td><input type="text" id="name" name="name" value="<?php echo $details['name']; ?>" maxlength="50" /></td>
		</tr>
		<tr>
			<th>Average Volunteer Hours</th>
			<td><input type="number" name="avgvolhrs" max="12" min=".5" step=".5" value="<?php echo $details['avgvolhrs']; ?>" title="Increments of .5 hours" /></td>
		</tr>
		<tr>
			<th>Emergency Contact</th>
			<td><input type="text" id="emergencycontact" name="emergencycontact" value="<?php echo $details['emergencycontact']; ?>" maxlength="20" /></td>
		</tr>
		<tr>
			<th>Emergency Phone</th>
			<td><input type="text" id="emergencyphone" name="emergencyphone" value="<?php echo $details['emergencyphone']; ?>" maxlength="20" /></td>
		</tr>
		<tr>
	    	<th>Date Of Birth</th>
	    	<td>
				<script type="text/javascript">
					var cal1x = new CalendarPopup("calDiv", false);
					cal1x.setCssPrefix("MMCAL");
					cal1x.showNavigationDropdowns();
					cal1x.setYearSelectStartOffset(20);
				</script>
				<div id="calDiv" style="position: absolute; visibility: hidden; background-color: white; layer-background-color: white;"></div>
				<input type="text" id="theDate" name="theDate" value="<?php echo $details['dob']; ?>" size=10 required onkeydown="return false;" />
				<a href="#" onClick="cal1x.select(document.forms[0].theDate, 'anchor1x', 'yyyy-MM-dd'); return false;" name="anchor1x" id="anchor1x">select date</a>
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
		//set location comboboxes to posted or default instead of none
		document.getElementById('mmLocation').value = '<?php echo $mmLocation; ?>';
		<?php 
			//select person in the list that has been queried
			if(isset($_POST['viewstate']) && ($_POST['viewstate'] == 'newPerson' || $_POST['viewstate'] == 'update')) {
				//if a person is selected, its information has been populated, so now select it in the list
				echo "document.getElementById('personList').value = '" . $_POST['personList'] . "';";
			}
		?>
		//select correct radio button
		document.getElementById('<?php echo $entryType; ?>').checked = true;
		//fade $info reply
		setTimeout(fadeMessage, 5000);
	}

	function cycleForm(state) {
		document.getElementById('viewstate').value = state;
		document.getElementById('addVolunteers').submit();
	}
	
	function trySubmit() {
		//Error checking
		
		//if you haven't selected a family on the list
		if(document.getElementById('new').checked == true && document.getElementById('familyList').selectedIndex == -1) {
			alert('Please select a household');
			return;
		}
		else if(document.getElementById('edit').checked == true && document.getElementById('personList').selectedIndex == -1) {
			alert('Please select a volunteer');
			return;
		}
		
		//see if required text fields are filled out
		var ids = ['theDate', 'name'];
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
