<?php
/**
 * Utility for testing a SIP connection
 *
 * @category VuFind-Plus-2014 
 * @author Mark Noble <mark@marmot.org>
 * Date: 7/20/2015
 * Time: 10:45 PM
 */
ini_set('display_errors', true);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

require_once 'bootstrap.php';

require_once ROOT_DIR . '/sys/SIP2.php';
$sip2 = new sip2();

$host = $_REQUEST['host'];
$port = $_REQUEST['port'];

$sip2->hostname = $host;
$sip2->port = $port;
if ($sip2->connect()) {
	//send selfcheck status message
	$in = $sip2->msgSCStatus();
	$msg_result = $sip2->get_message($in);
	// Make sure the response is 98 as expected
	if (preg_match("/^98/", $msg_result)) {
		$result = $sip2->parseACSStatusResponse($msg_result);
		//  Use result to populate SIP2 settings
		$sip2->AO = $result['variable']['AO'][0]; /* set AO to value returned */
		if (isset($result['variable']['AN'])){
			$sip2->AN = $result['variable']['AN'][0]; /* set AN to value returned */
		}
		echo("Yay we connected to the SIP2 server!");
	}else{
		echo("Did not get back valid status response from the SIP2 server");
	}
	$sip2->disconnect();
}else{
	echo("Failed to connect to the SIP2 server");
}
$sip2 = null;