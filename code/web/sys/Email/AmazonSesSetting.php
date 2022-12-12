<?php
require_once ROOT_DIR . '/sys/Email/AmazonSesRequest.php';

/**
 * Class AmazonSesSetting
 *
 * Code to connect to Amazon initially based on:
 * https://github.com/daniel-zahariev/php-aws-ses
 *
 */
class AmazonSesSetting extends DataObject {
	public $__table = 'amazon_ses_settings';
	public $id;
	public $fromAddress;
	public $accessKeyId;
	public $accessKeySecret;
	public $singleMailConfigSet;
	public $bulkMailConfigSet;
	public $region;

	/**
	 * @return string[]
	 */
	function getEncryptedFieldNames(): array {
		return ['accessKeySecret'];
	}

	public static function getObjectStructure($context = ''): array {
		$regions = [
			'us-east-2' => 'US East (Ohio)',
			'us-east-1' => 'US East (N. Virginia)',
			'us-west-1' => 'US West (N. California)',
			'us-west-2' => 'US West (Oregon)',
			'af-south-1' => 'Africa (Cape Town)',
			'ap-east-1' => 'Asia Pacific (Hong Kong)',
			'ap-south-1' => 'Asia Pacific (Mumbai)',
			'ap-northeast-3' => 'Asia Pacific (Osaka)',
			'ap-northeast-2' => 'Asia Pacific (Seoul)',
			'ap-southeast-1' => 'Asia Pacific (Singapore)',
			'ap-southeast-2' => 'Asia Pacific (Sydney)',
			'ap-northeast-1' => 'Asia Pacific (Tokyo)',
			'ca-central-1' => 'Canada (Central)',
			'cn-north-1' => 'China (Beijing)',
			'cn-northwest-1' => 'China (Ningxia)',
			'eu-central-1' => 'Europe (Frankfurt)',
			'eu-west-1' => 'Europe (Ireland)',
			'eu-west-2' => 'Europe (London)',
			'eu-south-1' => 'Europe (Milan)',
			'eu-west-3' => 'Europe (Paris)',
			'eu-north-1' => 'Europe (Stockholm)',
			'me-south-1' => 'Middle East (Bahrain)',
			'sa-east-1' => 'South America (São Paulo)',
		];
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'fromAddress' => [
				'property' => 'fromAddress',
				'type' => 'email',
				'label' => 'From Address',
				'description' => 'The address emails are sent from',
				'default' => 'no-reply@bywatersolutions.com',
			],
			'accessKeyId' => [
				'property' => 'accessKeyId',
				'type' => 'text',
				'label' => 'Access Key',
				'description' => 'The Access Key used for sending',
				'default' => '',
				'hideInLists' => true,
			],
			'accessKeySecret' => [
				'property' => 'accessKeySecret',
				'type' => 'storedPassword',
				'label' => 'Access Key Secret',
				'description' => 'The Access Key Secret used for sending',
				'default' => '',
				'hideInLists' => true,
			],
			'singleMailConfigSet' => [
				'property' => 'singleMailConfigSet',
				'type' => 'text',
				'label' => 'Single Mail Configuration Set',
				'description' => 'Configuration Set to use when sending single emails, can be blank',
				'default' => '',
				'hideInLists' => true,
			],
			'bulkMailConfigSet' => [
				'property' => 'bulkMailConfigSet',
				'type' => 'text',
				'label' => 'Bulk Mail Configuration Set',
				'description' => 'Configuration Set to use when sending multiple emails, can be blank',
				'default' => '',
				'hideInLists' => true,
			],
			'region' => [
				'property' => 'region',
				'type' => 'enum',
				'values' => $regions,
				'label' => 'Region',
				'description' => 'The region to use when sending emails',
				'default' => 'us-east-2',
				'hideInLists' => true,
			],
		];
	}

	public function isFromAddressValid(): bool {
		$ses_request = $this->getRequestHandler('GET');
		$ses_request->setParameter('Action', 'ListVerifiedEmailAddresses');

		$ses_response = $ses_request->getResponse();
		if ($ses_response->error === false && $ses_response->code !== 200) {
			$ses_response->error = [
				'code' => $ses_response->code,
				'message' => 'Unexpected HTTP status',
			];
		}
		if ($ses_response->error !== false) {
			AspenError::raiseError('listVerifiedEmailAddresses - ' . $ses_response->error->Error->Message);
			return false;
		}

		if (!isset($ses_response->body)) {
			return false;
		}

		foreach ($ses_response->body->ListVerifiedEmailAddressesResult->VerifiedEmailAddresses->member as $address) {
			if ((string)$address == $this->fromAddress) {
				return true;
			}
		}

		return false;
	}

	public function verifyFromAddress() {
		$ses_request = $this->getRequestHandler('POST');
		$ses_request->setParameter('Action', 'VerifyEmailAddress');
		$ses_request->setParameter('EmailAddress', $this->fromAddress);

		$ses_response = $ses_request->getResponse();
		if ($ses_response->error === false && $ses_response->code !== 200) {
			$ses_response->error = [
				'code' => $ses_response->code,
				'message' => 'Unexpected HTTP status',
			];
		}
		if ($ses_response->error !== false) {
			AspenError::raiseError('verifyEmailAddress - ' . $ses_response->error->Error->Message);
			return false;
		}

		$response['RequestId'] = (string)$ses_response->body->ResponseMetadata->RequestId;
		return $response;
	}

	/** @var AmazonSesRequest */
	private $_ses_request = null;

	/**
	 * Get SES Request
	 *
	 * @param string $verb HTTP Verb: GET, POST, DELETE
	 * @return AmazonSesRequest SES Request
	 */
	public function getRequestHandler(string $verb): AmazonSesRequest {
		if (empty($this->__ses_request)) {
			$this->_ses_request = new AmazonSesRequest($this, $verb);
		} else {
			$this->_ses_request->setVerb($verb);
		}

		return $this->_ses_request;
	}

	public function getHost(): string {
		return "email.{$this->region}.amazonaws.com";
	}

	/**
	 * Given a SimpleEmailServiceMessage object, submits the message to the service for sending.
	 *
	 * @param AmazonSesMessage $sesMessage An instance of the message class
	 * @param boolean $use_raw_request If this is true or there are attachments to the email `SendRawEmail` call will be used
	 * @param boolean $trigger_error Optionally overwrite the class setting for triggering an error (with type check to true/false)
	 * @return array|false An array containing the unique identifier for this message and a separate request id.
	 *         Returns false if the provided message is missing any required fields.
	 * @link(AWS SES Response formats, http://docs.aws.amazon.com/ses/latest/DeveloperGuide/query-interface-responses.html)
	 */
	public function sendEmail(AmazonSesMessage $sesMessage, bool $use_raw_request = false, ?bool $trigger_error = null) {
		$sesMessage->setFrom($this->fromAddress);
		$sesMessage->setConfigurationSet($this->singleMailConfigSet);

		if (!$sesMessage->validate()) {
			AspenError::raiseError('sendEmail - Message failed validation.');
			return false;
		}

		$ses_request = $this->getRequestHandler('POST');
		$action = !empty($sesMessage->attachments) || $use_raw_request ? 'SendRawEmail' : 'SendEmail';
		$ses_request->setParameter('Action', $action);

		// Works with both calls
		if (!is_null($sesMessage->configuration_set)) {
			$ses_request->setParameter('ConfigurationSetName', $sesMessage->configuration_set);
		}

		if ($action == 'SendRawEmail') {
			// https://docs.aws.amazon.com/ses/latest/APIReference/API_SendRawEmail.html
			$ses_request->setParameter('RawMessage.Data', $sesMessage->getRawMessage());
		} else {
			$i = 1;
			foreach ($sesMessage->to as $to) {
				$ses_request->setParameter('Destination.ToAddresses.member.' . $i, $sesMessage->encodeRecipients($to));
				$i++;
			}

			if (is_array($sesMessage->cc)) {
				$i = 1;
				foreach ($sesMessage->cc as $cc) {
					$ses_request->setParameter('Destination.CcAddresses.member.' . $i, $sesMessage->encodeRecipients($cc));
					$i++;
				}
			}

			if (is_array($sesMessage->bcc)) {
				$i = 1;
				foreach ($sesMessage->bcc as $bcc) {
					$ses_request->setParameter('Destination.BccAddresses.member.' . $i, $sesMessage->encodeRecipients($bcc));
					$i++;
				}
			}

			if (is_array($sesMessage->replyto)) {
				$i = 1;
				foreach ($sesMessage->replyto as $replyto) {
					$ses_request->setParameter('ReplyToAddresses.member.' . $i, $sesMessage->encodeRecipients($replyto));
					$i++;
				}
			}

			$ses_request->setParameter('Source', $sesMessage->encodeRecipients($sesMessage->from));

			if ($sesMessage->returnpath != null) {
				$ses_request->setParameter('ReturnPath', $sesMessage->returnpath);
			}

			if ($sesMessage->subject != null && strlen($sesMessage->subject) > 0) {
				$ses_request->setParameter('Message.Subject.Data', $sesMessage->subject);
				if ($sesMessage->subjectCharset != null && strlen($sesMessage->subjectCharset) > 0) {
					$ses_request->setParameter('Message.Subject.Charset', $sesMessage->subjectCharset);
				}
			}


			if ($sesMessage->messagetext != null && strlen($sesMessage->messagetext) > 0) {
				$ses_request->setParameter('Message.Body.Text.Data', $sesMessage->messagetext);
				if ($sesMessage->messageTextCharset != null && strlen($sesMessage->messageTextCharset) > 0) {
					$ses_request->setParameter('Message.Body.Text.Charset', $sesMessage->messageTextCharset);
				}
			}

			if ($sesMessage->messagehtml != null && strlen($sesMessage->messagehtml) > 0) {
				$ses_request->setParameter('Message.Body.Html.Data', $sesMessage->messagehtml);
				if ($sesMessage->messageHtmlCharset != null && strlen($sesMessage->messageHtmlCharset) > 0) {
					$ses_request->setParameter('Message.Body.Html.Charset', $sesMessage->messageHtmlCharset);
				}
			}

			$i = 1;
			foreach ($sesMessage->message_tags as $key => $value) {
				$ses_request->setParameter('Tags.member.' . $i . '.Name', $key);
				$ses_request->setParameter('Tags.member.' . $i . '.Value', $value);
				$i++;
			}
		}

		$ses_response = $ses_request->getResponse();
		if ($ses_response->error === false && $ses_response->code !== 200) {
			$response = [
				'code' => $ses_response->code,
				'error' => ['Error' => ['message' => 'Unexpected HTTP status']],
			];
			return $response;
		}
		if ($ses_response->error !== false) {
			if ($trigger_error) {
				AspenError::raiseError('sendEmail - ' . $ses_response->error->Error->Message);
				return false;
			}
			return $ses_response;
		}

		$response = [
			'MessageId' => (string)$ses_response->body->{"{$action}Result"}->MessageId,
			'RequestId' => (string)$ses_response->body->ResponseMetadata->RequestId,
		];
		return $response;
	}
}