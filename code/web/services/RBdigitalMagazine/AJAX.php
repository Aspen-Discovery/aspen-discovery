<?php
require_once ROOT_DIR . '/Action.php';

/** @noinspection PhpUnused */
class RBdigitalMagazine_AJAX extends Action
{
	function launch()
	{
		$method = $_GET['method'];
		if (method_exists($this, $method)) {
			header('Content-type: application/json');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			echo $this->$method();
		} else {
			echo json_encode(array('error' => 'invalid_method'));
		}
	}

	/** @noinspection PhpUnused */
	function reloadCover()
	{
		require_once ROOT_DIR . '/RecordDrivers/RBdigitalMagazineDriver.php';
		$id = $_REQUEST['id'];
		$recordDriver = new RBdigitalMagazineDriver($id);

		//Reload small cover
		$smallCoverUrl = str_replace('&amp;', '&', $recordDriver->getBookcoverUrl('small', true)) . '&reload';
		file_get_contents($smallCoverUrl);

		//Reload medium cover
		$mediumCoverUrl = str_replace('&amp;', '&', $recordDriver->getBookcoverUrl('medium', true)) . '&reload';
		file_get_contents($mediumCoverUrl);

		//Reload large cover
		$largeCoverUrl = str_replace('&amp;', '&', $recordDriver->getBookcoverUrl('large', true)) . '&reload';
		file_get_contents($largeCoverUrl);

		//Also reload covers for the grouped work
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$groupedWorkDriver = new GroupedWorkDriver($recordDriver->getGroupedWorkId());

		//Reload small cover
		$smallCoverUrl = str_replace('&amp;', '&', $groupedWorkDriver->getBookcoverUrl('small', true)) . '&reload';
		file_get_contents($smallCoverUrl);

		//Reload medium cover
		$mediumCoverUrl = str_replace('&amp;', '&', $groupedWorkDriver->getBookcoverUrl('medium', true)) . '&reload';
		file_get_contents($mediumCoverUrl);

		//Reload large cover
		$largeCoverUrl = str_replace('&amp;', '&', $groupedWorkDriver->getBookcoverUrl('large', true)) . '&reload';
		file_get_contents($largeCoverUrl);

		return json_encode(array('success' => true, 'message' => 'Covers have been reloaded.  You may need to refresh the page to clear your local cache.'));
	}
}