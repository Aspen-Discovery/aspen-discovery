<?php


class Record_DownloadSupplementalFile {
	/** @var MarcRecordDriver $recordDriver */
	private $recordDriver;
	private $title;

	function launch() {
		global $interface;

		$id = strip_tags($_REQUEST['id']);
		$interface->assign('id', $id);

		$this->recordDriver = RecordDriverFactory::initRecordDriverById($id);

		$fileId = $_REQUEST['fileId'];
		if (!$this->recordDriver->isValid()) {
			AspenError::raiseError(new AspenError("Invalid record ({$id}) while downloading file"));
		} else {
			require_once ROOT_DIR . '/sys/ILS/RecordFile.php';
			require_once ROOT_DIR . '/sys/File/FileUpload.php';
			$recordFile = new RecordFile();
			$recordFile->type = $this->recordDriver->getRecordType();
			$recordFile->identifier = $this->recordDriver->getUniqueID();
			$recordFile->fileId = $fileId;
			if ($recordFile->find(true)) {
				$fileUpload = new FileUpload();
				$fileUpload->id = $fileId;
				if ($fileUpload->find(true)) {
					$this->title = $fileUpload->title;
					if (isset($fileUpload->uploadedFileData)) {
						if ($this->recordDriver->getIndexingProfile() != null) {
							//Record the usage of the PDF
							if (UserAccount::isLoggedIn()) {
								require_once ROOT_DIR . '/sys/ILS/UserILSUsage.php';
								$userUsage = new UserILSUsage();
								global $aspenUsage;
								$userUsage->instance = $aspenUsage->getInstance();
								$userUsage->userId = UserAccount::getActiveUserId();
								$userUsage->indexingProfileId = $this->recordDriver->getIndexingProfile()->id;
								$userUsage->year = date('Y');
								$userUsage->month = date('n');
								if ($userUsage->find(true)) {
									$userUsage->supplementalFileDownloadCount++;
									$userUsage->update();
								} else {
									$userUsage->supplementalFileDownloadCount = 1;
									$userUsage->insert();
								}
							}

							//Track usage of the record
							require_once ROOT_DIR . '/sys/ILS/ILSRecordUsage.php';
							$recordUsage = new ILSRecordUsage();
							global $aspenUsage;
							$recordUsage->instance = $aspenUsage->getInstance();
							$recordUsage->indexingProfileId = $this->recordDriver->getIndexingProfile()->id;
							$recordUsage->recordId = $this->recordDriver->getUniqueID();
							$recordUsage->year = date('Y');
							$recordUsage->month = date('n');
							if ($recordUsage->find(true)) {
								$recordUsage->supplementalFileDownloadCount++;
								$recordUsage->update();
							} else {
								$recordUsage->supplementalFileDownloadCount = 1;
								$recordUsage->insert();
							}
						}

						set_time_limit(300);
						$size = strlen($fileUpload->uploadedFileData);
						header('Content-Type: application/octet-stream');
						header('Content-Transfer-Encoding: binary');
						header('Content-Length: ' . $size);
						$fileName = str_replace($this->recordDriver->getUniqueID() . '_', '', $fileUpload->getFileName());
						header('Content-Disposition: attachment;filename="' . $fileName . ".pdf");
						die();

					} else {
						AspenError::raiseError(new AspenError("File ($fileId) does not exist for record ({$id})"));
					}
				} else {
					AspenError::raiseError(new AspenError("File ($fileId) not found for record ({$id})"));
				}
			} else {
				AspenError::raiseError(new AspenError("Invalid file($fileId) specified for record ({$id})"));
			}
		}
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		if (!empty($this->recordDriver)) {
			$breadcrumbs[] = new Breadcrumb($this->recordDriver->getRecordUrl(), $this->recordDriver->getTitle(), false);
		}
		$breadcrumbs[] = new Breadcrumb('', $this->title, false);
		return $breadcrumbs;
	}
}