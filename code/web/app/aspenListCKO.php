<?php
# ****************************************************************************************************************************
# * Last Edit: May 3, 2021
# * - list the checkouts for the user
# *
# * 05-03-21: altered how the location information is pulled - CZ
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
$barcode = "thisisadummybarcodeincaseitisleftblank";
$pin     = 1234567890;

if (! empty($_GET['barcode'])) { $barcode = $_GET['barcode']; }
if (! empty($_GET['pin'])) { $pin = $_GET['pin']; }  

# ****************************************************************************************************************************
# * run the report and grab the JSON
# ****************************************************************************************************************************
$accountList = $urlPath . '/API/UserAPI?method=getPatronCheckedOutItems&username=' . $barcode . '&password=' . $pin;
$jsonData    = json_decode(file_get_contents($accountList), true);

# ****************************************************************************************************************************
# * no need to do the loops if there is nothing checked out - so do the test first
# ****************************************************************************************************************************
if (! empty($jsonData['result']['checkedOutItems'])) {
  foreach($jsonData['result']['checkedOutItems'] as $item) {

# ****************************************************************************************************************************
# * clean up the title and convert the due date from a timestamp
# ****************************************************************************************************************************
    if(substr($item['title'], -1) == '/') { $item['title'] = substr($item['title'], 0, -1); }
    $dueDate = date('Y-m-d', $item['dueDate']);

    $patronInfo['Items'][] = array('barcode' => $item['itemId'], 'key' => ucwords($item['title']), 'dateDue' => $dueDate, 'thumbnail' => $item['coverUrl'], 'author' => $item['author']); 
  }
}

# ****************************************************************************************************************************
# * Just in case there are no values to return, check and spit out empty rather than null
# ****************************************************************************************************************************
if (empty($patronInfo['Items'])) {
  $patronInfo['Items'] = '';
}

# ****************************************************************************************************************************
# * Output to JSON
# ****************************************************************************************************************************
header('Content-Type: application/json');
echo json_encode($patronInfo);
?>