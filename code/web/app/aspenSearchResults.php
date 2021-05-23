<?php
# ****************************************************************************************************************************
# * Last Edit: May 3, 2021
# * - basic searching funcitonality, but formats the data appropriately to be sent back
# * 
# * 05-03-21: needed to include shortname for location - CZ
# * 04-08-21: base version - CZ
# ****************************************************************************************************************************

# ****************************************************************************************************************************
# * Searching is handled in two ways:
# * - empty search terms are handled nicely in Apsen, so we no longer 'fail' on this
# * - the search term is a number and search type is quick: we've moved away from this and will now offer up quick searches
# *   that are pre defined (see aspenSearchLists.php)
# * - the search term is a term: run it!
# ****************************************************************************************************************************

# ****************************************************************************************************************************
# * include the helper file that holds the URL information by client
# ****************************************************************************************************************************
include_once 'config.php';

# ****************************************************************************************************************************
# * grab the passed location parameter, then find the path
# ****************************************************************************************************************************
$library      = $_GET['library'];
$locationInfo = urlPath($library);
$urlPath      = $locationInfo[0];
$shortname    = $locationInfo[1];

# ****************************************************************************************************************************
# * give the number of results to return from the search - needed to accomodate for the culling of Hoopla and Kanopy
# ****************************************************************************************************************************
$searchLimit = 100;

# ****************************************************************************************************************************
# * grab the parameters needed and clean it up
# ****************************************************************************************************************************
$searchTerm = $_GET['searchTerm'];
$searchTerm = str_replace(' ', '+', $searchTerm);

# ****************************************************************************************************************************
# * search link to the catalogue
# ****************************************************************************************************************************
$reportURL = $urlPath . '/API/SearchAPI?method=search&lookfor=' . $searchTerm . '&pageSize=' . $searchLimit;

# ****************************************************************************************************************************
# * run the report and grab the JSON
# ****************************************************************************************************************************
$jsonData = json_decode(file_get_contents($reportURL), true);

# ****************************************************************************************************************************
# * loop over results and massage - so do the test first
# * - https://stackoverflow.com/questions/6964403/parsing-json-with-php
# ****************************************************************************************************************************
if (! empty($jsonData['result']['recordSet'])) {
  foreach($jsonData['result']['recordSet'] as $item) {
    $author      = $item['author_display'];

# ****************************************************************************************************************************
# * collection code may be empty - need to dummy it out just in case
# ****************************************************************************************************************************
    $ccode       = '';
    if (isset($item['collection_' . $shortname][0])) { $ccode = $item['collection_' . $shortname][0]; }
    
	$format      = '';
	if (isset($item['format_' . $shortname][0])) { $format      = $item['format_' . $shortname][0]; }
    $iconName    = $urlPath . "/bookcover.php?id=" . $item['id'] . "&size=medium&type=grouped_work";
    $id          = $item['id'];
  
# ****************************************************************************************************************************
# * clean up the summary to remove some of the &# codes
# ****************************************************************************************************************************
    $summary     = utf8_encode(trim(strip_tags($item['display_description'])));
    $summary     = str_replace('&#8211;', ' - ', $summary);
    $summary     = str_replace('&#8212;', ' - ', $summary);
    $summary     = str_replace('&#160;', ' ', $summary);
    if (empty($summary)) { $summary = 'There is no summary available for this title'; }
    
    $title       = ucwords($item['title_display']);
    unset($itemList);
  
# ****************************************************************************************************************************
# * need to parse over the bib records
# ****************************************************************************************************************************
    foreach($item['record_details'] as $itemRecords) {
      if (strpos($itemRecords, 'ils:') > -1 || strpos($itemRecords, 'overdrive:') > -1) {
        $itemListing = explode('|', $itemRecords);
	    
	  //if (! is_array($itemList)) {
	  if (! isset($itemList)) {
	      $itemList[] = array('type' => $itemListing[0], 'name' => $itemListing[1]);
	    } elseif (! in_array($itemListing[1], array_column($itemList, 'name'))) {
          $itemList[] = array('type' => $itemListing[0], 'name' => $itemListing[1]);
        }
	  } 
    }

# ****************************************************************************************************************************
# * Build out results array ... ensure we have at least one item available
# ****************************************************************************************************************************
    if (! empty($itemList)) {
	  $searchResults['Items'][] = array('title' => trim($title), 'author' => $author, 'image' => $iconName, 'format' => $format . ' - ' . $ccode, 'itemList' => $itemList, 'key' => $id, 'summary' => $summary); 
    }
  }
}

# ****************************************************************************************************************************
# * Just in case there are no values to return, check and spit out empty rather than null
# ****************************************************************************************************************************
if (empty($searchResults['Items'])) {
  $searchResults['Items'] = '';
}

# ****************************************************************************************************************************
# * Output to JSON
# ****************************************************************************************************************************
header('Content-Type: application/json');
echo json_encode($searchResults);
?>