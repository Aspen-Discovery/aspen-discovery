<?php /** @noinspection PhpMissingFieldTypeInspection */

use PHPMailer\PHPMailer\PHPMailer;

class SMTPSetting extends DataObject {
	public $__table = 'smtp_settings';
	public $id;
	public $name;
	public $host;
	public $port;
	public $ssl_mode;
	public $from_address;
	public $from_name;
	public $user_name;
	public $password;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Server name',
				'description' => 'The name of the server',
				'required' => true,
			],
			'host' => [
				'property' => 'host',
				'type' => 'text',
				'label' => 'Host',
				'description' => 'The SMTP host',
				'default' => 'localhost',
				'required' => true,
			],
			'port' => [
				'property' => 'port',
				'type' => 'integer',
				'label' => 'Port',
				'description' => 'The utilized port',
				'default' => '25',
				'required' => true,
			],
			'ssl_mode' => [
				'property' => 'ssl_mode',
				'type' => 'enum',
				'values' => [
					'disabled' => 'Disabled',
					'ssl' => 'SSL',
					'tls' => 'StartTLS',
				],
				'label' => 'SSL mode',
				'description' => 'SSL mode',
			],
			'from_address' => [
				'property' => 'from_address',
				'type' => 'text',
				'label' => '\'From\' address',
				'description' => 'The \'From:\' e-mail address',
				'default' => '',
				'required' => true,
			],
			'from_name' => [
				'property' => 'from_name',
				'type' => 'text',
				'label' => '\'From\' name',
				'description' => 'The \'From:\' name',
				'required' => true,
			],
			'user_name' => [
				'property' => 'user_name',
				'type' => 'text',
				'label' => 'Username',
				'description' => 'The username',
				'default' => '',
				'required' => false,
			],
			'password' => [
				'property' => 'password',
				'type' => 'storedPassword',
				'label' => 'Password',
				'description' => 'The password',
				'default' => '',
				'hideInLists' => true
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	/** @noinspection PhpUnusedParameterInspection */
	function sendEmail($to, $replyTo, $subject, $body, $htmlBody, $attachments) : bool{

		require_once ('PHPMailer-6.9.1/src/PHPMailer.php');
		require_once ('PHPMailer-6.9.1/src/SMTP.php');
		require_once ('PHPMailer-6.9.1/src/Exception.php');

		$mail = new PHPMailer();

		$mail->isSMTP();
		// $mail->SMTPDebug = SMTP::DEBUG_SERVER;
		$mail->Host = $this->host;
		$mail->Port = $this->port;
		if (!empty($this->user_name)) {
			$mail->SMTPAuth = true;
			$mail->Username = $this->user_name;
			$mail->Password = $this->password;
		}

		if($this->ssl_mode != 'disabled'){
			$mail->SMTPSecure = $this->ssl_mode;
		}

		$mail->From = $this->from_address;
		$mail->FromName = $this->from_name;
		$mail->CharSet = 'UTF-8';

		$toAddresses = explode(';', $to);
		foreach ($toAddresses as $toAddress) {
			$mail->addAddress($toAddress);
		}

		if(!empty($attachments)) {
			for ($i = 0; $i < sizeof($attachments['name']); $i++) {
				$mail->addAttachment($attachments['tmp_name'][$i], $attachments['name'][0]);
			}
		}

		if (!mb_check_encoding($subject, 'ASCII')) {
			$subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
		}

		$mail->Subject = $subject;
		if (!empty($htmlBody)) {
			$mail->isHTML();
			$mail->Body = $htmlBody;
		} else {
			$mail->isHTML(false);
			$mail->Body = $body;
		}

		if(!$mail->send()) {
			global $logger;
			$logger->log('Message could not be sent.', Logger::LOG_ERROR);
			$logger->log('Mailer Error: ' . $mail->ErrorInfo, Logger::LOG_ERROR);
			return false;
		} else {
		//	echo 'Message has been sent';
			return true;
		}
	}

	function getActiveAdminSection(): string {
		return 'system_admin';
	}
}