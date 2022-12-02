<?php
require_once ROOT_DIR . '/JSON_Action.php';

class SideLoads_AJAX extends JSON_Action {
	/** @noinspection PhpUnused */
	public function deleteMarc() {
		if (UserAccount::userHasPermission('Administer Side Loads')) {
			$id = $_REQUEST['id'];
			$sideLoadConfiguration = new SideLoad();
			$sideLoadConfiguration->id = $id;
			if ($sideLoadConfiguration->find(true) && !empty($sideLoadConfiguration->marcPath)) {
				$marcPath = $sideLoadConfiguration->marcPath;
				$file = $_REQUEST['file'];
				$fullName = $marcPath . DIR_SEP . $file;
				if (file_exists($fullName)) {
					if (unlink($fullName)) {
						$sideLoadConfiguration->runFullUpdate = true;
						$sideLoadConfiguration->update();
						return [
							'success' => true,
							'message' => 'The file was deleted.',
						];
					} else {
						return [
							'success' => false,
							'message' => 'The file could not be deleted.',
						];
					}
				} else {
					return [
						'success' => false,
						'message' => 'Could not find the file to download.',
					];
				}
			} else {
				return [
					'success' => false,
					'message' => 'Could not find the Side Load for this file.',
				];
			}
		} else {
			return [
				'success' => false,
				'message' => 'You do not have permissions to perform this action.',
			];
		}
	}
}