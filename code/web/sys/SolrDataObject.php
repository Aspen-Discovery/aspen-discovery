<?php
require_once ROOT_DIR . '/sys/SolrConnector/Solr.php';
require_once ROOT_DIR . '/sys/DB/DataObject.php';

abstract class SolrDataObject extends DataObject {
	/**
	 * Return an array describing the structure of the object fields, etc.
	 */
	abstract static function getObjectStructure();

	function update() {
		return $this->updateDetailed(true);
	}

	private $updateStarted = false;

	function updateDetailed($insertInSolr = true) {
		if ($this->updateStarted) {
			return true;
		}
		$this->updateStarted = true;

		global $logger;
		$result = parent::update();
		if (!$insertInSolr) {
			$logger->log("updateDetailed, not inserting in solr because insertInSolr was false", Logger::LOG_DEBUG);
			$this->updateStarted = false;
			return $result == 1;
		} else {
			if ($result !== FALSE) {
				$logger->log("Updating Solr", Logger::LOG_DEBUG);
				if (!$this->saveToSolr()) {
					$logger->log("Could not update Solr", Logger::LOG_ERROR);
					//Could not save to solr
					$this->updateStarted = false;
					return false;
				}
			} else {
				$logger->log("Saving to database failed, not updating solr", Logger::LOG_ERROR);
				$this->updateStarted = false;
				return false;
			}
			$this->updateStarted = false;
			return true;
		}
	}

	function insert() {
		return $this->insertDetailed(true);
	}

	function insertDetailed($insertInSolr = true) {
		$result = parent::insert();
		if (!$insertInSolr) {
			return $result;
		} else {
			if ($result !== 0) {
				if (!$this->saveToSolr()) {
					//Could not save to solr
					return false;
				}
			} else {
				return false;
			}
			return true;
		}
	}

	function delete($useWhere = false) {
		$result = parent::delete();
		if ($result != FALSE) {
			$this->removeFromSolr();
		}
		return $result;
	}

	/**
	 * return string
	 */
	abstract function getCore();

	/**
	 * Return a unique id for the object
	 */
	abstract function solrId();

	function removeFromSolr() {
		require_once ROOT_DIR . '/sys/SolrConnector/Solr.php';
		global $logger;
		$logger->log("Deleting Record {$this->solrId()}", Logger::LOG_NOTICE);

		$coreName = $this->getCore();
		/** @var SearchObject_SolrSearcher $searcher */
		$searcher = SearchObjectFactory::initSearchObjectBySearchSource($coreName);
		$index = $searcher->getIndexEngine();
		if ($index->deleteRecord($this->solrId())) {
			$index->commit();
		} else {
			return new AspenError("Could not remove from $coreName index");
		}
		return true;
	}

	protected $_quickReindex = false;
	private $saveStarted = false;

	function saveToSolr($quick = false) {
		if ($this->saveStarted) {
			return true;
		}
		$this->saveStarted = true;

		global $timer;
		$this->_quickReindex = $quick;
		global $logger;
		$logger->log("Updating " . $this->solrId() . " in solr", Logger::LOG_NOTICE);

		$objectStructure = $this->getObjectStructure();
		$doc = [];
		foreach ($objectStructure as $property) {
			if ((isset($property['storeSolr']) && $property['storeSolr']) || (isset($property['properties']) && count($property['properties']) > 0)) {
				$doc = $this->updateSolrDocumentForProperty($doc, $property);
			}
		}
		$timer->logTime('Built Contents to save to Solr');

		$coreName = $this->getCore();
		/** @var SearchObject_SolrSearcher $searcher */
		$searcher = SearchObjectFactory::initSearchObjectBySearchSource($coreName);
		$index = $searcher->getIndexEngine();

		$xml = $index->getSaveXML($doc, !$this->_quickReindex, $this->_quickReindex);
		//$logger->log('XML ' . print_r($xml, true), Logger::LOG_NOTICE);
		$timer->logTime('Created XML to save to the main index');
		if ($index->saveRecord($xml)) {
			if (!$this->_quickReindex) {
				$index->commit();
			}
		} else {
			$this->saveStarted = false;
			return new AspenError("Could not save to $coreName");
		}
		$timer->logTime("Saved to the $coreName index");

		$this->saveStarted = false;
		return true;
	}

	function updateSolrDocumentForProperty($doc, $property) {
		if (isset($property['storeSolr']) && $property['storeSolr']) {
			$propertyName = $property['property'];
			if ($property['type'] == 'method') {
				$methodName = isset($property['methodName']) ? $property['methodName'] : $property['property'];
				$doc[$propertyName] = $this->$methodName();
			} elseif ($property['type'] == 'crSeparated') {
				if (strlen($this->$propertyName)) {
					$propertyValues = explode("\r\n", $this->$propertyName);
					$doc[$propertyName] = $propertyValues;
				}
			} elseif ($property['type'] == 'date' || $property['type'] == 'partialDate') {
				if ($this->$propertyName != null && strlen($this->$propertyName) > 0) {
					//get the date array and reformat for solr
					$dateParts = date_parse($this->$propertyName);
					if ($dateParts['year'] != false && $dateParts['month'] != false && $dateParts['day'] != false) {
						$time = $dateParts['year'] . '-' . $dateParts['month'] . '-' . $dateParts['day'] . 'T00:00:00Z';
						$doc[$propertyName] = $time;
					}
				}
			} else {
				if (isset($this->$propertyName)) {
					$doc[$propertyName] = $this->$propertyName;
				}
			}
		} elseif (isset($property['properties']) && count($property['properties']) > 0) {
			$properties = $property['properties'];
			foreach ($properties as $subProperty) {
				$doc = $this->updateSolrDocumentForProperty($doc, $subProperty);
			}
		}
		return $doc;
	}

	function optimize() {
		require_once ROOT_DIR . '/sys/SolrConnector/Solr.php';
		$coreName = $this->getCore();
		global $logger;
		$logger->log("Optimizing Solr Core! $coreName", Logger::LOG_NOTICE);

		/** @var SearchObject_SolrSearcher $searcher */
		$searcher = SearchObjectFactory::initSearchObjectBySearchSource($coreName);
		$index = $searcher->getIndexEngine();
		$index->optimize();

		return true;
	}
}