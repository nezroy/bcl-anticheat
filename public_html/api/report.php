<?php
include("../admin/include.php");

$hwid = sanitize_hwid($_POST["h"]);
$moddata = filter_var(trim($_POST["a"]), FILTER_SANITIZE_STRING);
$version = substr(filter_var(trim($_POST["v"]), FILTER_SANITIZE_STRING), 0, 8);
$db = getDB();
$player = verify_hwid($db, $hwid);
if ($player) {
	$o_error = $_FILES["o"]["error"];
	if ($o_error == UPLOAD_ERR_OK) {
		$o_tmp_name = $_FILES["o"]["tmp_name"];
		$ts = time();
		$o_res = move_uploaded_file($o_tmp_name, "$DATA_DIR/$hwid/$ts.jpg");
		if ($o_res) {
			$sql = "INSERT INTO reports (hwid, utimestamp, imgsize, version, moddata) VALUES('$hwid', $ts, " . $_FILES["o"]["size"] . ",'" . SQLite3::escapeString($version) . "','" . SQLite3::escapeString($moddata) . "')";
			$db->exec($sql);
			echo "success";
		}
		else {
			echo "data upload error: move failed";
		}
	}
	else {
		echo "data upload error: $o_error";
	}
}
else {
	echo "HWID not registered\n   !!! register your HWID at http://bcl.vg/bclac.php";
}
$db->close();
?>
