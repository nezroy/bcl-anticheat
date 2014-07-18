<!DOCTYPE html>
<html>
<head>
	<title>DB debug</title>
</head>
<body>
<form method="POST">
	<select name="table" id="table">
		<option value="" selected></option>
		<option value="hwid">hwid</option>
		<option value="reports">reports</option>
	</select>
	<input type="submit" name="submit" value="submit">
</form>
<?php
include("include.php");

$table = preg_replace("/[^A-Za-z0-9\-_ ]/", "", substr(trim($_POST["table"]), 0, 32));
if ($table) {
	$db = getDB();
	if (!$db) {
		echo "<p>ERROR: $err</p>";
	}
	else {
		echo "<h1>$table</h1>";
		dumpTable($db->query("SELECT * FROM reports WHERE moddata LIKE '%xfire%'"));
		$db->close();
	}
}
?>
</body>
</html>