<!DOCTYPE html>
<html>
<head>
	<title>delete hwid</title>
</head>
<body>
<?php
include("include.php");

$hwid = sanitize_hwid($_POST["hwid"]);
if (isset($_POST["confirm"])) {
	$confirm = $_POST["confirm"];
}
else {
	$confirm = false;
}
if (isset($_POST["fulldelete"])) {
	$fulldelete = $_POST["fulldelete"];
}
else {
	$fulldelete = false;
}
if (!$hwid) {
?>
<form method="POST">
	<label for="hwid">hwid to delete/clear</label>
	<input type="text" name="hwid" id="hwid" size="36">
	<input type="submit" name="submit" value="submit">
</form>
<?php
}
else if ($confirm != "confirm") {
	$db = getDB();
	$sql = "SELECT COUNT(*) FROM reports WHERE hwid='$hwid'";
	$rq = $db->querySingle($sql);
	echo "<p>there are $rq reports associated with $hwid</p>";
	$db->close();
?>
<p>are you certain you want to delete this hwid and related data?</p>
<form method="POST">
	<input type="hidden" name="hwid" value="<?php echo $hwid ?>">
	<input type="hidden" name="confirm" value="confirm">
	<input type="hidden" name="fulldelete" value="fulldelete">
	<input type="submit" name="submit" value="clear reports and delete">
</form>
<form method="POST">
	<input type="hidden" name="hwid" value="<?php echo $hwid ?>">
	<input type="hidden" name="confirm" value="confirm">
	<input type="submit" name="submit" value="clear reports only">
</form>
<?php
}
else {
	$db = getDB();
	// clear reports
	$sql = "DELETE FROM reports WHERE hwid='$hwid'";
	$rq = $db->exec($sql);
	$datdir = "$DATA_DIR/$hwid";
	if (is_dir($datdir)) {
		$files = glob("$datdir/*");
		foreach ($files as $file) {
			if (is_file($file)) {
				unlink($file);
			}
		}
	}
	echo "<p>deleted reports/files for $hwid</p>";
	if ($fulldelete == "fulldelete") {
		// delete dir and hwid
		if (is_dir($datdir)) {
			rmdir($datdir);
		}
		$sql = "DELETE FROM hwid WHERE id='$hwid'";
		$rq = $db->exec($sql);	
		echo "<p>deleted directory and entry for $hwid</p>";
	}
	$db->close();
}
?>
</body>
</html>