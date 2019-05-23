<?php

class Mailer {
	protected $settings;      // settings for PEAR Mail object

	/**
	 * Constructor
	 *
	 * Sets up mailing functionality using settings from config.ini.
	 *
	 * @access  public
	 */
	public function __construct() {
		global $configArray;

		// Load settings from the config file into the object; we'll do the
		// actual creation of the mail object later since that will make error
		// detection easier to control.
		$this->settings = array('host' => $configArray['Mail']['host'],
                            'port' => $configArray['Mail']['port']);
		if (isset($configArray['Mail']['username']) && isset($configArray['Mail']['password'])) {
			$this->settings['auth'] = true;
			$this->settings['username'] = $configArray['Mail']['username'];
			$this->settings['password'] = $configArray['Mail']['password'];
		}
		if (isset($configArray['Mail']['fromAddress'])){
			$this->settings['fromAddress'] = $configArray['Mail']['fromAddress'];
		}
	}

	/**
	 * Send an email message.
	 *
	 * @access  public
	 * @param   string  $to         Recipient email address
	 * @param   string  $from       Sender email address
	 * @param   string  $subject    Subject line for message
	 * @param   string  $body       Message body
	 * @param   string  $replyTo    Someone to reply to
	 *
	 * @return  mixed               PEAR error on error, boolean true otherwise
	 */
	public function send($to, $from, $subject, $body, $replyTo = null) {
		global $logger;
		// Validate sender and recipient
		$validator = new Mail_RFC822();
		//Allow the to address to be split
		disableErrorHandler();
		try{
			//Validate the address list to make sure we don't get an error.
			$validator->parseAddressList($to);
		}catch (Exception $e){
			return new AspenError('Invalid Recipient Email Address');
		}
		enableErrorHandler();

		if (!$validator->isValidInetAddress($from)) {
			return new AspenError('Invalid Sender Email Address');
		}

		$headers = array('To' => $to, 'Subject' => $subject,
		                 'Date' => date('D, d M Y H:i:s O'),
		                 'Content-Type' => 'text/plain; charset="UTF-8"');
		if (isset($this->settings['fromAddress'])){
			$logger->log("Overriding From address, using " . $this->settings['fromAddress'], Logger::LOG_NOTICE);
			$headers['From'] = $this->settings['fromAddress'];
			$headers['Reply-To'] = $from;
		}else{
			$headers['From'] = $from;
		}
		if ($replyTo != null){
			$headers['Reply-To'] = $replyTo;
		}

		// Get mail object
		if ($this->settings['host'] != false){
			$mailFactory = new Mail();
			$mail =& $mailFactory->factory('smtp', $this->settings);
			if ($mail instanceof AspenError) {
				return $mail;
			}

			// Send message
			return $mail->send($to, $headers, $body);
		}else{
			//Mail to false just emits the information to screen
			$formattedMail = '';
			foreach ($headers as $key => $header){
				$formattedMail .= $key . ': ' . $header . '<br />';
			}
			$formattedMail .= $body;
			$logger->log("Sending e-mail", Logger::LOG_NOTICE);
			$logger->log("From = $from", Logger::LOG_NOTICE);
			$logger->log("To = $to", Logger::LOG_NOTICE);
			$logger->log($subject, Logger::LOG_NOTICE);
			$logger->log($formattedMail, Logger::LOG_NOTICE);
			return true;
		}

	}
}