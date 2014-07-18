<?php
include("../admin/include.php");

$hwid = sanitize_hwid($_POST["h"]);
$db = getDB();
$player = verify_hwid($db, $hwid);
if ($player) {
	echo "registered to $player";
}
else {
	echo "not registered; go to http://bcl.vg/bclac.php";
}
$db->close();
?>