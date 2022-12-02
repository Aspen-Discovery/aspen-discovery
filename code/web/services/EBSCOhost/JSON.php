<?php

require_once ROOT_DIR . '/JSON_Action.php';

class EBSCOhost_JSON extends JSON_Action {

	/** @noinspection PhpUnused */
	function getTitleAuthor(): array {
		$result = [
			'success' => false,
			'title' => 'Unknown',
			'author' => 'Unknown',
		];
		require_once ROOT_DIR . '/RecordDrivers/EbscohostRecordDriver.php';
		$id = $_REQUEST['id'];
		if (!empty($id)) {
			$recordDriver = new EbscohostRecordDriver($id);
			if ($recordDriver->isValid()) {
				$result['success'] = true;
				$result['title'] = $recordDriver->getTitle();
				$result['author'] = $recordDriver->getAuthor();
			}
		}
		return $result;
	}
}