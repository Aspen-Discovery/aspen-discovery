<?php
require_once ROOT_DIR . '/sys/DB/DataObject.php';

class AspenError extends DataObject
{
	public $__table = 'errors';
	public $id;
	public $module;
	public $action;
	public $url;
	public $message;
	public $backtrace;
	public $_rawBacktrace;
	public $timestamp;
	public $userAgent;

	/**
	 * Create a new Aspen Error.  For new Errors raised by the system, message should be filled out.
	 * For searching old errors, provide no parameters
	 *
	 * @param null $message
	 * @param null $backtrace
	 */
	public function __construct($message = null, $backtrace = null)
	{
		if ($message != null) {
			if (isset($_SERVER['REQUEST_URI'])) {
				$this->url = $_SERVER['REQUEST_URI'];
			}
			global $module;
			global $action;
			$this->module = $module;
			$this->action = $action;
			if (isset($_SERVER['HTTP_USER_AGENT'])){
				$this->userAgent = $_SERVER['HTTP_USER_AGENT'];
			}else{
				$this->userAgent = 'None, command line';
			}

			$this->timestamp = time();

			$this->message = $message;
			if ($backtrace == null) {
				$this->_rawBacktrace = debug_backtrace();
			} else {
				$this->_rawBacktrace = $backtrace;
			}
			foreach ($this->_rawBacktrace as $backtraceLine) {
				if (isset($backtraceLine['class'])) {
					$this->backtrace .= $backtraceLine['class'];
				}
				if (isset($backtraceLine['type'])) {
					$this->backtrace .= $backtraceLine['type'];
				}
				if (isset($backtraceLine['function'])) {
					$this->backtrace .= $backtraceLine['function'];
				}
				$this->backtrace .= ' ';
				if (isset($backtraceLine['line'])) {
					$this->backtrace .= ' [' . $backtraceLine['line'] . ']';
				}
				if (isset($backtraceLine['file'])) {
					$this->backtrace .= ' - ' . $backtraceLine['file'];
				}
				$this->backtrace .= '<br/>';
			}
		}
	}

	public static function getObjectStructure() : array
	{
		$structure = array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'module' => array('property' => 'module', 'type' => 'label', 'label' => 'Module', 'description' => 'The Module that caused the error'),
			'action' => array('property' => 'action', 'type' => 'label', 'label' => 'Action', 'description' => 'The Action that caused the error'),
			'url' => array('property' => 'url', 'type' => 'label', 'label' => 'Url', 'description' => 'The URL that caused the error'),
			'userAgent' => array('property' => 'userAgent', 'type' => 'label', 'label' => 'User Agent', 'description' => 'The User agent for the user'),
			'message' => array('property' => 'message', 'type' => 'label', 'label' => 'Message', 'description' => 'A description of the error'),
			'backtrace' => array('property' => 'backtrace', 'type' => 'label', 'label' => 'Backtrace', 'description' => 'The trace that led to the error'),
			'timestamp' => array('property' => 'timestamp', 'type' => 'timestamp', 'label' => 'Timestamp', 'description' => 'When the error occurred'),
		);
		return $structure;
	}

	public function getMessage()
	{
		return $this->message;
	}

	public function getRawBacktrace()
	{
		return $this->_rawBacktrace;
	}

	public function toString()
	{
		return $this->message;
	}

	/**
	 * Run the Error handler
	 *
	 * @param string|AspenError $error
	 */
	static function raiseError($error)
	{
		if (is_string($error)) {
			$error = new AspenError($error);
		}
		$error->handleAspenError();
	}

	/**
	 * Handle an error raised by aspen
	 *
	 * TODO: When we are loading AJAX information, we should return a JSON formatted error rather than an HTML page
	 *
	 * @return null
	 */
	function handleAspenError()
	{
		global $errorHandlingEnabled;
		if (isset($errorHandlingEnabled) && ($errorHandlingEnabled < 0)) {
			return;
		}

		// It would be really bad if an error got raised from within the error handler;
		// we would go into an infinite loop and run out of memory.  To avoid this,
		// we'll set a static value to indicate that we're inside the error handler.
		// If the error handler gets called again from within itself, it will just
		// return without doing anything to avoid problems.  We know that the top-level
		// call will terminate execution anyway.
		static $errorAlreadyOccurred = false;
		if ($errorAlreadyOccurred) {
			return;
		} else {
			$errorAlreadyOccurred = true;
		}

		global $aspenUsage;
		if (!empty($aspenUsage)) {
			$aspenUsage->pagesWithErrors++;
			try {
				$aspenUsage->update();
			} catch (Exception $e) {
				//Table does not exist yet
			}
		}
		global $usageByIPAddress;
		try{
			if ($usageByIPAddress->id){
				$usageByIPAddress->update();
			}else{
				$usageByIPAddress->insert();
			}
		} catch (Exception $e) {
			//Table does not exist yet
		}

		try {
			$this->insert();
		} catch (Exception $e) {
			//Table has not been created yet
		}

		//Clear any output that has been generated so far so the user just gets the error message.
		if (IPAddress::showDebuggingInformation()) {
			@ob_clean();
		}

		// Display an error screen to the user:
		global $interface;
		if (!isset($interface) || $interface == false) {
			require_once ROOT_DIR . '/sys/Interface.php';
			$interface = new UInterface();
		}

		$interface->assign('error', $this);
		$debug = IPAddress::showDebuggingInformation();
		$interface->assign('debug', $debug);

		global $isAJAX;
		if ($isAJAX){
			$result = [
				'success' => false,
				'message' => $this->getMessage()
			];
			if ($debug){
				foreach ($this->getRawBacktrace() as $trace){
					$result['message'] .= "<br/>[{$trace['line']}] {$trace['file']}";
				}
			}
			echo json_encode($result);
		}else {
			global $module;
			if (!empty($module)) {
				$interface->setTemplate('../error.tpl');
			}else{
				$interface->setTemplate('error.tpl');
			}
			$interface->setPageTitle('An Error has occurred');
			$interface->display('layout.tpl');
		}

		// Exceptions we don't want to log
		$doLog = true;
		// Microsoft Web Discussions Toolbar polls the server for these two files
		//    it's not script kiddie hacking, just annoying in logs, ignore them.
		if (strpos($_SERVER['REQUEST_URI'], "cltreq.asp") !== false) $doLog = false;
		if (strpos($_SERVER['REQUEST_URI'], "owssvr.dll") !== false) $doLog = false;
		// If we found any exceptions, finish here
		if (!$doLog) exit();

		// Log the error for administrative purposes -- we need to build a variety
		// of pieces so we can supply information at five different verbosity levels:
		$baseError = $this->toString();
		/*$basicServer = " (Server: IP = {$_SERVER['REMOTE_ADDR']}, " .
			"Referer = " . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '') . ", " .
			"User Agent = " . (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '') . ", " .
			"Request URI = {$_SERVER['REQUEST_URI']})";*/
		$detailedServer = "\nServer Context:\n" . print_r($_SERVER, true);
		$basicBacktrace = "\nBacktrace:\n";
		if (!empty($this->backtrace)) {
			if (is_array($this->backtrace)) {
				foreach ($this->backtrace as $line) {
					$basicBacktrace .= (isset($line['file']) ? $line['file'] : 'none') . "  line " . (isset($line['line']) ? $line['line'] : 'none') . " - " .
						"class = " . (isset($line['class']) ? $line['class'] : 'none') . ", function = " . (isset($line['function']) ? $line['function'] : 'none') . "\n";
				}
			}else{
				$basicBacktrace .= $this->backtrace;
			}
		}
		//$detailedBacktrace = "\nBacktrace:\n" . print_r($error->backtrace, true);
		$errorDetails = $baseError . $detailedServer . $basicBacktrace;

		global $logger;
		$logger->log($errorDetails, Logger::LOG_ERROR);

		try{
			require_once ROOT_DIR . '/sys/SystemVariables.php';
			$systemVariables = new SystemVariables();
			if ($systemVariables->find(true) && !empty($systemVariables->errorEmail)) {
				global $serverName;

				require_once ROOT_DIR . '/sys/Email/Mailer.php';
				$mailer = new Mailer();
				$emailErrorDetails = $this->url . "\n" . $errorDetails;
				$mailer->send($systemVariables->errorEmail, "$serverName Error in User Interface", $emailErrorDetails);
			}
		}catch (Exception $e){
			//This happens when the table has not been created
		}
		exit();
	}
}