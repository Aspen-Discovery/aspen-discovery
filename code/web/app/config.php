<?php
# ****************************************************************************************************************************
# * Last Edit: May 31, 2021
# * - Helper function to ensure that we grab the correct path for the library using it
# *
# * 05-31-21: needed to add libraryIdNumber in order to differentiate on Consortia - CZ
# * 05-03-21: needed to add shortname for the json being returned - CZ
# * 04-08-21: Base Version
# ****************************************************************************************************************************

# ****************************************************************************************************************************
# * FUNCTION urlPath
# * PARAM: $location: the location of selected in the pulldown of the app.
# *
# * Helper function that sets the pathing for the app to follow
# ****************************************************************************************************************************
function urlPath($location) {
  switch ($location) {
    case 'arlingtonva':
      $url = 'https://libcat.arlingtonva.us';
	  $shortname = 'arlington';
      break;
    case 'test':
      $url             = 'https://aspen-test.bywatersolutions.com';
	  $shortname       = 'm';
	  $libraryIdNumber = 3;
      break;
  }
  
  return array($url, $shortname, $libraryIdNumber);
}

?>
