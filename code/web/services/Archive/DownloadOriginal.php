<?php

/**
 * Allows downloading the original object after checking permissions
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 5/16/2016
 * Time: 10:47 AM
 */
require_once ROOT_DIR . '/services/Archive/Object.php';
class Archive_DownloadOriginal extends Archive_Object{
	function launch(){
		global $interface;
		global $logger;
		$this->loadArchiveObjectData();
		$anonymousMasterDownload = $interface->getVariable('anonymousMasterDownload');
		$verifiedMasterDownload = $interface->getVariable('verifiedMasterDownload');

		if ($anonymousMasterDownload || (UserAccount::isLoggedIn() && $verifiedMasterDownload)){
			$expires = 60*60*24*14;  //expire the cover in 2 weeks on the client side
			header("Cache-Control: maxage=".$expires);
			header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');
			$pid = $this->pid;
			$pid = str_replace(':', '_', $pid);
			$masterDataStream = $this->archiveObject->getDatastream('OBJ');
			header('Content-Disposition: attachment; filename=' . $pid . '_original' . $this->recordDriver->getExtension($masterDataStream->mimetype));
			header('Content-type: ' . $masterDataStream->mimetype);
			header('Content-Length: ' . $masterDataStream->size);
			$tempFile = tempnam(sys_get_temp_dir(), 'ido');
			$masterDataStream->getContent($tempFile);
			$bytesWritten = $this->readfile_chunked($tempFile);
			unlink($tempFile);
			exit();
		}else{
			PEAR_Singleton::raiseError('Sorry, You do not have permission to download this image.');
		}
	}

	// Read a file and display its content chunk by chunk
	function readfile_chunked($tempFile, $retbytes = TRUE) {
		$handle = fopen($tempFile, 'rb');
		$buffer = '';
		$cnt    = 0;

		if ($handle === false) {
			return false;
		}

		while (!feof($handle)) {
			$buffer = fread($handle, 1048576);
			echo $buffer;
			ob_flush();
			flush();

			if ($retbytes) {
				$cnt += strlen($buffer);
			}
		}

		$status = fclose($handle);

		if ($retbytes && $status) {
			return $cnt; // return num. bytes delivered like readfile() does.
		}

		return $status;
	}

}