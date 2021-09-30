<?php 
# *********************************************************************************************************************************************
# * File last edited: April 8, 2021
# * - handles the MORE page of the app
# * 
# * 04-08-21: Base version - CZ
# *********************************************************************************************************************************************

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
$libraryId = $_GET['id'];
$version = $_GET['version'];

# ****************************************************************************************************************************
# * assemble the login API URL
# ****************************************************************************************************************************
$libraryInfo = $urlPath . '/API/SystemAPI?method=getLocationInfo&id=' . $libraryId . '&library=' . $shortname . '&version=' . $version;

# ****************************************************************************************************************************
# * grab the library info
# ****************************************************************************************************************************
$jsonData = json_decode(file_get_contents($libraryInfo), true);

# ****************************************************************************************************************************
# * grab the website URL
# ****************************************************************************************************************************
$website = '';
if (! empty ($jsonData['result']['location']['homeLink'])) {
  $website = $jsonData['result']['location']['homeLink'];
}

# ****************************************************************************************************************************
# * grab the website catalogue URL - we should already know this
# ****************************************************************************************************************************
$catalogue = $urlPath;

# ****************************************************************************************************************************
# * grab the Library Phone number 
# ****************************************************************************************************************************
$phone = '';
if (! empty ($jsonData['result']['location']['phone'])) {
  $phone = $jsonData['result']['location']['phone'];
}

# ****************************************************************************************************************************
# * grab the today's hours
# ****************************************************************************************************************************
$todayHours = '';
if (! empty ($jsonData['result']['location']['hoursMessage'])) {
  $todayHours = $jsonData['result']['location']['hoursMessage'];
}

# *********************************************************************************************************************************************
# * Links for the More Page - unique key is needed to prevent a warning in iOS
# *********************************************************************************************************************************************
//$pageLink[] = array('key' => 0, 'title' => "Program Calendar", 'subtitle' => "Programs, Events and Services.", 'path' => "WhatsOn");
$pageLink[] = array('key' => 1, 'title' => "Contact Us", 'subtitle' => "We're just a click away.", 'path' => "ContactUs");
//$pageLink[] = array('key' => 2, 'title' => "News", 'subtitle' => "Stay up to date on important Library News.", 'path' => "News");
$pageLink[] = array('key' => 3, 'title' => "About", 'subtitle' => $version, 'path' => "null");

# *********************************************************************************************************************************************
# * WHATS ON
# *********************************************************************************************************************************************
$whatsOnBlurb = "Discover Programs at the Library and learn more about our current programs, events, and services.";
$whatsOnButton = "View this Month's Program Calendar";
$whatsOnLink  = 'http://www.bywatersolutions.com';

# *********************************************************************************************************************************************
# * CONTACT US
# *********************************************************************************************************************************************
$contactUsBlurb    = "It's easy to get help and information on any of our services.";
$contactUsMailLink = "mailto:aspensupport@bywatersolutions.com";

# *********************************************************************************************************************************************
# * NEWS - unique key is needed to prevent warning in iOS
# *********************************************************************************************************************************************
$news[] = array('key' => 0, 'date' => 'Apr. 1, 2021', 'newsItem' => "News Item 1", 'link' =>'');
$news[] = array('key' => 2, 'date' => 'Dec. 2, 2020', 'newsItem' => "News Item 2");
$news[] = array('key' => 3, 'date' => 'Nov. 11, 2020', 'newsItem' => "News Item 3 - click", 'link' =>'https://www.bywatersolutions.com');


# ****************************************************************************************************************************
# * assemble the data above into an array for json-ing
# ****************************************************************************************************************************
$more['universal'] = array('todayHours' => $todayHours, 'phone' => $phone, 'website' => $website, 'catalogue' => $catalogue); 
$more['options']   = $pageLink;
//$more['whatsOn']   = array('button' => $whatsOnButton, 'blurb' => $whatsOnBlurb, 'link' => $whatsOnLink);
$more['contactUs'] = array('blurb' => $contactUsBlurb, 'email' => $contactUsMailLink);
//$more['news']      = array('news' => $news);


# ****************************************************************************************************************************
# * Output to JSON
# ****************************************************************************************************************************
header('Content-Type: application/json');
echo json_encode($more);
?>