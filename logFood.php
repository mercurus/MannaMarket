<?php
	$title = 'Log Food';
	$requiredAuthorityLevel = 2;
	$menuArea = 'log';
	include('appHeader.php'); 
	
	$info = '';
	$theDate = isset($_POST['theDate']) ? $_POST['theDate'] : date('Y-m-d');
	$mmLocation = isset($_POST['mmLocation']) ? $_POST['mmLocation'] : $_SESSION['locAlias'];
	
	if(isset($_POST['viewstate']) && $_POST['viewstate'] == 'new') {
		//begin the prepared statement
		if(!($stmt = $mysqli->prepare('INSERT INTO pickuplog VALUES (null,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'))) {
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
			$info = 'Insert Successful';
		}
	}
	
?>

<form id="logFood" name="logFood" method="post" action="logFood.php">
	<input type="hidden" name="viewstate" id="viewstate" value="" />
	<table>
		<tr>
			<td colspan="2"><span id="sqlInfo"><?php echo $info ?>.</span></td>
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
				<input type="text" id="theDate" name="theDate" value="<?php echo $theDate; ?>" size=10 required onkeydown="return false;" />
				<a href="#" onclick="Javascript: cal1x.select(document.forms[0].theDate, 'anchor1x', 'yyyy-MM-dd'); return false;" name="anchor1x" id="anchor1x">select date</a>
			</td>
	    </tr>
		<tr>
	    	<th>Manna Market Location</th>
	    	<td>
	    		<select id="mmLocation" name="mmLocation" onchange="cycleForm('cycle');">
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
	        			//the only location with a null primarylocation should be the second harvest truck, with an id of 1
						$sql = 'SELECT * FROM pickuplocations 
								WHERE primarylocation = "' . $mmLocation . '" 
								OR primarylocation IS NULL 
								ORDER BY primarylocation DESC'; 
						if(isset($_POST['allLocations'])) {
							$sql = 'SELECT * FROM pickuplocations ORDER BY primarylocation DESC'; //or reset query to list all locations
						}
						$result = $mysqli->query($sql);
						while($row = $result->fetch_assoc()) {
							echo '<option value="' . $row['locationid'] . '">' . $row['name'] . '</option>' . "\n";
						}
					?>
	        	</select>
	        	<input type="checkbox" class="css-checkbox" id="allLocations" name="allLocations" onchange="cycleForm('listall');" 
	        		<?php if(isset($_POST['allLocations'])) {echo 'checked="checked"';} ?> title="Display donation locations regardless of its assigned MM location" />
        		<label class="css-label" for="allLocations" title="Display donation locations regardless of its assigned MM location">list all</label>
	    	</td>
	    </tr>
	    <tr>
	        <th>Notes</th>
	        <td><textarea cols="30" rows="2" name="notes" maxlength="255"></textarea></td>
	    </tr>
	</table>
	<div id="divTruck">
    	<table>
	    	<tr>
		    	<th>2nd Harvest Truck</th>
		    	<td><input type="number" id="2htruck" name="2htruck" min="0" value="0" /> pounds</td>
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
		    	<td><input type="number" id="bakery" name="bakery" min="0" value="0" class="crates" /> @ 18 pounds</td>
			</tr>
			<tr>
		    	<th>Dairy</th>
		    	<td><input type="number" id="dairy" name="dairy" min="0" value="0" class="crates" /> @ 25 pounds</td>
			</tr>
			<tr>
		        <th>Deli</th>
		        <td><input type="number" id="deli" name="deli" min="0" value="0" class="crates" /> @ 20 pounds</td>
		    </tr>
			<tr>
		        <th>Grocery</th>
		        <td><input type="number" id="grocery" name="grocery" min="0" value="0" class="crates" /> @ 30 pounds</td>
		    </tr>
			<tr>
		        <th>Household</th>
		        <td><input type="number" id="household" name="household" min="0" value="0" class="crates" /> @ 20 pounds</td>
		    </tr>
			<tr>
		    	<th>Meat</th>
		    	<td><input type="number" id="meat" name="meat" min="0" value="0" class="crates" /> @ 30 pounds</td>
			</tr>
			<tr>
		    	<th>Produce</th>
		        <td><input type="number" id="produce" name="produce" min="0" value="0" class="crates" /> @ 30 pounds</td>
		    </tr>
			<tr>
		        <th>Extra Food</th>
		        <td><input type="number" id="extraFood" name="extraFood" min="0" value="0" /> pounds</td>
		    </tr>
	    </table>
    </div>
    <table>
		<tr>
	    	<th></th>
	    	<td><input type="button" class="btn" 	value="Add Donation" onclick="verify();" /></td>
		</tr>
	</table>
</form>

<script type="text/javascript">
	window.onload = function() {
		document.getElementById('mmLocation').value = '<?php echo $mmLocation; ?>';
		cratesToTruck();
		//fade $info reply
		setTimeout(fadeMessage, 3000);
	}

	function cycleForm(state) {
		document.getElementById('viewstate').value = state;
		document.getElementById('logFood').submit();
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
		//see if required text fields are filled out
		var ids = ['bakery', 'dairy', 'deli', 'grocery', 'household', 'meat', 'produce', 'extraFood'];
		total = 0;
		//if it's a 2nd Harvest Truck
		if(String(document.getElementById('pickupLocation').value) == '1' ) {
			if(isNaN(document.getElementById('2htruck').value.trim())
				|| document.getElementById('2htruck').value.trim() == '') {
				alert('Your 2nd Harvest sum is not a number');
				document.getElementById('2htruck').focus();
				return;
			}
			else {
				total = document.getElementById('2htruck').value;
			}
		}
		//or anything else with crates
		else { 
			for(var i = 0; i < ids.length; i++) {
				if(isNaN(document.getElementById(ids[i]).value.trim())
					|| document.getElementById(ids[i]).value.trim() == '') {
					alert('Your ' + ids[i] + ' sum is not a number');
					document.getElementById(ids[i]).focus();
					return;
				}
				else {
					total += document.getElementById(ids[i]).value;
				}
			}
		}
		//errors will trigger return, so if we're this far it should be okay to go through
		if(total == 0) {
			alert('Your totals are 0. Did you pick up anything?');
		}
		else {
			cycleForm('new');
		}
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
	