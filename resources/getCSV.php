<?php
	class DBCSV {
		//nabbed from mml21: http://stackoverflow.com/questions/16391528/query-mysql-and-export-data-as-csv-in-php
		//the \n needs to be in double quotes "" for whatever reason...
		public function __construct($conn, $sql, $f) {
			$this->conn = $conn;
			$this->theq = $sql;
			$this->filename = $f;
		}
		public function downloadCsv() {
			$header = '';
			$data = '';
			$result = $this->conn->query($this->theq);
			$names = $result->fetch_fields(); //column names
			foreach($names as $value) {
			    $header .= $value->name . ',';
		    }
			while($row = $result->fetch_assoc()) {
			    $line = '';
			    foreach($row as $value) {
			        $line .= trim($value) . ',';
			    }
			    $data .= trim($line) . "\n"; //adds row and starts new line
			}
			//if empty query
			if ($data == '') {
			    $data = "\nno matching records found\n";
			}
			header('Content-type: application/octet-stream');
			header('Content-Disposition: attachment; filename=' . $this->filename . '.csv');
			header('Pragma: no-cache');
			header('Expires: 0');
			echo $header . "\n" . $data;
		}
	}
?>

