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
	 * @param ?string $replyTo Someone to reply to
	 * @param ?string $htmlBody Message body
	 * @param array $attachments an array of attachments to include
	 *
	 * @return  boolean
	 */
	public function send(string $to, string $subject, string $body, ?string $replyTo = null, ?string $htmlBody = null, array $attachments = []) : bool {

		require_once ROOT_DIR . '/sys/Email/SendGridSetting.php';
		require_once ROOT_DIR . '/sys/Email/AmazonSesSetting.php';
		require_once ROOT_DIR . '/sys/Email/SMTPSetting.php';
		require_once ROOT_DIR . '/sys/CurlWrapper.php';
		$amazonSesSettings = new AmazonSesSetting();
		$smtpServerSettings = new SMTPSetting();

		$to = $this->validateAndFilterEmails($to);

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
		$aspenUsage->update();

		return $result;
	}

	/**
	 * @param string $emails
	 */
	private function validateAndFilterEmails(string $emails) : string {
		$isValidEmail = fn($address) => filter_var(trim($address), FILTER_VALIDATE_EMAIL);
		$validEmails = array_filter(explode(';', $emails), $isValidEmail);
		return implode(';', $validEmails);
	}

	/**
	 * @param SendGridSetting $sendGridSettings
	 * @param string $to
	 * @param string|null $replyTo
	 * @param string $subject
	 * @param string|null $body
	 * @param string|null $htmlBody
	 * @return bool
	 */
	protected function sendViaSendGrid(SendGridSetting $sendGridSettings, string $to, ?string $replyTo, string $subject, ?string $body, ?string $htmlBody) : bool {
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

		$response = $curlWrapper->curlPostPage(!empty($sendGridSettings->baseUrl) ? $sendGridSettings->baseUrl :'https://api.sendgrid.com/v3/mail/send', json_encode($apiBody));
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
		if (!$response) {
			$logger->log("Amazon SES send failed no response", Logger::LOG_ERROR);
			return false;
		} else {
			if (isset($response->error) && count($response->error) > 0) {
				$logger->log('Amazon SES send failed: ' . print_r($response->error, true), Logger::LOG_ERROR);
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