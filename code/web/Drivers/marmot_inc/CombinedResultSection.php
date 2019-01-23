<?php
/**
 * Created by PhpStorm.
 * User: mnoble
 * Date: 11/17/2017
 * Time: 4:00 PM
 */

abstract class CombinedResultSection extends DB_DataObject{
	public $id;
	public $displayName;
	public $weight;
	public $source;
	public $numberOfResultsToShow;

	static function getObjectStructure(){
		global $configArray;
		$validResultSources = array();
		if (!empty($configArray['Islandora']['repositoryUrl'])){
			$validResultSources['archive'] = 'Digital Archive';
		}
		if ($configArray['DPLA']['enabled']){
			$validResultSources['dpla'] = 'DPLA';
		}
		$validResultSources['eds'] = 'EBSCO EDS';
		$validResultSources['pika'] = 'Pika Results';
		if ($configArray['Content']['Prospector']) {
			$validResultSources['prospector'] = 'Prospector';
		}

		$structure = array(
				'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of this section'),
				'weight' => array('property'=>'weight', 'type'=>'integer', 'label'=>'Weight', 'description'=>'The sort order of the section', 'default' => 0),
				'displayName' => array('property'=>'displayName', 'type'=>'text', 'label'=>'Display Name', 'description'=>'The full name of the section for display to the user', 'maxLength' => 255),
				'numberOfResultsToShow' => array('property'=>'numberOfResultsToShow', 'type'=>'integer', 'label'=>'Num Results', 'description'=>'The number of results to show in the box.', 'default' => '5'),
				'source' => array('property'=>'source', 'type'=>'enum', 'label'=>'Source', 'values' => $validResultSources, 'description'=>'The source of results in the section.', 'default'=>'pika'),
		);
		return $structure;
	}

	function getResultsLink($searchTerm, $searchType){
		if ($this->source == 'pika') {
			return "/Search/Results?lookfor=$searchTerm&basicType=$searchType&searchSource=local";
		}elseif ($this->source == 'prospector'){
			require_once ROOT_DIR . '/Drivers/marmot_inc/Prospector.php';
			$prospector = new Prospector();
			$search = array(array('lookfor' => $searchTerm, 'index' => $searchType));
			return $prospector->getSearchLink($search);
		}elseif ($this->source == 'dpla'){
			return "https://dp.la/search?q=$searchTerm";
		}elseif ($this->source == 'eds'){
			global $library;
			return "https://search.ebscohost.com/login.aspx?direct=true&site=eds-live&scope=site&type=1&custid={$library->edsApiUsername}&groupid=main&profid={$library->edsSearchProfile}&mode=bool&lang=en&authtype=cookie,ip,guest&bquery=$searchTerm";
		}elseif ($this->source == 'archive'){
			return "/Archive/Results?lookfor=$searchTerm&basicType=$searchType";
		}else{
			return '';
		}
	}
}