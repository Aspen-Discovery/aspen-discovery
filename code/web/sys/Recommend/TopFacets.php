<?php

require_once ROOT_DIR . '/sys/Recommend/Interface.php';

class TopFacets implements RecommendationInterface {
	/** @var SearchObject_SolrSearcher searchObject */
	private $searchObject;
	private $facets = [];

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

		require_once ROOT_DIR . '/sys/SystemVariables.php';
		$systemVariables = SystemVariables::getSystemVariables();
		// Load the desired facet information:
		if ($this->searchObject instanceof SearchObject_AbstractGroupedWorkSearcher) {
			$searchLibrary = Library::getActiveLibrary();
			global $locationSingleton;
			$searchLocation = $locationSingleton->getActiveLocation();
			if ($searchLocation != null) {
				$facets = $searchLocation->getGroupedWorkDisplaySettings()->getFacets();
			} else {
				$facets = $searchLibrary->getGroupedWorkDisplaySettings()->getFacets();
			}
			global $solrScope;
			foreach ($facets as &$facet) {
				if ($facet->showAboveResults == 1) {
					if ($solrScope) {
						if ($facet->facetName == 'availability_toggle' && $systemVariables->searchVersion == 1) {
							$facet->facetName = 'availability_toggle_' . $solrScope;
						} elseif ($facet->facetName == 'format_category' && $systemVariables->searchVersion == 1) {
							$facet->facetName = 'format_category_' . $solrScope;
						} elseif ($facet->facetName == 'format' && $systemVariables->searchVersion == 1) {
							$facet->facetName = 'format_' . $solrScope;
						}
					}
					$this->facets[$facet->facetName] = $facet;
				}
			}
		} else {
			$this->facets = [];
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
	public function init() {}

	/* process
	 *
	 * Called after the SearchObject has performed its main search.  This may be
	 * used to extract necessary information from the SearchObject or to perform
	 * completely unrelated processing.
	 *
	 * @access  public
	 */
	public function process() {
		global $interface;
		global $library;

		//Figure out which counts to show.
		$facetCountsToShow = $library->getGroupedWorkDisplaySettings()->facetCountsToShow;
		$interface->assign('facetCountsToShow', $facetCountsToShow);

		$appliedTheme = $interface->getAppliedTheme();

		// Grab the facet set
		$facetList = $this->searchObject->getFacetList($this->facets);
		foreach ($facetList as $facetSetkey => $facetSet) {
			if (strpos($facetSetkey, 'format_category') === 0) {
				//add an image name for display in the template
				foreach ($facetSet['list'] as $facetKey => $facet) {
					if (!empty($facetKey) && array_key_exists($facetKey, TopFacets::$formatCategorySortOrder)) {
						if ($appliedTheme != null){
							if (strtolower($facet['value']) == "books" && !empty($appliedTheme->booksImage)){
								$facet['imageName'] = '/files/original/' . $appliedTheme->booksImage;
								if (!empty($appliedTheme->booksImageSelected)){
									$facet['imageNameSelected'] = '/files/original/' . $appliedTheme->booksImageSelected;
								}else{
									$facet['imageNameSelected'] = strtolower(str_replace(' ', '', $facet['value'])) . "_selected.png";
								}
							}elseif (strtolower($facet['value']) == "ebook" && !empty($appliedTheme->eBooksImage)){
								$facet['imageName'] = '/files/original/' . $appliedTheme->eBooksImage;
								if (!empty($appliedTheme->eBooksImageSelected)){
									$facet['imageNameSelected'] = '/files/original/' . $appliedTheme->eBooksImageSelected;
								}else{
									$facet['imageNameSelected'] = strtolower(str_replace(' ', '', $facet['value'])) . "_selected.png";
								}
							}elseif (strtolower($facet['value']) == "audio books" && !empty($appliedTheme->audioBooksImage)){
								$facet['imageName'] = '/files/original/' . $appliedTheme->audioBooksImage;
								if (!empty($appliedTheme->audioBooksImageSelected)){
									$facet['imageNameSelected'] = '/files/original/' . $appliedTheme->audioBooksImageSelected;
								}else{
									$facet['imageNameSelected'] = strtolower(str_replace(' ', '', $facet['value'])) . "_selected.png";
								}
							}elseif (strtolower($facet['value']) == "music" && !empty($appliedTheme->musicImage)){
								$facet['imageName'] = '/files/original/' . $appliedTheme->musicImage;
								if (!empty($appliedTheme->musicImageSelected)){
									$facet['imageNameSelected'] = '/files/original/' . $appliedTheme->musicImageSelected;
								}else{
									$facet['imageNameSelected'] = strtolower(str_replace(' ', '', $facet['value'])) . "_selected.png";
								}
							}elseif (strtolower($facet['value']) == "movies" && !empty($appliedTheme->moviesImage)){
								$facet['imageName'] = '/files/original/' . $appliedTheme->moviesImage;
								if (!empty($appliedTheme->moviesImageSelected)){
									$facet['imageNameSelected'] = '/files/original/' . $appliedTheme->moviesImageSelected;
								}else{
									$facet['imageNameSelected'] = strtolower(str_replace(' ', '', $facet['value'])) . "_selected.png";
								}
							}else{
								$facet['imageName'] = strtolower(str_replace(' ', '', $facet['value'])) . ".png";
								$facet['imageNameSelected'] = strtolower(str_replace(' ', '', $facet['value'])) . "_selected.png";
							}
						}else{
							$facet['imageName'] = strtolower(str_replace(' ', '', $facet['value'])) . ".png";
							$facet['imageNameSelected'] = strtolower(str_replace(' ', '', $facet['value'])) . "_selected.png";
						}
						$facetSet['list'][$facetKey] = $facet;
					} else {
						unset($facetSet['list'][$facetKey]);
					}
				}

				uksort($facetSet['list'], "format_category_comparator");

				$facetSet['isFormatCategory'] = true;
				$facetSet['isAvailabilityToggle'] = false;
				$facetList[$facetSetkey] = $facetSet;
			} elseif (strpos($facetSetkey, 'availability_toggle') === 0) {

				$numSelected = 0;
				foreach ($facetSet['list'] as $facetKey => $facet) {
					if ($facet['isApplied']) {
						$numSelected++;
					}
				}

				//If nothing is selected, select entire collection by default
				$sortedFacetList = [];
				$searchLibrary = Library::getSearchLibrary(null);
				$searchLocation = Location::getSearchLocation(null);

				if ($searchLocation) {
					$superScopeLabel = $searchLocation->getGroupedWorkDisplaySettings()->availabilityToggleLabelSuperScope;
					$localLabel = $searchLocation->getGroupedWorkDisplaySettings()->availabilityToggleLabelLocal;
					$localLabel = str_ireplace('{display name}', $searchLocation->displayName, $localLabel);
					$availableLabel = $searchLocation->getGroupedWorkDisplaySettings()->availabilityToggleLabelAvailable;
					$availableLabel = str_ireplace('{display name}', $searchLocation->displayName, $availableLabel);
					$availableOnlineLabel = $searchLocation->getGroupedWorkDisplaySettings()->availabilityToggleLabelAvailableOnline;
					$availableOnlineLabel = str_ireplace('{display name}', $searchLocation->displayName, $availableOnlineLabel);
					$availabilityToggleValue = $searchLocation->getGroupedWorkDisplaySettings()->defaultAvailabilityToggle;
				} else {
					$superScopeLabel = $searchLibrary->getGroupedWorkDisplaySettings()->availabilityToggleLabelSuperScope;
					$localLabel = $searchLibrary->getGroupedWorkDisplaySettings()->availabilityToggleLabelLocal;
					$localLabel = str_ireplace('{display name}', $searchLibrary->displayName, $localLabel);
					$availableLabel = $searchLibrary->getGroupedWorkDisplaySettings()->availabilityToggleLabelAvailable;
					$availableLabel = str_ireplace('{display name}', $searchLibrary->displayName, $availableLabel);
					$availableOnlineLabel = $searchLibrary->getGroupedWorkDisplaySettings()->availabilityToggleLabelAvailableOnline;
					$availableOnlineLabel = str_ireplace('{display name}', $searchLibrary->displayName, $availableOnlineLabel);
					$availabilityToggleValue = $searchLibrary->getGroupedWorkDisplaySettings()->defaultAvailabilityToggle;
				}

				if ($numSelected == 0) {
					foreach ($facetSet['list'] as $facetKey => $facet) {
						if ($availabilityToggleValue == $facetKey) {
							$facetSet['list'][$facetKey]['isApplied'] = true;
						}
					}
				}

				$numButtons = 4;
				foreach ($facetSet['list'] as $facetKey => $facet) {
					if ($facetKey == 'local') {
						$includeButton = true;
						$facet['display'] = $localLabel;
						if (trim($localLabel) == '') {
							$includeButton = false;
						}

						if ($includeButton) {
							$sortedFacetList[1] = $facet;
						}
					} elseif ($facetKey == 'global' || $facetKey == '') {
						$facet['display'] = $superScopeLabel;
						$sortedFacetList[0] = $facet;
					} elseif ($facetKey == 'available') {
						$facet['display'] = $availableLabel;
						$sortedFacetList[2] = $facet;
					} elseif ($facet['value'] == 'available_online') {
						if (strlen($availableOnlineLabel) > 0) {
							$facet['display'] = $availableOnlineLabel;
							$sortedFacetList[3] = $facet;
						}
					}/*else{
						$sortedFacetList[$numButtons++] = $facet;
					}*/
				}

				ksort($sortedFacetList);
				$facetSet['list'] = $sortedFacetList;
				$facetSet['isFormatCategory'] = false;
				$facetSet['isAvailabilityToggle'] = true;
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
	public function getTemplate() {
		return 'Search/Recommend/TopFacets.tpl';
	}

	public static $formatCategorySortOrder = [
		'Books' => 1,
		'eBook' => 2,
		'Audio Books' => 3,
		'eAudio' => 4,
		'Music' => 5,
		'Movies' => 6,
	];
}

function format_category_comparator($a, $b) {
	$a = TopFacets::$formatCategorySortOrder[$a];
	$b = TopFacets::$formatCategorySortOrder[$b];
	if ($a == $b) {
		return 0;
	} else {
		return ($a > $b ? 1 : -1);
	}
}

;