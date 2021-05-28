<?php

require_once ROOT_DIR . '/sys/File/FileUpload.php';
require_once ROOT_DIR . '/sys/ILS/RecordFile.php';
class ViewPDF extends Action
{
	/** @var MarcRecordDriver $recordDriver */
	private $recordDriver;
	private $title;
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
				$this->recordDriver = RecordDriverFactory::initRecordDriverById($recordFile->type . ':' . $recordFile->identifier);
				if ($this->recordDriver->isValid() && $this->recordDriver->getIndexingProfile() != null){
					if (UserAccount::isLoggedIn()){
						require_once ROOT_DIR . '/sys/ILS/UserILSUsage.php';
						$userUsage = new UserILSUsage();
						$userUsage->userId = UserAccount::getActiveUserId();
						$userUsage->indexingProfileId = $this->recordDriver->getIndexingProfile()->id;
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
					$recordUsage->indexingProfileId = $this->recordDriver->getIndexingProfile()->id;
					$recordUsage->recordId = $this->recordDriver->getUniqueID();
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
			$this->title = $fileUpload->title;
			$interface->assign('title', $this->title);
			$fileSize = filesize($fileUpload->fullPath);
			$interface->assign('fileSize', StringUtils::formatBytes($fileSize));
			global $configArray;
			$interface->assign('pdfPath', $configArray['Site']['url'] . '/Files/' . $fileId . '/Contents');
			$this->display('pdfViewer.tpl', $this->title, '');
		}else{
			$this->display('invalidRecord.tpl', 'Invalid File', '');
		}
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		if ($this->recordDriver != null) {
			$breadcrumbs[] = new Breadcrumb($this->recordDriver->getRecordUrl(), $this->recordDriver->getTitle(), false);
		}
		$breadcrumbs[] = new Breadcrumb('', $this->title, false);
		return $breadcrumbs;
	}
}