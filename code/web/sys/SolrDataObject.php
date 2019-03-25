<?php
require_once ROOT_DIR . '/sys/SolrConnector/Solr.php';
require_once ROOT_DIR .'/sys/DB/DataObject.php';
abstract class SolrDataObject extends DataObject{
	/**
	 * Return an array describing the structure of the object fields, etc.
	 */
	abstract static function getObjectStructure();

	function update(){
		return $this->updateDetailed(true);
	}
	private $updateStarted = false;
	function updateDetailed($insertInSolr = true){
		if ($this->updateStarted){
			return true;
		}
		$this->updateStarted = true;

		global $logger;
		$result = parent::update();
		if (!$insertInSolr){
			$logger->log("updateDetailed, not inserting in solr because insertInSolr was false", PEAR_LOG_DEBUG);
			$this->updateStarted = false;
			return $result == 1;
		}else{
			if ($result !== FALSE){
				$logger->log("Updating Solr", PEAR_LOG_DEBUG);
				if (!$this->saveToSolr()){
					$logger->log("Could not update Solr", PEAR_LOG_ERR);
					//Could not save to solr
					$this->updateStarted = false;
					return false;
				}
			}else{
				$logger->log("Saving to database failed, not updating solr", PEAR_LOG_ERR);
				$this->updateStarted = false;
				return false;
			}
			$this->updateStarted = false;
			return true;
		}
	}
	function insert(){
		return $this->insertDetailed(true);
	}
	function insertDetailed($insertInSolr = true){
		$result = parent::insert($insertInSolr);
		if (!$insertInSolr){
			return $result;
		}else{
			if ($result !== 0){
				if (!$this->saveToSolr()){
					//Could not save to solr
					return false;
				}
			}else{
				return false;
			}
			return true;
		}
	}
	function delete($useWhere = false){
		$result = parent::delete($useWhere);
		if ($result != FALSE){
			$this->removeFromSolr();
		}
		return $result;
	}

	/**
	 * Return an array with the name or names of the cores that contain this object
	 */
	abstract function cores();

	/**
	 * Return a unique id for the object
	 */
	abstract function solrId();

	function removeFromSolr(){
		require_once ROOT_DIR . '/sys/SolrConnector/Solr.php';
		global $configArray;
		$host = $configArray['Index']['url'];

		global $logger;
		$logger->log("Deleting Record {$this->solrId()}", PEAR_LOG_INFO);

		$cores = $this->cores();
		foreach ($cores as $corename){
			$index = new Solr($host, $corename);
			if ($index->deleteRecord($this->solrId())) {
				$index->commit();
			} else {
				return new PEAR_Error("Could not remove from $corename index");
			}
		}
		return true;
	}

	protected $_quickReindex = false;
	private $saveStarted = false;
	function saveToSolr($quick = false){
		if ($this->saveStarted){
			return true;
		}
		$this->saveStarted = true;

		global $timer;
		global $configArray;
		$this->_quickReindex = $quick;
		$host = $configArray['Index']['url'];
		global $logger;
		$logger->log("Updating " . $this->solrId() . " in solr", PEAR_LOG_INFO);

		$cores = $this->cores();
		$objectStructure = $this->getObjectStructure();
		$doc = array();
		foreach ($objectStructure as $property){
			if ((isset($property['storeSolr']) && $property['storeSolr']) || (isset($property['properties']) && count($property['properties']) > 0)){
				$doc = $this->updateSolrDocumentForProperty($doc, $property);
			}
		}
		$timer->logTime('Built Contents to save to Solr');

		foreach ($cores as $corename){
			$index = new Solr($host, $corename);

			$xml = $index->getSaveXML($doc, !$this->_quickReindex, $this->_quickReindex);
			//$logger->log('XML ' . print_r($xml, true), PEAR_LOG_INFO);
			$timer->logTime('Created XML to save to the main index');
			if ($index->saveRecord($xml)) {
				if (!$this->_quickReindex){
					$result = $index->commit();
					//$logger->log($xml, PEAR_LOG_INFO);
					//$logger->log("Result saving to $corename index " . print_r($result, true), PEAR_LOG_INFO);
				}
			} else {
				$this->saveStarted = false;
				return new PEAR_Error("Could not save to $corename");
			}
			$timer->logTime("Saved to the $corename index");
		}
		$this->saveStarted = false;
		return true;
	}

	function updateSolrDocumentForProperty($doc, $property){
		if (isset($property['storeSolr']) && $property['storeSolr']){
			$propertyName = $property['property'];
			if ($property['type'] == 'method'){
				$methodName = isset($property['methodName']) ? $property['methodName'] : $property['property'];
				$doc[$propertyName] = $this->$methodName();
			}elseif ($property['type'] == 'crSeparated'){
				if (strlen($this->$propertyName)){
					$propertyValues = explode("\r\n", $this->$propertyName);
					$doc[$propertyName] = $propertyValues;
				}
			}elseif ($property['type'] == 'date' || $property['type'] == 'partialDate'){
				if ($this->$propertyName != null && strlen($this->$propertyName) > 0){
					//get the date array and reformat for solr
					$dateParts = date_parse($this->$propertyName);
					if ($dateParts['year'] != false && $dateParts['month'] != false && $dateParts['day'] != false){
						$time = $dateParts['year'] . '-' . $dateParts['month'] . '-' . $dateParts['day'] . 'T00:00:00Z';
						$doc[$propertyName] = $time;
					}
				}
			}else{
				if (isset($this->$propertyName)){
					$doc[$propertyName] = $this->$propertyName;
				}
			}
		}elseif (isset($property['properties']) && count($property['properties']) > 0){
			$properties = $property['properties'];
			foreach ($properties as $subProperty){
				$doc = $this->updateSolrDocumentForProperty($doc, $subProperty);
			}
		}
		return $doc;
	}

	function optimize(){
		require_once ROOT_DIR . '/sys/SolrConnector/Solr.php';
		global $configArray;
		$host = $configArray['Index']['url'];

		$cores = $this->cores();
		foreach ($cores as $corename){
			global $logger;
			$logger->log("Optimizing Solr Core! $corename", PEAR_LOG_INFO);

			$index = new Solr($host, $corename);
			$index->optimize();
		}
		return true;
	}
}