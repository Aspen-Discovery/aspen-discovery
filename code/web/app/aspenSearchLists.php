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
$quickSearch['list'][] = array('SearchName' => 'History', 'SearchTerm' => 'history');
$quickSearch['list'][] = array('SearchName' => 'Mystery', 'SearchTerm' => 'mystery');
$quickSearch['list'][] = array('SearchName' => 'England', 'SearchTerm' => 'england');
$quickSearch['list'][] = array('SearchName' => 'Soccer', 'SearchTerm' => 'soccer');

# ****************************************************************************************************************************
# * Output to JSON
# ****************************************************************************************************************************
header('Content-Type: application/json');
echo json_encode($quickSearch);
?>