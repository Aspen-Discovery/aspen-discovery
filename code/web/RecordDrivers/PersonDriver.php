<?php

/**
 * Record Driver for display of LargeImages from Islandora
 *
 * @category VuFind-Plus-2014
 * @author Mark Noble <mark@marmot.org>
 * Date: 12/9/2015
 * Time: 1:47 PM
 */
require_once ROOT_DIR . '/RecordDrivers/IslandoraDriver.php';
class PersonDriver extends IslandoraDriver {

	public function getViewAction() {
		return 'Person';
	}

	protected function getPlaceholderImage() {
		global $configArray;
		return $configArray['Site']['path'] . '/interface/themes/responsive/images/people.png';
	}

	public function isEntity(){
		return true;
	}

	public function getFormat(){
		return 'Person';
	}

	public function getMoreDetailsOptions() {
		//Load more details options
		global $interface;
		$moreDetailsOptions = $this->getBaseMoreDetailsOptions();
		unset($moreDetailsOptions['relatedPlaces']);

		$relatedPlaces = $this->getRelatedPlaces();
		$unlinkedEntities = $this->unlinkedEntities;
		$linkedAddresses = array();
		$unlinkedAddresses = array();
		$linkedMilitaryAddresses = array();
		$unlinkedMilitaryAddresses = array();
		foreach ($unlinkedEntities as $key => $tmpEntity){
			if ($tmpEntity['type'] == 'place'){
				if (strcasecmp($tmpEntity['role'], 'ServedInMilitary') === 0){
					$unlinkedMilitaryAddresses[] = $tmpEntity;
				}else{
					$unlinkedAddresses[] = $tmpEntity;
				}
				unset($this->unlinkedEntities[$key]);
				$interface->assign('unlinkedEntities', $this->unlinkedEntities);
			}
		}
		foreach ($relatedPlaces as $key => $tmpEntity){
			if (strcasecmp($tmpEntity['role'], 'ServedInMilitary') === 0){
				$linkedMilitaryAddresses[] = $tmpEntity;
			}else{
				$linkedAddresses[] = $tmpEntity;
			}
			unset($this->relatedPlaces[$key]);
			$interface->assign('relatedPlaces', $this->relatedPlaces);
		}
		$interface->assign('unlinkedAddresses', $unlinkedAddresses);
		$interface->assign('linkedAddresses', $linkedAddresses);
		if (count($linkedAddresses) || count($unlinkedAddresses)) {
			$moreDetailsOptions['addresses'] = array(
					'label' => 'Addresses',
					'body' => $interface->fetch('Archive/addressSection.tpl'),
					'hideByDefault' => false,
			);
		}
		if ((count($interface->getVariable('creators')) > 0)
				|| $this->hasDetails
				|| (count($interface->getVariable('marriages')) > 0)
				|| (count($this->unlinkedEntities) > 0)){
			$moreDetailsOptions['details'] = array(
					'label' => 'Details',
					'body' => $interface->fetch('Archive/detailsSection.tpl'),
					'hideByDefault' => false
			);
		}else{
			unset($moreDetailsOptions['details']);
		}

		$relatedPeople = $this->getRelatedPeople();
		if (count($relatedPeople)) {
			$moreDetailsOptions['familyDetails'] = array(
					'label' => 'Family Details',
					'body' => $interface->fetch('Archive/relatedPeopleSection.tpl'),
					'hideByDefault' => false,
			);
			unset($moreDetailsOptions['relatedPeople']);
		}

		return $this->filterAndSortMoreDetailsOptions($moreDetailsOptions);
	}
}