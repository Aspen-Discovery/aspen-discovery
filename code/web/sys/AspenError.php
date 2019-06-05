<?php


class AspenError
{
    static $errorCallback = null;

    private $message;

    public $backtrace;

    public function __construct($message){
        $this->message = $message;
        $this->backtrace = debug_backtrace();
    }

    public function getMessage(){
        return $this->message;
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
    static function raiseError($error) {
        if (is_string($error)){
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
    function handleAspenError(){
        global $errorHandlingEnabled;
        if (isset($errorHandlingEnabled) && $errorHandlingEnabled == false){
            return;
        }
        global $configArray;

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
        $aspenUsage->pagesWithErrors++;

        //Clear any output that has been generated so far so the user just gets the error message.
        if (!$configArray['System']['debug']){
            @ob_clean();
            header("Content-Type: text/html");
        }

        // Display an error screen to the user:
        global $interface;
        if (!isset($interface) || $interface == false){
            $interface = new UInterface();
        }

        $interface->assign('error', $this);
        $interface->assign('debug', $configArray['System']['debug']);
        $interface->setTemplate('error.tpl');
        $interface->display('layout.tpl');

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
        if (is_array($this->backtrace)) {
            foreach($this->backtrace as $line) {
                $basicBacktrace .= (isset($line['file']) ? $line['file'] : 'none') . "  line " . (isset($line['line']) ? $line['line'] : 'none') . " - " .
                    "class = " . (isset($line['class']) ? $line['class'] : 'none') . ", function = " . (isset($line['function']) ? $line['function'] : 'none') . "\n";
            }
        }
        //$detailedBacktrace = "\nBacktrace:\n" . print_r($error->backtrace, true);
        $errorDetails = $baseError . $detailedServer . $basicBacktrace;

        global $logger;
        $logger->log($errorDetails, Logger::LOG_ERROR);

        exit();
    }
}