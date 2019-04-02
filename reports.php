<?php
	$title = 'Reports';
	$requiredAuthorityLevel = 2;
	$menuArea = 'admin';
	include('appHeader.php'); 
	
	//add MM locations to array so we only have to query once
	//this process is different from what I did on other pages
	//why? Just trying something new
	$locations = array();
	$result = $mysqli->query('SELECT alias, name FROM mmlocations');
	while($row = $result->fetch_assoc()) {
		$record = array();
		$record['alias'] =  $row['alias'];
		$record['name'] = $row['name'];
		$locations[] = $record;
	}
?>

<form id="reports" name="reports" method="post" action="generateReport.php" target="_blank">
	<input type="hidden" name="viewstate" id="viewstate" value="" />
	<table>
		<tr>
			<th>MM Location</th>
			<td>
				<select id="mmLocation" name="mmLocation" class="selectBox">
					<option value="all">-- All Locations --</option>
					<?php
						for($i = 0; $i < sizeof($locations); $i++) {
							echo '<option value="' . $locations[$i]['alias'] . '">' . $locations[$i]['name'] . '</option>' . "\n";
						}
					?>
				</select>
			</td>
		</tr>
		<tr>
			<th>Start Date</th>
			<td>
				<script type="text/javascript">
					var calStartDate = new CalendarPopup("calStartDate", false);
					calStartDate.setCssPrefix("MMCAL");
					calStartDate.showNavigationDropdowns();
				</script>
				<div id="calStartDate" style="position: absolute; visibility: hidden; background-color: white; layer-background-color: white;"></div>
				<input type="text" id="startDate" name="startDate" size=10 onkeydown="return false;" />
				<a href="#" onclick="Javascript: calStartDate.select(document.forms[0].startDate, 'startAnchor', 'yyyy-MM-dd'); return false;" name="startAnchor" id="startAnchor">select date</a>
			</td>
		</tr>
		<tr>
			<th>End Date</th>
			<td>
				<script type="text/javascript">
					var calEndDate = new CalendarPopup("calEndDate", false);
					calEndDate.setCssPrefix("MMCAL");
					calEndDate.showNavigationDropdowns();
				</script>
				<div id="calEndDate" style="position: absolute; visibility: hidden; background-color: white; layer-background-color: white;"></div>
				<input type="text" id="endDate" name="endDate" size=10 onkeydown="return false;" />
				<a href="#" onclick="Javascript: calEndDate.select(document.forms[0].endDate, 'endAnchor', 'yyyy-MM-dd'); return false;" name="endAnchor" id="endAnchor">select date</a>
			</td>
		</tr>
		<tr>
			<th>Visit Totals</th>
			<td>
				<input type="radio" class="css-checkboxR" name="reportType" id="visits" value="visits" checked="checked" />
				<label for="visits" class="css-labelR"></label>
			</td>
		</tr>
		<tr>
			<th>Food Pickup Totals</th>
			<td>
				<input type="radio" class="css-checkboxR" name="reportType" id="food" value="food" />
				<label for="food" class="css-labelR"></label>
			</td>
		</tr>
		<tr>
			<th>Volunteer Statistics</th>
			<td>
				<input type="radio" class="css-checkboxR" name="reportType" id="volunteers" value="volunteers" />
				<label for="volunteers" class="css-labelR"></label>
			</td>
		</tr>
		<tr>
			<th>Guest Information</th>
			<td>
				<input id="guestinfo" class="css-checkboxR" type="radio" name="reportType" id="guestinfo" value="guestinfo" />
				<label for="guestinfo" class="css-labelR"></label>
				<img src="resources/questionMark.png" title="This report only lists info on guests that have visited for the set year" />
			</td>
		</tr>
		<tr>
			<td></td>
			<td>
				<input type="button" class="btn" value="Run Report" onclick="runReport('primaryReport');" />
				<?php if($_SESSION['authorityLevel'] == 1) { //only allow downloading .csv if they're level 1 ?>
				<input type="button" class="btn" value="Download Details (.CSV)" onclick="runReport('downloadCSV');" />
				<?php } ?>
			</td>
		</tr>
	</table>
</form>

<script type="text/javascript">
	function runReport(state) {
		//Error check
		if(document.getElementById('guestinfo').checked == false) { //guestinfo is time independent
			if(document.getElementById('startDate').value == '') {
				alert('You need to enter a start date');
				return;
			}
			if(document.getElementById('endDate').value == '') {
				alert('You need to enter an end date');
				return;
			}
		}
		//set action
		if(state == 'primaryReport') {
			document.getElementById('reports').action = 'generateReport.php'
		}
		else {
			document.getElementById('reports').action = 'downloadCSV.php'
		}
		document.getElementById('viewstate').value = state;
		document.getElementById('reports').submit();
	}
</script>

<?php include('appFooter.php'); ?>
