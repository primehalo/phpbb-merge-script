<!DOCTYPE html><html><head><title>phpBB3 Merge Script</title></head>
<body style="font-size: 100%;">
<h1>phpBB3 Merge Script</h1>

<p>This script was modified by <a href="https://www.phpbb.com/community/memberlist.php?mode=viewprofile&amp;u=183323">Ken Innes IV</a> to make it work with phpBB 3.2, to fix bugs, and to improve informational feedback. The original script was written by <a href="https://www.phpbb.com/community/memberlist.php?mode=viewprofile&amp;u=162644">abertoll</a> and released in the <a href="https://www.phpbb.com/community/viewtopic.php?f=65&amp;t=1917165&amp;start=75#p13249594">Merge two phpbb3 forums</a> thread.</p>

<p>You may run into problems if you're merging very large phpBB boards. If you have one phpBB that is significantly larger than the other I would recommend making it the primary board and merging the smaller one into it. When I was trying to merge a very large board into a smaller board it would fail to increment values in Step 4 for some of the tables. The query result indicated success but the IDs weren't incremented and affected rows was returned as -1.</p>

<?php
set_time_limit(0);
require("config.php");
require("keymap-3.2.7.php");

if ($use_form) {
	if ($_SERVER['REQUEST_METHOD'] == 'POST' )
	{
		$dbhost = empty($_REQUEST['dbhost']) ? $dbhost : $_REQUEST['dbhost'];
		$dbname =  empty($_REQUEST['dbname']) ? $dbname : $_REQUEST['dbname'];
		$dbuser =  empty($_REQUEST['dbuser']) ? $dbuser : $_REQUEST['dbuser'];
		$dbpasswd =  empty($_REQUEST['dbpasswd']) ? $dbpasswd : $_REQUEST['dbpasswd'];
		$db1 =  empty($_REQUEST['db1']) ? $db1 : $_REQUEST['db1'];
		$db2 =  empty($_REQUEST['db2']) ? $db2 : $_REQUEST['db2'];
		$db3 = empty($_REQUEST['db3']) ? $db3 : $_REQUEST['db3'];
		$nametag = empty($_REQUEST['nametag']) ? $nametag : $_REQUEST['nametag'];
	}
	if (empty($dbhost) || empty($dbname) || empty($dbuser) || empty($dbpasswd) || empty($db1) || empty($db2) || empty($db3) || empty($nametag)) {
		$msgs[] = 'All fields are required.';
	}
	if (count(array_unique(array($db1, $db2, $db3))) !== 3) {
		$msgs[] = 'All phpBB table prefixes must be unique.';
	}
	if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !empty($msgs)) {
		echo '<style scoped>dd {margin:0 0 1em 0;}</style>';
		echo !empty($msgs) ? '<p style="color:red;">' . implode(' ', $msgs) . '</p>' : '';
		echo '<form method="post"><dl>';
		echo '<dt><label>DB Host</label></dt><dd><input name="dbhost" value="' . htmlspecialchars($dbhost) . '" /></dd>';
		echo '<dt><label>DB Name</label></dt><dd><input name="dbname" value="' . htmlspecialchars($dbname) . '" /></dd>';
		echo '<dt><label>DB User</label></dt><dd><input name="dbuser" value="' . htmlspecialchars($dbuser) . '" /></dd>';
		echo '<dt><label>DB Password</label></dt><dd><input name="dbpasswd" type="password" value="' . htmlspecialchars($dbpasswd) . '" /></dd>';
		echo '<dt><label>Primary phpBB tables</label></dt><dd><input name="db1" placeholder="phpbb3_" value="' . htmlspecialchars($db1) . '" /></dd>';
		echo '<dt><label>phpBB tables to merge</label></dt><dd><input name="db2" placeholder="other_phpbb_" value="' . htmlspecialchars($db2) . '" /></dd>';
		echo '<dt><label>Merged phpBB tables</label></dt><dd><input name="db3" placeholder="merged_phpbb_" value="' . htmlspecialchars($db3) . '" /></dd>';
		echo '<dt><label>Duplicate Names Suffix</label></dt><dd><input name="nametag" placeholder="" value="' . htmlspecialchars($nametag) . '" /></dd>';
		echo '<dt><button>Do the Merge!</button></dt>';
		echo '</dl></form>';
		echo '</body></html>';
		exit;
	}
}

echo "<pre>\n";
echo "Primary phpBB DB tables: $db1\n";
echo "phpBB DB tables to merge: $db2\n";
echo "Merged phpBB DB tables: $db3\n";
echo "Duplicate usernames from $db2 will be tagged with $nametag\n";

//----------------------------------------------------------------------------//
// Step 1
//----------------------------------------------------------------------------//

echo "<h2>(1) Checking defined key integrity</h2>";
foreach ($fkey as $table => $keys) {
	foreach ($keys as $key => $ref) {
		if ( isset($pkey["$ref"]) || ($ref === 0) ) {
			echo "$key->$ref <span style='color:green;'>ok</span>\n";
		} else {
			die("Error on $key->$ref");
		}
	}
}

$mysqli = new mysqli($dbhost, $dbuser, $dbpasswd, $dbname);
if (mysqli_connect_errno()) die("Connect failed: ". mysqli_connect_error());

//----------------------------------------------------------------------------//
// Step 2
//----------------------------------------------------------------------------//

echo "<h2>(2) Determining offsets for primary keys on $db1\n</h2>";
ob_flush();flush();
foreach ($pkey as $table => $key) {
	// Get the maximum value from db1 and add 100
	$db1table = "$db1$table";
	$result = $mysqli->query("SELECT MAX($key) FROM $db1table");
	$row = $result->fetch_row();
	$max = 0;
	$max += $row[0];
	$offset = round($max + 100,-1);
	$inc["$table"]=$offset;
	echo "$table +$offset\n";
}

//----------------------------------------------------------------------------//
// Step 3
//----------------------------------------------------------------------------//

echo "<h2>(3) Copying $db2 to $db3\n</h2>";
ob_flush();flush();
$total = count($fkey);
$idx = 0;

foreach ($fkey as $table => $keys) {
	$idx++;
	$db2table = "$db2$table";
	$db3table = "$db3$table";
	$mysqli->query("DROP TABLE IF EXISTS $db3table");
	//$result_of_insert = $mysqli->query("CREATE TABLE $db3table SELECT * FROM $db2table");
	$mysqli->query("CREATE TABLE $db3table LIKE $db2table");	// Do the LIKE instead of the SELECT because the SELECT doesn't copy keys
	$result_of_count = $mysqli->query("SELECT COUNT(*) AS `count` FROM $db2table");
	$total_count = $result_of_count->fetch_object()->count;
	$offset = 0;
	$affected_total = 0;
	do {
		echo "[$idx/$total] ";
		echo "$db2table -> $db3table... ";
		ob_flush();flush();
		$result_of_insert = $mysqli->query("INSERT INTO $db3table SELECT * FROM $db2table LIMIT $offset, $limit");
		$affected_total = $offset + $mysqli->affected_rows;
		echo $result_of_insert === true ? "<span style='color:green;'>Success</span> (rows: {$offset}&ndash;{$affected_total} of {$total_count})\n" : "<strong style='color:red;'>Failed ({$mysqli->error})</strong>\n";
		ob_flush();flush();
		$offset += $limit;
	} while ($mysqli->affected_rows == $limit && $affected_total < $total_count);
}
$total = count($pkey);
$idx = 0;
foreach ($pkey as $table => $key) {
	$idx++;
	if (isset($fkey[$table])) {
		echo "[$idx/$total] ";
		echo "$db2table -> $db3table... Already done\n";
		continue; // Already did this one just above with $fkey
	}
	$db2table = "$db2$table";
	$db3table = "$db3$table";
	$mysqli->query("DROP TABLE IF EXISTS $db3table");
	//$result_of_insert = $mysqli->query("CREATE TABLE $db3table SELECT * FROM $db2table");
	$mysqli->query("CREATE TABLE $db3table LIKE $db2table");

	$result_of_count = $mysqli->query("SELECT COUNT(*) AS `count` FROM $db2table");
	$total_count = $result_of_count->fetch_object()->count;
	$offset = 0;
	$affected_total = 0;
	do {

		echo "[$idx/$total] ";
		echo "$db2table -> $db3table... ";
		ob_flush();flush();
		$result_of_insert = $mysqli->query("INSERT INTO $db3table SELECT * FROM $db2table LIMIT $offset, $limit");
		$affected_total = $offset + $mysqli->affected_rows;
		echo $result_of_insert === true ? "<span style='color:green;'>Success</span> (rows: {$offset}&ndash;{$affected_total} of {$total_count})\n" : "<strong style='color:red;'>Failed ({$mysqli->error})</strong>\n";
		ob_flush();flush();
		$offset += $limit;
	} while ($mysqli->affected_rows == $limit && $affected_total < $total_count);
}

//----------------------------------------------------------------------------//
// Step 4
//----------------------------------------------------------------------------//

echo "<h2>(4) Incrementing items in $db3\n</h2>";
ob_flush();flush();
foreach ($pkey as $table => $field) {
	$db3table = "$db3$table";
	$offset = $inc[$table];
	echo "PK $db3table.$field +$offset... ";
	$result_of_update = $mysqli->query("UPDATE $db3table SET $field=$field+$offset WHERE $field > 0");	//ken+ added WHERE $field>0 so we don't increment 0 IDs
	echo $result_of_insert === true ? "<span style='color:green;'>Success</span> (rows: {$mysqli->affected_rows})\n" : "<strong style='color:red;'>Failed ({$mysqli->error})</strong>\n";
	ob_flush();flush();
}
foreach ($fkey as $table => $keys) {
	$db3table = "$db3$table";
	foreach ($keys as $field => $ref) {
		if ($ref === 0) {
			$mysqli->query("UPDATE $db3table SET $field=0");
			echo "FK $db3table.$field = 0\n";
		} else {
			$offset = $inc[$ref];
			echo "FK $db3table.$field +$offset... ";
			$mysqli->query("UPDATE $db3table SET $field=$field+$offset WHERE $field>0");	//ken+ added WHERE $field>0 so we don't increment 0 IDs
			echo $result_of_insert === true ? "<span style='color:green;'>Success</span> (rows: {$mysqli->affected_rows})\n" : "<strong style='color:red;'>Failed ({$mysqli->error})</strong>\n";
		}
		ob_flush();flush();
	}
}
//----------------------------------------------------------------------------//
// Step 5
//----------------------------------------------------------------------------//

echo "<h2>(5) Resolving duplicate users\n</h2>";
ob_flush();flush();

echo "Deleting excluded users (bots, etc.): ";
$query = "DELETE from ${db3}users WHERE ";
foreach ($excludepat as $userpat) {
	echo "$userpat ";
	$query .= "username LIKE '$userpat' OR ";
}
echo "\n";
$query .= "username IS NULL";
$mysqli->query($query);

$query = "SELECT db1.username, db1.user_id, db3.user_id "
	."FROM ${db1}users db1 INNER JOIN ${db3}users db3 "
	."ON UCASE(db1.user_email) = UCASE(db3.user_email) "
	."WHERE ( db1.user_email LIKE '%@%' OR db3.username = 'Anonymous' ) "
	."AND UCASE(db1.username) = UCASE(db3.username) "
	."ORDER BY db1.user_id";
$result = $mysqli->query($query);
while ($row = $result->fetch_row()) {
	echo "Map user $row[0]($row[2]) to $row[1]\n";
	$usermap[$row[2]] = $row[1];
}

foreach ($fkey as $table => $keys) {
    foreach ($keys as $key => $ref) {
		if ($ref === "users") {
			$db3table = "$db3$table";
			echo "Updating $db3table $key\n";
			foreach ($usermap as $user => $update) {
				$mysqli->query("UPDATE $db3table SET $key = $update WHERE $key = $user");
				//echo "... UPDATE $db3table SET $key = $update WHERE $key = $user\n";
			}
		}
    }
}

echo "Deleting duplicate users: ";
$output = "";
foreach ($usermap as $user => $update) {
	$output .= "$user ";
	$mysqli->query("DELETE from ${db3}users WHERE user_id = $user");
}
echo wordwrap($output)."\n";

// For every user where username is the same, change the username
echo "Fixing leftover duplicate usernames\n";
$query = "SELECT db1.username FROM  ${db1}users db1 INNER JOIN ${db3}users db3 ON UCASE(db1.username) = UCASE(db3.username)";
$result = $mysqli->query($query);
while ($row = $result->fetch_row()) {
	//echo "$row[1] -> $row[1]$nametag (row=" . var_export($row, true) . ")\n";
	echo "$row[0] -> $row[0]$nametag\n";
}
$query = "UPDATE ${db1}users db1 INNER JOIN ${db3}users db3 ON UCASE(db1.username) = UCASE(db3.username) SET db3.username = CONCAT(db3.username,'$nametag')";
$result = $mysqli->query($query);

//----------------------------------------------------------------------------//
// Step 6
//----------------------------------------------------------------------------//

echo "<h2>(6) Importing data from db1 to db3\n</h2>";
ob_flush();flush();
// Probably want to query using "show" to get all tables and do the copy or else just do this part by hand.

$result = $mysqli->query("SHOW TABLES LIKE '$db1%'");
while ($row = $result->fetch_row()) {
	//$table = str_replace($db1,"",$row[0]); // remove the db1 prefix
	if (substr($row[0], 0, strlen($db1)) == $db1) {	// remove the db1 prefix
    	$table = substr($row[0], strlen($db1));		// remove the db1 prefix
	}												// remove the db1 prefix

	$db1table = $db1.$table;
	$db3table = $db3.$table;
	$mysqli->query("CREATE TABLE IF NOT EXISTS $db3table LIKE $db1table");
	$result_of_count = $mysqli->query("SELECT COUNT(*) AS `count` FROM $db1table");
	$total_count = $result_of_count->fetch_object()->count;
	$offset = 0;
	$affected_total = 0;
	do {
		echo "$db1table -> $db3table... ";
		ob_flush();flush();
		$result_of_insert = $mysqli->query("INSERT INTO $db3table SELECT * FROM $db1table LIMIT $offset, $limit"); // Need IGNORE in case duplicate entries which can happen in the zebra table if users had been merged
		$affected_total = $offset + $mysqli->affected_rows;
		$affected_range = $mysqli->affected_rows == -1 ? "-1" : "{$offset}&ndash;{$affected_total}";
		echo $result_of_insert === true ? "<span style='color:green;'>Success</span> (rows: {$affected_range} of {$total_count})\n" : "<strong style='color:red;'>Failed ({$mysqli->error}, rows:{$mysqli->affected_rows})</strong>\n";
		if ($result_of_insert !== true) {
			$result_of_insert = $mysqli->query("INSERT IGNORE INTO $db3table SELECT * FROM $db1table LIMIT $offset, $limit"); // Need IGNORE in case duplicate entries which can happen in the zebra table if users had been merged
			$affected_total = $offset + $mysqli->affected_rows;
			$affected_range = $mysqli->affected_rows == -1 ? "-1" : "{$offset}&ndash;{$affected_total}";
			echo "&mdash; Retrying, ignoring errors... ";
			echo $result_of_insert === true ? "<span style='color:green;'>Success</span> (rows: {$affected_range} of {$total_count})\n" : "<strong style='color:red;'>Failed ({$mysqli->error}, rows:{$mysqli->affected_rows})</strong>\n";
		}
		ob_flush();flush();
		$offset += $limit;
	} while ($mysqli->affected_rows == $limit && $affected_total < $total_count);
}

//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++//
// The IDs for the predefined groups in my primary phpbb3 forum were different
// than those in the phpBB I was trying to merge. I wrote this code to unify
// them during the merge.
/*
$predefined_groups = array(
	//Change 	To
	//this 		this
	22717		=> 5,
	22718		=> 6,
	22716		=> 4,
	22713		=> 1,
	22726		=> 7,
	22714		=> 2,
	22715		=> 3,

);
foreach ($predefined_groups as $from => $to) {
	$mysqli->query("UPDATE {$db3}groups SET group_id={$to} WHERE group_id={$from}");
	$mysqli->query("UPDATE {$db3}users SET group_id={$to} WHERE group_id={$from}");
	$mysqli->query("UPDATE {$db3}user_group SET group_id={$to} WHERE group_id={$from}");
	$mysqli->query("UPDATE {$db3}acl_groups SET group_id={$to} WHERE group_id={$from}");
	echo "Changing group_id $from -> $to\n";
}
*/
//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++//

$mysqli->close();
?>
Done!

<h2>(7) Manual cleanup</h2>
<span style="overflow-x: auto;white-space: pre-wrap;white-space: -moz-pre-wrap;white-space: -pre-wrap;white-space: -o-pre-wrap;word-wrap: break-word;">Now it's your turn to go through the forums and fix whatever issues have arisen due to the import, such as organizing the forums, setting forum permissions, figuring out how you want to handle the different config['avatar_salt'] values, configuring ranks, etcetera.</span>

</pre>

</body>
</html>
