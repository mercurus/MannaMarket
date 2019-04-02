<?php
	require_once('../initializeConn.php'); //connect to database
	//query the db
	$result = $mysqli->query('SELECT * FROM mmlocations');
	//put results into array
	$locInfo = array(); 
	while($row = $result->fetch_assoc()) {
		$singleLoc = array(); //info for single location
		$colNames = array(); //gets reset each time, oh well
		foreach($row as $key => $value) {
			$colNames[] = $key; //put column headers into array
			$singleLoc[] = $value; //all info for one location goes into this array
		}
		$locInfo[] = $singleLoc; //add location info to total array, reset $singleLoc on next iteration
	}
?>

<html>
	<body>
		<div id="locations"></div>
		<script type="text/javascript">
			window.onload = function() {
				var table, tBody, th, tr, td;
				var locInfo = <?php echo json_encode($locInfo); ?>;
				var colNames = <?php echo json_encode($colNames); ?>;
				
				//initialize the table
				table = document.createElement('table');
				//table.className = 'mytable'; //CSS
				tBody = document.createElement('tbody');
				tr = document.createElement('tr');
				
				//add table headers, start at 1 to leave out alias
				for(var i = 1; i < colNames.length; i++) {
					th = document.createElement('th');
					th.appendChild(document.createTextNode(colNames[i]));
					tr.appendChild(th);
				}
				tBody.appendChild(tr);
				
				//add location info, start n at 1 to leave out alias
				for(var l = 0; l < locInfo.length; l++) {
					tr = document.createElement('tr');
					for(var n = 1; n < locInfo[l].length; n++) {
						td = document.createElement('td');
						td.appendChild(document.createTextNode(locInfo[l][n]));
						tr.appendChild(td);
					}
					tBody.appendChild(tr);
				}
				
				table.appendChild(tBody);
				document.getElementById('locations').appendChild(table);
			}
		</script>
	</body>
</html>