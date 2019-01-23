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
class CompoundDriver extends IslandoraDriver {

	public function getViewAction() {
		$genre = $this->getModsValue('genre', 'mods');
		if ($genre != null && strlen($genre) > 0){
			return ucfirst($genre);
		}
		return "Compound";
	}

	public function getFormat(){
		return $this->getViewAction();
	}

	public function loadBookContents() {
		global $configArray;
		$objectUrl = $configArray['Islandora']['objectUrl'];
		$rels_predicate = 'isConstituentOf';
		$sections = array();

		$fedoraUtils = FedoraUtils::getInstance();

		$escaped_pid = str_replace(':', '_', $this->getUniqueID());
		$query = <<<EOQ
PREFIX islandora-rels-ext: <http://islandora.ca/ontology/relsext#>
SELECT ?object ?title ?seq
FROM <#ri>
WHERE {
  ?object <fedora-model:label> ?title ;
          <fedora-rels-ext:$rels_predicate> <info:fedora/{$this->getUniqueID()}> .
  OPTIONAL {
    ?object islandora-rels-ext:isSequenceNumberOf$escaped_pid ?seq
  }
}
EOQ;

		$queryResults = $fedoraUtils->doSparqlQuery($query);

		if (count($queryResults) == 0){
			$sectionDetails = array(
					'pid' => $this->getUniqueID(),
					'title' => $this->getTitle(),
					'seq' => 0,
					'cover' => $this->getBookcoverUrl('small'),
					'transcript' => ''
			);
			$sectionObject = $fedoraUtils->getObject($this->getUniqueID());
			$sectionDetails = $this->loadPagesForSection($sectionObject, $sectionDetails);

			$sections[$this->getUniqueID()] = $sectionDetails;
		}else{
			// Sort the objects into their proper order.
			$sort = function($a, $b) {
				$a = $a['seq']['value'];
				$b = $b['seq']['value'];
				if ($a === $b) {
					return 0;
				}
				if (empty($a)) {
					return 1;
				}
				if (empty($b)) {
					return -1;
				}
				return $a - $b;
			};
			uasort($queryResults, $sort);

			foreach ($queryResults as $result) {
				$objectPid = $result['object']['value'];
				//TODO: check access
				/** @var FedoraObject $sectionObject */
				$sectionObject = $fedoraUtils->getObject($objectPid);
				$sectionDetails = array(
						'pid' => $objectPid,
						'title' => $result['title']['value'],
						'seq' => $result['seq']['value'],
						'cover' => $fedoraUtils->getObjectImageUrl($sectionObject, 'thumbnail'),
						'transcript' => ''
				);
				$pdfStream = $sectionObject->getDatastream('PDF');
				if ($pdfStream != null){
					$sectionDetails['pdf'] = $objectUrl . '/' . $objectPid . '/datastream/PDF/view';;
				}
				$videoStream = $sectionObject->getDatastream('MP4');
				if ($videoStream != null){
					$sectionDetails['video'] = $objectUrl . '/' . $objectPid . '/datastream/MP4/view';;
				}
				$objStream = $sectionObject->getDatastream('OBJ');
				if ($objStream && $objStream->mimetype == 'audio/mpeg'){
					$sectionDetails['audio'] = $objectUrl . '/' . $objectPid . '/datastream/OBJ/view';;
				}elseif ($objStream && $objStream->mimetype == 'video/mp4'){
					$sectionDetails['video'] = $objectUrl . '/' . $objectPid . '/datastream/OBJ/view';;
				}
				//Load individual pages for this section
				$sectionDetails = $this->loadPagesForSection($sectionObject, $sectionDetails);

				$sections[$objectPid] = $sectionDetails;
			}
		}

		return $sections;

	}

	/**
	 * Main logic happily borrowed from islandora_paged_content/includes/utilities.inc
	 * @param FedoraObject $sectionObject
	 * @param array $sectionDetails
	 * @return array
	 */
	function loadPagesForSection($sectionObject, $sectionDetails){
		global $configArray;
		$objectUrl = $configArray['Islandora']['objectUrl'];

		$fedoraUtils = FedoraUtils::getInstance();
		$query = <<<EOQ
PREFIX islandora-rels-ext: <http://islandora.ca/ontology/relsext#>
SELECT ?pid ?page ?label ?width ?height
FROM <#ri>
WHERE {
  ?pid <fedora-rels-ext:isMemberOf> <info:fedora/{$sectionObject->id}> ;
       <fedora-model:label> ?label ;
       islandora-rels-ext:isSequenceNumber ?page ;
       <fedora-model:state> <fedora-model:Active> .
  OPTIONAL {
    ?pid <fedora-view:disseminates> ?dss .
    ?dss <fedora-view:disseminationType> <info:fedora/*/JP2> ;
         islandora-rels-ext:width ?width ;
         islandora-rels-ext:height ?height .
 }
}
ORDER BY ?page
EOQ;

		$results = $fedoraUtils->doSparqlQuery($query);

		// Get rid of the "extra" info...
		$map = function($o) {
			foreach ($o as $key => &$info) {
				$info = $info['value'];
			}

			$o = array_filter($o);

			return $o;
		};
		$pages = array_map($map, $results);

		// Sort the pages into their proper order.
		$sort = function($a, $b) {
			$a = (is_array($a) && isset($a['page'])) ? $a['page'] : 0;
			$b = (is_array($b) && isset($b['page'])) ? $b['page'] : 0;
			if ($a == $b) {
				return 0;
			}
			return ($a < $b) ? -1 : 1;
		};
		uasort($pages, $sort);

		foreach ($pages as $index=>$page){
			//Get additional details about the page
			$pageObject = $fedoraUtils->getObject($page['pid']);
			if ($pageObject->getDataStream('JP2') != null){
				$page['jp2'] = $objectUrl . '/' . $page['pid'] . '/datastream/JP2/view';
			}
			if ($pageObject->getDataStream('PDF') != null){
				$page['pdf'] = $objectUrl . '/' . $page['pid'] . '/datastream/PDF/view';
			}
			//Get MODS before the HOCR or OCR
			$modsForPage = $fedoraUtils->getModsData($pageObject);
			$transcript = $fedoraUtils->getModsValue('transcriptionText', 'marmot', $modsForPage);
			if (strlen($transcript) > 0){
				$page['transcript'] = 'mods:' . $page['pid'];
			}else{
				$hasTranscript = false;
				$parents = $pageObject->getParents();
				foreach ($parents as $parent) {
					$parentObject = $fedoraUtils->getObject($parent);
					if ($parentObject != null) {
						$modsForParent = $fedoraUtils->getModsData($parentObject);
						$transcript = $fedoraUtils->getModsValue('transcriptionText', 'marmot', $modsForParent);
						if (strlen($transcript) > 0) {
							$page['transcript'] = 'mods:' . $parentObject->id;
							$hasTranscript = true;
						}
					}
				}
				if (!$hasTranscript) {
					if ($pageObject->getDataStream('HOCR') != null && $pageObject->getDataStream('HOCR')->size > 1) {
						$page['transcript'] = $page['pid'] . '/datastream/HOCR/view';
					} elseif ($pageObject->getDataStream('OCR') != null && $pageObject->getDataStream('OCR')->size > 1) {
						$page['transcript'] = $page['pid'] . '/datastream/OCR/view';
					}
				}
			}
			$page['cover'] = $fedoraUtils->getObjectImageUrl($pageObject, 'thumbnail');
			$pages[$index] = $page;
		}

		$sectionDetails['pages'] = $pages;

		return $sectionDetails;
	}
}