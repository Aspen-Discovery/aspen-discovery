<?php
# ****************************************************************************************************************************
# * Last Edit: May 22, 2021
# * - Grabs the valid pickup locations based on the users login credentials
# *
# * 05-22-21: base version - CZ
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
$barcode = "thisisadummybarcodeincaseitisleftblank";
$pin     = 1234567890;

if (! empty($_GET['barcode'])) { $barcode = $_GET['barcode']; }
if (! empty($_GET['pin'])) { $pin = $_GET['pin']; }  

# ****************************************************************************************************************************
# * run the report and grab the JSON
# ****************************************************************************************************************************
$pickUpLocations = $urlPath . '/API/UserAPI?method=getValidPickupLocations&username=' . $barcode . '&password=' . $pin;
$jsonData        = json_decode(file_get_contents($pickUpLocations), true);

# ****************************************************************************************************************************
# * no need to do the loops if there is nothing checked out - so do the test first
# ****************************************************************************************************************************
if (! empty($jsonData['result']['pickupLocations'])) {
  foreach($jsonData['result']['pickupLocations'] as $item) {

    $locations['pickup'][] = array('displayName' => $item['displayName'], 'code' => $item['code']); 
  }
}

# ****************************************************************************************************************************
# * Just in case there are no values to return, check and spit out empty rather than null
# ****************************************************************************************************************************
if (empty($locations['pickup'])) {
  $locations['pickup'] = '';
}

# ****************************************************************************************************************************
# * Output to JSON
# ****************************************************************************************************************************
header('Content-Type: application/json');
echo json_encode($locations);
?>