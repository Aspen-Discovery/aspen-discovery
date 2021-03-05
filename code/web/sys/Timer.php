<?php
class Timer{
	private $lastTime = 0;
	private $firstTime = 0;
	private $timingMessages;
	private $timingsEnabled = false;
	private $minTimeToLog = 0;
	private $timingsWritten = false;

	public function __construct($startTime = null){
		global $configArray;
		if ($configArray){
			if (isset($configArray['System']['minTimeToLog'])){
				$this->minTimeToLog = $configArray['System']['minTimeToLog'];
			}
		}

		if (!$startTime) $startTime = microtime(true);
		$this->lastTime = $startTime;
		$this->firstTime = $startTime;
		$this->timingMessages = array();
	}

	public function getElapsedTime(){
		return microtime(true) - $this->firstTime;
	}

	public function logTime($message){
		if ($this->timingsEnabled){
			$curTime = microtime(true);
			$elapsedTime = round($curTime - $this->lastTime, 4);
			if ($elapsedTime > $this->minTimeToLog){
				$totalElapsedTime = round($curTime - $this->firstTime, 4);
				$this->timingMessages[] = "\"$message\",\"$elapsedTime\",\"$totalElapsedTime\"";
			}
			$this->lastTime = $curTime;
		}
	}

	public function enableTimings($enable){
		$this->timingsEnabled = $enable;
	}

	public function writeTimings(){
		if ($this->timingsEnabled && !$this->timingsWritten){
			$this->timingsWritten = true;
			$minTimeToLog = 0;

			$curTime = microtime(true);
			$elapsedTime = round($curTime - $this->lastTime, 4);
			if ($elapsedTime > $minTimeToLog){
				$this->timingMessages[] = "Finished run: $curTime ($elapsedTime sec)";
			}
			$this->lastTime = $curTime;
			global $logger;
			$totalElapsedTime =round(microtime(true) - $this->firstTime, 4);
			if (isset($_SERVER['REQUEST_URI'])) {
				$timingInfo = "\r\nTiming for: " . $_SERVER['REQUEST_URI'] . "\r\n";
			}else{
				$timingInfo = "\r\nTiming info\r\n";
			}
			$timingInfo .= implode("\r\n", $this->timingMessages);
			$timingInfo .= "\r\nTotal Elapsed time was: $totalElapsedTime seconds.\r\n";
			$logger->log($timingInfo, Logger::LOG_ALERT);
		}
	}

	function __destruct() {
		if ($this->timingsEnabled){
			$this->writeTimings();
		}
	}
}