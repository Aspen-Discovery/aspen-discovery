<?php
# ****************************************************************************************************************************
# * Last Edit: May 3, 2021
# * - place holds on items
# *
# * 05-03-21: altered how the location information was pulled - CZ
# * 04-08-21: Base Version - CZ
# ****************************************************************************************************************************

# ****************************************************************************************************************************
# * include the helper file that holds the URL information by client
# ****************************************************************************************************************************
//include_once 'config.php';

# ****************************************************************************************************************************
# * grab the passed location parameter, then find the path
# ****************************************************************************************************************************
$libraryInfo      = $_GET['library'];
$locationInfo = urlPath($libraryInfo);
$urlPath      = $locationInfo[0];
$shortname    = $locationInfo[1];

# ****************************************************************************************************************************
# * Prep the patron information for checking - dummy out something just in case
# ****************************************************************************************************************************
$barcode  = "thisisadummybarcodeincaseitisleftblank";
$pin      = 1234567890;
$itemCode = 1234567890;

# ****************************************************************************************************************************
# * grab the passed variables
# ****************************************************************************************************************************
if (! empty($_GET['barcode'])) { $barcode = $_GET['barcode']; }
if (! empty($_GET['pin'])) { $pin = $_GET['pin']; }  
if (! empty($_GET['item'])) { $itemCode = $_GET['item']; }  
if (! empty($_GET['location'])) { $location = $_GET['location']; }  

# ****************************************************************************************************************************
# * depending on the value passed through the item code, we have a bit of a toggle to run
# * - for APL, we've only got ILS and Overdrive materials
# ****************************************************************************************************************************
list ($platform, $identifier) = explode(':', $itemCode);

# ****************************************************************************************************************************
# * prep the URL depending on the path
# ****************************************************************************************************************************
switch ($platform) {
  case "ils":
    $reportURL = $urlPath . "/API/UserAPI?method=placeHold&username=" . $barcode . "&password=" . $pin . "&bibId=" . $identifier . "&pickupBranch=" . $location;
    break;
  case "overdrive":
    $reportURL = $urlPath . "/API/UserAPI?method=placeOverDriveHold&username=" . $barcode . "&password=" . $pin . "&overDriveId=" . $identifier;
    break;
  default:
    $reportURL = '';
}

# ****************************************************************************************************************************
# * run the report and grab the JSON
# ****************************************************************************************************************************
$jsonData = json_decode(file_get_contents($reportURL), true);

# ****************************************************************************************************************************
# * clean up the message
# ****************************************************************************************************************************
$message = explode('.', $jsonData['result']['message']);
if (! empty ($message[1])) { 
  $message[1] .= '.'; 
  $message[1] = strip_tags(str_replace('Patron is', 'You are', str_replace("&nbsp;", '', $message[1])));
}

if ($message[0] == 'Login unsuccessful') {
  $message[0] = "There was an issue with your account. To correct it, you'll need to log back into the App using your barcode and PIN.";
}

$messageOutput = strip_tags($message[0]) . '. ' . $message[1];

# ****************************************************************************************************************************
# * assemble the response - holds whether the renewal was successful and the new due date
# ****************************************************************************************************************************
$holdInfo['data']['hold'] = array('ok' => $jsonData['result']['success'], 'message' => $messageOutput); 

# ****************************************************************************************************************************
# * Output to JSON
# ****************************************************************************************************************************
header('Content-Type: application/json');
echo json_encode($holdInfo);
?>