<?php

class Logger
{
	private $logAlerts = false;
	private $logErrors = false;
	private $logWarnings = false;
	private $logNotices = false;
	private $logDebugs = false;

	private $logFilePath;

	public const LOG_DEBUG = 5;
	public const LOG_NOTICE = 4;
	public const LOG_WARNING = 3;
	public const LOG_ERROR = 2;
	public const LOG_ALERT = 1;

	public function __construct()
	{
		global $configArray;
		global $serverName;

		$this->logAlerts = true;
		$this->logErrors = true;

		$this->logFilePath = '/var/log/' . $configArray['System']['applicationName'] . '/' . $serverName . '/messages.log';
	}

	public function log($msg, $level)
	{
		try {
			if ($level == self::LOG_DEBUG && !IPAddress::showDebuggingInformation()) {
				return;
			}
			if ($level == self::LOG_NOTICE && !IPAddress::showDebuggingInformation()) {
				return;
			}
			if ($level == self::LOG_WARNING && !IPAddress::showDebuggingInformation()) {
				return;
			}
		}catch (PDOException $e){
			//Logging is too early, ignore at least for now.
		}
		if ($level == self::LOG_ERROR && !$this->logErrors) {
			return;
		}
		if ($level == self::LOG_ALERT && !$this->logAlerts) {
			return;
		}

		// Write the message to the log:
		$fhnd = @fopen($this->logFilePath, 'a');
		if ($fhnd) {
			fwrite($fhnd, '[' . date('Y M d H:i:s') . '] ' . $msg . "\r\n");
			fclose($fhnd);
		}
	}
}