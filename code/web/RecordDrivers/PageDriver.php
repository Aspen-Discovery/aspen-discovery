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
class PageDriver extends IslandoraDriver {

	public function getViewAction() {
		return 'Page';
	}

	public function getFormat(){
		return 'Page';
	}

	function getRecordUrl($absolutePath = false){
		global $configArray;
		$recordId = $this->getUniqueID();
		//For Pages we do things a little differently since we want to link to the page within the book so we get context.
		$parentObject = $this->getParentObject();
		$parentDriver = RecordDriverFactory::initIslandoraDriverFromObject($parentObject);
		if ($parentDriver != null && $parentDriver instanceof BookDriver){
			return $parentDriver->getRecordUrl($absolutePath) . '?pagePid=' . urlencode($recordId);
		}else{
			if ($absolutePath){
				return $configArray['Site']['url'] . '/Archive/' . urlencode($recordId) . '/' . $this->getViewAction();
			}else{
				return $configArray['Site']['path'] . '/Archive/' . urlencode($recordId) . '/' . $this->getViewAction();
			}
		}
	}
}