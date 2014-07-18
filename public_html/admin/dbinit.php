<!DOCTYPE html>
<html>
<head>
	<title>DB debug</title>
</head>
<body>
<?php
include("include.php");

$db = getDB();
if (!$db) {
	echo "<p>ERROR: $err</p>";
}
else {
	// create tables
	$db->exec("CREATE TABLE hwid (id CHAR(32), player CHAR(32), PRIMARY KEY(id))");
	
	$db->exec("CREATE TABLE reports (id INTEGER PRIMARY KEY ASC, hwid CHAR(32), utimestamp INTEGER, imgsize INTEGER, version CHAR(8), moddata TEXT)");
	$db->exec("CREATE INDEX reports_hwid_idx ON reports (hwid)");	
	$db->exec("CREATE INDEX reports_uts_idx ON reports (utimestamp)");	
	
	$db->close();
	
	echo "<p>tables created</p>";
}
?>
</body>
</html>