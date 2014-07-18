<?php
include("../admin/include.php");

if (isset($_POST["submit"])) {
	$hwid = sanitize_hwid($_POST["h"]);
	$player = preg_replace("/[^A-Za-z0-9_\- ]/", "", substr(trim($_POST["p"]), 0, 32));
	$pdir = "/home/nezroy/public_html/nezroy.com/bclac/admin/data/$hwid";
	
	$db = getDB();
	if ($db && (is_dir($pdir) || mkdir($pdir))) {		
		$sql = "SELECT COUNT(*) FROM hwid WHERE id='" . SQLite3::escapeString($hwid) . "'";
		$qr = $db->querySingle($sql);
		if ($qr == 1) {
			$sql = "UPDATE hwid SET player='" . SQLite3::escapeString($player) . "' WHERE id='" . SQLite3::escapeString($hwid) . "'";
			$db->exec($sql);			
		}
		else {
			$sql = "INSERT INTO hwid (id, player) VALUES('" . SQLite3::escapeString($hwid) . "','" . SQLite3::escapeString($player) . "')";
			$db->exec($sql);
		}
		$sql = "SELECT player FROM hwid WHERE id='" . SQLite3::escapeString($hwid) . "'";
		$qr = $db->querySingle($sql);
		if ($qr == $player) {					
			echo "success";
		}
		else {
			echo "HWID registration failed";
		}
	}
	else {
		echo("INIT ERROR: $err");
	}
	$db->close();
}
else {
	echo "no POST data";
}