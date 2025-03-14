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
		global $logger;
		//TODO: Do validation of the address
		$amazonSesSettings = new AmazonSesSetting();
		$smtpServerSettings = new SMTPSetting();

		if($smtpServerSettings->find(true)) {
			$result = $this->sendViaSMTP($smtpServerSettings, $to, $replyTo, $subject, $body, $htmlBody, $attachments);
		}elseif ($amazonSesSettings->find(true)) {
			$result = $this->sendViaAmazonSes($amazonSesSettings, $to, $replyTo, $subject, $body, $htmlBody, $attachments);
		} else {
			$sendGridSettings = new SendGridSetting();
			if ($sendGridSettings->find(true)) {
				$result = $this->sendViaSendGrid($sendGridSettings, $to, $replyTo, $subject, $body, $htmlBody);
			} else {
				$result = false;
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
		global $logger;
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
			if(isset($attachments['name'])) {
				foreach ($attachments['name'] as $attachment) {
					$message->addAttachmentFromFile($attachments['name'][$i], $attachments['tmp_name'][$i], $attachments['type'][$i]);
					$i++;
				}
			}
		}

		$response = $amazonSesSettings->sendEmail($message, false, false);
		if ($response == false) {
			$logger->log("Amazon SES send failed no response", Logger::LOG_ERROR);
			return false;
		} else {
			if (isset($response->error) && count($response->error) > 0) {
				$logger->log('Amazon SES send failed: ' . implode(', ', $response->error), Logger::LOG_ERROR);
				return false;
			} else {
				return true;
			}
		}
	}

	private function sendViaSMTP(SMTPSetting $smtpSettings, string $to, ?string $replyTo, string $subject, ?string $body, ?string $htmlBody, ?array $attachments): bool {
		return $smtpSettings->sendEmail($to, $replyTo, $subject, $body, $htmlBody, $attachments);
	}
}