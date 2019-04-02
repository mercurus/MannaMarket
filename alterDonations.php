<?php
	$title = 'Alter Donations';
	$requiredAuthorityLevel = 1;
	$menuArea = 'admin';
	include('appHeader.php'); 
	
	$info = '';
	
	if(isset($_POST['viewstate'])) {
		//save edits
		if($_POST['viewstate'] == 'update') {
			//begin the prepared statement
			if(!($stmt = $mysqli->prepare('	UPDATE pickuplog
											SET locationid = ?
											, mmalias = ?
											, personid = ?
											, pickupdate = ?
											, 2htruck = ?
											, bakery = ?
											, dairy = ?
											, deli = ?
											, grocery = ?
											, household = ?
											, meat = ?
											, produce = ?
											, extrafood = ?
											, notes = ?
											WHERE pickupid = ' . $_POST['donationList']))) {
	     		echo 'Prepare failed: (' . $mysqli->errno . ') ' . $mysqli->error;
			}
			
			//zero out crate totals if it's a 2nd harvest truck
			//this is because a 2nd harvest pickup is mutually exclusive with picking up crates
			if($_POST['pickupLocation'] == '1') {
				$_POST['bakery'] = 0;
				$_POST['dairy'] = 0;
				$_POST['deli'] = 0;
				$_POST['grocery'] = 0;
				$_POST['household'] = 0;
				$_POST['meat'] = 0;
				$_POST['produce'] = 0;
				$_POST['extraFood'] = 0;
			}
			else {
				$_POST['2htruck'] = 0;
			}
			
			//multiply the number of crates by their typical weight
			//if it's a 2htruck, 0 * x = 0 so it's negated
			$_POST['bakery'] *= 18;
			$_POST['dairy'] *= 25;
			$_POST['deli'] *= 20;
			$_POST['grocery'] *= 30;
			$_POST['household'] *= 20;
			$_POST['meat'] *= 30;
			$_POST['produce'] *= 30;
			
			//bind the parameters in the prepared statement
			if(!$stmt->bind_param('isisiiiiiiiiis'
				, $_POST['pickupLocation']
				, $_POST['mmLocation']
				, $_SESSION['personid']
				, $_POST['theDate']
				, $_POST['2htruck']
				, $_POST['bakery']
				, $_POST['dairy']
				, $_POST['deli']
				, $_POST['grocery']
				, $_POST['household']
				, $_POST['meat']
				, $_POST['produce']
				, $_POST['extraFood']
				, trim($_POST['notes']))) {
			    echo 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error;
			}
			
			//execute the prepared statement
			if(!$stmt->execute()) {
			    echo 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error;
			}
			else {
				//everything was successful, so tell user
				$info = 'Update Successful';
				//requery so it's less jarring
				$result = $mysqli->query('SELECT * FROM pickuplog WHERE pickupid = "' . $_POST['donationList'] . '"');
				$donation = $result->fetch_assoc();
			}
		}
		//selected donation
		elseif($_POST['viewstate'] == 'populate') {
			$result = $mysqli->query('SELECT * FROM pickuplog WHERE pickupid = "' . $_POST['donationList'] . '"');
			$donation = $result->fetch_assoc();
		}
	}
	
	$details = array();
	if(isset($donation)) { // = the populate query happened, and there's data
		//NOTE: food types are stored as their weight equivalents, 
		//so when populating this data it divides these numbers by their average crate weight
		foreach($donation as $key => $value) { 
			$details[$key] = $value;
		}
	}
	else { // = there's no query, so blank the fields. Yes they're blank by default, but there are set statements down there that expect data.
		$details = array(	'locationid' => '',
							'mmalias' => '',
							'pickupdate' => '',
							'2htruck' => 0,
							'bakery' => 0,
							'dairy' => 0,
							'deli' => 0,
							'grocery' => 0,
							'household' => 0,
							'meat' => 0,
							'produce' => 0,
							'extrafood' => 0,
							'notes' => '');	
	}
		
?>

<form id="alterDonations" name="alterDonations" method="post" action="alterDonations.php">
	<input type="hidden" name="viewstate" id="viewstate" value="" />
	<table>
		<tr>
			<td colspan="2"><span id="sqlInfo"><?php echo $info ?>.</span></td>
		</tr>
		<tr>
			<th>Donation List</th>
			<td>
				<select id="donationList" name="donationList" size="7" onchange="cycleForm('populate');">
					<?php 
						$sql = 'SELECT p.pickupid, m.name, p.pickupdate 
								FROM pickuplog p INNER JOIN mmlocations m 
									ON p.mmalias = m.alias
								ORDER BY p.pickupdate DESC';
						$result = $mysqli->query($sql);
						while($row = $result->fetch_assoc()) {
							echo '<option value="' . $row['pickupid'] . '">' . $row['pickupdate'] . ' for ' . $row['name'] . '</option>' . "\n";
						}
					?>
				</select>
			</td>
		</tr>
		<tr>
	    	<th>Pickup Date</th>
	    	<td>
				<script type="text/javascript">
					var cal1x = new CalendarPopup("calDiv", false);
					cal1x.setCssPrefix("MMCAL");
					cal1x.showNavigationDropdowns();
				</script>
				<div id="calDiv" style="position: absolute; visibility: hidden; background-color: white; layer-background-color: white;"></div>
				<input type="text" id="theDate" name="theDate" value="<?php echo $details['pickupdate']; ?>" size=10 required onkeydown="return false;" />
				<a href="#" onclick="Javascript: cal1x.select(document.forms[0].theDate, 'anchor1x', 'yyyy-MM-dd'); return false;" name="anchor1x" id="anchor1x">select date</a>
			</td>
	    </tr>
		<tr>
	    	<th>Manna Market Location</th>
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
	        <th>Pickup Location</th>
	        <td>
	        	<select id="pickupLocation" name="pickupLocation" onchange="cratesToTruck();">
	        		<?php 
	        			//list all pickup locations to make it easier for when pickups have been logged from sites not default to MM location
						$result = $mysqli->query('SELECT * FROM pickuplocations');
						while($row = $result->fetch_assoc()) {
							echo '<option value="' . $row['locationid'] . '">' . $row['name'] . '</option>' . "\n";
						}
					?>
	        	</select>
	        	<input type="checkbox" id="listall" class="css-checkbox" checked="checked" disabled /><label class="css-label" for="listall">list all</label>
	    	</td>
	    </tr>
	    <tr>
	        <th>Notes</th>
	        <td><textarea cols="30" rows="2" name="notes" maxlength="255"><?php echo $details['notes']; ?></textarea></td>
	    </tr>
	</table>
	<div id="divTruck">
    	<table>
	    	<tr>
		    	<th>2nd Harvest Truck</th>
		    	<td><input type="number" id="2htruck" name="2htruck" min="0" value="<?php echo $details['2htruck']; ?>" /> pounds</td>
			</tr>
		</table>
	</div>
    <div id="divCrates">
    	<table>
    		<tr>
		    	<th>Food Types</th>
		    	<td>Crates</td>
		    </tr>
			<tr>
		    	<th>Bakery</th>
		    	<td><input type="number" id="bakery" name="bakery" min="0" value="<?php echo $details['bakery'] / 18; ?>" class="crates" /> @ 18 pounds</td>
			</tr>
			<tr>
		    	<th>Dairy</th>
		    	<td><input type="number" id="dairy" name="dairy" min="0" value="<?php echo $details['dairy'] / 25; ?>" class="crates" /> @ 25 pounds</td>
			</tr>
			<tr>
		        <th>Deli</th>
		        <td><input type="number" id="deli" name="deli" min="0" value="<?php echo $details['deli'] / 20; ?>" class="crates" /> @ 20 pounds</td>
		    </tr>
			<tr>
		        <th>Grocery</th>
		        <td><input type="number" id="grocery" name="grocery" min="0" value="<?php echo $details['grocery'] / 30; ?>" class="crates" /> @ 30 pounds</td>
		    </tr>
			<tr>
		        <th>Household</th>
		        <td><input type="number" id="household" name="household" min="0" value="<?php echo $details['household'] / 20; ?>" class="crates" /> @ 20 pounds</td>
		    </tr>
			<tr>
		    	<th>Meat</th>
		    	<td><input type="number" id="meat" name="meat" min="0" value="<?php echo $details['meat'] / 30; ?>" class="crates" /> @ 30 pounds</td>
			</tr>
			<tr>
		    	<th>Produce</th>
		        <td><input type="number" id="produce" name="produce" min="0" value="<?php echo $details['produce'] / 30; ?>" class="crates" /> @ 30 pounds</td>
		    </tr>
			<tr>
		        <th>Extra Food</th>
		        <td><input type="number" id="extraFood" name="extraFood" min="0" value="<?php echo $details['extrafood']; ?>" /> pounds</td>
		    </tr>
	    </table>
    </div>
    <table>
		<tr>
	    	<th></th>
	    	<td><input type="button" class="btn" value="Save Changes" onclick="verify();" /></td>
		</tr>
	</table>
</form>

<script type="text/javascript">
	window.onload = function() {
		//set <select>s to proper value
		document.getElementById('donationList').value = "<?php echo isset($_POST['donationList']) ? $_POST['donationList'] : ''; ?>";
		document.getElementById('mmLocation').value = "<?php echo $details['mmalias']; ?>";
		document.getElementById('pickupLocation').value = "<?php echo $details['locationid']; ?>";
		//ensure that the proper div of textboxes displays (crates vs 2h truck)
		cratesToTruck();
		//fade $info reply
		setTimeout(fadeMessage, 3000);
	}

	function cycleForm(state) {
		document.getElementById('viewstate').value = state;
		document.getElementById('alterDonations').submit();
	}

	var opacity = 100;
	function fadeMessage() {
		if(opacity > 0) {
			opacity -= 10;
			document.getElementById('sqlInfo').style.opacity = opacity / 100;
			setTimeout(fadeMessage, 100);
		}
	}
	
	function verify() {
		//Error checking
		//make sure a donation is selected to alter
		if(document.getElementById('donationList').selectedIndex == -1) {
			alert('You need to select a donation to alter');
			return;
		}
		
		//see if required text fields are filled out
		var ids = ['bakery', 'dairy', 'deli', 'grocery', 'household', 'meat', 'produce', 'extraFood'];
		if(String(document.getElementById('pickupLocation').value) == '1' 
			&& (isNaN(document.getElementById('2htruck').value.trim())
			|| document.getElementById('2htruck').value.trim() == '')) {
			alert('Your 2nd Harvest sum is not a number');
			document.getElementById('2htruck').focus();
			return;
		}
		else { //anything with crates
			for(var i = 0; i < ids.length; i++) {
				if(isNaN(document.getElementById(ids[i]).value.trim())
					|| document.getElementById(ids[i]).value.trim() == '') {
					alert('Your ' + ids[i] + ' sum is not a number');
					document.getElementById(ids[i]).focus();
					return;
				}
			}
		}
		//errors will trigger return, so if we're this far it should be okay to go through
		cycleForm('update');
	}
	
	function cratesToTruck() {
		//if you're picking up from a 2nd Harvest truck, hide the crate inputs
		if(document.getElementById('pickupLocation').value == '1') { //entry for Second Harvest Truck in database must be number 1!!!
			document.getElementById('divTruck').style.display = 'block';
			document.getElementById('divCrates').style.display = 'none';
		}
		else { //or the reverse
			document.getElementById('divTruck').style.display = 'none';
			document.getElementById('divCrates').style.display = 'block';
		}
	}
</script>

<?php include('appFooter.php'); ?>
	