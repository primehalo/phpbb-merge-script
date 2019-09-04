<?php
//die('Not ready to run yet.');

$dbhost = 'localhost';
$dbname = '';
$dbuser = '';
$dbpasswd = '';
$db1 = 'phpbb3_';
$db2 = 'other_';
$db3 = 'merged_';
$use_form = true;	// Allow this info to be set in a form (true) or force hard-coded values (false)?
$nametag ='.other';	// For duplicate names, will tag names imported from db2
$limit = 50000;		// Limit for the number of records we handle in a single SQL query

// Exclude these users (bots, etc.) by pattern
$excludepat[] = '%Google%';
$excludepat[] = '%Bot%';
$excludepat[] = '%Spider%';
$excludepat[] = '%Crawler%';
$excludepat[] = 'MSN%';
$excludepat[] = '%Validator%';
$excludepat[] = '%search%';
$excludepat[] = 'W3C%';
