<?php
/**
 *
 * Copyright (C) Villanova University 2009.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */
require_once 'Mail.php';
require_once 'Mail/RFC822.php';

/**
 * VuFind Mailer Class
 *
 * This is a wrapper class to load configuration options and perform email
 * functions.  See the comments in web/conf/config.ini for details on how
 * email is configured.
 *
 * @author      Demian Katz <demian.katz@villanova.edu>
 * @access      public
 */
class VuFindMailer {
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
			return new PEAR_Error('Invalid Recipient Email Address');
		}
		enableErrorHandler();

		if (!$validator->isValidInetAddress($from)) {
			return new PEAR_Error('Invalid Sender Email Address');
		}

		$headers = array('To' => $to, 'Subject' => $subject,
		                 'Date' => date('D, d M Y H:i:s O'),
		                 'Content-Type' => 'text/plain; charset="UTF-8"');
		if (isset($this->settings['fromAddress'])){
			$logger->log("Overriding From address, using " . $this->settings['fromAddress'], PEAR_LOG_INFO);
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
			if (PEAR_Singleton::isError($mail)) {
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
			$logger->log("Sending e-mail", PEAR_LOG_INFO);
			$logger->log("From = $from", PEAR_LOG_INFO);
			$logger->log("To = $to", PEAR_LOG_INFO);
			$logger->log($subject, PEAR_LOG_INFO);
			$logger->log($formattedMail, PEAR_LOG_INFO);
			return true;
		}

	}
}