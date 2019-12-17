<?php

require_once ROOT_DIR . '/sys/Recommend/Interface.php';

class TopFacets implements RecommendationInterface
{
	/** @var SearchObject_SolrSearcher searchObject */
	private $searchObject;
	private $facetSettings = array();
	private $facets = array();

	/* Constructor
	 *
	 * Establishes base settings for making recommendations.
	 *
	 * @access  public
	 * @param   SearchObject_BaseSearcher  $searchObject   The SearchObject requesting recommendations.
	 * @param   string  $params         Additional settings from the searches.ini.
	 */
	public function __construct(SearchObject_BaseSearcher $searchObject, $params) {
		// Save the basic parameters:
		/** @var SearchObject_SolrSearcher searchObject */
		$this->searchObject = $searchObject;

		// Parse the additional parameters:
		$params = explode(':', $params);

		// Load the desired facet information:
		if ($this->searchObject instanceof SearchObject_GroupedWorkSearcher) {
            $searchLibrary = Library::getActiveLibrary();
            global $locationSingleton;
            $searchLocation = $locationSingleton->getActiveLocation();
            if ($searchLocation != null) {
                $facets = $searchLocation->getGroupedWorkDisplaySettings()->getFacets();
            } else {
                $facets = $searchLibrary->getGroupedWorkDisplaySettings()->getFacets();
            }
            global $solrScope;
            foreach ($facets as $facet) {
                if ($facet->showAboveResults == 1) {
                    $facetName = $facet->facetName;
                    if ($solrScope) {
                        if ($facet->facetName == 'availability_toggle') {
                            $facetName = 'availability_toggle_' . $solrScope;
                        } else if ($facet->facetName == 'format_category') {
                            $facetName = 'format_category_' . $solrScope;
                        } else if ($facet->facetName == 'format') {
                            $facetName = 'format_' . $solrScope;
                        }
                    }
                    $this->facets[$facetName] = $facet->displayName;
                    $this->facetSettings[$facetName] = $facet;
                }
            }
        }else{
            $this->facets = array();
        }
	}

	/* init
	 *
	 * Called before the SearchObject performs its main search.  This may be used
	 * to set SearchObject parameters in order to generate recommendations as part
	 * of the search.
	 *
	 * @access  public
	 */
	public function init()
	{
		// Turn on top facets in the search results:
//		foreach($this->facets as $name => $desc) {
//			$this->searchObject->addFacet($name, $this->facetSettings[$name]);
//		}
	}

	/* process
	 *
	 * Called after the SearchObject has performed its main search.  This may be
	 * used to extract necessary information from the SearchObject or to perform
	 * completely unrelated processing.
	 *
	 * @access  public
	 */
	public function process()
	{
		global $interface;

		// Grab the facet set
		$facetList = $this->searchObject->getFacetList($this->facets);
		foreach ($facetList as $facetSetkey => $facetSet){
			if ($facetSet['label'] == 'Category' || $facetSet['label'] == 'Format Category'){
				$validCategories = array(
						'Books',
						'eBook',
						'Audio Books',
						'eAudio',
						'Music',
						'Movies',
				);

				//add an image name for display in the template
				foreach ($facetSet['list'] as $facetKey => $facet){
					if (in_array($facetKey,$validCategories)){
						$facet['imageName'] = strtolower(str_replace(' ', '', $facet['value'])) . ".png";
						$facet['imageNameSelected'] = strtolower(str_replace(' ', '', $facet['value'])) . "_selected.png";
						$facetSet['list'][$facetKey] = $facet;
					}else{
						unset($facetSet['list'][$facetKey]);
					}
				}

				uksort($facetSet['list'], "format_category_comparator");

				$facetList[$facetSetkey] = $facetSet;
			}elseif (preg_match('/available/i', $facetSet['label'])){

				$numSelected = 0;
				foreach ($facetSet['list'] as $facetKey => $facet){
					if ($facet['isApplied']){
						$numSelected++;
					}
				}

				//If nothing is selected, select entire collection by default
				$sortedFacetList = array();
				$numTitlesWithNoValue = 0;
				$numTitlesWithEntireCollection = 0;
				$searchLibrary = Library::getSearchLibrary(null);
				$searchLocation = Location::getSearchLocation(null);

				if ($searchLocation){
					$superScopeLabel = $searchLocation->getGroupedWorkDisplaySettings()->availabilityToggleLabelSuperScope;
					$localLabel = $searchLocation->getGroupedWorkDisplaySettings()->availabilityToggleLabelLocal;
					$localLabel = str_ireplace('{display name}', $searchLocation->displayName, $localLabel);
					$availableLabel = $searchLocation->getGroupedWorkDisplaySettings()->availabilityToggleLabelAvailable;
					$availableLabel = str_ireplace('{display name}', $searchLocation->displayName, $availableLabel);
					$availableOnlineLabel = $searchLocation->getGroupedWorkDisplaySettings()->availabilityToggleLabelAvailableOnline;
					$availableOnlineLabel = str_ireplace('{display name}', $searchLocation->displayName, $availableOnlineLabel);
				}else{
					$superScopeLabel = $searchLibrary->getGroupedWorkDisplaySettings()->availabilityToggleLabelSuperScope;
					$localLabel = $searchLibrary->getGroupedWorkDisplaySettings()->availabilityToggleLabelLocal;
					$localLabel = str_ireplace('{display name}', $searchLibrary->displayName, $localLabel);
					$availableLabel = $searchLibrary->getGroupedWorkDisplaySettings()->availabilityToggleLabelAvailable;
					$availableLabel = str_ireplace('{display name}', $searchLibrary->displayName, $availableLabel);
					$availableOnlineLabel = $searchLibrary->getGroupedWorkDisplaySettings()->availabilityToggleLabelAvailableOnline;
					$availableOnlineLabel = str_ireplace('{display name}', $searchLibrary->displayName, $availableOnlineLabel);
				}

				$numButtons = 4;
				foreach ($facetSet['list'] as $facet){
					if ($facet['value'] == 'Entire Collection'){

						$includeButton = true;
						$facet['value'] = $localLabel;
						if (trim($localLabel) == ''){
							$includeButton = false;
						}else{
							if ($searchLocation){
								$includeButton = !$searchLocation->restrictSearchByLocation;
							}elseif ($searchLibrary){
								$includeButton = !$searchLibrary->restrictSearchByLibrary;
							}
						}

						$numTitlesWithEntireCollection = $facet['count'];

						if ($includeButton){
							$sortedFacetList[1] = $facet;
						}
					}elseif ($facet['value'] == ''){
						$facet['isApplied'] = $facet['isApplied'] || ($numSelected == 0);
						$facet['value'] = $superScopeLabel;
						$sortedFacetList[0] = $facet;
						$numTitlesWithNoValue = $facet['count'];
					}elseif ($facet['value'] == 'Available Now'){
						$facet['value'] = $availableLabel;
						$sortedFacetList[2] = $facet;
					}elseif ($facet['value'] == 'Available Online'){
						if (strlen($availableOnlineLabel) > 0){
							$facet['value'] = $availableOnlineLabel;
							$sortedFacetList[3] = $facet;
						}
					}else{
						//$facet['value'] = $availableLabel;
						$sortedFacetList[$numButtons++] = $facet;
					}
				}
				if (isset($sortedFacetList[0])){
					$sortedFacetList[0]['count'] = $numTitlesWithEntireCollection + $numTitlesWithNoValue;
				}

				ksort($sortedFacetList);
				$facetSet['list'] = $sortedFacetList;
				$facetList[$facetSetkey] = $facetSet;
			}
		}
		$interface->assign('topFacetSet', $facetList);
	}

	/* getTemplate
	 *
	 * This method provides a template name so that recommendations can be displayed
	 * to the end user.  It is the responsibility of the process() method to
	 * populate all necessary template variables.
	 *
	 * @access  public
	 * @return  string      The template to use to display the recommendations.
	 */
	public function getTemplate()
	{
		return 'Search/Recommend/TopFacets.tpl';
	}
}

function format_category_comparator($a, $b){
	$formatCategorySortOrder = array(
		'Books' => 1,
		'eBook' => 2,
		'Audio Books' => 3,
		'eAudio' => 4,
		'Music' => 5,
		'Movies' => 6,
	);

	$a = $formatCategorySortOrder[$a];
	$b = $formatCategorySortOrder[$b];
	if ($a==$b){return 0;}else{return ($a > $b ? 1 : -1);}
};