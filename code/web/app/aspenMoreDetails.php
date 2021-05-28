<?php 
# *********************************************************************************************************************************************
# * File last edited: April 8, 2021
# * - handles the MORE page of the app
# * 
# * 04-08-21: Base version - CZ
# *********************************************************************************************************************************************

# *********************************************************************************************************************************************
# * Universal Links - potetially used in multiple places in the MORE section
# *********************************************************************************************************************************************
$phone      = '1-888-900-8944';
$website    = 'https://www.bywatersolutions.ca';
$catalogue  = 'https://www.bywatersolutions.ca';

$hours      = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
$weekDay    = date("w");   # used to determine what day of the week we're on
$todayHours = $hours[$weekDay];

# *********************************************************************************************************************************************
# * Links for the More Page - unique key is needed to prevent a warning in iOS
# *********************************************************************************************************************************************
//$pageLink[] = array('key' => 0, 'title' => "Program Calendar", 'subtitle' => "Programs, Events and Services.", 'path' => "WhatsOn");
$pageLink[] = array('key' => 1, 'title' => "Contact Us", 'subtitle' => "We're just a click away.", 'path' => "ContactUs");
//$pageLink[] = array('key' => 2, 'title' => "News", 'subtitle' => "Stay up to date on important Library News.", 'path' => "News");
$pageLink[] = array('key' => 3, 'title' => "About", 'subtitle' => "Version 1.5.0", 'path' => "null");

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
$more['whatsOn']   = array('button' => $whatsOnButton, 'blurb' => $whatsOnBlurb, 'link' => $whatsOnLink);
$more['contactUs'] = array('blurb' => $contactUsBlurb, 'email' => $contactUsMailLink);
$more['news']      = array('news' => $news);


# ****************************************************************************************************************************
# * Output to JSON
# ****************************************************************************************************************************
header('Content-Type: application/json');
echo json_encode($more);
?>