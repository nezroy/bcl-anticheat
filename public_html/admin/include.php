<?php
$DATA_DIR = "/home/nezroy/public_html/nezroy.com/bclac/admin/data";
$DB3_FILE = "/run/shm/bclac/bclac.db3";

function getDB() {
	global $DB3_FILE;
	$db = new SQLite3($DB3_FILE);
	return $db;
}

function dumpTable($qr) {
	if ($qr) {
		echo "<table border='1'><thead><tr>";
		for ($i = 0; $i < $qr->numColumns(); $i++) {
			echo "<th>" . $qr->columnName($i) . "</th>";
		}
		while ($row = $qr->fetchArray(SQLITE3_NUM)) {
			echo "<tr>";
			foreach ($row as $value) {
				echo "<td>" . htmlspecialchars($value) . "</td>";
			}
			echo "</tr>";
		}
		echo "</table>";
	}
	else {
		echo "<p>query failed</p>";
	}	
}

function sanitize_hwid($value) {
	return preg_replace("/[^A-Fa-f0-9]/", "", substr(trim($value), 0, 32));
}

function verify_hwid($db, $hwid) {
	if ($db) {
		$sql = "SELECT player FROM hwid WHERE id='" . SQLite3::escapeString($hwid) . "'";
		$qr = $db->querySingle($sql);
		if ($qr) return $qr;
	}
	return false;
}
?>