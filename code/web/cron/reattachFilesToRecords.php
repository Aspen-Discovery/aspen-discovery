<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';

global $configArray;
global $serverName;
$numReattached = 0;

$dataPath = '/data/aspen-discovery/' . $serverName . '/uploads/record_pdfs/';
if (file_exists($dataPath)) {
	$existingFiles = scandir($dataPath);
	foreach ($existingFiles as $existingFile) {
		if ($existingFile != "." && $existingFile != "..") {
			require_once ROOT_DIR . '/sys/File/FileUpload.php';
			require_once ROOT_DIR . '/sys/ILS/RecordFile.php';
			$fileIdentifier = substr($existingFile, 0, strpos($existingFile, '_'));

			require_once ROOT_DIR . '/RecordDrivers/RecordDriverFactory.php';
			$recordDriver = RecordDriverFactory::initRecordDriverById('ils:' . $fileIdentifier);
			if ($recordDriver->isValid()) {
				$fullPath = $dataPath . '/' . $existingFile;
				$fileUpload = new FileUpload();
				$fileUpload->fullPath = $fullPath;
				if (!$fileUpload->find(true)) {
					$fileUpload->title = $recordDriver->getTitle();
					$fileUpload->fullPath = $fullPath;
					$fileUpload->type = 'RecordPDF';
					$fileUpload->insert();
				}

				$recordFile = new RecordFile();
				$recordFile->type = $recordDriver->getRecordType();
				$recordFile->identifier = $recordDriver->getUniqueID();
				$recordFile->fileId = $fileUpload->id;
				if (!$recordFile->find(true)) {
					$recordFile->insert();
					$numReattached++;
				}
			}
		}
	}
}
echo "Reattached $numReattached PDFs to their record";