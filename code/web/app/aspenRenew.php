<?php
# ****************************************************************************************************************************
# * Last Edit: May 3, 2021
# * - renew items through the Aspen APIs
# *
# * 05-03-21: altered how the location information was pulled - CZ
# * 04-08-21: base version - CZ
# ****************************************************************************************************************************

# ****************************************************************************************************************************
# * include the helper file that holds the URL information by client
# ****************************************************************************************************************************
//include_once 'config.php';

# ****************************************************************************************************************************
# * grab the passed location parameter, then find the path
# ****************************************************************************************************************************
$urlPath = $_SERVER['SERVER_NAME'];
$shortname = $_GET['library'];

# ****************************************************************************************************************************
# * Prep the patron information for checking - dummy out something just in case
# ****************************************************************************************************************************
$barcode = "thisisadummybarcodeincaseitisleftblank";
$pin     = 1234567890;

if (! empty($_GET['barcode'])) { $barcode = $_GET['barcode']; }
if (! empty($_GET['pin'])) { $pin = $_GET['pin']; }  
if (! empty($_GET['itemId'])) { $itemId = $_GET['itemId']; }  

# ****************************************************************************************************************************
# * need to know what method to call based on the action (ilsHolds, ilsCKO or eItems)
# ****************************************************************************************************************************
$renewal = $urlPath . '/API/UserAPI?method=renewCheckout&username=' . $barcode . '&password=' . $pin . '&itemBarcode=' . $itemId;

if (strcmp($itemId, 'all') == 0) { 
  $renewal = $urlPath . '/API/UserAPI?method=renewAll&username=' . $barcode . '&password=' . $pin;
}

# ****************************************************************************************************************************
# * run the report and grab the JSON
# ****************************************************************************************************************************
$jsonData = json_decode(file_get_contents($renewal), true);

$renewalInfo['renewed'] = $jsonData['result']['renewalMessage']['success'];
$renewalInfo['message'] = $jsonData['result']['renewalMessage']['message'];

if (strcmp($itemId, 'all') == 0) {
  $renewalInfo['renewed'] = $jsonData['result']['success'];
  $renewalInfo['message'] = $jsonData['result']['message'][0];
}

# ****************************************************************************************************************************
# * Output to JSON
# ****************************************************************************************************************************
header('Content-Type: application/json');
echo json_encode($renewalInfo);
?>