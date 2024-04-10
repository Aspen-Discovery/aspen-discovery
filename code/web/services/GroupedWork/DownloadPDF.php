<?php


class GroupedWork_DownloadPDF {
	/** @var MarcRecordDriver $recordDriver */
	private $recordDriver;
	private $title;

	function launch() {
		$id = strip_tags($_REQUEST['id']);
		error_log("LGM INTENTO DESCARGAR");
		$fileId = $_REQUEST['fileId'];

		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
		require_once ROOT_DIR . '/sys/Grouping/GroupedWorkPrimaryIdentifier.php';
		$groupedWork = new GroupedWork();
		$groupedWork->permanent_id = $id;
		if (!$groupedWork->find(true)) {
			AspenError::raiseError(new AspenError("Invalid record ({$id}) while downloading file"));
		} else {
			require_once ROOT_DIR . '/sys/ILS/RecordFile.php';
			require_once ROOT_DIR . '/sys/File/FileUpload.php';
			$recordFile = new RecordFile();
			$recordFile->fileId = $fileId;
			if ($recordFile->find(true)) {
				$fileUpload = new FileUpload();
				$fileUpload->id = $fileId;
				if ($fileUpload->find(true)) {
					error_log("LGM ID : " . print_r($fileUpload->id,true));
					if (isset($fileUpload->uploadedFileData)) {
						error_log("LGM TENGO DATA");
						$this->recordDriver = RecordDriverFactory::initRecordDriverById($recordFile->type . ':' . $recordFile->identifier);
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
									$userUsage->pdfDownloadCount++;
									$userUsage->update();
								} else {
									$userUsage->pdfDownloadCount = 1;
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
								$recordUsage->pdfDownloadCount++;
								$recordUsage->update();
							} else {
								$recordUsage->pdfDownloadCount = 1;
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