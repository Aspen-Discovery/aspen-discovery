<?php

require_once ROOT_DIR . '/RecordDrivers/IslandoraRecordDriver.php';
class PageRecordDriver extends IslandoraRecordDriver {

	public function getViewAction() {
		return 'Page';
	}

	public function getFormat(){
		return 'Page';
	}

	function getRecordUrl(){
		$recordId = $this->getUniqueID();
		//For Pages we do things a little differently since we want to link to the page within the book so we get context.
		$parentObject = $this->getParentObject();
		$parentDriver = RecordDriverFactory::initIslandoraDriverFromObject($parentObject);
		if ($parentDriver != null && $parentDriver instanceof BookDriver){
			return $parentDriver->getRecordUrl() . '?pagePid=' . urlencode($recordId);
		}else{
            return '/Archive/' . urlencode($recordId) . '/' . $this->getViewAction();
		}
	}

    function getAbsoluteUrl(){
        global $configArray;
        $recordId = $this->getUniqueID();
        //For Pages we do things a little differently since we want to link to the page within the book so we get context.
        $parentObject = $this->getParentObject();
        $parentDriver = RecordDriverFactory::initIslandoraDriverFromObject($parentObject);
        if ($parentDriver != null && $parentDriver instanceof BookDriver){
            return $parentDriver->getAbsoluteUrl() . '?pagePid=' . urlencode($recordId);
        }else{
            return $configArray['Site']['url'] . '/Archive/' . urlencode($recordId) . '/' . $this->getViewAction();
        }
    }
}