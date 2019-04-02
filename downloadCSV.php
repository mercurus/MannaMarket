<?php
	$requiredAuthorityLevel = 1;
	require_once('maintainLogin.php'); //starts the session
	require_once('initializeConn.php'); 
	require_once('resources/getCSV.php');
	
	if(!isset($_POST['viewstate'])) {
		echo 'You need to access this through the reports page.';
	}
	else {
		//get POST variables
		$startDate = isset($_POST['startDate']) ? $_POST['startDate'] : ''; //might not be set if you're doing guestinfo
		$endDate = isset($_POST['endDate']) ? $_POST['endDate'] : '';
		$mmAlias = $_POST['mmLocation'];
		
		//determine what kind of report it is, and set the SQL query
		if($_POST['reportType'] == 'visits') {
			$filename = 'visitInfo';
			$sql = 'SELECT m.name, g.date, f.famlast, f.famfirst, f.numminors, f.numadults, f.numseniors, g.extra, g.warning, g.notes
					FROM family f INNER JOIN guestlog g
						ON f.familyid = g.familyid
					INNER JOIN mmlocations m
						ON g.location = m.alias
					WHERE g.date BETWEEN CAST("' . $startDate . '" AS DATE) AND CAST("' . $endDate . '" AS DATE)';
			if($mmAlias != 'all') {$sql .= ' AND g.location = "'. $mmAlias . '"';}
			$sql .= ' ORDER BY g.location, g.date';
		}
		elseif($_POST['reportType'] == 'food') {
			$filename = 'donationPickups';
			$sql = 'SELECT 	p.pickupid,
							m.name AS "MM Location",
							p.pickupdate,
							l.name AS "Donation Location",
							CONCAT(e.firstname, " ", e.lastname) AS "Logged By",
							p.2htruck,
							p.bakery,
							p.dairy,
							p.deli,
							p.grocery,
							p.household,
							p.meat,
							p.produce,
							p.extrafood,
							p.notes
					FROM pickuplog p INNER JOIN pickuplocations l
						ON p.locationid = l.locationid
					INNER JOIN person e
						ON e.personid = p.personid
					INNER JOIN mmlocations m
						ON m.alias = p.mmalias
					WHERE p.pickupdate BETWEEN CAST("' . $startDate . '" AS DATE) AND CAST("' . $endDate . '" AS DATE)';
			if($mmAlias != 'all') {$sql .= ' AND mmalias = "'. $mmAlias . '"';}
			$sql .= ' ORDER BY p.mmalias, p.pickupdate';
		}
		elseif($_POST['reportType'] == 'volunteers') {
			$filename = 'volunteerStats';
			//I added header names on this because there aren't a million columns
			$sql = 'SELECT  m.name, v.volunteerdate, p.firstname, p.lastname, v.hours
					FROM person p INNER JOIN volunteerlog v
						ON p.personid = v.personid
					INNER JOIN mmlocations m
						ON v.location = m.alias 
					WHERE v.volunteerdate BETWEEN CAST("' . $startDate . '" AS DATE) AND CAST("' . $endDate . '" AS DATE)';
			if($mmAlias != 'all') {$sql .= ' AND location = "'. $mmAlias . '"';}
			$sql .= ' ORDER BY v.location, v.volunteerdate';
		}
		elseif($_POST['reportType'] == 'guestinfo') {
			$filename = 'guestInfo';
			//if there's no posted year, use current year
			$year = ($startDate == '' ? 'NOW()' : '"' . $startDate . '"'); 
			$sql = 'SELECT *, 
						(SELECT g.date 
						FROM guestlog g 
						WHERE g.familyid = f.familyid 
						AND YEAR(g.date) = YEAR(' . $year . ')
						ORDER BY g.date ASC 
						LIMIT 1) AS "First Visit Of The Year" 
					FROM family f'; 
			if($mmAlias != 'all') {$sql .= ' WHERE primarylocation = "' . $mmAlias . '"';}
			$sql .= ' ORDER BY primarylocation';
		}

		//create instance of CSV class, and run it 
		$export = new DBCSV($mysqli, $sql, $filename . date('Y-m-d'));
		$export->downloadCsv();
	}
?>