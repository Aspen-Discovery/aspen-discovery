<?php /** @noinspection PhpUnused */

/**
 * Class AmazonSesRequest
 *
 * Some code initially based on:
 * https://github.com/daniel-zahariev/php-aws-ses
 */
class AmazonSesMessage
{
// these are public for convenience only
	// these are not to be used outside of the SimpleEmailService class!
	public $to;
	public $cc;
	public $bcc;
	public $replyto;
	public $recipientsCharset;
	public $from;
	public $returnpath;
	public $subject;
	public $messagetext;
	public $messagehtml;
	public $subjectCharset;
	public $messageTextCharset;
	public $messageHtmlCharset;
	public $attachments;
	public $customHeaders;
	public $configuration_set;
	public $message_tags;
	public $is_clean;
	public $raw_message;

	public function __construct() {
		$this->to = array();
		$this->cc = array();
		$this->bcc = array();
		$this->replyto = array();
		$this->recipientsCharset = 'UTF-8';

		$this->from = null;
		$this->returnpath = null;

		$this->subject = null;
		$this->messagetext = null;
		$this->messagehtml = null;

		$this->subjectCharset = 'UTF-8';
		$this->messageTextCharset = 'UTF-8';
		$this->messageHtmlCharset = 'UTF-8';

		$this->attachments = array();
		$this->customHeaders = array();
		$this->configuration_set = null;
		$this->message_tags = array();

		$this->is_clean = true;
		$this->raw_message = null;
	}

	/**
	 * addTo, addCC, addBCC, and addReplyTo have the following behavior:
	 * If a single address is passed, it is appended to the current list of addresses.
	 * If an array of addresses is passed, that array is merged into the current list.
	 *
	 * @return AmazonSesMessage $this
	 * @link http://docs.aws.amazon.com/ses/latest/APIReference/API_Destination.html
	 */
	public function addTo($to) : AmazonSesMessage {
		if (!is_array($to)) {
			$this->to[] = $to;
		} else {
			$this->to = array_unique(array_merge($this->to, $to));
		}

		$this->is_clean = false;

		return $this;
	}

	/**
	 * @return AmazonSesMessage $this
	 */
	public function setTo($to) : AmazonSesMessage {
		$this->to = (array) $to;

		$this->is_clean = false;

		return $this;
	}

	/**
	 * Clear the To: email address(es) for the message
	 *
	 * @return AmazonSesMessage $this
	 */
	public function clearTo() : AmazonSesMessage {
		$this->to = array();

		$this->is_clean = false;

		return $this;
	}

	/**
	 * @return AmazonSesMessage $this
	 * @see addTo()
	 */
	public function addCC($cc) : AmazonSesMessage {
		if (!is_array($cc)) {
			$this->cc[] = $cc;
		} else {
			$this->cc = array_merge($this->cc, $cc);
		}

		$this->is_clean = false;

		return $this;
	}

	/**
	 * Clear the CC: email address(es) for the message
	 *
	 * @return AmazonSesMessage $this
	 */
	public function clearCC() : AmazonSesMessage {
		$this->cc = array();

		$this->is_clean = false;

		return $this;
	}

	/**
	 * @return AmazonSesMessage $this
	 * @see addTo()
	 */
	public function addBCC($bcc) : AmazonSesMessage {
		if (!is_array($bcc)) {
			$this->bcc[] = $bcc;
		} else {
			$this->bcc = array_merge($this->bcc, $bcc);
		}

		$this->is_clean = false;

		return $this;
	}

	/**
	 * Clear the BCC: email address(es) for the message
	 *
	 * @return AmazonSesMessage $this
	 */
	public function clearBCC() : AmazonSesMessage {
		$this->bcc = array();

		$this->is_clean = false;

		return $this;
	}

	/**
	 * @return AmazonSesMessage $this
	 * @see addTo()
	 */
	public function addReplyTo($replyto) : AmazonSesMessage {
		if (!is_array($replyto)) {
			$this->replyto[] = $replyto;
		} else {
			$this->replyto = array_merge($this->replyto, $replyto);
		}

		$this->is_clean = false;

		return $this;
	}

	/**
	 * Clear the Reply-To: email address(es) for the message
	 *
	 * @return AmazonSesMessage $this
	 */
	public function clearReplyTo() : AmazonSesMessage {
		$this->replyto = array();

		$this->is_clean = false;

		return $this;
	}

	/**
	 * Clear all of the message recipients in one go
	 *
	 * @return AmazonSesMessage $this
	 * @uses clearTo()
	 * @uses clearCC()
	 * @uses clearBCC()
	 * @uses clearReplyTo()
	 */
	public function clearRecipients() : AmazonSesMessage {
		$this->clearTo();
		$this->clearCC();
		$this->clearBCC();
		$this->clearReplyTo();

		$this->is_clean = false;

		return $this;
	}

	/**
	 * @return AmazonSesMessage $this
	 */
	public function setFrom($from) : AmazonSesMessage {
		$this->from = $from;

		$this->is_clean = false;

		return $this;
	}

	/**
	 * @return AmazonSesMessage $this
	 */
	public function setReturnPath($returnpath) : AmazonSesMessage {
		$this->returnpath = $returnpath;

		$this->is_clean = false;

		return $this;
	}

	/**
	 * @return AmazonSesMessage $this
	 */
	public function setRecipientsCharset($charset) : AmazonSesMessage {
		$this->recipientsCharset = $charset;

		$this->is_clean = false;

		return $this;
	}

	/**
	 * @return AmazonSesMessage $this
	 */
	public function setSubject($subject) : AmazonSesMessage {
		$this->subject = $subject;

		$this->is_clean = false;

		return $this;
	}

	/**
	 * @return AmazonSesMessage $this
	 */
	public function setSubjectCharset($charset) : AmazonSesMessage {
		$this->subjectCharset = $charset;

		$this->is_clean = false;

		return $this;
	}

	/**
	 * @return AmazonSesMessage $this
	 * @link http://docs.aws.amazon.com/ses/latest/APIReference/API_Message.html
	 */
	public function setMessageFromString($text, $html = null) : AmazonSesMessage {
		$this->messagetext = $text;
		$this->messagehtml = $html;

		$this->is_clean = false;

		return $this;
	}

	/**
	 * @return AmazonSesMessage $this
	 */
	public function setMessageFromFile($textfile, $htmlfile = null) : AmazonSesMessage {
		if (file_exists($textfile) && is_file($textfile) && is_readable($textfile)) {
			$this->messagetext = file_get_contents($textfile);
		} else {
			$this->messagetext = null;
		}
		if (file_exists($htmlfile) && is_file($htmlfile) && is_readable($htmlfile)) {
			$this->messagehtml = file_get_contents($htmlfile);
		} else {
			$this->messagehtml = null;
		}

		$this->is_clean = false;

		return $this;
	}

	/**
	 * @return AmazonSesMessage $this
	 */
	public function setMessageFromURL($texturl, $htmlurl = null) : AmazonSesMessage {
		if ($texturl !== null) {
			$this->messagetext = file_get_contents($texturl);
		} else {
			$this->messagetext = null;
		}
		if ($htmlurl !== null) {
			$this->messagehtml = file_get_contents($htmlurl);
		} else {
			$this->messagehtml = null;
		}

		$this->is_clean = false;

		return $this;
	}

	/**
	 * @return AmazonSesMessage $this
	 */
	public function setMessageCharset($textCharset, $htmlCharset = null) : AmazonSesMessage {
		$this->messageTextCharset = $textCharset;
		$this->messageHtmlCharset = $htmlCharset;

		$this->is_clean = false;

		return $this;
	}

	/**
	 * @return AmazonSesMessage $this
	 */
	public function setConfigurationSet($configuration_set = null) : AmazonSesMessage {
		$this->configuration_set = $configuration_set;

		$this->is_clean = false;

		return $this;
	}

	/**
	 * @return array $message_tags
	 */
	public function getMessageTags(): array
	{
		return $this->message_tags;
	}

	/**
	 * @return null|mixed $message_tag
	 */
	public function getMessageTag($key) {
		return $this->message_tags[$key] ?? null;
	}

	/**
	 * Add Message tag
	 *
	 * Both key and value can contain only ASCII letters (a-z, A-Z), numbers (0-9), underscores (_), or dashes (-) and be less than 256 characters.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return AmazonSesMessage $this
	 * @link https://docs.aws.amazon.com/ses/latest/DeveloperGuide/event-publishing-send-email.html
	 * @link https://docs.aws.amazon.com/ses/latest/APIReference/API_MessageTag.html
	 */
	public function setMessageTag(string $key, $value) : AmazonSesMessage {
		$this->message_tags[$key] = $value;

		$this->is_clean = false;

		return $this;
	}

	/**
	 * @param string $key The key of the tag to be removed
	 * @return AmazonSesMessage $this
	 */
	public function removeMessageTag(string $key) : AmazonSesMessage {
		unset($this->message_tags[$key]);

		$this->is_clean = false;

		return $this;
	}

	/**
	 * @param array $message_tags
	 * @return AmazonSesMessage $this
	 */
	public function setMessageTags(array $message_tags = array()) : AmazonSesMessage {
		$this->message_tags = array_merge($this->message_tags, $message_tags);

		$this->is_clean = false;

		return $this;
	}

	/**
	 * @return AmazonSesMessage $this
	 */
	public function removeMessageTags() : AmazonSesMessage {
		$this->message_tags = array();

		$this->is_clean = false;

		return $this;
	}

	/**
	 * Add custom header - this works only with SendRawEmail
	 *
	 * @param string $header Your custom header
	 * @return AmazonSesMessage $this
	 * @link( Restrictions on headers, http://docs.aws.amazon.com/ses/latest/DeveloperGuide/header-fields.html)
	 */
	public function addCustomHeader(string $header) : AmazonSesMessage {
		$this->customHeaders[] = $header;

		$this->is_clean = false;

		return $this;
	}

	/**
	 * Add email attachment by directly passing the content
	 *
	 * @param string $name The name of the file attachment as it will appear in the email
	 * @param string $data The contents of the attachment file
	 * @param string $mimeType Specify custom MIME type
	 * @param string|null $contentId Content ID of the attachment for inclusion in the mail message
	 * @param string $attachmentType Attachment type: attachment or inline
	 * @return AmazonSesMessage $this
	 */
	public function addAttachmentFromData(string $name, string $data, string $mimeType = 'application/octet-stream', string $contentId = null, string $attachmentType = 'attachment') : AmazonSesMessage {
		$this->attachments[$name] = array(
			'name' => $name,
			'mimeType' => $mimeType,
			'data' => $data,
			'contentId' => $contentId,
			'attachmentType' => ($attachmentType == 'inline' ? 'inline; filename="' . $name . '"' : $attachmentType),
		);

		$this->is_clean = false;

		return $this;
	}

	/**
	 * Add email attachment by passing file path
	 *
	 * @param string $name      The name of the file attachment as it will appear in the email
	 * @param string $path      Path to the attachment file
	 * @param string $mimeType  Specify custom MIME type
	 * @param string $contentId Content ID of the attachment for inclusion in the mail message
	 * @param string $attachmentType    Attachment type: attachment or inline
	 * @return boolean Status of the operation
	 */
	public function addAttachmentFromFile(string $name, $path, $mimeType = 'application/octet-stream', $contentId = null, $attachmentType = 'attachment') : bool {
		if (file_exists($path) && is_file($path) && is_readable($path)) {
			$this->addAttachmentFromData($name, file_get_contents($path), $mimeType, $contentId, $attachmentType);
			return true;
		}

		$this->is_clean = false;

		return false;
	}

	/**
	 * Add email attachment by passing file path
	 *
	 * @param string $name      The name of the file attachment as it will appear in the email
	 * @param string $url      URL to the attachment file
	 * @param string $mimeType  Specify custom MIME type
	 * @param string $contentId Content ID of the attachment for inclusion in the mail message
	 * @param string $attachmentType    Attachment type: attachment or inline
	 * @return boolean Status of the operation
	 */
	public function addAttachmentFromUrl(string $name, string $url, string $mimeType = 'application/octet-stream', string $contentId = null, string $attachmentType = 'attachment') : bool {
		$data = file_get_contents($url);
		if ($data !== false) {
			$this->addAttachmentFromData($name, $data, $mimeType, $contentId, $attachmentType);
			return true;
		}

		$this->is_clean = false;

		return false;
	}

	/**
	 * Get the existence of attached inline messages
	 *
	 * @return boolean
	 */
	public function hasInlineAttachments(): bool
	{
		foreach ($this->attachments as $attachment) {
			if ($attachment['attachmentType'] != 'attachment') {
				return true;
			}

		}
		return false;
	}

	/**
	 * Get the raw mail message
	 *
	 * @return string
	 */
	public function getRawMessage($encode = true): ?string
	{
		if ($this->is_clean && !is_null($this->raw_message) && $encode) {
			return $this->raw_message;
		}

		$this->is_clean = true;

		$boundary = uniqid(rand(), true);
		$raw_message = count($this->customHeaders) > 0 ? join("\n", $this->customHeaders) . "\n" : '';

		if (!empty($this->message_tags)) {
			$message_tags = array();
			foreach ($this->message_tags as $key => $value) {
				$message_tags[] = "{$key}={$value}";
			}

			$raw_message .= 'X-SES-MESSAGE-TAGS: ' . join(', ', $message_tags) . "\n";
		}

		if (!is_null($this->configuration_set)) {
			$raw_message .= 'X-SES-CONFIGURATION-SET: ' . $this->configuration_set . "\n";
		}

		$raw_message .= count($this->to) > 0 ? 'To: ' . $this->encodeRecipients($this->to) . "\n" : '';
		$raw_message .= 'From: ' . $this->encodeRecipients($this->from) . "\n";
		if (!empty($this->replyto)) {
			$raw_message .= 'Reply-To: ' . $this->encodeRecipients($this->replyto) . "\n";
		}

		if (!empty($this->cc)) {
			$raw_message .= 'CC: ' . $this->encodeRecipients($this->cc) . "\n";
		}
		if (!empty($this->bcc)) {
			$raw_message .= 'BCC: ' . $this->encodeRecipients($this->bcc) . "\n";
		}

		if ($this->subject != null && strlen($this->subject) > 0) {
			$raw_message .= 'Subject: =?' . $this->subjectCharset . '?B?' . base64_encode($this->subject) . "?=\n";
		}

		$raw_message .= 'MIME-Version: 1.0' . "\n";
		$raw_message .= 'Content-type: ' . ($this->hasInlineAttachments() ? 'multipart/related' : 'Multipart/Mixed') . '; boundary="' . $boundary . '"' . "\n";
		$raw_message .= "\n--$boundary\n";
		$raw_message .= 'Content-type: Multipart/Alternative; boundary="alt-' . $boundary . '"' . "\n";

		if ($this->messagetext != null && strlen($this->messagetext) > 0) {
			$charset = empty($this->messageTextCharset) ? '' : "; charset=\"$this->messageTextCharset\"";
			$raw_message .= "\n--alt-{$boundary}\n";
			$raw_message .= 'Content-Type: text/plain' . $charset . "\n\n";
			$raw_message .= $this->messagetext . "\n";
		}

		if ($this->messagehtml != null && strlen($this->messagehtml) > 0) {
			$charset = empty($this->messageHtmlCharset) ? '' : "; charset=\"$this->messageHtmlCharset\"";
			$raw_message .= "\n--alt-$boundary\n";
			$raw_message .= 'Content-Type: text/html' . $charset . "\n\n";
			$raw_message .= $this->messagehtml . "\n";
		}
		$raw_message .= "\n--alt-{$boundary}--\n";

		foreach ($this->attachments as $attachment) {
			$raw_message .= "\n--$boundary\n";
			$raw_message .= 'Content-Type: ' . $attachment['mimeType'] . '; name="' . $attachment['name'] . '"' . "\n";
			$raw_message .= 'Content-Disposition: ' . $attachment['attachmentType'] . "\n";
			if (!empty($attachment['contentId'])) {
				$raw_message .= 'Content-ID: ' . $attachment['contentId'] . '' . "\n";
			}
			$raw_message .= 'Content-Transfer-Encoding: base64' . "\n";
			$raw_message .= "\n" . chunk_split(base64_encode($attachment['data']), 76, "\n") . "\n";
		}

		$raw_message .= "\n--$boundary--\n";

		if (!$encode) {
			return $raw_message;
		}

		$this->raw_message = base64_encode($raw_message);

		return $this->raw_message;
	}

	/**
	 * Encode recipient with the specified charset in `recipientsCharset`
	 *
	 * @return string Encoded recipients joined with comma
	 */
	public function encodeRecipients($recipient): string
	{
		if (is_array($recipient)) {
			return join(', ', array_map(array($this, 'encodeRecipients'), $recipient));
		}

		if (preg_match("/(.*)<(.*)>/", $recipient, $regs)) {
			$recipient = '=?' . $this->recipientsCharset . '?B?' . base64_encode($regs[1]) . '?= <' . $regs[2] . '>';
		}

		return $recipient;
	}

	/**
	 * Validates whether the message object has sufficient information to submit a request to SES.
	 *
	 * This does not guarantee the message will arrive, nor that the request will succeed;
	 * instead, it makes sure that no required fields are missing.
	 *
	 * This is used internally before attempting a SendEmail or SendRawEmail request,
	 * but it can be used outside of this file if verification is desired.
	 * May be useful if e.g. the data is being populated from a form; developers can generally
	 * use this function to verify completeness instead of writing custom logic.
	 *
	 * @return boolean
	 */
	public function validate() : bool {
		// at least one destination is required
		if (count($this->to) == 0 && count($this->cc) == 0 && count($this->bcc) == 0) {
			return false;
		}

		// sender is required
		if ($this->from == null || strlen($this->from) == 0) {
			return false;
		}

		// subject is required
		if (($this->subject == null || strlen($this->subject) == 0)) {
			return false;
		}

		// message is required
		if ((empty($this->messagetext) || strlen((string) $this->messagetext) == 0)
			&& (empty($this->messagehtml) || strlen((string) $this->messagehtml) == 0)) {
			return false;
		}

		return true;
	}
}