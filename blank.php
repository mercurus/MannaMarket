<?php
	$title = 'Blank';
	$requiredAuthorityLevel = 1;
	$menuArea = ''; //log, add, admin
	include('appHeader.php'); 
	
	$info = '';
	/*
	$theDate = isset($_POST['theDate']) ? $_POST['theDate'] : date('Y-m-d');
	$mmLocation = isset($_POST['mmLocation']) ? $_POST['mmLocation'] : $_SESSION['locAlias'];
	*/
?>

<form id="blank" name="blank" method="post" action="blank.php">
	<input type="hidden" name="viewstate" id="viewstate" value="" />
	<table>
		<tr>
			<td colspan="2"><span id="sqlInfo"><?php echo $info ?>.</span></td>
		</tr>
		<tr>
			<th></th>
			<td></td>
		</tr>
	</table>
</form>

<script type="text/javascript">
	window.onload = function() {
		//set location comboboxes to posted or default instead of 0
		//document.getElementById('mmLocation').value = "< ?php echo $mmLocation; ?>";
		//fade $info reply
		setTimeout(fadeMessage, 3000);
	}

	function cycleForm(state) {
		document.getElementById('viewstate').value = state;
		document.getElementById('blank').submit();
	}
	
	function trySubmit() {
		//if you haven't selected something on the list
		if(1 == 1) {
			alert('Please fix something');
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
	}
</script>

<?php include('appFooter.php'); ?>
