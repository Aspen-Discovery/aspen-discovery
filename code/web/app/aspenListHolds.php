<?php
# ****************************************************************************************************************************
# * Last Edit: May 3, 2021
# * - show the hold listing information using Aspen APIs
# *
# * 05-03-21: altered how location information is pulled and stored - CZ
# * 04-08-21: base version - CZ
# ****************************************************************************************************************************

# ****************************************************************************************************************************
# * include the helper file that holds the URL information by client
# ****************************************************************************************************************************
require_once '../bootstrap.php';
require_once '../bootstrap_aspen.php';

# ****************************************************************************************************************************
# * grab the passed location parameter, then find the path
# ****************************************************************************************************************************
$urlPath = 'https://'.$_SERVER['SERVER_NAME'];
$shortname = $_GET['library'];

# ****************************************************************************************************************************
# * Prep the patron information for checking - dummy out something just in case
# ****************************************************************************************************************************
$barcode = '';
$pin     = '';

if (! empty($_GET['barcode'])) { $barcode = $_GET['barcode']; }
if (! empty($_GET['pin'])) { $pin = $_GET['pin']; }  

# ****************************************************************************************************************************
# * run the report and grab the JSON
# ****************************************************************************************************************************
$accountList = $urlPath . '/API/UserAPI?method=getPatronHolds&username=' . $barcode . '&password=' . $pin;
$jsonData    = json_decode(file_get_contents($accountList), true);

# ****************************************************************************************************************************
# * Available: no need to do the loops if there is nothing available - so do the test first
# ****************************************************************************************************************************
if (! empty($jsonData['result']['holds']['available'])) {
  foreach($jsonData['result']['holds']['available'] as $item) {

# ****************************************************************************************************************************
# * clean up the title and convert the due date from a timestamp
# ****************************************************************************************************************************
    if(substr($item['title'], -1) == '/') { $item['title'] = substr($item['title'], 0, -1); }
    if($item['pickupLocationName'] && $item['expire']) {
	    $pickUpDetails = $item['pickupLocationName'] . ',' . date('Y-m-d', $item['expire']);

    } else {
	    $pickUpDetails = date('Y-m-d', $item['expire']);
    }

	$type = '';
	if($item['source']) {
	  $type = $item['source'].':';
	}
	// make sure an id always populates
	if(!$item['id']) {
	  $id = $type . $item['recordId'];
	} else {
	  $id = $type . $item['id'];
	}

	$format = '';
	if($item['format'][0]) {
	  $format = $item['format'][0];
	}

    $holdInfo['Items'][] = array('key' => ucwords($item['title']), 'holdSource' => $item['holdSource'], 'position' => $pickUpDetails, 'thumbnail' => $item['coverUrl'], 'author' => $item['author'], 'id' => $id, 'format' => $format);
  }
}

# ****************************************************************************************************************************
# * Unavailable: no need to do the loops if there is nothing unavailable - so do the test first
# ****************************************************************************************************************************
if (! empty($jsonData['result']['holds']['unavailable'])) {
  foreach($jsonData['result']['holds']['unavailable'] as $item) {

# ****************************************************************************************************************************
# * clean up the title and convert the due date from a timestamp
# ****************************************************************************************************************************
    if(substr($item['title'], -1) == '/') { $item['title'] = substr($item['title'], 0, -1); }

	  $type = '';
	  if($item['type']) {
		  $type = $item['type'].':';
	  }
	  // make sure an id always populates
	  if(!$item['id']) {
		  $id = $type . $item['recordId'];
	  } else {
		  $id = $type . $item['id'];
	  }

	  $format = '';
	  if($item['format'][0]) {
		  $format = $item['format'][0];
	  }
  
    $holdInfo['Items'][] = array('key' => ucwords($item['title']), 'holdSource' => $item['holdSource'], 'position' => $item['position'], 'thumbnail' => $item['coverUrl'], 'author' => $item['author'], 'id' => $id, 'format' => $format);
  }
}

# ****************************************************************************************************************************
# * Just in case there are no values to return, check and spit out empty rather than null
# ****************************************************************************************************************************
if (empty($holdInfo['Items'])) {
  $holdInfo['Items'] = '';
}

# ****************************************************************************************************************************
# * Output to JSON
# ****************************************************************************************************************************
header('Content-Type: application/json');
echo json_encode($holdInfo);
?>