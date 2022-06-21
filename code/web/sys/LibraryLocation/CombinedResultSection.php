<?php /** @noinspection PhpUnused */

abstract class CombinedResultSection extends DataObject{
	public $__displayNameColumn = 'displayName';
	public $id;
	public $displayName;
	public $weight;
	public $source;
	public $numberOfResultsToShow;

	static function getObjectStructure() : array {
		global $configArray;
		global $enabledModules;
		$validResultSources = array();
		$validResultSources['catalog'] = 'Catalog Results';
		require_once ROOT_DIR . '/sys/Enrichment/DPLASetting.php';
		$dplaSetting = new DPLASetting();
		if ($dplaSetting->find(true)){
			$validResultSources['dpla'] = 'DP.LA';
		}
		if (array_key_exists('EBSCO EDS', $enabledModules)) {
			$validResultSources['ebsco_eds'] = 'EBSCO EDS';
		}
		if (array_key_exists('Events', $enabledModules)){
			$validResultSources['events'] = 'Events';
		}
		if (array_key_exists('Genealogy', $enabledModules)) {
			$validResultSources['genealogy'] = 'Genealogy';
		}
		if (array_key_exists('Open Archives', $enabledModules)) {
			$validResultSources['open_archives'] = 'Open Archives';
		}
		if ($configArray['Content']['Prospector']) {
			$validResultSources['prospector'] = 'Prospector';
		}
		if (array_key_exists('Web Indexer', $enabledModules)){
			$validResultSources['websites'] = 'Website Search';
		}
		$validResultSources['lists'] = 'User Lists';

		return array(
				'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of this section'),
				'weight' => array('property'=>'weight', 'type'=>'integer', 'label'=>'Weight', 'description'=>'The sort order', 'default' => 0),
				'displayName' => array('property'=>'displayName', 'type'=>'text', 'label'=>'Display Name', 'description'=>'The full name of the section for display to the user', 'maxLength' => 255),
				'numberOfResultsToShow' => array('property'=>'numberOfResultsToShow', 'type'=>'integer', 'label'=>'Num Results', 'description'=>'The number of results to show in the box.', 'default' => '5'),
				'source' => array('property'=>'source', 'type'=>'enum', 'label'=>'Source', 'values' => $validResultSources, 'description'=>'The source of results in the section.', 'default'=>'catalog'),
		);
	}

	function getResultsLink($searchTerm, $searchType){
		if ($this->source == 'archive'){
			return "/Archive/Results?lookfor=$searchTerm";
		}elseif ($this->source == 'catalog') {
			return "/Search/Results?lookfor=$searchTerm&searchSource=local";
		}elseif ($this->source == 'dpla'){
			return "https://dp.la/search?q=$searchTerm";
		}elseif ($this->source == 'ebsco_eds'){
			return "/EBSCO/Results?lookfor=$searchTerm&searchSource=ebsco_eds";
		}elseif ($this->source == 'events'){
			return "/Events/Results?lookfor=$searchTerm&searchSource=events";
		}elseif ($this->source == 'genealogy'){
			return "/Genealogy/Results?lookfor=$searchTerm&searchSource=genealogy";
		}elseif ($this->source == 'lists'){
			return "/Lists/Results?lookfor=$searchTerm&searchSource=lists";
		}elseif ($this->source == 'open_archives'){
			return "/OpenArchives/Results?lookfor=$searchTerm&searchSource=open_archives";
		}elseif ($this->source == 'prospector'){
			require_once ROOT_DIR . '/Drivers/marmot_inc/Prospector.php';
			$prospector = new Prospector();
			$search = array(array('lookfor' => $searchTerm, 'index' => $searchType));
			return $prospector->getSearchLink($search);
		}elseif ($this->source == 'websites'){
			return "/Websites/Results?lookfor=$searchTerm&searchSource=websites";
		}else{
			return '';
		}
	}
}