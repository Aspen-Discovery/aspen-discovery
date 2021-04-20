<?php
# ****************************************************************************************************************************
# * Last Edit: April 8, 2021
# * - Helper function to ensure that we grab the correct path for the library using it
# *
# * 04-08-21: Base Version
# ****************************************************************************************************************************

# ****************************************************************************************************************************
# * SIP Connection constants (based on location constants)
# ****************************************************************************************************************************
function urlPath($location) {
  switch ($location) {
    case 'pueblo':
      $url = 'https://catalog.pueblolibrary.org';
      break;
    case 'arlingtonva':
      $url = 'https://libcat.arlingtonva.us';
      break;
    case 'test':
      $url = 'https://aspen-test.bywatersolutions.com';
      break;
  }
  
  return $url;
}

?>
