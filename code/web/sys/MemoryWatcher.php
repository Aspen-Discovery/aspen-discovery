<?php

class MemoryWatcher {
	private $lastMemory = 0;
	private $firstMemory = 0;
	private $memoryMessages;
	private $memoryLoggingEnabled = false;

	public function __construct() {
		global $configArray;
		if ($configArray) {
			if (isset($configArray['System']['logMemoryUsage'])) {
				$this->memoryLoggingEnabled = $configArray['System']['logMemoryUsage'];
			}
		} else {
			$this->memoryLoggingEnabled = true;
		}

		$startMemory = memory_get_usage();
		$this->lastMemory = $startMemory;
		$this->firstMemory = $startMemory;
		$this->memoryMessages = [];
	}

	public function logMemory($message) {
		if ($this->memoryLoggingEnabled) {
			$curTime = memory_get_usage();
			$elapsedTime = number_format($curTime - $this->lastMemory);
			$totalElapsedTime = number_format($curTime - $this->firstMemory);
			$this->memoryMessages[] = "\"$message\",\"$elapsedTime\",\"$totalElapsedTime\"";
			$this->lastMemory = $curTime;
		}
	}

	public function enableMemoryLogging($enable) {
		$this->memoryLoggingEnabled = $enable;
	}

	public function writeMemory() {
		if ($this->memoryLoggingEnabled) {
			$curMemoryUsage = memory_get_usage();
			$memoryChange = $curMemoryUsage - $this->lastMemory;
			$this->memoryMessages[] = "Finished run: $curMemoryUsage ($memoryChange bytes)";
			$this->lastMemory = $curMemoryUsage;
			global $logger;
			$totalMemoryUsage = number_format($curMemoryUsage - $this->firstMemory);
			$timingInfo = "\r\nMemory usage for: " . $_SERVER['REQUEST_URI'] . "\r\n";
			$timingInfo .= implode("\r\n", $this->memoryMessages);
			$timingInfo .= "\r\nFinal Memory usage was: $totalMemoryUsage bytes.";
			$peakUsage = number_format(memory_get_peak_usage());
			$timingInfo .= "\r\nPeak Memory usage was: $peakUsage bytes.\r\n";
			$logger->log($timingInfo, Logger::LOG_NOTICE);
		}
	}

	function __destruct() {
		if ($this->memoryLoggingEnabled) {
			global $logger;
			if ($logger) {
				$curMemoryUsage = memory_get_usage();
				$totalMemoryUsage = number_format($curMemoryUsage - $this->firstMemory);
				if (isset($_SERVER['REQUEST_URI'])) {
					$timingInfo = "\r\nMemory usage for: " . $_SERVER['REQUEST_URI'] . "\r\n";
				} else {

				}
				if (count($this->memoryMessages) > 0) {
					$timingInfo .= implode("\r\n", $this->memoryMessages);
				}
				$timingInfo .= "\r\nFinal Memory usage in destructor was: $totalMemoryUsage bytes.\r\n";
				$logger->log($timingInfo, Logger::LOG_NOTICE);
			}
		}
	}
}