<?php

// TO DO: copy pika config > [catalog|school] > basic display > additional css from galacto to production
// TO DO: ensure MNPS Staff, Library Staff, and Libraries should be included (privacy?)
// TO DO: maybe sort teachers with their homerooms
// TO DO: sort opted-outs with homeroom

// SYNTAX: path/to/php NashvilleAdHocOverdues.php $_SERVER['SERVER_NAME'], e.g., 
// $ sudo /opt/rh/php55/root/usr/bin/php NashvilleAdHocOverdues.php nashville.test

$_SERVER['SERVER_NAME'] = $argv[1];
if(is_null($_SERVER['SERVER_NAME'])) {
	echo 'SYNTAX: path/to/php NashvilleAdHocOverdues.php $_SERVER[\'SERVER_NAME\'], e.g., $ sudo /opt/rh/php55/root/usr/bin/php NashvilleAdHocOverdues.php nashville.test\n';
	exit();
}

global $errorHandlingEnabled;
$errorHandlingEnabled = true;

$startTime = microtime(true);
require_once '../web/sys/Logger.php';
require_once '../web/sys/PEAR_Singleton.php';
PEAR_Singleton::init();

require_once '../web/sys/ConfigArray.php';
require_once 'PEAR.php';

// Sets global error handler for PEAR errors
PEAR::setErrorHandling(PEAR_ERROR_CALLBACK, 'utilErrorHandler');

// Read Config Pwd file
$configArray = readConfig();

$carlx_db_php = $configArray['Catalog']['carlx_db_php'];
$carlx_db_php_user = $configArray['Catalog']['carlx_db_php_user'];
$carlx_db_php_password = $configArray['Catalog']['carlx_db_php_password'];

$reportPath = preg_replace('/[^\/]$/','$0/',$configArray['Site']['reportPath']);

// delete old files
array_map('unlink', glob($reportPath . "*_school_report.csv"));

// connect to carlx oracle db
$conn = oci_connect($carlx_db_php_user, $carlx_db_php_password, $carlx_db_php);
if (!$conn) {
    $e = oci_error();
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

// query school branch codes
$sql = <<<EOT
	select branch_v.branchcode
	from branch_v
	where branch_v.branchgroup = '2'
	order by branch_v.branchcode
EOT;
$stid = oci_parse($conn, $sql);
oci_execute($stid);
while (($row = oci_fetch_array ($stid, OCI_ASSOC)) != false) {
	$aSchool[] = $row['BRANCHCODE'];
}
oci_free_statement($stid);

// for each school branch, query overdues
foreach ($aSchool as $sSchool) {
	//echo $sSchool . "\n";

	$sql = <<<EOT
    select
      patronbranch.branchcode AS Home_Lib_Code
      , patronbranch.branchname AS Home_Lib
      , bty_v.btynumber AS P_Type
      , bty_v.btyname AS Grd_Lvl
      , patron_v.sponsor AS Home_Room
      , patron_v.name AS Patron_Name
      , patron_v.patronid AS P_Barcode
      , itembranch.branchgroup AS SYSTEM
      , item_v.cn AS Call_#
      , bbibmap_v.title AS Title
      , to_char(jts.todate(transitem_v.dueornotneededafterdate),'MM/DD/YYYY') AS Due_Date
      , item_v.price AS Owed
      , to_char(jts.todate(transitem_v.dueornotneededafterdate),'MM/DD/YYYY') AS Due_Date_Dup
      , item_v.item AS Item
    from 
      bbibmap_v
      , branch_v patronbranch
      , branch_v itembranch
      , branchgroup_v patronbranchgroup
      , branchgroup_v itembranchgroup
      , bty_v
      , item_v
      , location_v
      , patron_v
      , transitem_v
    where
      patron_v.patronid = transitem_v.patronid
      and patron_v.bty = bty_v.btynumber
      and transitem_v.item = item_v.item
      and bbibmap_v.bid = item_v.bid
      and patronbranch.branchnumber = patron_v.defaultbranch
      and location_v.locnumber = item_v.location
      and itembranch.branchnumber = transitem_v.holdingbranch
      and itembranchgroup.branchgroup = itembranch.branchgroup
      and (TRANSITEM_V.transcode = 'O' or transitem_v.transcode='L' or transitem_v.transcode='C')
      and patronbranch.branchgroup = '2'
      and patronbranchgroup.branchgroup = patronbranch.branchgroup
      and bty in ('13','21','22','23','24','25','26','27','28','29','30','31','32','33','34','35','36','37','40','42')
      and patronbranch.branchcode = '$sSchool'
    order by 
      patronbranch.branchcode
      , patron_v.bty
      , patron_v.sponsor
      , patron_v.name
      , itembranch.branchgroup
      , item_v.cn
      , bbibmap_v.title
EOT;

	$stid = oci_parse($conn, $sql);
	// consider using oci_set_prefetch to improve performance
	// oci_set_prefetch($stid, 1000);
	oci_execute($stid);
	// start a new file for the new school
	$df;
	$df = fopen($reportPath . $sSchool . "_school_report.csv", 'w');
	while (($row = oci_fetch_array ($stid, OCI_ASSOC+OCI_RETURN_NULLS)) != false) {
		// CSV OUTPUT
		fputcsv($df, $row);
	}
	fclose($df);
	//echo $sSchool . " overdue report written\n";
}
oci_free_statement($stid);
oci_close($conn);
?>
