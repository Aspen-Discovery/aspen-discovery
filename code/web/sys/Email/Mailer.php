<?php

class Mailer {
	protected $settings;      // settings for PEAR Mail object

	/**
	 * Send an email message.
	 *
	 * @access  public
	 * @param string $to Recipient email address
	 * @param string $subject Subject line for message
	 * @param string $body Message body
	 * @param string $replyTo Someone to reply to
	 * @param bool $htmlMessage True to send the email as html
	 * @param string? $htmlBody Message body
	 *
	 * @return  boolean
	 */
	public function send($to, $subject, $body, $replyTo = null, $htmlBody = null, $attachments = []) : bool {

		require_once ROOT_DIR . '/sys/Email/SendGridSetting.php';
		require_once ROOT_DIR . '/sys/Email/AmazonSesSetting.php';
		require_once ROOT_DIR . '/sys/Email/SMTPSetting.php';
		require_once ROOT_DIR . '/sys/CurlWrapper.php';
		require_once ROOT_DIR . '/sys/SystemVariables.php';
		//TODO: Do validation of the address

		$systemVariables = new SystemVariables();
		if ($systemVariables->find(true) && !empty($systemVariables->preferredMailSender)) {
			$mailSenders = explode("|", $systemVariables->preferredMailSender);
			foreach ($mailSenders as $mailSender){
				$result = $this->sendViaAbstractSender($mailSender, $to, $replyTo, $subject, $body, $htmlBody, $attachments);
				if ($result) {
					break;
				}
			}
		}

		/** AspenUsage $aspenUsage */
		global $aspenUsage;
		if ($result) {
			$aspenUsage->incEmailsSent();
		}else{
			$aspenUsage->incEmailsFailed();
		}
		$ret = $aspenUsage->update();

		return $result;
	}

	/**
	 * @param SendGridSetting $sendGridSettings
	 * @param string $to
	 * @param string|null $replyTo
	 * @param string $subject
	 * @param bool $htmlMessage
	 * @param string $body
	 * @return bool
	 */
	protected function sendViaSendGrid(SendGridSetting $sendGridSettings, string $to, ?string $replyTo, string $subject, string $body, ?string $htmlBody) {
		//Send the email
		$curlWrapper = new CurlWrapper();
		$headers = [
			'Authorization: Bearer ' . $sendGridSettings->apiKey,
			'Content-Type: application/json',
		];
		$curlWrapper->addCustomHeaders($headers, false);

		$apiBody = new stdClass();
		$apiBody->personalizations = [];
		$toAddresses = explode(';', $to);
		foreach ($toAddresses as $tmpToAddress) {
			$personalization = new stdClass();
			$personalization->to = [];

			$toAddress = new stdClass();
			$toAddress->email = trim($tmpToAddress);
			$personalization->to[] = $toAddress;

			$apiBody->personalizations[] = $personalization;
		}
		$apiBody->from = new stdClass();
		$apiBody->from->email = $sendGridSettings->fromAddress;
		$apiBody->reply_to = new stdClass();
		$apiBody->reply_to->email = (($replyTo != null) ? $replyTo : $sendGridSettings->replyToAddress);
		$apiBody->subject = $subject;
		$apiBody->content = [];
		$content = new stdClass();
		if (!empty($htmlBody)) {
			$content->type = 'text/html';
			$content->value = $htmlBody;
		} else {
			$content->type = 'text/plain';
			$content->value = $body;
		}

		$apiBody->content[] = $content;

		$response = $curlWrapper->curlPostPage('https://api.sendgrid.com/v3/mail/send', json_encode($apiBody));
		if ($response != '') {
			global $logger;
			$logger->log('Error sending email via SendGrid ' . $curlWrapper->getResponseCode() . ' ' . $response, Logger::LOG_ERROR);
			return false;
		} else {
			return true;
		}
	}

	private function sendViaAmazonSes(AmazonSesSetting $amazonSesSettings, string $to, ?string $replyTo, string $subject, ?string $body, ?string $htmlBody, ?array $attachments): bool {
		require_once ROOT_DIR . '/sys/Email/AmazonSesMessage.php';
		$message = new AmazonSesMessage();
		$toAddresses = explode(';', $to);
		$message->addTo($toAddresses);
		if (!empty($replyTo)) {
			$message->addReplyTo($replyTo);
		}
		$message->setSubject($subject);
		$message->setMessageFromString($body, $htmlBody);

		if(!empty($attachments)) {
			$i = 0;
			foreach($attachments as $attachment) {
				if($attachment['name'][$i]) {
					$message->addAttachmentFromFile($attachment['name'][$i], $attachment['tmp_name'][$i], $attachment['type'][$i]);
					$i++;
				}
			}
		}

		$response = $amazonSesSettings->sendEmail($message, false, false);
		if ($response == false) {
			return false;
		} else {
			if (isset($response->error) && count($response->error) > 0) {
				return false;
			} else {
				return true;
			}
		}
	}

	private function sendViaSMTP(SMTPSetting $smtpSettings, string $to, ?string $replyTo, string $subject, ?string $body, ?string $htmlBody, ?array $attachments): bool {
		return $smtpSettings->sendEmail($to, $replyTo, $subject, $body, $htmlBody, $attachments);
	}

	private function sendViaAbstractSender($mailSender, $to, $replyTo, $subject, $body, $htmlBody, $attachments) {
		$result = false;
		if($mailSender == 'AmazonSES') {
			$amazonSesSettings = new AmazonSesSetting();
			if($amazonSesSettings->find(true)){
				$result = $this->sendViaAmazonSes($amazonSesSettings, $to, $replyTo, $subject, $body, $htmlBody, $attachments);
			}
		} else if($mailSender == 'SendGrid') {
			$sendGridSettings = new SendGridSetting();
			if($sendGridSettings->find(true)){
				$result = $this->sendViaSendGrid($sendGridSettings, $to, $replyTo, $subject, $body, $htmlBody);
			}
		} else if($mailSender == 'SMTP') {
			$smtpSettings = new SMTPSetting();
			if($smtpSettings->find(true)){
				$result = $this->sendViaSMTP($smtpSettings, $to, $replyTo, $subject, $body, $htmlBody, $attachments);
			}
		}
		return $result;
	}
}