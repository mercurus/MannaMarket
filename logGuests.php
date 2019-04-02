<?php
	$title = 'Log Guests';
	$requiredAuthorityLevel = 3;
	$menuArea = 'log';
	$pageClass = 'logGuests';
	include('appHeader.php');
	
	$info = '';
	$theDate = isset($_POST['theDate']) ? $_POST['theDate'] : date('Y-m-d');
	$mmLocation = isset($_POST['mmLocation']) ? $_POST['mmLocation'] : $_SESSION['locAlias'];
	$guestLocation = isset($_POST['guestLocation']) ? $_POST['guestLocation'] : $_SESSION['locAlias'];
	$loggedExtra = $loggedWarnings = $loggedNotes = $famPrayer = $famCrisis = array(); //holds data to avoid more querying
	
	if(isset($_POST['viewstate'])) {
		//new entry
		if($_POST['viewstate'] == 'add') {
			//begin the prepared statement
			if(!($stmt = $mysqli->prepare('INSERT INTO guestlog VALUES (?,?,?,?,?,?)'))) {
	     		echo 'Prepare failed: (' . $mysqli->errno . ') ' . $mysqli->error;
			}
			
			//checkbox = bit/boolean
			$extra = isset($_POST['extra']) ? 1 : 0;
			$warning = isset($_POST['warning']) ? 1 : 0;
			$prayer = isset($_POST['prayer']) ? 1 : 0;
			$crisis = isset($_POST['crisis']) ? 1 : 0;
			
			//bind the parameters in the prepared statement
			if(!($stmt->bind_param('issiis'
				, $_POST['familyList']
				, $_POST['mmLocation']
				, $_POST['theDate']
				, $extra
				, $warning
				, $_POST['famNotes']))) {
			    echo 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error;
			}
			
			//execute the prepared statement
			if(!$stmt->execute()) {
			    echo 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error;
			}
			else {
				//everything worked, so tell user
				$info = 'Family Logged';
			}

			//IF family's prayer or crisis state changes, change their database record
			//checkboxes are matched against the updated hidden inputs
			if($prayer != $_POST['famPrayer'] || $crisis != $_POST['famCrisis']) {
				if(!($stmt = $mysqli->prepare('	UPDATE family
												SET prayer = ?,
												crisis = ?
												WHERE familyid = ?'))) {
	     			echo 'Prepare failed: (' . $mysqli->errno . ') ' . $mysqli->error;
				}
				
				//bind the parameters in the prepared statement
				if(!($stmt->bind_param('iii'
					, $prayer
					, $crisis
					, $_POST['familyList']))) {
				    echo 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error;
				}
				
				//execute the prepared statement
				if(!$stmt->execute()) {
				    echo 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error;
				}
				else {
					//it worked
				}
			} //end fam status update
		}
		//remove family from logged list
		elseif($_POST['viewstate'] == 'update') {
			//begin the prepared statement
			if(!($stmt = $mysqli->prepare('	UPDATE guestlog
											SET extra = ?,
											warning = ?,
											notes = ?
											WHERE familyid = ?
											AND location = ?
											AND date = ?'))) {
	     		echo 'Prepare failed: (' . $mysqli->errno . ') ' . $mysqli->error;
			}
			
			//checkbox = bit/boolean
			$extra = isset($_POST['logExtra']) ? 1 : 0;
			$warning = isset($_POST['logWarning']) ? 1 : 0;
			
			//bind the parameters in the prepared statement
			if(!($stmt->bind_param('iisiss'
				, $extra
				, $warning
				, $_POST['logNotes']
				, $_POST['loggedList']
				, $_POST['mmLocation']
				, $_POST['theDate']))) {
			    echo 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error;
			}
			
			//execute the prepared statement
			if(!$stmt->execute()) {
			    echo 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error;
			}
			else {
				//it worked, so tell user
				$info = 'Log Updated';
			}
		}
		//remove family from logged list
		elseif($_POST['viewstate'] == 'remove') {
			//begin the prepared statement
			if(!($stmt = $mysqli->prepare('	DELETE FROM guestlog
											WHERE familyid = ?
											AND date = ?'))) {
	     		echo 'Prepare failed: (' . $mysqli->errno . ') ' . $mysqli->error;
			}
			
			//bind the parameters in the prepared statement
			if(!($stmt->bind_param('is'
				, $_POST['loggedList']
				, $_POST['theDate']))) {
			    echo 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error;
			}
			
			//execute the prepared statement
			if(!$stmt->execute()) {
			    echo 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error;
			}
			else {
				//it worked, so tell user
				$info = 'Log Removed';
			}
		}
	}
?>

<form id="logGuests" name="logGuests" method="post" action="logGuests.php">
	<input type="hidden" name="viewstate" id="viewstate" value="" />
	<table>
		<tr>
			<td colspan="2"><span id="sqlInfo"><?php echo $info ?>.</span></td>
		</tr>
		<tr>
	    	<th>Donation Date</th>
	    	<td>
				<script type="text/javascript">
					var cal1x = new CalendarPopup("calDiv", true);
					cal1x.setCssPrefix("MMCAL");
					cal1x.showNavigationDropdowns();
				</script>
				<div id="calDiv" style="position: absolute; visibility: hidden; background-color: white; layer-background-color: white;"></div>
				<input type="text" id="theDate" name="theDate" value="<?php echo $theDate; ?>" size=10 required onkeydown="return false;" />
				<a href="#" onclick="cal1x.select(document.forms[0].theDate, 'anchor1x', 'yyyy-MM-dd'); return false;" name="anchor1x" id="anchor1x">select date</a>
			</td>
	    </tr>
		<tr>
			<th>MM Location</th>
			<td>
				<select id="mmLocation" name="mmLocation" onchange="cycleForm('newLocation');">
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
			<th>Guest's Location</th>
			<td>
				<select id="guestLocation" name="guestLocation" onchange="cycleForm('newGuestLocation');">
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
				Household
				<br />
				<span class="note">
					Can't find them?<br />
					Make sure they're not<br />
					logged already, or<br />
					<a href="addHousehold.php">Add Household</a>
				</span>
			</th>
			<td>
				<select id="familyList" name="familyList" size="7" onclick="setFamInfo();">
					<?php 
						$result = $mysqli->query('	SELECT f.familyid, f.primaryshopper, f.address1, f.prayer, f.crisis 
													FROM family f
													WHERE f.primarylocation = "' . $guestLocation . '"
													AND f.familyid NOT IN 
													(SELECT g.familyid
													FROM guestlog g
													WHERE g.date = "' . $theDate . '")
													ORDER BY f.address1 ASC');
						while($row = $result->fetch_assoc()) {
							$famCrisis[] = $row['crisis'];
							$famPrayer[] = $row['prayer'];
							echo '<option value="' . $row['familyid'] . '">' . $row['address1'] . ' : ' . $row['primaryshopper'] . '</option>' . "\n";
						}
					?>
				</select>
			</td>
		</tr>
		<tr>
	        <th>Family Status</th>
	        <td>
				<input type="checkbox" class="css-checkbox" id="prayer" name="prayer" /><label class="css-label" for="prayer">Prayer Request</label>
				<input type="checkbox" class="css-checkbox" id="crisis" name="crisis" /><label class="css-label" for="crisis">In Crisis</label>
				<input type="hidden" id="famPrayer" name="famPrayer" value="" />
				<input type="hidden" id="famCrisis" name="famCrisis" value="" />
			</td>
	    </tr>
		<tr>
	        <th>Visit Info</th>
	        <td>
				<input type="checkbox" class="css-checkbox" id="extra" name="extra" /><label class="css-label" for="extra">Extra</label>
				<input type="checkbox" class="css-checkbox" id="warning" name="warning" <?php if($mmLocation != $guestLocation) {echo 'checked="checked" ';} ?>/><label class="css-label" for="warning">Warning</label>
			</td>
	    </tr>
		<tr>
	        <th>Notes</th>
	        <td><textarea cols="30" rows="1" name="famNotes"><?php if($mmLocation != $guestLocation) {echo 'Family is visiting MM Location outside of bounds.';} ?></textarea></td>
	    </tr>
		<tr>
			<th></th>
			<td>
				<input type="button" class="btn" value="Log Household" onclick="trySubmit('add');" />
				<br />
				<br />
			</td>
		</tr>
		<tr>
			<th>Households Logged</th>
			<td>
				<select id="loggedList" name="loggedList" size="7" onclick="setLoggedInfo();">
					<?php 
						
						$result = $mysqli->query('	SELECT g.familyid, f.primaryshopper, f.address1, g.extra, g.warning, g.notes
													FROM family f
													INNER JOIN guestlog g ON f.familyid = g.familyid
													AND g.date = "' . $theDate . '"
													AND g.location = "' . $mmLocation . '"
													ORDER BY f.address1 ASC');
						while($row = $result->fetch_assoc()) {
							$loggedExtra[] = $row['extra']; 
							$loggedWarnings[] = $row['warning'];
							$loggedNotes[] = $row['notes'];
							echo '<option value="' . $row['familyid'] . '">' . $row['address1'] . ' : ' . $row['primaryshopper'] . '</option>' . "\n";
						}
					?>
					
				</select>
			</td>
		</tr>
		<tr>
	        <th></th>
	        <td>
				<input type="checkbox" class="css-checkbox" id="logExtra" name="logExtra" /><label class="css-label" for="logExtra">Extra</label>
				<input type="checkbox" class="css-checkbox" id="logWarning" name="logWarning" /><label class="css-label" for="logWarning">Warning</label>
			</td>
	    </tr>
		<tr>
	        <th>Notes</th>
	        <td><textarea cols="30" rows="1" id="logNotes" name="logNotes"></textarea></td>
	    </tr>
	    <tr>
	    	<th></th>
	    	<td>
	    		<?php //if($_SESSION['authorityLevel'] <= 2) { ?>
				<input type="button" class="btn" value="Update Log" onclick="trySubmit('update');" />
				<input type="button" class="btn" value="Remove Log" onclick="trySubmit('remove');" />
				<?php //} ?>
			</td>
		</tr>
	</table>
</form>

<script type="text/javascript">
	var loggedExtra = <?php echo json_encode($loggedExtra); ?>;
	var loggedWarnings = <?php echo json_encode($loggedWarnings); ?>;
	var loggedNotes = <?php echo json_encode($loggedNotes); ?>;
	var famPrayer = <?php echo json_encode($famPrayer); ?>;
	var famCrisis = <?php echo json_encode($famCrisis); ?>;
	
	window.onload = function() {
		//set location comboboxes to posted or default instead of 0
		document.getElementById('mmLocation').value = "<?php echo $mmLocation; ?>";
		document.getElementById('guestLocation').value = "<?php echo $guestLocation; ?>";
		<?php if($mmLocation != $guestLocation) {echo 'alert("The MM location and the guest location do not match.\nFamilies logged this way will get a warning.")';} ?>
		//fade $info reply
		setTimeout(fadeMessage, 3000);
	}

	function cycleForm(state) {
		document.getElementById('viewstate').value = state;
		document.getElementById('logGuests').submit();
	}
	
	function trySubmit(state) {
		//if you haven't selected a family on the list
		if((state == 'add' && document.getElementById('familyList').selectedIndex == -1) || 
			((state == 'remove' || state == 'update') && document.getElementById('loggedList').selectedIndex == -1)) {
			alert('Please select a family');
		}
		else {
			if(state == 'add') {
				//set values of hidden inputs so that after post values can be matched against checkboxes actual state,
				//and database can be changed if need be
				document.getElementById('famPrayer').value = famPrayer[document.getElementById('familyList').selectedIndex];
				document.getElementById('famCrisis').value = famCrisis[document.getElementById('familyList').selectedIndex];
			}
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
	}
	
	function setFamInfo() {
		document.getElementById('prayer').checked = (famPrayer[document.getElementById('familyList').selectedIndex] == "1" ? true : false);
		document.getElementById('crisis').checked = (famCrisis[document.getElementById('familyList').selectedIndex] == "1" ? true : false);
	}
	
	function setLoggedInfo() {
		document.getElementById('logExtra').checked = (loggedExtra[document.getElementById('loggedList').selectedIndex] == "1" ? true : false);
		document.getElementById('logWarning').checked = (loggedWarnings[document.getElementById('loggedList').selectedIndex] == "1" ? true : false);
		document.getElementById('logNotes').value = loggedNotes[document.getElementById('loggedList').selectedIndex];
	}
</script>

<?php include('appFooter.php'); ?>
