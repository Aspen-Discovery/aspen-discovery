<?php
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Indexing/SideLoad.php';

class SideLoads_UploadMarc extends Admin_Admin {
	function launch() {
		global $interface;

		//Figure out the maximum upload size
		require_once ROOT_DIR . '/sys/Utils/SystemUtils.php';
		$interface->assign('max_file_size', SystemUtils::file_upload_max_size() / (1024 * 1024));

		$id = $_REQUEST['id'];
		$sideload = new SideLoad();
		$sideload->id = $id;
		if ($sideload->find(true)) {
			$interface->assign('sideload', $sideload);
			if (isset($_FILES['marcFile'])) {
				$replaceExisting = isset($_REQUEST['replaceExisting']) && $_REQUEST['replaceExisting'] == 'on';
				$uploadedFile = $_FILES['marcFile'];
				if (isset($uploadedFile["error"]) && $uploadedFile["error"] == 4) {
					$interface->assign('error', "No MARC file was uploaded");
				} elseif (isset($uploadedFile["error"]) && ($uploadedFile["error"] == UPLOAD_ERR_FORM_SIZE || $uploadedFile["error"] == UPLOAD_ERR_INI_SIZE)) {
					$interface->assign('error', "The MARC File was too large, compress the file or break it into multiple files");
				} elseif (isset($uploadedFile["error"]) && $uploadedFile["error"] > 0) {
					$interface->assign('error', "Error in file upload for MARC File");
				} else {
					//File was uploaded, need to verify it was the correct type
					$fileType = $uploadedFile["type"];
					$uploadPath = $sideload->marcPath;
					if ($replaceExisting) {
						$files = glob($uploadPath . '/*'); // get all file names
						foreach ($files as $file) {
							if (is_file($file)) {
								unlink($file);
							}
						}
					}
					$destFileName = $uploadedFile["name"];
					$destFullPath = $uploadPath . '/' . $destFileName;
					if ($fileType == 'application/x-zip-compressed') {
						$zip = new ZipArchive;
						$res = $zip->open($uploadedFile["tmp_name"]);
						if ($res === TRUE) {
							// extract it to the path we determined above
							$zip->extractTo($uploadPath);
							$zip->close();
							$filesInSideLoadDir = scandir($uploadPath);
							foreach ($filesInSideLoadDir as $file) {
								$fullFileName = $uploadPath . '/' . $file;
								if (is_file($fullFileName)) {
									chgrp($fullFileName, 'aspen_apache');
									chmod($fullFileName, 0664);
								}
							}
							$interface->assign('message', "File uploaded and unzipped");
						} else {
							$interface->assign('error', "Could not unzip the file");
						}
					} elseif ($fileType == 'application/x-gzip') {
						// Raising this value may increase performance
						$buffer_size = 4096; // read 4kb at a time
						$out_file_name = str_replace('.gz', '', $destFullPath);
						// Open our files (in binary mode)
						$file = gzopen($uploadedFile["tmp_name"], 'rb');
						$out_file = fopen($out_file_name, 'wb');
						while (!gzeof($file)) {
							// Read buffer-size bytes
							// Both fwrite and gzread and binary-safe
							fwrite($out_file, gzread($file, $buffer_size));
						}
						fclose($out_file);
						gzclose($file);
						$filesInSideLoadDir = scandir($uploadPath);
						foreach ($filesInSideLoadDir as $file) {
							$fullFileName = $uploadPath . '/' . $file;
							if (is_file($fullFileName)) {
								chgrp($fullFileName, 'aspen_apache');
								chmod($fullFileName, 0664);
							}
						}
						$interface->assign('message', "The file was uploaded and unzipped successfully");
					} else {
						$copyResult = copy($uploadedFile["tmp_name"], $destFullPath);
						if ($copyResult) {
							chgrp($destFullPath, 'aspen_apache');
							chmod($destFullPath, 0774);
							$interface->assign('message', "The file was uploaded successfully");
						} else {
							$interface->assign('error', "Could not copy the file to $uploadPath");
						}
					}
				}
			}
		} else {
			$interface->assign('error', "Could not find the specified Side Load configuration.");
		}

		$interface->assign('id', $_REQUEST['id']);
		$this->display('uploadMarc.tpl', 'Upload MARC File');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#side_loads', 'Side Loads');
		$breadcrumbs[] = new Breadcrumb('/SideLoads/SideLoads', 'Side Load Settings');
		if (!empty($this->activeObject) && $this->activeObject instanceof SideLoad) {
			$breadcrumbs[] = new Breadcrumb('/SideLoads/SideLoads?objectAction=edit&id=' . $this->activeObject->id, $this->activeObject->name);
		}
		$breadcrumbs[] = new Breadcrumb('', 'Upload MARC Record');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'side_loads';
	}

	function canView(): bool {
		return UserAccount::userHasPermission(['Administer All Side Loads', 'Administer Side Loads for Home Library']);
	}
}