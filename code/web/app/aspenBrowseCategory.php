<?php
# ****************************************************************************************************************************
# * Last Edit: May 3, 2021
# * - helper file to grab the browse categories from Aspen
# *
# * 05-03-21: altered how the location information is grabbed - CZ
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
# * Grab the browse categories
# ****************************************************************************************************************************
$browseCategories = $urlPath . '/API/SearchAPI?method=getActiveBrowseCategories&includeSubCategories=true';
$jsonBrowseCat    = json_decode(file_get_contents($browseCategories), true);

# ****************************************************************************************************************************
# * loop over result set, add the parent value then include any subcategories as well
# ****************************************************************************************************************************
$firstBrowseCategory = null;
foreach($jsonBrowseCat['result'] as $obj){
  
# ****************************************************************************************************************************
# * skip listing the item if the Browse Category was created as a List
# ****************************************************************************************************************************
  if (strcmp($obj['source'], 'List') == 0) { continue; } 	
	
# ****************************************************************************************************************************
# * loop over the subCategories to generate the listing and ignore the top one, or add the top level
# ****************************************************************************************************************************
  if (count($obj['subCategories']) > 0) {
    foreach($obj['subCategories'] as $subCats){
      if (strcmp($subCats['source'], 'List') == 0) { continue; } 	
      $browseCatList['Items'][] = array('title' => $subCats['display_label'], 'reference' => $subCats['text_id']);
      if (empty($firstBrowseCategory)){
      	$firstBrowseCategory = $subCats['text_id'];
      }
    }
  } else { 
    $browseCatList['Items'][] = array('title' => $obj['display_label'], 'reference' => $obj['text_id']);
    if (empty($firstBrowseCategory)){
      $firstBrowseCategory = $obj['text_id'];
    }
  }
}

# ****************************************************************************************************************************
# * give the system a default browse category to show
# ****************************************************************************************************************************
$browseCatList['default'] = $firstBrowseCategory;

# ****************************************************************************************************************************
# * Output to JSON
# ****************************************************************************************************************************
header('Content-Type: application/json');
echo json_encode($browseCatList);
?>