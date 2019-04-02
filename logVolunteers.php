<?php
	$title = 'Log Volunteers';
	$requiredAuthorityLevel = 3;
	$menuArea = 'log';
	include('appHeader.php');
	
	$info = '';
	$theDate = isset($_POST['theDate']) ? $_POST['theDate'] : date('Y-m-d');
	$mmLocation = isset($_POST['mmLocation']) ? $_POST['mmLocation'] : $_SESSION['locAlias'];
	$volLocation = isset($_POST['volLocation']) ? $_POST['volLocation'] : $_SESSION['locAlias'];

	if(isset($_POST['viewstate'])) {
		//new entry
		if($_POST['viewstate'] == 'add') {
			//begin the prepared statement
			if(!($stmt = $mysqli->prepare('INSERT INTO volunteerlog VALUES (?,?,?,?,?)'))) {
	     		echo 'Prepare failed: (' . $mysqli->errno . ') ' . $mysqli->error;
			}
			
			//bind the parameters in the prepared statement
			if(!($stmt->bind_param('issds'
				, $_POST['volunteerName']
				, $_POST['mmLocation']
				, $_POST['theDate']
				, $_POST['numHours']
				, $_POST['volNotes']))) {
			    echo 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error;
			}
			
			//execute the prepared statement
			if(!$stmt->execute()) {
			    echo 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error;
			}
			else {
				//everything worked, so tell user
				$info = 'Volunteer Logged';
			}
		}
		//remove volunteer from logged list
		elseif($_POST['viewstate'] == 'remove') {
			//begin the prepared statement
			if(!($stmt = $mysqli->prepare('	DELETE FROM volunteerlog
											WHERE personid = ?
											AND location = ?
											AND volunteerdate = ?'))) {
	     		echo 'Prepare failed: (' . $mysqli->errno . ') ' . $mysqli->error;
			}
			
			//bind the parameters in the prepared statement
			if(!($stmt->bind_param('iss'
				, $_POST['volunteersLogged']
				, $_POST['mmLocation']
				, $_POST['theDate']))) {
			    echo 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error;
			}
			
			//execute the prepared statement
			if(!$stmt->execute()) {
			    echo 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error;
			}
			else {
				//everything worked, so tell user
				$info = 'Log Removed';
			}
		}
		//update volunteer info
		elseif($_POST['viewstate'] == 'update') {
			//begin the prepared statement
			if(!($stmt = $mysqli->prepare('	UPDATE volunteerlog
											SET hours = ?,
											notes = ?
											WHERE personid = ?
											AND location = ?
											AND volunteerdate = ?'))) {
	     		echo 'Prepare failed: (' . $mysqli->errno . ') ' . $mysqli->error;
			}
			
			//bind the parameters in the prepared statement
			if(!($stmt->bind_param('dsiss'
				, $_POST['loggedHours']
				, $_POST['loggedNotes']
				, $_POST['volunteersLogged']
				, $_POST['mmLocation']
				, $_POST['theDate']))) {
			    echo 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error;
			}
			
			//execute the prepared statement
			if(!$stmt->execute()) {
			    echo 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error;
			}
			else {
				//everything worked, so tell user
				$info = 'Log Updated';
			}
		}
		//MM location changed, so set the Volunteer's Primary Site to be the same
		elseif($_POST['viewstate'] == 'cycleMMLocation') {
			$volLocation = $mmLocation;
		}
	}
?>

<form id="logVolunteers" name="logVolunteers" method="post" action="logVolunteers.php">
	<input type="hidden" name="viewstate" id="viewstate" value="" />
	<table>
		<tr>
			<td colspan="2"><span id="sqlInfo"><?php echo $info ?>.</span></td>
		</tr>
		<tr>
	    	<th>Volunteer Date</th>
	    	<td>
				<script type="text/javascript">
					var cal1x = new CalendarPopup("calDiv", true);
					cal1x.setCssPrefix("MMCAL");
					cal1x.showNavigationDropdowns();
				</script>
				<div id="calDiv" style="position: absolute; visibility: hidden; background-color: white; layer-background-color: white;"></div>
				<input type="text" id="theDate" name="theDate" value="<?php echo $theDate; ?>" size=10 required onkeydown="return false;" />
				<a href="#" onclick="Javascript: cal1x.select(document.forms[0].theDate, 'anchor1x', 'yyyy-MM-dd'); return false;" name="anchor1x" id="anchor1x">select date</a>
			</td>
	    </tr>
		<tr>
			<th>MM Location</th>
			<td>
				<select id="mmLocation" name="mmLocation" onchange="cycleForm('cycleMMLocation');">
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
			<th>Volunteer's primary site</th>
			<td>
				<select id="volLocation" name="volLocation" onchange="cycleForm('newVolLocation');">
	        		<?php 
						$result = $mysqli->query('SELECT alias, name FROM mmlocations');
						while($row = $result->fetch_assoc()) {
							echo '<option value="' . $row['alias'] . '">' . $row['name'] . '</option>' . "\n";
						}
					?>
					<option value="all">-- All Locations --</option>
	        	</select>
        	</td>
		</tr>
		<tr>
			<th>
				Volunteer
				<br />
				<span class="note">
					Can't find them?<br />
					Make sure they're not<br />
					logged already, or<br />
					<a href="addVolunteers.php">Add Volunteer</a>
				</span>
			</th>
			<td>
				<select id="volunteerName" name="volunteerName" size="7" onchange="setHours();">
	        		<?php 
					 	$sql = 'SELECT p.personid, p.name, p.avgvolhrs, m.name as mmname
				 				FROM person p INNER JOIN family f
				 					ON p.familyid = f.familyid
			 					INNER JOIN mmlocations m 
			 						ON m.alias = f.primarylocation
				 				WHERE ';
						if(!isset($_POST['volLocation']) || $_POST['volLocation'] != 'all') {
		 					$sql .= 'f.primarylocation = "' . $volLocation . '" AND ';
						}
					 	$sql .= 'p.personid NOT IN
					 			(SELECT personid
								FROM volunteerlog 
								WHERE volunteerdate = "' . $theDate . '"
								AND location = "' . $mmLocation . '")
								ORDER BY f.primarylocation, p.name ASC;';
						$result = $mysqli->query($sql);
						$fromLocation = ''; //add primary location if we're listing volunteers from everywhere
						$numHours = array();
						while($row = $result->fetch_assoc()) {
							if($volLocation == 'all') {
								$fromLocation = $row['mmname'] . ': ';
							}
							$numHours[] = (double)$row['avgvolhrs'];
							echo '<option value="' . $row['personid'] . '">' . $fromLocation . $row['name'] . '</option>' . "\n";
						}
					?>
	        	</select>
        	</td>
		</tr>
		<tr>
			<th>Hours Worked</th>
			<td><input type="number" id="numHours" name="numHours" max="12" min=".5" step=".5" value=".5" title="Increments of .5 hours" /></td>
		</tr>
		<tr>
	        <th>Notes</th>
	        <td><textarea cols="30" rows="1" name="volNotes"></textarea></td>
	    </tr>
		<tr>
			<td></td>
			<td>
				<input type="button" class="btn" value="Log Volunteer" onclick="trySubmit('add');" />
				<br />
				<br />
			</td>
		</tr>
		<tr>
			<th>Volunteers Logged</th>
			<td>
				<select id="volunteersLogged" name="volunteersLogged" class="selectBox" size="7" onchange="setLoggedInfo();">
	        		<?php 
						$sql = 'SELECT v.personid, p.name, v.hours, v.notes
				 				FROM person p, volunteerlog v
				 				WHERE p.personid = v.personid
					 			AND v.location = "' . $mmLocation . '"
								AND v.volunteerdate = "' . $theDate . '"';
						$result = $mysqli->query($sql);
						$loggedHours = array();
						$loggedNotes = array();
						while($row = $result->fetch_assoc()) {
							$loggedHours[] = (double)$row['hours'];
							$loggedNotes[] = $row['notes'];
							echo '<option value="' . $row['personid'] . '">' . $row['name'] . ' - ' . $row['hours'] . ' hours</option>' . "\n";
						}
					?>
	        	</select>
        	</td>
		</tr>
		<tr>
			<th>Hours Worked</th>
			<td><input type="number" id="loggedHours" name="loggedHours" max="12" min=".5" step=".5" value=".5" title="Increments of .5 hours" /></td>
		</tr>
		<tr>
	        <th>Notes</th>
	        <td><textarea cols="30" rows="1" id="loggedNotes" name="loggedNotes"></textarea></td>
	    </tr>
		<tr>
			<td></td>
			<td>
				<input type="button" class="btn" value="Update Log" onclick="trySubmit('update');" />
				<input type="button" class="btn" value="Remove Log" onclick="trySubmit('remove');" />
			</td>
		</tr>
	</table>
</form>

<script type="text/javascript">
	var numHours = <?php echo json_encode($numHours); ?>;
	var loggedHours = <?php echo json_encode($loggedHours); ?>;
	var loggedNotes = <?php echo json_encode($loggedNotes); ?>;
	
	window.onload = function() {
		//set location comboboxes to posted or default instead of 0
		document.getElementById('mmLocation').value = "<?php echo $mmLocation ?>";
		document.getElementById('volLocation').value = "<?php echo $volLocation ?>";
		//fade $info reply
		setTimeout(fadeMessage, 3000);
	}
	
	function cycleForm(state) {
		document.getElementById('viewstate').value = state;
		document.getElementById('logVolunteers').submit();
	}
	
	function trySubmit(state) {
		//if you haven't selected a volunteer on the list
		if(	(state == 'add' && document.getElementById('volunteerName').selectedIndex == -1) ||
			((state == 'remove' || state == 'update') && document.getElementById('volunteersLogged').selectedIndex == -1)) {
			alert('Please select a volunteer');
		}
		else {
			cycleForm(state);
		}
	}
	
	var opacity = 100;
	function fadeMessage() {
		if(opacity > 0) {
			opacity -= 10;
			document.getElementById('sqlInfo').style.opacity = opacity / 100;
			setTimeout(fadeMessage, 100);
		}
		else {
			document.getElementById('sqlInfo').innerHTML = '.';
		}
	}
	
	function setHours() {
		//the selected volunteer has changed, so should numHours
		document.getElementById('numHours').value = numHours[document.getElementById('volunteerName').selectedIndex];
	}
	
	function setLoggedInfo() {
		//the selected volunteer has changed, so should numHours
		document.getElementById('loggedHours').value = loggedHours[document.getElementById('volunteersLogged').selectedIndex];
		document.getElementById('loggedNotes').value = loggedNotes[document.getElementById('volunteersLogged').selectedIndex];
	}
</script>

<?php include('appFooter.php'); ?>
