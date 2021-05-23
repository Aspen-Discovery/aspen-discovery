<?php 
# *********************************************************************************************************************************************
# * File last edited: April 19, 2021
# * - shows the canned searching options
# * 
# * 04-19-21: Base version - CZ
# *********************************************************************************************************************************************

# *********************************************************************************************************************************************
# * Need ability to specify the Koha Report number, then the report name to show on the screen. Needs to pull back uniform results
# *********************************************************************************************************************************************
$quickSearch['list'][] = array('SearchName' => 'New York Times', 'SearchTerm' => 'new york times');
$quickSearch['list'][] = array('SearchName' => 'Autobiography', 'SearchTerm' => 'autobiography');
$quickSearch['list'][] = array('SearchName' => 'Super Heroes', 'SearchTerm' => 'super hero');
$quickSearch['list'][] = array('SearchName' => 'US History', 'SearchTerm' => 'US History');

# ****************************************************************************************************************************
# * Output to JSON
# ****************************************************************************************************************************
header('Content-Type: application/json');
echo json_encode($quickSearch);
?>