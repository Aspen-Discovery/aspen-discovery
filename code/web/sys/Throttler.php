<?php

//don't suggest setting interval > 200 but do what you want

class Throttler {

	private int $requestInterval = -1;
	/** @var array<int> */
	private array $lastRequest = [];
	//constants for shifting between microseconds and milliseconds for utime
	// and between seconds and milliseconds for microtime
	private const int MICRO_PER_MILLI = 1000;
	private const int MILLI_PER_SEC = 1000;

	public function __construct(int $requestInterval = -1)
	{
		$this->configureRequestInterval($requestInterval);
	}

	public function getInterval() : int {
		return $this->requestInterval;
	}

	protected function configureRequestInterval(int $requestInterval = -1) : void
	{
		//any negative value doesn't make sense
		//just turning off requests at this point
		if($requestInterval < 0)
		{
			$requestInterval = -1;
		}
		//if we were passed an interval just use that
		if($requestInterval != -1)
		{
			$this->requestInterval = $requestInterval;
			return;
		}

		//if we weren't passed an interval check the config for one'
		//config values of 0 will be ignored but skipping checking
		//will be identical to a 0ms interval anyway
		global $configArray;
		if(empty($configArray['CurlWrapper']) 
			|| empty($configArray['CurlWrapper']['requestInterval']))
		{
			//no log here because if they haven't configured this
			//we don't want to bother them about it.
			return;
		}
		$rawInterval = $configArray['CurlWrapper']['requestInterval'];
		if(!is_numeric($rawInterval))
		{
			global $logger;
			$logger->log("rate limiting skipped because of poorly configured requestInterval: " . $rawInterval, Logger::LOG_WARNING);
			return;//skip limiting if not configured properly
		}
		if($rawInterval < 0)
		{
			global $logger;
			$logger->log("Negative request interval set: ".$rawInterval. " ignoring.", Logger::LOG_WARNING);
			$rawInterval = -1;
		}
		$this->requestInterval = intval($rawInterval);
	}

	/**
	 * if rate limiting is turned on for this wrapper 
	 * we will check if this endpoint has been hit recently
	 * and if so wait before returning.
	 * @param string $url the url we are checking. 
	 */
	public function throttle(string $url) : void {
		//skip limiting if not turned on in config or explicitly set
		if($this->requestInterval === -1)
		{
			return;
		}
		$endpoint = parse_url($url, PHP_URL_HOST);
		if(empty($endpoint))
		{
			//looks like we have a malformed $url
			//let this get handled upstream
			global $logger;
			$logger->log("Failed to determine endpoint for: ".$url, Logger::LOG_WARNING);
			return;
		}
		
		
		//if we don't have any previous requests for this url
		//go ahead and send it after recording the last request
		if(empty($this->lastRequest[$endpoint]))
		{
			$this->lastRequest[$endpoint] = (int)floor(self::MILLI_PER_SEC * microtime(true));
			return;
		}

		$request_time = (int)floor(self::MILLI_PER_SEC * microtime(true));
		$time_diff = $request_time - $this->lastRequest[$endpoint];
		if($time_diff >= $this->requestInterval)
		{
			$this->lastRequest[$endpoint] = (int)floor(self::MILLI_PER_SEC * microtime(true));
			return;
		}

		//log too frequent requests so they can be corrected upstream
		global $logger;
		$logger->log(
			"Attempting to send too many requests to "
			. $endpoint . 
			" slowing requests down to "
			. $this->requestInterval . 
			" milliseconds between requests", Logger::LOG_WARNING);

		//loop until we have waited long enough
		//we should only need to wait once since
		//a separate thread would have a separate
		//CurlWrapper object but being cautious.
		while($time_diff < $this->requestInterval)
		{
			usleep((int)($this->requestInterval - $time_diff) * self::MICRO_PER_MILLI);
			$current_time = floor(self::MILLI_PER_SEC * microtime(true));
			$time_diff = $current_time - $this->lastRequest[$endpoint];
		}
		//update last request time in case and return control
		$this->lastRequest[$endpoint] = (int)floor(self::MILLI_PER_SEC * microtime(true));
	}
}