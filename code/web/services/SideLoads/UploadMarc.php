<?php
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Indexing/SideLoad.php';

/** @noinspection PhpUnused */
class SideLoads_UploadMarc extends Admin_Admin
{
	function launch()
	{
		global $interface;
		$id = $_REQUEST['id'];
		$sideload = new SideLoad();
		$sideload->id = $id;
		if ($sideload->find(true)) {
			$interface->assign('sideload', $sideload);
			if (isset($_REQUEST['marcFile'])){
				$replaceExisting = isset($_REQUEST['replaceExisting']) && $_REQUEST['replaceExisting'] == 'on';
				$uploadedFile = $_FILES['marcFile'];
				if (isset($uploadedFile["error"]) && $uploadedFile["error"] == 4){
					$interface->assign('error', "No MARC file was uploaded");
					//No image supplied, use the existing value
				}else if (isset($uploadedFile["error"]) && $uploadedFile["error"] > 0){
					$interface->assign('error', "Error in file upload for MARC File");
				}else{
					//File was uploaded, need to verify it was the correct typ
					$fileType = $uploadedFile["type"];

					$uploadPath = $sideload->marcPath;
					if ($replaceExisting){
						$files = glob($uploadPath .'/*'); // get all file names
						foreach($files as $file){
							if(is_file($file)) {
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
							$interface->assign('message', "File uploaded and unzipped");
						} else {
							$interface->assign('error', "Could not unzip the file");
						}
					}elseif ($fileType == 'application/x-gzip'){
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
						$interface->assign('message', "The file was uploaded and unzipped successfully");
					}else{
						$copyResult = copy($uploadedFile["tmp_name"], $destFullPath);
						if ($copyResult){
							$interface->assign('message', "The file was uploaded successfully");
						}else{
							$interface->assign('error', "Could not copy the file to $uploadPath");
						}
					}
				}

			}
		}else{
			$interface->assign('error', "Could not find the specified Side Load configuration.");
		}

		$interface->assign('id', $_REQUEST['id']);
		$this->display('uploadMarc.tpl', 'Upload MARC File');
	}

	function getAllowableRoles(){
		return array('opacAdmin', 'cataloging');
	}
}