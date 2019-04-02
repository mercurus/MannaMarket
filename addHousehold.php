<?php
	$title = 'Households';
	$requiredAuthorityLevel = 3;
	$menuArea = 'add';
	include('appHeader.php');
	
	$info = ''; 
	$theDate = isset($_POST['theDate']) ? $_POST['theDate'] : date('Y-m-d');
	$mmLocation = isset($_POST['mmLocation']) ? $_POST['mmLocation'] : $_SESSION['locAlias'];
	
	//if page has been [re]submitted
	if(isset($_POST['viewstate'])) {
		//new entry
		if($_POST['viewstate'] == 'new') {
			//begin the prepared statement
			if(!($stmt = $mysqli->prepare('INSERT INTO family VALUES (null,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'))) {
	     		echo 'Prepare failed: (' . $mysqli->errno . ') ' . $mysqli->error;
			}
			
			//checkbox = bit/boolean
			$blockGrant = isset($_POST['blockGrant']) ? 1 : 0;
			$prayer = isset($_POST['prayer']) ? 1 : 0;
			$crisis = isset($_POST['crisis']) ? 1 : 0;
			
			//bind the parameters in the prepared statement
			if(!($stmt->bind_param('sssssssssiiiiiii'
				, $_POST['primaryshopper']
				, $_POST['mmLocation']
				, $_POST['address1']
				, $_POST['address2']
				, $_POST['city']
				, $_POST['state']
				, $_POST['zip']
				, $_POST['phone']
				, $_POST['email']
				, $_POST['householdIncome']
				, $_POST['numMinors']
				, $_POST['numAdults']
				, $_POST['numSeniors']
				, $blockGrant
				, $prayer
				, $crisis))) {
			    echo 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error;
			}
			
			//execute the prepared statement
			if(!$stmt->execute()) {
			    echo 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error;
			}
			else {
				//everything was successful, so tell the user
				$info = 'Household Added';
			}
		}
		//update existing info
		elseif($_POST['viewstate'] == 'update') {
			//begin the prepared statement
			if(!($stmt = $mysqli->prepare('	UPDATE family
											SET primaryshopper = ?,
											primarylocation = ?,
											address1 = ?,
											address2 = ?,
											city = ?,
											state = ?,
											zip = ?,
											phone = ?,
											email = ?,
											householdincome = ?,
											numminors = ?,
											numadults = ?,
											numseniors = ?,
											blockgrant = ?,
											prayer = ?,
											crisis = ?
											WHERE familyid = ' . $_POST['familyList']))) {
	     		echo 'Prepare failed: (' . $mysqli->errno . ') ' . $mysqli->error;
			}
			
			//checkbox = bit/boolean
			$blockGrant = isset($_POST['blockGrant']) ? 1 : 0;
			$prayer = isset($_POST['prayer']) ? 1 : 0;
			$crisis = isset($_POST['crisis']) ? 1 : 0;
			
			//bind the parameters in the prepared statement
			if(!($stmt->bind_param('sssssssssiiiiiii'
				, $_POST['primaryshopper']
				, $_POST['mmLocation']
				, $_POST['address1']
				, $_POST['address2']
				, $_POST['city']
				, $_POST['state']
				, $_POST['zip']
				, $_POST['phone']
				, $_POST['email']
				, $_POST['householdIncome']
				, $_POST['numMinors']
				, $_POST['numAdults']
				, $_POST['numSeniors']
				, $blockGrant
				, $prayer
				, $crisis))) {
			    echo 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error;
			}
			
			//execute the prepared statement
			if(!$stmt->execute()) {
			    echo 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error;
			}
			else {
				$info = 'Update Successful';
				//requery so data gets repopulated
				$result = $mysqli->query('SELECT * FROM family WHERE familyid = "' . $_POST['familyList'] . '"');
				$loc = $result->fetch_assoc();
			}
		}
		//a particular family has been selected
		elseif($_POST['viewstate'] == 'repopulate') {
			$result = $mysqli->query('SELECT * FROM family WHERE familyid = "' . $_POST['familyList'] . '"');
			$loc = $result->fetch_assoc();
		}
	}
	
	$details = array();
	if(isset($loc)) { // = the query happened, and there's data
		foreach($loc as $key => $value) {
			$details[$key] = $value;
		}
	}
	else { // = there's no query, so blank the fields. Yes they're blank by default, but there are set statements down there that expect data.
		$details = array(	'primaryshopper' => '',
							'address1' => '',
							'address2' => '',
							'city' => '',
							'state' => 'MN',
							'zip' => '',
							'phone' => '',
							'email' => '',
							'householdincome' => 10000,
							'numminors' => 0,
							'numadults' => 1,
							'numseniors' => 0,
							'blockgrant' => 0,
							'prayer' => 0,
							'crisis' => 0);
	}
?>

<form id="addHousehold" name="addHousehold" method="post" action="addHousehold.php">
	<input type="hidden" name="viewstate" id="viewstate" value="" />
	<table>
		<tr>
			<td colspan="2"><span id="sqlInfo"><?php echo $info ?>.</span></td>
		</tr>
		<tr>
			<th>Entry</th>
			<td>
				<input type="radio" class="css-checkboxR" name="entryType" id="new" value="new" onclick="cycleForm('newEntry');" /><label for="new" class="css-labelR">New</label> 
				<?php 
					if($_SESSION['authorityLevel'] <= 2) {
						echo '<input type="radio" class="css-checkboxR" name="entryType" id="edit" value="edit" onclick="cycleForm('."'editEntry'".');" /><label for="edit" class="css-labelR">Edit</label>';
					} 
				?>
			</td>
		</tr>
			
		<!-- TODO allow families to change primary locations -->
		
		<tr>
			<th>Primary Location</th>
			<td>
				<select id="mmLocation" name="mmLocation" onchange="if(document.getElementById('new').checked == false) {cycleForm('editEntry');}"> <!-- As long as we're not inputing new data, cycle the form onchange (household list by location) -->
	        		<?php 
						$result = $mysqli->query('SELECT alias, name FROM mmlocations');
						while($row = $result->fetch_assoc()) {
							echo '<option value="' . $row['alias'] . '">' . $row['name'] . '</option>' . "\n";
						}
					?>
	        	</select>
	        </td>
		</tr>
		<?php if(isset($_POST['entryType']) && $_POST['entryType'] == 'edit') { ?>
		<tr>
			<th>List</th>
			<td>
				<select id="familyList" name="familyList" size="7" onchange="cycleForm('repopulate');">
				<?php 
					$sql = 'SELECT familyid, primaryshopper, address1 FROM family WHERE primarylocation = "'. $_POST['mmLocation'] .'" ORDER BY address1';
					$result = $mysqli->query($sql);
					while($row = $result->fetch_assoc()) {
						echo '<option value="' . $row['familyid'] . '">' . $row['address1'] . ' : ' . $row['primaryshopper'] . '</option>' ."\n";
					}
				?>
				</select>
			</td>
		</tr>
		<?php } //end edit <select> ?>
		<tr>
			<th>Primary Shopper(s)</th>
			<td>
				<input type="text" id="primaryshopper" name="primaryshopper" value="<?php echo $details['primaryshopper']; ?>" maxlength="50" />
			</td>
		</tr>
		<tr>
			<th>Address</th>
			<td><input type="text" id="address1" name="address1" value="<?php echo $details['address1']; ?>" maxlength="50" /></td>
		</tr>
		<tr>
			<th></th>
			<td>
				<input type="text" name="address2" value="<?php echo $details['address2']; ?>" maxlength="25" />
			</td>
		</tr>
		<tr>
			<th>City</th>
			<td><input type="text" id="city" name="city" value="<?php echo $details['city']; ?>" maxlength="25" /></td>
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
			<th>Phone</th>
			<td><input type="text" id="phone" name="phone" value="<?php echo $details['phone']; ?>" maxlength="20" /></td>
		</tr>
		<tr>
			<th>Email</th>
			<td><input type="text" id="email" name="email" value="<?php echo $details['email']; ?>" maxlength="30" /></td>
		</tr>
		<tr>
			<th>Household income</th>
			<td><input type="number" id="householdIncome" name="householdIncome" step="1" min="0" max="1000000" value="<?php echo $details['householdincome']; ?>" /></td>
		</tr>
		<tr>
			<th>Number of minors (0 - 17)</th>
			<td><input type="number" id="numMinors" name="numMinors" step="1" min="0" max="10" value="<?php echo $details['numminors']; ?>" /></td>
		</tr>
		<tr>
			<th>Number of adults (18 - 60)</th>
			<td><input type="number" id="numAdults" name="numAdults" step="1" min="0" max="10" value="<?php echo $details['numadults']; ?>" /></td>
		</tr>
		<tr>
			<th>Number of seniors (60+)</th>
			<td><input type="number" id="numSeniors" name="numSeniors" step="1" min="0" max="10" value="<?php echo $details['numseniors']; ?>" /></td>
		</tr>
		<tr>
			<th>Block Grant Recipient?</th>
			<td><input type="checkbox" class="css-checkbox" id="blockGrant" name="blockGrant" <?php if($details['blockgrant'] == 1) {echo 'checked="checked"';} ?> /><label class="css-label" for="blockGrant"></label></td>
		</tr>
		<tr>
			<th>Prayer Request?</th>
			<td><input type="checkbox" class="css-checkbox" id="prayer" name="prayer" <?php if($details['prayer'] == 1) {echo 'checked="checked"';} ?> /><label class="css-label" for="prayer"></label></td>
		</tr>
		<tr>
			<th>In crisis?</th>
			<td><input type="checkbox" class="css-checkbox" id="crisis" name="crisis" <?php if($details['crisis'] == 1) {echo 'checked="checked"';} ?> /><label class="css-label" for="crisis"></label></td>
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
		document.getElementById('mmLocation').value = "<?php echo $mmLocation ?>";
		//select correct radio button
		var id = "<?php if(isset($_POST['entryType'])) {echo $_POST['entryType'];} else {echo 'new';} ?>";
		document.getElementById(id).checked = true;
		
		<?php 
			//select family in the list that has been queried
			if(isset($_POST['viewstate']) && ($_POST['viewstate'] == 'repopulate' || $_POST['viewstate'] == 'update')) {
				echo 'document.getElementById("familyList").value = ' . $_POST['familyList'];
			}
		?>
		
		//fade $info reply
		setTimeout(fadeMessage, 3000);
	}

	function cycleForm(state) {
		document.getElementById('viewstate').value = state;
		document.getElementById('addHousehold').submit();
	}
	
	function trySubmit() {
		//Error checking
		
		if(document.getElementById('new').checked == false && document.getElementById('familyList').selectedIndex == -1) {
			//edit radio was checked, but no family was selected
			alert('Please select a family to edit');
			return;
		}
		
		//see if required text fields are filled out
		var ids = ['numSeniors', 'numAdults', 'numMinors', 'city', 'address1', 'primaryshopper'];
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
