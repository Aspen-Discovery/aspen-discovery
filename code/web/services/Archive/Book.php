<?php
/**
 * Allows display of a single image from Islandora
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 9/8/2015
 * Time: 8:43 PM
 */

require_once ROOT_DIR . '/services/Archive/Object.php';
class Archive_Book extends Archive_Object{
	function launch() {
		global $interface;
		$this->loadArchiveObjectData();
		//$this->loadExploreMoreContent();

		//Get the contents of the book
		/** @var BookDriver $bookDriver */
		$bookDriver = $this->recordDriver;
		$bookContents = $bookDriver->loadBookContents();
		$interface->assign('bookContents', $bookContents);

		$interface->assign('showExploreMore', true);

		//Get the active page pid
		if (isset($_REQUEST['pagePid'])){
			$interface->assign('activePage', $_REQUEST['pagePid']);
			// The variable page is used by the javascript url creation to track the kind of object we are in, ie Book, Map, ..
		}else{
			//Get the first page from the contents
			foreach($bookContents as $section){
				if (count($section['pages'])){
					$firstPage = reset($section['pages']);
					$interface->assign('activePage', $firstPage['pid']);
					break;
				}else{
					$interface->assign('activePage', $section['pid']);
					break;
				}
			}
		}

		if (isset($_REQUEST['viewer'])){
			$interface->assign('activeViewer', $_REQUEST['viewer']);
		}else{
			$interface->assign('activeViewer', 'image');
		}

		if ($this->archiveObject->getDatastream('PDF') != null){
			$interface->assign('hasPdf', true);
		}else{
			$interface->assign('hasPdf', false);
		}

		// Display Page
		$this->display('book.tpl');
	}


}