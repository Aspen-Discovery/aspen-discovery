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
class EventDriver extends IslandoraDriver {


	public function getViewAction() {
		return 'Event';
	}

	protected function getPlaceholderImage() {
		global $configArray;
		return $configArray['Site']['path'] . '/interface/themes/responsive/images/events.png';
	}

	public function isEntity(){
		return true;
	}

	public function getFormat(){
		return 'Event';
	}

	public function getMoreDetailsOptions() {
		//Load more details options
		global $interface;
		$moreDetailsOptions = $this->getBaseMoreDetailsOptions();

		$relatedPlaces = $this->getRelatedPlaces();
		$unlinkedEntities = $this->unlinkedEntities;
		$linkedAddresses = array();
		$unlinkedAddresses = array();
		foreach ($unlinkedEntities as $key => $tmpEntity){
			if ($tmpEntity['type'] == 'place'){
				if (strcasecmp($tmpEntity['role'], 'address') === 0 || $tmpEntity['role'] == ''){
					$tmpEntity['role'] = 'Address';
					$unlinkedAddresses[] = $tmpEntity;
					unset($this->unlinkedEntities[$key]);
					$interface->assign('unlinkedEntities', $this->unlinkedEntities);
				}
			}
		}
		foreach ($relatedPlaces as $key => $tmpEntity){
			if (strcasecmp($tmpEntity['role'], 'address') === 0 || $tmpEntity['role'] == ''){
				$tmpEntity['role'] = 'Address';
				$linkedAddresses[] = $tmpEntity;
				unset($this->relatedPlaces[$key]);
				$interface->assign('relatedPlaces', $this->relatedPlaces);
			}

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
		if (count($this->relatedPlaces) == 0){
			unset($moreDetailsOptions['relatedPlaces']);
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

		return $this->filterAndSortMoreDetailsOptions($moreDetailsOptions);
	}
}