<?php

/**
 * Docker logging class for Aspen Discovery services
 * Matches service names: apache, cron, backend
 */
class DockerLogger {
	
	private static $serviceName = 'BACKEND';
	
	/**
	 * Initialize logger with service name
	 */
	public static function init($serviceName = 'BACKEND') {
		self::$serviceName = strtoupper($serviceName);
	}
	
	/**
	 * Main logging function
	 */
	private static function log($level, $message) {
		$timestamp = date('Y-m-d H:i:s');
		echo "[{$timestamp}] [" . self::$serviceName . "] [{$level}] {$message}" . PHP_EOL;
	}
	
	/**
	 * Log info message
	 */
	public static function info($message) {
		self::log('INFO', $message);
	}
	
	/**
	 * Log warning message
	 */
	public static function warn($message) {
		self::log('WARN', $message);
	}
	
	/**
	 * Log error message and exit
	 */
	public static function error($message) {
		self::log('ERROR', $message);
		exit(1);
	}

	/**
	* Set ownership and permissions using LOCAL_USER_ID strategy
	*/
	public static function setPermissions($path, $owner = 'www-data', $permissions = '755') {
		if (!file_exists($path)) {
			self::warn("Path does not exist: {$path}");
			return;
		}

		$recursive = is_dir($path) ? '-R ' : '';
		exec("chown {$recursive}{$owner} {$path}");
		exec("chmod {$recursive}{$permissions} {$path}");
	}
}

// Auto-initialize
DockerLogger::init();
?>
