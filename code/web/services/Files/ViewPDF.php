<?php

require_once ROOT_DIR . '/sys/File/FileUpload.php';
require_once ROOT_DIR . '/sys/ILS/RecordFile.php';
class ViewPDF extends Action
{
	function launch()
	{
		//Get the id of the file to display
		$fileId = $_REQUEST['id'];
		$fileUpload = new FileUpload();
		$fileUpload->id = $fileId;
		if ($fileUpload->find(true)){
			//Record the usage
			$recordFile = new RecordFile();
			$recordFile->fileId = $fileId;
			if ($recordFile->find(true)){
				/** @var MarcRecordDriver $recordDriver */
				$recordDriver = RecordDriverFactory::initRecordDriverById($recordFile->type . ':' . $recordFile->identifier);
				if ($recordDriver->isValid() && $recordDriver->getIndexingProfile() != null){
					if (UserAccount::isLoggedIn()){
						require_once ROOT_DIR . '/sys/ILS/UserILSUsage.php';
						$userUsage = new UserILSUsage();
						$userUsage->userId = UserAccount::getActiveUserId();
						$userUsage->indexingProfileId = $recordDriver->getIndexingProfile()->id;
						$userUsage->year = date('Y');
						$userUsage->month = date('n');
						if ($userUsage->find(true)) {
							$userUsage->pdfViewCount++;
							$userUsage->update();
						} else {
							$userUsage->pdfViewCount = 1;
							$userUsage->insert();
						}
					}

					//Track usage of the record
					require_once ROOT_DIR . '/sys/ILS/ILSRecordUsage.php';
					$recordUsage = new ILSRecordUsage();
					$recordUsage->indexingProfileId = $recordDriver->getIndexingProfile()->id;
					$recordUsage->recordId = $recordDriver->getUniqueID();
					$recordUsage->year = date('Y');
					$recordUsage->month = date('n');
					if ($recordUsage->find(true)) {
						$recordUsage->pdfViewCount++;
						$recordUsage->update();
					} else {
						$recordUsage->pdfViewCount = 1;
						$recordUsage->insert();
					}
				}
			}

			global $interface;
			$title = $fileUpload->title;
			$interface->assign('title', $title);
			$fileSize = filesize($fileUpload->fullPath);
			$interface->assign('fileSize', StringUtils::formatBytes($fileSize));
			global $configArray;
			$interface->assign('pdfPath', $configArray['Site']['url'] . '/Files/' . $fileId . '/Contents');
			$this->display('pdfViewer.tpl', $title);
		}else{
			$this->display('invalidRecord.tpl', 'Invalid Record');
		}
	}
}