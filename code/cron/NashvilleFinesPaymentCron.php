#!/usr/bin/php
<?php
/*
	Run this once before going into production with no arguments.
	purge sqlite once a week (or month?) via ./nplcron.php 1 admin@email.com
	find/credit orphaned invoices often. (hourly or daily) via ./nplcron.php 2 
*/

define("ROOT_DIR", "/usr/local/VuFind-Plus/vufind/web");
require_once ROOT_DIR . '/services/MyAccount/PayOnlineNashville.php';
date_default_timezone_set('America/Chicago');
$subject = 'CC Payment issues ' . date("Y-m-d");
$from = 'Payment Processing';
$fromaddr = 'root';
// $sqldb = '/tmp/fines/librarypayment.db'; // place this somewhere outside of web root
$sqldb = '/data/vufind-plus/catalog.library.nashville.org/librarypayment.db'; // place this somewhere outside of web root
list(, $call, $notify) = $argv;
$db = new SQLite3($sqldb);
if(!$db) die($db->lastErrorMsg());
if($call == 1) {
	$sql = 'DELETE FROM payments WHERE complete = 1';
	if(!$db->exec($sql)) die($sql . ' ' . $db->lastErrorMsg());
	$sql = 'VACUUM payments';
	if(!$db->exec($sql)) die($sql . ' ' . $db->lastErrorMsg());
} elseif($call == 2) {
	$recycle = new PayOnlineNashville();
	$recycle->recycle();
} else {
	$sql = 'CREATE TABLE payments (id INTEGER PRIMARY KEY AUTOINCREMENT, complete INT NULL, invoice INT(10), patronID VARCHAR(10), data VARCHAR(255))';
	if(!$db->exec($sql)) die($sql . ' ' . $db->lastErrorMsg());
}
if(isset($notify)) {
	if(filter_var($notify, FILTER_VALIDATE_EMAIL)) {
		$sql = "SELECT COUNT(id) as count FROM payments";
		$result = $db->query($sql);
		list($count) = $result->fetchArray();
		if($count > 0) {
			$message = "There are $count invoices that have not been credited.\n";
			mail($notify,$subject,$message,"From: $from <$fromaddr>");
		}
	} else {
		echo "$notify does not appear to be a valid e-mail address.\n";
	}
}
$db->close();
?>

