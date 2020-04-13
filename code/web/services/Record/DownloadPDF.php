<?php


class Record_DownloadPDF
{
	function launch()
	{
		global $interface;

		$id = strip_tags($_REQUEST['id']);
		$interface->assign('id', $id);
		require_once ROOT_DIR . '/RecordDrivers/RBdigitalRecordDriver.php';
		$recordDriver = RecordDriverFactory::initRecordDriverById($id);

		$fileId = $_REQUEST['fileId'];
		if (!$recordDriver->isValid()){
			AspenError::raiseError(new AspenError("Invalid record ({$id}) while downloading file"));
		}else{
			require_once ROOT_DIR . '/sys/ILS/RecordFile.php';
			require_once ROOT_DIR . '/sys/File/FileUpload.php';
			$recordFile = new RecordFile();
			$recordFile->type = $recordDriver->getRecordType();
			$recordFile->identifier = $recordDriver->getUniqueID();
			$recordFile->fileId = $fileId;
			if ($recordFile->find(true)){
				$fileUpload = new FileUpload();
				$fileUpload->id = $fileId;
				if ($fileUpload->find(true)){
					if (file_exists($fileUpload->fullPath)) {
						set_time_limit(300);
						$chunkSize = 2 * (1024 * 1024);

						$size = intval(sprintf("%u", filesize($fileUpload->fullPath)));

						header('Content-Type: application/octet-stream');
						header('Content-Transfer-Encoding: binary');
						header('Content-Length: ' . $size);
						$fileName = str_replace($recordDriver->getUniqueID() . '_', '', basename($fileUpload->fullPath));
						header('Content-Disposition: attachment;filename="' . $fileName . '"');

						if ($size > $chunkSize) {
							$handle = fopen($fileUpload->fullPath, 'rb');

							while (!feof($handle)) {
								set_time_limit(300);
								print(@fread($handle, $chunkSize));

								ob_flush();
								flush();
							}

							fclose($handle);
						} else {
							readfile($fileUpload->fullPath);
						}

						die();
					} else {
						AspenError::raiseError(new AspenError("File ($fileId) does not exist for record ({$id})"));
					}
				}else{
					AspenError::raiseError(new AspenError("File ($fileId) not found for record ({$id})"));
				}
			}else{
				AspenError::raiseError(new AspenError("Invalid file($fileId) specified for record ({$id})"));
			}
		}

	}
}