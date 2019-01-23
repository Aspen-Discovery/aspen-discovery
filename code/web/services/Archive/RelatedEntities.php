<?php

/**
 * Finde Entities related to a search term
 *
 * @category VuFind-Plus-2014
 * @author Mark Noble <mark@marmot.org>
 * Date: 2/17/2016
 * Time: 3:48 PM
 */
class Archive_RelatedEntities extends Action {
	function launch(){
		global $interface;
		global $configArray;
		global $timer;

		// Include Search Engine Class
		require_once ROOT_DIR . '/sys/Solr.php';
		$timer->logTime('Include search engine');

		$searchTerm = $_REQUEST['lookfor'];

		/** @var SearchObject_Islandora $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject('Islandora');
		$searchObject->init();

		$searchObject->setDebugging(false, false);

		//Get a list of objects in the archive related to this search
		$searchObject->setSearchTerms(array(
			'lookfor' => $searchTerm,
			'index' => 'IslandoraKeyword'
		));
		$searchObject->clearHiddenFilters();
		$searchObject->addHiddenFilter('!RELS_EXT_isViewableByRole_literal_ms', "administrator");
		$searchObject->clearFilters();
		//Don't get any documents
		$searchObject->setLimit(0);
		//Add the appropriate facet based on what we are looking for.
		$entityType = $_REQUEST['entityType'];
		if ($entityType == 'person'){
			$facetField = 'mods_extension_marmotLocal_relatedEntity_person_entityPid_ms';
			$pageTitle = "People related to $searchTerm";
			$searchObject->addFacet($facetField, 'People');
			$urlAction = 'Person';

		}elseif ($entityType == 'place') {
			$facetField = 'mods_extension_marmotLocal_relatedEntity_place_entityPid_ms';
			$pageTitle = "Places related to $searchTerm";
			$searchObject->addFacet($facetField, 'Places');
			$urlAction = 'Place';
		}else{
			$facetField = 'mods_extension_marmotLocal_relatedEntity_event_entityPid_ms';
			$pageTitle = "Events related to $searchTerm";
			$searchObject->addFacet($facetField, 'Events');
			$urlAction = 'Event';
		}
		$interface->assign('shortPageTitle', $pageTitle);

		//TODO: Sort and paginate facet values.
		//  The problem with doing it now is that we are faceting based on pid which can't
		//  be sorted on properly. We will need a field combining PID and label
		/*$searchObject->setFacetSortOrder('index');
		$pageSize = 24;
		$searchObject->setFacetLimit($pageSize);
		if (isset($_REQUEST['page'])){
			$page = $_REQUEST['page'];
			$searchObject->setFacetOffset($page - 1 * $pageSize);
		}*/

		$response = $searchObject->processSearch(true, false);
		$relatedEntities = array();
		require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
		$fedoraUtils = FedoraUtils::getInstance();
		if (isset($response['facet_counts']['facet_fields'][$facetField])) {
			foreach ($response['facet_counts']['facet_fields'][$facetField] as $facetValue){
				$archiveObject = $fedoraUtils->getObject($facetValue[0]);
				if ($archiveObject != null) {
					$relatedEntities[] = array(
						'title' => $archiveObject->label,
						'description' => $archiveObject->label,
						'image' => $fedoraUtils->getObjectImageUrl($archiveObject, 'small', $entityType),
						'link' => $configArray['Site']['path'] . "/Archive/{$archiveObject->id}/$urlAction",
					);
				}

			}
		}
		$interface->assign('relatedEntities', $relatedEntities);

		$this->display('relatedEntities.tpl', $pageTitle, 'Search/results-sidebar.tpl');
	}
}