<?php
require_once ROOT_DIR . '/services/Admin/Admin.php';

class IndexerInformation extends Admin_Admin{
	function launch() {
		global $interface;

		$runningProcesses = $this->loadRunningProcesses();
		$killResults = '';
		if (!empty($_REQUEST['selectedProcesses'])) {
			$processesToStop = $_REQUEST['selectedProcesses'];
			global $configArray;
			if ($configArray['System']['operatingSystem'] != 'windows') {
				foreach ($processesToStop as $processId => $value){
					if (in_array($processId, $runningProcesses)) {
						exec("kill $processId", $stopResults);
						$killResults .= $stopResults;
					}else{
						exec("kill $processId", $stopResults);
					}
				}
			}else{
				$killResults = 'Unable to stop processes on Windows';
			}
			$interface->assign('killResults', $killResults);
			$runningProcesses = $this->loadRunningProcesses();

		}

		$interface->assign('runningProcesses', $runningProcesses);

		$this->display('indexerInformation.tpl', 'Indexer Information', false);
	}

	function loadRunningProcesses() {
		global $configArray;
		global $serverName;
		$runningProcesses = [];
		if ($configArray['System']['operatingSystem'] == 'windows') {
			/** @noinspection SpellCheckingInspection */
			exec("WMIC PROCESS get Processid,Commandline", $processes);
			$processRegEx = '/.*?java(?:.exe\")?\s+-jar\s(.*?)\.jar.*?\s+(\d+)/ix';
			$processIdIndex = 2;
			$processNameIndex = 1;
			$processStartIndex = -1;
			$solrRegex = "/$serverName\\\\solr7/ix";
		} else {
			exec("ps -ef | grep java", $processes);
			$processRegEx = '/(\d+)\s+.*?([a-zA-Z0-9:]{5}).*?(\d{2}:\d{2}:\d{2})\sjava\s-jar\s(.*?)\.jar\s' . $serverName . '/ix';
			$processIdIndex = 1;
			$processNameIndex = 4;
			$processStartIndex = 2;
			$solrRegex = "/(\d+)\s+.*?([a-zA-Z0-9:]{5}).*?(\d{2}:\d{2}:\d{2})\s.*?$serverName\/solr7/ix";
		}

		$results = "";

		$solrRunning = false;
		foreach ($processes as $processInfo) {
			if (preg_match($processRegEx, $processInfo, $matches)) {
				$processId = $matches[$processIdIndex];
				if ($processStartIndex > 0) {
					$startDayTime = $matches[$processStartIndex];
				}else{
					$startDayTime = translate(['text'=>'Not Available', 'isPublicFacing'=>true]);
				}

				$process = $matches[$processNameIndex];
				if (array_key_exists($process, $runningProcesses)) {
					$results .= "There is more than one process for $process PID: {$runningProcesses[$process]['pid']} and $processId\r\n";
				} else {
					$runningProcesses[$processId] = [
						'name' => $process,
						'pid' => $processId,
						'startTime' => $startDayTime,
					];
				}

				//echo("Process: $process ($processId)\r\n");
			} elseif (preg_match($solrRegex, $processInfo)) {
				$solrRunning = true;
			}
		}

		return $runningProcesses;
	}

	function getActiveAdminSection(): string {
		return 'greenhouse';
	}

	function canView(): bool {
		if (UserAccount::isLoggedIn()) {
			if (UserAccount::getActiveUserObj()->isAspenAdminUser()) {
				return true;
			}
		}
		return false;
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('', 'Indexer Information');

		return $breadcrumbs;
	}
}