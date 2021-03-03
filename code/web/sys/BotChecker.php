<?php

class BotChecker{

	static $isBot = null;
	/**
	 *
	 * Determines if the current request appears to be from a bot
	 */
	public static function isRequestFromBot(){
		if (BotChecker::$isBot == null){
			global $logger;
			global $timer;
			global $memCache;
			global $configArray;
			if (isset($_SERVER['HTTP_USER_AGENT'])){
				$userAgent = $_SERVER['HTTP_USER_AGENT'];
			}else{
				//No user agent passed, assume it is a bot
				return true;
			}

			$isBot = $memCache->get("bot_by_user_agent_" . $userAgent);
			if ($isBot === FALSE){
				global $serverName;
				if (file_exists('../../sites/' . $serverName . '/conf/bots.txt')){
					$fileHandle = fopen('../../sites/' . $serverName . '/conf/bots.txt', 'r');
				}elseif (file_exists('../../sites/default/conf/bots.txt')){
					$fileHandle = fopen('../../sites/default/conf/bots.txt', 'r');
				}else{
					$logger->log("Did not find bots.txt file, cannot detect bots", Logger::LOG_ERROR);
					return false;
				}

				$isBot = false;
				while (($curAgent = fgets($fileHandle, 4096)) !== false) {
					//Remove line separators
					$curAgent = str_replace("\r", '', $curAgent);
					$curAgent = str_replace("\n", '', $curAgent);
					if (strcasecmp($userAgent, $curAgent) == 0 ){
						$isBot = true;
						break;
					}
				}
				fclose($fileHandle);

				$memCache->set("bot_by_user_agent_" . $userAgent, ($isBot ? 'TRUE' : 'FALSE'), $configArray['Caching']['bot_by_user_agent']);
				if ($isBot){
					$logger->log("$userAgent is a bot", Logger::LOG_DEBUG);
				}else{
					$logger->log("$userAgent is not a bot", Logger::LOG_DEBUG);
				}
				BotChecker::$isBot = $isBot;
			}else{
				//$logger->log("Got bot info from memcache $isBot", Logger::LOG_DEBUG);
				BotChecker::$isBot = ($isBot === 'TRUE');
			}

			$timer->logTime("Checking isRequestFromBot");
		}
		return BotChecker::$isBot;
	}
}