<?php
$config = parse_ini_file('./fetchMarc.ini', true);

$sshConnection = ssh2_connect($config['SFTP_Server']['host'], $config['SFTP_Server']['port'], array('hostkey'=>'ssh-rsa'));

if (!ssh2_auth_pubkey_file($sshConnection, 'aspen_file_xfer', $config['SFTP_Server']['publicKey'], $config['SFTP_Server']['privateKey'], '')) {
	die(date('Y-m-d H:i:s') . "Public Key Authentication Failed \n");
}

//Make sftp connection
$sftpConnection = @ssh2_sftp($sshConnection);

if (!$sftpConnection){
	ssh2_disconnect($sshConnection);
	die(date('Y-m-d H:i:s') . "Could not establish SFTP Connection\n");
}

//Copy Files
copyFiles($sshConnection, $sftpConnection, $config['SFTP_Server']['remoteDir'] . '/marc', $config['SFTP_Server']['localDir'] . '/marc');
copyFiles($sshConnection, $sftpConnection, $config['SFTP_Server']['remoteDir'] . '/marc_delta', $config['SFTP_Server']['localDir'] . '/marc_delta');

ssh2_disconnect($sshConnection);

//End!

function copyFiles($sshConnection, $sftpConnection, $remotePath, $localPath){
	$remote_path = 'ssh2.sftp://' . intval($sftpConnection) . '/' . $remotePath;
	$fullExports = listFilesInDir($remote_path);
	foreach ($fullExports as $exportName){
		//Check to see if the file is still changing
		$fileInfo = stat($remote_path . "/$exportName");
		$modificationTime = $fileInfo['mtime'];
		$size = $fileInfo['size'];
		sleep(1);
		//Check the size and time again
		$fileInfo = stat($remote_path . "/$exportName");
		$modificationTime2 = $fileInfo['mtime'];
		$size2 = $fileInfo['size'];
		if ($size == $size2 && $modificationTime == $modificationTime2){
			//save the file locally
			if (ssh2_scp_recv($sshConnection, $remotePath . '/' . $exportName, '/tmp/' . $exportName)){
				//delete the original file
				if (rename('/tmp/' . $exportName, $localPath . '/' . $exportName)){
					ssh2_sftp_unlink($sftpConnection, $remotePath . '/' . $exportName);
					echo(date('Y-m-d H:i:s') . "Copied file /tmp/$exportName to " . $localPath . '/' . $exportName . "\n");
				}else{
					echo(date('Y-m-d H:i:s') . "ERROR could not move /tmp/$exportName to " . $localPath . '/' . $exportName . "\n");
				}

			}else{
				ssh2_disconnect($sshConnection);
				die(date('Y-m-d H:i:s') . "Could not write file /tmp/" . $exportName . "\n");
			}
		}else{
			echo(date('Y-m-d H:i:s') . $remote_path . "/$exportName is still changing\n");
		}
	}
}

function listFilesInDir($path){
	$contents = array();
	$handle = opendir($path);
	while (($file = readdir($handle)) !== false) {
		//Skip . and ..
		if (substr("$file", 0, 1) != "."){
			//Don't recurse into subdirectories
			if (!is_dir("$path/$file")){
				$contents[] = $file;
			}
		}
	}
	closedir($handle);

	return $contents;
}