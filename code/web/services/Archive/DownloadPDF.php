<?php

require_once ROOT_DIR . '/services/Archive/Object.php';
class DownloadPDF extends Archive_Object{
	function launch(){
		$this->loadArchiveObjectData();
		$pdfStream = $this->archiveObject->getDatastream('PDF');
		if ($pdfStream == null){
			$pdfStream = $this->archiveObject->getDatastream('OBJ');
		}

		if (!$pdfStream){
			PEAR_Singleton::raiseError('Sorry, We could not create a PDF for that selection.');
		}else{
			$expires = 60*60*24*14;  //expire the cover in 2 weeks on the client side
			header("Cache-Control: maxage=".$expires);
			header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');
			$pid = $this->pid;
			$pid = str_replace(':', '_', $pid);
			header('Content-Disposition: attachment; filename=' . $pid . '_original' . $this->recordDriver->getExtension($pdfStream->mimetype));
			header('Content-type: ' . $pdfStream->mimetype);
			$masterDataStream = $pdfStream;
			echo($masterDataStream->content);
			exit();
		}

	}
}