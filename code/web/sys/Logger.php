<?php

class Logger
{
	private $logMethods = array();

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

        if (!$configArray['Site']['isProduction']){
            $this->logNotices = true;
            $this->logDebugs = true;
            $this->logWarnings = true;
        }
		$this->logFilePath = '/var/log/aspen-discovery/' . $serverName . '/messages.log';
	}

	public function log($msg, $level)
	{
	    if ($level == self::LOG_DEBUG && !$this->logDebugs){
	        return;
        }
        if ($level == self::LOG_NOTICE && !$this->logNotices){
            return;
        }
        if ($level == self::LOG_WARNING && !$this->logWarnings){
            return;
        }
        if ($level == self::LOG_ERROR && !$this->logErrors){
            return;
        }
        if ($level == self::LOG_ALERT && !$this->logAlerts){
            return;
        }

        // Write the message to the log:
        $fhnd = fopen($this->logFilePath, 'a');
        fwrite($fhnd, $msg);
        fclose($fhnd);
	}
}