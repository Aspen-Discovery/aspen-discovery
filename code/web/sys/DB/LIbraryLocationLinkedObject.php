<?php

require_once ROOT_DIR . '/sys/DB/LibraryLinkedObject.php';
abstract class DB_LibraryLocationLinkedObject extends DB_LibraryLinkedObject
{
	/**
	 * @return int[]
	 */
	public abstract function getLocations() : ?array;

	public function okToExport(array $selectedFilters) : bool{
		$okToExport = parent::okToExport($selectedFilters);
		$selectedLibraries = $selectedFilters['locations'];
		foreach ($selectedLibraries as $locationId) {
			if (array_key_exists($locationId, $this->getLocations())) {
				$okToExport = true;
				break;
			}
		}
		return $okToExport;
	}

	public function getLinksForJSON() : array{
		$links = parent::getLinksForJSON();
		$allLocations = Location::getLocationListAsObjects(false);

		$locations = $this->getLocations();

		$links['locations'] = [];
		foreach ($locations as $locationId){
			if (array_key_exists($locationId, $allLocations)) {
				$location = $allLocations[$locationId];
				$links['locations'][$locationId] = $location->code;
			}
		}
		return $links;
	}

	public function loadRelatedLinksFromJSON($jsonLinks, $mappings, $overrideExisting = 'keepExisting') : bool{
		$result = parent::loadRelatedLinksFromJSON($jsonLinks, $mappings);
		if (array_key_exists('locations', $jsonLinks)){
			$allLocations = Location::getLocationListAsObjects(false);
			$locations = [];
			foreach ($jsonLinks['locations'] as $ilsCode){
				if (array_key_exists($ilsCode, $mappings['locations'])){
					$ilsCode = $mappings['locations'][$ilsCode];
				}
				foreach ($allLocations as $tmpLocation) {
					if ($tmpLocation->code == $ilsCode) {
						$locations[$tmpLocation->locationId] = $tmpLocation->locationId;
					}
				}
			}
			$this->_locations = $locations;
			$result = true;
		}
		return $result;
	}

	public function toArray() : array
	{
		//Unset locations since they will be added as links
		$return = parent::toArray();
		unset($return['locations']);
		return $return;
	}
}
