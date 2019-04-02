<!DOCTYPE html>
<html>
<head>
	<title>MM Report</title>
</head>
<body>
	
<style type="text/css">
	body {
		background: #eee;
		font-family: Tahoma, Geneva, sans-serif;
	}
	h1, h3 {
		color: #222;
	}
	table.reportTable {
		border-collapse: collapse;
	}
	table.reportTable tr:nth-child(even) { /*(even) or (2n 0)*/
		background: #EAF4FF;
	}
	table.reportTable tr:nth-child(odd) { /*(odd) or (2n 1)*/
		background: #A4D1FF;
	}
	table.reportTable tr td, th {
		border: 1px solid #222;
		min-width: 40px;
		padding: 4px;
	}
	table.reportTable tr td {
		text-align: left;
	}
	table.reportTable tr th {
		text-align: center;
	}
</style>

<?php
	$requiredAuthorityLevel = 2;
	require_once('maintainLogin.php'); //starts the session
	require_once('initializeConn.php'); 
	
	//Populate an array of MM Location names
	$mmLocArray = array('all' => 'all');
	$result = $mysqli->query('SELECT alias, name FROM mmlocations;');
	while($row = $result->fetch_assoc()) {
		$mmLocArray[$row['alias']] = $row['name'];
	}
	
	//if they're simplying typing the link into the address bar 
	//or generally trying to access it when it hasn't had any posted info, tell em to do it right
	if(!isset($_POST['viewstate'])) {
		echo 'You need to access this through the reports page.';
	}
	//otherwise figure out which report it is and run it
	elseif($_POST['reportType'] == 'visits') {
		//get POST variables
		$startDate = $_POST['startDate'];
		$endDate = $_POST['endDate'];
		$mmAlias = $_POST['mmLocation'];
		$mmLocation = $mmLocArray[$_POST['mmLocation']];
		//initialize other variables
		$tableData = array();
		$regServiceHHList = array(); //keeps track of first (regular service) visits in query
		$curLoc = '';
		$curLocAlias = '';
		$curDate = '';
		$curMonth = '';
		
		$displayGT = false; //display grand totals (stays false if there's only one month)
		//create totals arrays
		$dateTotals = $regService = $extraService = $grandTotals = $firstVisits =
		array(	'minors' => 0,
				'adults' => 0,
				'seniors' => 0,
				'totalHH' => 0);
		
		//get first visits
		$firstVisitsRaw = array();
		$firstQuery = ' SELECT f.familyid, f.primaryshopper,
						(SELECT g.date 
						FROM guestlog g 
						WHERE g.familyid = f.familyid 
						ORDER BY g.date ASC LIMIT 1) AS fv
						FROM family f';		
		$result = $mysqli->query($firstQuery);
		while($row = $result->fetch_assoc()) {
			$y = substr($row['fv'], 0, 4);	
			$m = substr($row['fv'], 5, 2);
			//$d = substr($row['fv'], 8, 2);
			$firstVisitsRaw[$row['familyid']] = array('ps' => $row['primaryshopper'], 'y' => $y, 'm' => $m, 'fv' => $row['fv']);
			echo $firstVisitsRaw[$row['familyid']]['fv'];
		}
				
		//set main SQL query
		$sql = 'SELECT g.location, g.date, MONTH(g.date) AS mo, YEAR(g.date) AS yr, g.familyid, f.numminors, f.numadults, f.numseniors   
				FROM family f INNER JOIN guestlog g
					ON f.familyid = g.familyid
				WHERE g.date BETWEEN CAST("' . $startDate . '" AS DATE) AND CAST("' . $endDate . '" AS DATE)';
		if($mmAlias != 'all') {$sql .= ' AND g.location = "'. $mmAlias . '"';}
		$sql .= ' ORDER BY g.location, g.date';
		
		$result = $mysqli->query($sql);
		if($result->num_rows > 0) {
			//loop through results and add totals
			while($row = $result->fetch_assoc()) {
				//set date initially
				if($curDate == '') {$curDate = $row['date'];}
				
				//first add up totals from individual visits
				if($curDate == $row['date']) {
					$dateTotals['minors'] += $row['numminors'];
					$dateTotals['adults'] += $row['numadults'];
					$dateTotals['seniors'] += $row['numseniors'];
					$dateTotals['totalHH']++;
					//check for a first visit
					if($firstVisitsRaw[$row['familyid']]['y'] == $row['yr'] &&
					   $firstVisitsRaw[$row['familyid']]['m'] == $row['mo']) {
					   	$firstVisits['minors'] += $row['numminors'];
						$firstVisits['adults'] += $row['numadults'];
						$firstVisits['seniors'] += $row['numseniors'];
						$firstVisits['totalHH']++;
						//then negate the entry so it doesn't proc more than once
						$firstVisitsRaw[$row['familyid']]['m'] = $firstVisitsRaw[$row['familyid']]['y'] = 0;
					}
				}
				//if the date changed, display totals for the visit and reset array
				else {
					//insert data to report table
					$dTotal = $dateTotals['minors'] + $dateTotals['adults'] + $dateTotals['seniors'];
					$tableData[] = array(1, $curDate, $dateTotals['totalHH'], quickPercent($dateTotals['minors'], $dTotal), quickPercent($dateTotals['adults'], $dTotal), quickPercent($dateTotals['seniors'], $dTotal), $dTotal);
					//then set totals to current row's totals
					$dateTotals['minors'] = $row['numminors'];
					$dateTotals['adults'] = $row['numadults'];
					$dateTotals['seniors'] = $row['numseniors'];
					$dateTotals['totalHH'] = 1;
					$curDate = $row['date'];
				}
				
				//then when the location or month changes list totals and new header (typically won't fire on last iteration)
				if($curLocAlias != $row['location'] || $curMonth != $row['mo']) {
					//if it's not the first iteration, set totals for the location/month
					if($curLocAlias != '') {
						//make total variables for percents
						$rsTotal = $regService['minors'] + $regService['adults'] + $regService['seniors'];
						$esTotal = $extraService['minors'] + $extraService['adults'] + $extraService['seniors'];
						$tableData[] = array(1, 'Regular Service', $regService['totalHH'], quickPercent($regService['minors'], $rsTotal), quickPercent($regService['adults'], $rsTotal), quickPercent($regService['seniors'], $rsTotal), $rsTotal);
						$tableData[] = array(1, 'Extra Service', $extraService['totalHH'], quickPercent($extraService['minors'], $esTotal), quickPercent($extraService['adults'], $esTotal), quickPercent($extraService['seniors'], $esTotal), $esTotal);
						$tableData[] = array(1, monthName($curMonth) . ' Totals', $regService['totalHH'] + $extraService['totalHH']
																				, quickPercent($regService['minors'] + $extraService['minors'], $rsTotal + $esTotal)
																				, quickPercent($regService['adults'] + $extraService['adults'], $rsTotal + $esTotal)
																				, quickPercent($regService['seniors'] + $extraService['seniors'], $rsTotal + $esTotal), $rsTotal + $esTotal);
						$tableData[] = array(1, monthName($curMonth) . ' ' . $row['yr'] . ' First Visits', $firstVisits['totalHH'], $firstVisits['minors'], $firstVisits['adults'], $firstVisits['seniors'], $firstVisits['minors'] + $firstVisits['adults'] + $firstVisits['seniors']);
						//reset current totals
						$regService['minors'] = $extraService['minors'] = $firstVisits['minors'] = 0;
						$regService['adults'] = $extraService['adults'] = $firstVisits['adults'] = 0;
						$regService['seniors'] = $extraService['seniors'] = $firstVisits['seniors'] = 0;
						$regService['totalHH'] = $extraService['totalHH'] = $firstVisits['totalHH'] = 0;
						$displayGT = true;
					}
					//set current information (location and dates)
					$curLocAlias = $row['location'];
					$curLoc = $mmLocArray[$row['location']];
					$curDate = $row['date'];
					$curMonth = $row['mo'];
					//clear family id array
					$regServiceHHList = array();
					//insert headers into table (will fire the first iteration, because $curLocAlias = '' initially)
					$tableData[] = array(6, $curLoc, 'Households', 'Minors', 'Adults', 'Seniors', 'Total Individuals');
				}

				//then figure out whether it's regular or extra service
				//this is after the previous block which sets totals because 
				//if family is unique, add totals to regular service
				if(uniqueFamily($row['familyid'])) {
					$regService['minors'] += $row['numminors'];
					$regService['adults'] += $row['numadults'];
					$regService['seniors'] += $row['numseniors'];
					$regService['totalHH']++;
				}
				//or if it's not the family's first visit, add to extra service
				else {
					$extraService['minors'] += $row['numminors'];
					$extraService['adults'] += $row['numadults'];
					$extraService['seniors'] += $row['numseniors'];
					$extraService['totalHH']++;
				}
				//then add into grand totals
				$grandTotals['minors'] += $row['numminors'];
				$grandTotals['adults'] += $row['numadults'];
				$grandTotals['seniors'] += $row['numseniors'];
				$grandTotals['totalHH']++;
			}
			//the while loop has finished
			//final listing of totals
			//current date
			$dTotal = $dateTotals['minors'] + $dateTotals['adults'] + $dateTotals['seniors'];
			$tableData[] = array(1, $curDate, $dateTotals['totalHH'], quickPercent($dateTotals['minors'], $dTotal), quickPercent($dateTotals['adults'], $dTotal), quickPercent($dateTotals['seniors'], $dTotal), $dTotal);
			//regular service, extra service, and month totals
			$rsTotal = $regService['minors'] + $regService['adults'] + $regService['seniors'];
			$esTotal = $extraService['minors'] + $extraService['adults'] + $extraService['seniors'];
			$tableData[] = array(1, 'Regular Service', $regService['totalHH'], quickPercent($regService['minors'], $rsTotal), quickPercent($regService['adults'], $rsTotal), quickPercent($regService['seniors'], $rsTotal), $rsTotal);
			$tableData[] = array(1, 'Extra Service', $extraService['totalHH'], quickPercent($extraService['minors'], $esTotal), quickPercent($extraService['adults'], $esTotal), quickPercent($extraService['seniors'], $esTotal), $esTotal);
			$tableData[] = array(1, monthName($curMonth) . ' Totals', $regService['totalHH'] + $extraService['totalHH']
																	, quickPercent($regService['minors'] + $extraService['minors'], $rsTotal + $esTotal)
																	, quickPercent($regService['adults'] + $extraService['adults'], $rsTotal + $esTotal)
																	, quickPercent($regService['seniors'] + $extraService['seniors'], $rsTotal + $esTotal), $rsTotal + $esTotal);
			$tableData[] = array(1, monthName($curMonth) . ' ' . substr($curDate, 0, 4) . ' First Visits', $firstVisits['totalHH'], $firstVisits['minors'], $firstVisits['adults'], $firstVisits['seniors'], $firstVisits['minors'] + $firstVisits['adults'] + $firstVisits['seniors']);
			if($displayGT) {
				$gTotal = $grandTotals['minors'] + $grandTotals['adults'] + $grandTotals['seniors'];
				$tableData[] = array(1, '**Grand Totals**', $grandTotals['totalHH'], quickPercent($grandTotals['minors'], $gTotal), quickPercent($grandTotals['adults'], $gTotal), quickPercent($grandTotals['seniors'], $gTotal), $gTotal);
			}
			//finally, display the report yo
			generateTable('Visit Information', $tableData);
		}
		else {
			echo 'There were no family records between ' . $startDate . ' and ' . $endDate . ' for ' . $mmLocation;
		}
	}
	elseif($_POST['reportType'] == 'food') {
		//get POST variables
		$startDate = $_POST['startDate'];
		$endDate = $_POST['endDate'];
		$mmAlias = $_POST['mmLocation'];
		$mmLocation = $mmLocArray[$_POST['mmLocation']];
		//initialize other variables
		$tableData = array();
		$days = 0;
		$totalDays = 0;
		$curLoc = '';
		$curLocAlias = '';
		$curTotals = $grandTotals =
		array(	'2HTruck' => 0,
				'Bakery' => 0,
				'Dairy' => 0,
				'Deli' => 0,
				'Grocery' => 0,
				'Household' => 0,
				'Meat' => 0,
				'Produce' => 0,
				'ExtraFood' => 0);
		//get pickup location names
		$pickupLoc = array();
		$result = $mysqli->query('SELECT locationid, name FROM pickuplocations');
		while($row = $result->fetch_assoc()) {	
			$pickupLoc[$row['locationid']] = $row['name'];
		}

		//set main SQL query
		$sql = 'SELECT * FROM pickuplog
				WHERE pickupdate BETWEEN CAST("' . $startDate . '" AS DATE) AND CAST("' . $endDate . '" AS DATE)';
		if($mmAlias != 'all') {$sql .= ' AND mmalias = "'. $mmAlias . '"';}
		$sql .= ' ORDER BY mmalias, locationid, pickupdate';
		
		$result = $mysqli->query($sql);
		if($result->num_rows > 0) {
			//loop through results and add totals
			while($row = $result->fetch_assoc()) {
				//when the location changes
				if($curLocAlias != $row['mmalias']) {
					//if it's not the first iteration, set totals for the location (works only when querying all locations)
					if($curLocAlias != '') {
						$tableData[] = array(1, 'Totals', $days . ' pickup(s)', $curTotals['2HTruck'], $curTotals['Bakery'], $curTotals['Dairy'], $curTotals['Deli'], $curTotals['Grocery'], $curTotals['Household'], $curTotals['Meat'], $curTotals['Produce'], $curTotals['ExtraFood'],
																				$curTotals['2HTruck'] + $curTotals['Bakery'] + $curTotals['Dairy'] + $curTotals['Deli'] + $curTotals['Grocery'] + $curTotals['Household'] + $curTotals['Meat'] + $curTotals['Produce'] + $curTotals['ExtraFood'], '');
						//reset current totals
						$curTotals = array(	'2HTruck' => 0,
											'Bakery' => 0,
											'Dairy' => 0,
											'Deli' => 0,
											'Grocery' => 0,
											'Household' => 0,
											'Meat' => 0,
											'Produce' => 0,
											'ExtraFood' => 0);
						$days = 0;
					}
					//set headers
					$curLocAlias = $row['mmalias'];
					$curLoc = $mmLocArray[$row['mmalias']];
					$tableData[] = array(13, $curLoc, 'Date', '2H Truck', 'Bakery', 'Dairy', 'Deli', 'Grocery', 'Household', 'Meat', 'Produce', 'Extra Food', 'Total', 'Notes');
				}
				//same location, so add info of single pickup
				$tableData[] = array(0, $pickupLoc[$row['locationid']] , $row['pickupdate'], $row['2htruck'], $row['bakery'], $row['dairy'], $row['deli'], $row['grocery'], $row['household'], $row['meat'], $row['produce'], $row['extrafood'], 
																							$row['2htruck'] + $row['bakery'] + $row['dairy'] + $row['deli'] + $row['grocery'] + $row['household'] + $row['meat'] + $row['produce'] + $row['extrafood'], $row['notes']);
				//add that info to the location totals
				$curTotals['2HTruck'] += $row['2htruck'];
				$curTotals['Bakery'] += $row['bakery'];
				$curTotals['Dairy'] += $row['dairy'];
				$curTotals['Deli'] += $row['deli'];
				$curTotals['Grocery'] += $row['grocery'];
				$curTotals['Household'] += $row['household'];
				$curTotals['Meat'] += $row['meat'];
				$curTotals['Produce'] += $row['produce'];
				$curTotals['ExtraFood'] += $row['extrafood'];
				$days++;
				//and also into the grand totals
				if($mmAlias == 'all') {
					$grandTotals['2HTruck'] += $row['2htruck'];
					$grandTotals['Bakery'] += $row['bakery'];
					$grandTotals['Dairy'] += $row['dairy'];
					$grandTotals['Deli'] += $row['deli'];
					$grandTotals['Grocery'] += $row['grocery'];
					$grandTotals['Household'] += $row['household'];
					$grandTotals['Meat'] += $row['meat'];
					$grandTotals['Produce'] += $row['produce'];
					$grandTotals['ExtraFood'] += $row['extrafood'];
					$totalDays++;
				}
			}
			//final (or only) totals
			$tableData[] = array(1, 'Totals', $days . ' pickup(s)', $curTotals['2HTruck'], $curTotals['Bakery'], $curTotals['Dairy'], $curTotals['Deli'], $curTotals['Grocery'], $curTotals['Household'], $curTotals['Meat'], $curTotals['Produce'], $curTotals['ExtraFood'], 
																	$curTotals['2HTruck'] + $curTotals['Bakery'] + $curTotals['Dairy'] + $curTotals['Deli'] + $curTotals['Grocery'] + $curTotals['Household'] + $curTotals['Meat'] + $curTotals['Produce'] + $curTotals['ExtraFood'], '');
			//grand totals if it has all locations
			if($mmAlias == 'all') { 
				$tableData[] = array(1, '**Grand Totals**', $totalDays . ' pickup(s)', $grandTotals['2HTruck'], $grandTotals['Bakery'], $grandTotals['Dairy'], $grandTotals['Deli'], $grandTotals['Grocery'], $grandTotals['Household'], $grandTotals['Meat'], $grandTotals['Produce'], $grandTotals['ExtraFood'], 
																				$grandTotals['2HTruck'] + $grandTotals['Bakery'] + $grandTotals['Dairy'] + $grandTotals['Deli'] + $grandTotals['Grocery'] + $grandTotals['Household'] + $grandTotals['Meat'] + $grandTotals['Produce'] + $grandTotals['ExtraFood'], '');
			}
			//finally, display the report yo
			generateTable('Food Pickup Totals', $tableData);
		}
		else {
			echo 'There were no food pickup records between ' . $startDate . ' and ' . $endDate . ' for ' . $mmLocation;
		}
	}
	elseif($_POST['reportType'] == 'volunteers') {
		//get POST variables
		$startDate = $_POST['startDate'];
		$endDate = $_POST['endDate'];
		$mmAlias = $_POST['mmLocation'];
		$mmLocation = $mmLocArray[$_POST['mmLocation']];
		//initialize other variables
		$tableData = array();
		$totalVolunteers = 0;
		$totalHoursWorked = 0;
		$totalWages = 0;
		$wage = 21.95;
		$days = 0;
		
		//set main SQL query
		$sql = 'SELECT location, volunteerdate, COUNT(personid) AS numworkers, SUM(hours) AS totalhours FROM volunteerlog 
				WHERE volunteerdate BETWEEN CAST("' . $startDate . '" AS DATE) AND CAST("' . $endDate . '" AS DATE)';
		if($mmAlias != 'all') {$sql .= ' AND location = "'. $mmAlias . '"';}
		$sql .= ' GROUP BY location, volunteerdate';

		$result = $mysqli->query($sql);
		if($result->num_rows > 0) {
			//initial headers
			$tableData[] = array(5, 'Location', 'Date', 'Total Volunteers', 'Hours Worked', 'Wage Equivalent');
			//loop through results and add totals
			while($row = $result->fetch_assoc()) {
				$tableData[] = array(0, $mmLocArray[$row['location']], $row['volunteerdate'], $row['numworkers'], $row['totalhours'], '$' . round($row['totalhours'] * $wage, 2));
				$totalHoursWorked += $row['totalhours'];
				$totalWages += round($row['totalhours'] * $wage, 2);
				$totalVolunteers += $row['numworkers'];
				$days++;
			}
			if($days > 1) {
				$tableData[] = array(1, 'Grand Totals', $days . ' Days', $totalVolunteers, $totalHoursWorked, '$' . round($totalWages, 2));
			}
			//finally, display the report yo
			generateTable('Volunteer Statistics', $tableData);
			echo 'Wage used: $' . $wage;
		}
		else {
			echo 'There were no volunteer records between ' . $startDate . ' and ' . $endDate . ' for ' . $mmLocation;
		}
	}
	elseif($_POST['reportType'] == 'guestinfo') {
		$year = ($_POST['startDate'] != '' ? '"' . $_POST['startDate'] . '"': 'NOW()');
		$tableData = array();
		//get POST variables
		$mmAlias = $_POST['mmLocation'];
		$mmLocation = $mmLocArray[$_POST['mmLocation']];
		//set other variables
		$curLoc = '';
		$curLocAlias = '';
		$locTotals = $grandTotals =
		array(	'minors' => 0,
				'adults' => 0,
				'seniors' => 0,
				'singles' => 0,
				'totalHH' => 0,
				'prayer' => 0,
				'crisis' => 0,
				'blockgrant' => 0,
				'income' => 0);
		
		//set main SQL query
		$sql = 'SELECT * FROM family f
				WHERE f.familyid IN 
				(SELECT g.familyid
				FROM guestlog g
				WHERE YEAR(g.date) = YEAR(' . $year . '))'; 
		if($mmAlias != 'all') {$sql .= ' AND primarylocation = "' . $mmAlias . '"';}
		$sql .= ' ORDER BY primarylocation';
		
		$result = $mysqli->query($sql);
		if($result->num_rows > 0) {
			//loop through results and add totals
			while($row = $result->fetch_assoc()) {
				if($curLocAlias != $row['primarylocation']) {
					//if it's not the first iteration, set totals for the location (works only when querying all locations)
					if($curLocAlias != '') {
						//report totals
						$t = $locTotals['minors'] + $locTotals['adults'] + $locTotals['seniors'];
						$tableData[] = array(1, 'Total Households', $locTotals['totalHH']);
						$tableData[] = array(1, 'Minors', quickPercent($locTotals['minors'], $t));
						$tableData[] = array(1, 'Adults', quickPercent($locTotals['adults'], $t));
						$tableData[] = array(1, 'Seniors', quickPercent($locTotals['seniors'], $t));
						$tableData[] = array(1, 'Total Individuals', $t);
						$tableData[] = array(1, 'Households of One', $locTotals['singles']);
						$tableData[] = array(1, 'Average Income', '$' . round($locTotals['income'] / $locTotals['totalHH'], 0));
						$tableData[] = array(1, 'Desiring Prayer', quickPercent($locTotals['prayer'], $locTotals['totalHH']));
						$tableData[] = array(1, 'Families in Crisis', quickPercent($locTotals['crisis'], $locTotals['totalHH']));
						$tableData[] = array(1, 'Block Grant Recipients', quickPercent($locTotals['blockgrant'], $locTotals['totalHH']));
						//then reset current totals
						$locTotals = array(	'minors' => 0,
											'adults' => 0,
											'seniors' => 0,
											'singles' => 0,
											'totalHH' => 0,
											'prayer' => 0,
											'crisis' => 0,
											'blockgrant' => 0,
											'income' => 0);
					}
					//set headers
					$curLocAlias = $row['primarylocation'];
					$curLoc = $mmLocArray[$row['primarylocation']];
					$tableData[] = array(1, $curLoc, '', '');
				}
				//add data to array
				$t = $row['numminors'] + $row['numadults'] + $row['numseniors'];
				$locTotals['minors'] += $row['numminors'];
				$locTotals['adults'] += $row['numadults'];
				$locTotals['seniors'] += $row['numseniors'];
				if($t == 1) {$locTotals['singles']++;} //households of 1
				$locTotals['totalHH']++;
				$locTotals['income'] += $row['householdincome'];
				$locTotals['prayer'] += $row['prayer'];
				$locTotals['crisis'] += $row['crisis'];
				$locTotals['blockgrant'] += $row['blockgrant'];
				//if all locations are set, add to grand totals
				if($mmAlias == 'all') {
					$grandTotals['minors'] += $row['numminors'];
					$grandTotals['adults'] += $row['numadults'];
					$grandTotals['seniors'] += $row['numseniors'];
					if($t == 1) {$grandTotals['singles']++;} //households of 1
					$grandTotals['totalHH']++;
					$grandTotals['income'] += $row['householdincome'];
					$grandTotals['prayer'] += $row['prayer'];
					$grandTotals['crisis'] += $row['crisis'];
					$grandTotals['blockgrant'] += $row['blockgrant'];
				}
			}
			//while loop has finished, display final totals
			$t = $locTotals['minors'] + $locTotals['adults'] + $locTotals['seniors'];
			$tableData[] = array(1, 'Total Households', $locTotals['totalHH']);
			$tableData[] = array(1, 'Minors', quickPercent($locTotals['minors'], $t));
			$tableData[] = array(1, 'Adults', quickPercent($locTotals['adults'], $t));
			$tableData[] = array(1, 'Seniors', quickPercent($locTotals['seniors'], $t));
			$tableData[] = array(1, 'Total Individuals', $t);
			$tableData[] = array(1, 'Households of One', $locTotals['singles']);
			$tableData[] = array(1, 'Average Income', '$' . round($locTotals['income'] / $locTotals['totalHH'], 0));
			$tableData[] = array(1, 'Desiring Prayer', quickPercent($locTotals['prayer'], $locTotals['totalHH']));
			$tableData[] = array(1, 'Families in Crisis', quickPercent($locTotals['crisis'], $locTotals['totalHH']));
			$tableData[] = array(1, 'Block Grant Recipients', quickPercent($locTotals['blockgrant'], $locTotals['totalHH']));
			//and grand totals if applicable
			if($mmAlias == 'all') {
				$t = $grandTotals['minors'] + $grandTotals['adults'] + $grandTotals['seniors'];
				$tableData[] = array(1, '**Grand Totals**', '', '');
				$tableData[] = array(1, 'Total Households', $grandTotals['totalHH']);
				$tableData[] = array(1, 'Minors', quickPercent($grandTotals['minors'], $t));
				$tableData[] = array(1, 'Adults', quickPercent($grandTotals['adults'], $t));
				$tableData[] = array(1, 'Seniors', quickPercent($grandTotals['seniors'], $t));
				$tableData[] = array(1, 'Total Individuals', $t);
				$tableData[] = array(1, 'Households of One', $grandTotals['singles']);
				$tableData[] = array(1, 'Average Income', '$' . round($grandTotals['income'] / $grandTotals['totalHH'], 0));
				$tableData[] = array(1, 'Desiring Prayer', quickPercent($grandTotals['prayer'], $grandTotals['totalHH']));
				$tableData[] = array(1, 'Families in Crisis', quickPercent($grandTotals['crisis'], $grandTotals['totalHH']));
				$tableData[] = array(1, 'Block Grant Recipients', quickPercent($grandTotals['blockgrant'], $grandTotals['totalHH']));
			}
			//finally, display the report yo
			generateTable('Guest Information Statistics', $tableData);
			echo "Note: These totals only reflect households that visited in the selected year.\nIf no year was selected the current year is used.";
		}
		else {
			echo 'There were no family records for ' . $mmLocation;
		}
	}
	else {
		echo 'Something went wrong...';
	}
	
	function quickPercent($n, $t) {
		if($t > 0) {
			$p = round(($n / $t) * 100, 2);
			return $n . ' (' . $p . '%)';
		}
		else {
			return $n . ' (' . $t . '%)';
		}
	}
	
	function uniqueFamily($fid) {
		global $regServiceHHList;
		for($i = 0; $i < sizeof($regServiceHHList); ++$i) {
			//see if the family id is on the list
			if($fid == $regServiceHHList[$i]) {
				return false;
			}
		}
		$regServiceHHList[] = $fid; //it's unique, so add it to the list
		return true;
	}
	
	function monthName($n) {
		switch($n) {
			case 1: return 'January'; break;
			case 2: return 'February'; break;
			case 3: return 'March'; break;
			case 4: return 'April'; break;
			case 5: return 'May'; break;
			case 6: return 'June'; break;
			case 7: return 'July'; break;
			case 8: return 'August'; break;
			case 9: return 'Septmeber'; break;
			case 10: return 'October'; break;
			case 11: return 'November'; break;
			case 12: return 'December'; break;
		}
	}

	function generateTable($header, $data) {
		//$data is a multidimensional array with a single index consisting of {numTHs, [column data]}
		global $mmLocation, $startDate, $endDate;
		echo "<h1>" . $header . "</h1>\n";
		echo "<h3>For Location: " . $mmLocation . "</h3>\n";
		if($_POST['reportType'] != 'guestinfo') { //guestinfo is time independent, so if it's not that report, then show date range
			echo "<h3>" . $startDate . " to " . $endDate . "</h3>\n";
		}
		echo "<table class='reportTable'>\n";
		for($i = 0; $i < sizeof($data); ++$i) {
			echo "<tr>\n";	
			$numTH = $data[$i][0] + 1;
			for($d = 1; $d < sizeof($data[$i]); ++$d) {
				echo --$numTH > 0 ? "<th>" : "<td>"; //decide whether it's a header or regular cell
				echo $data[$i][$d];
				echo $numTH > 0 ? "</th>\n" : "</td>\n";
			}
			echo "</tr>\n";
		}
		echo "</table>\n";
	}
	
	mysqli_close($mysqli); 
?>

</body>
</html>
