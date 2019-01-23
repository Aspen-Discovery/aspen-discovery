<?php
/**
 * Allows display of a compound object from Islandora
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 9/8/2015
 * Time: 8:43 PM
 */

require_once ROOT_DIR . '/services/Archive/Object.php';
class Archive_Compound extends Archive_Object{
	function launch() {
		global $interface;
		$this->loadArchiveObjectData();
		//$this->loadExploreMoreContent();

		//Get the contents of the book
		/** @var CompoundDriver $bookDriver */
		$bookDriver = $this->recordDriver;
		$bookContents = $bookDriver->loadBookContents();
		$interface->assign('bookContents', $bookContents);

		$interface->assign('showExploreMore', true);

		//Get the active page pid
		$defaultViewer = 'image';
		if (isset($_REQUEST['pagePid'])){
			$activePid = $_REQUEST['pagePid'];
			$interface->assign('activePage', $activePid);
			// The variable page is used by the javascript url creation to track the kind of object we are in, ie Book, Map, ..
		}else{
			//Get the first page from the contents
			foreach($bookContents as $section){
				if (count($section['pages'])){
					$firstPage = reset($section['pages']);
					$interface->assign('activePage', $firstPage['pid']);
					if ($firstPage['jp2'] != ''){
						$defaultViewer = 'image';
					}elseif ($firstPage['pdf'] != ''){
						$defaultViewer = 'pdf';
					}elseif ($firstPage['audio'] != ''){
						$defaultViewer = 'audio';
					}elseif ($firstPage['video'] != ''){
						$defaultViewer = 'video';
					}
					break;
				}else{
					$interface->assign('activePage', $section['pid']);
					if (isset($section['jp2']) && $section['jp2'] != ''){
						$defaultViewer = 'image';
					}elseif (isset($section['pdf']) && $section['pdf'] != ''){
						$defaultViewer = 'pdf';
					}elseif (isset($section['audio']) && $section['audio'] != ''){
						$defaultViewer = 'audio';
					}elseif (isset($section['video']) && $section['video'] != ''){
						$defaultViewer = 'video';
					}
					break;
				}
			}
		}

		if (isset($_REQUEST['viewer'])){
			$interface->assign('activeViewer', $_REQUEST['viewer']);
		}else{
			$interface->assign('activeViewer', $defaultViewer);
		}

		if ($this->archiveObject->getDatastream('PDF') != null){
			$interface->assign('hasPdf', true);
		}else{
			$interface->assign('hasPdf', false);
		}

		// Display Page
		$this->display('compound.tpl');
	}


}