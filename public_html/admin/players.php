<!DOCTYPE html>
<html>
<head>
	<title>player summary</title>
	<style type="text/css">
		a, a:visited, a:active, a:hover  {
			color: #FF7200;
			text-decoration: none;
		}
		a:hover {
			text-decoration: underline;
		}
		html {
			color: #D3D3D3;
			font-family: Verdana,Geneva,sans-serif;
			background: #1D1D1C;
			line-height: 1.2;
		}
		body {
			padding: 20px;
		}
		table {
			width:800px;
			margin: 0 auto 0 auto;
		}
		td {
			padding: 5px;
		}
		td.hwid {
			font-family: monospace;
			text-align: center;
		}
		th {
			padding: 5px;
			border-bottom: solid 1px #a0a0a0;
		}
		tr.even {
			background: #292929;
		}
	</style>
</head>
<body>
<?php
function time_since($since) {
    $chunks = array(
        array(60 * 60 * 24 * 365 , 'year'),
        array(60 * 60 * 24 * 30 , 'month'),
        array(60 * 60 * 24 * 7, 'week'),
        array(60 * 60 * 24 , 'day'),
        array(60 * 60 , 'hour'),
        array(60 , 'minute'),
        array(1 , 'second')
    );

    for ($i = 0, $j = count($chunks); $i < $j; $i++) {
        $seconds = $chunks[$i][0];
        $name = $chunks[$i][1];
        if (($count = floor($since / $seconds)) != 0) {
            break;
        }
    }

    $print = ($count == 1) ? '1 '.$name : "$count {$name}s";
    return $print;
}

include("include.php");

$db = getDB();
if (!$db) {
	echo "<p>ERROR: $err</p>";
}
else {
	$curtime = time();
	echo "<table cellspacing='0' cellpadding='0'><thead><tr><th>player</th><th>reports</th><th>latest report</th><th>hwid</th></tr></thead><tbody>";
	$qr = $db->query("SELECT h.id,h.player,COUNT(r.hwid),MAX(r.utimestamp) FROM hwid AS h LEFT JOIN reports AS r ON h.id=r.hwid GROUP BY h.id ORDER BY h.player COLLATE NOCASE");
	$rownum = 0;
	while ($row = $qr->fetchArray(SQLITE3_NUM)) {
		if ($row[3]) {
			$since = time_since($curtime - $row[3]) . " ago";
		}
		else {
			$since = "";
		}
		echo "<tr class='" . (($rownum++ % 2) ? "even" : "odd") . "'><td><a href='player_detail.php?h=$row[0]'>$row[1]</a></td><td>$row[2]</td><td>$since</td><td class='hwid'>$row[0]</td></tr>";
	}
	echo "</tbody></table>";
	$db->close();
}
?>
</body>
</html>