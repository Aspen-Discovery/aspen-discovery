<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

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

	public static function getObjectStructure($context = ''): array {
		return [
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
				'required' => true,
			],
			'password' => [
				'property' => 'password',
				'type' => 'storedPassword',
				'label' => 'Password',
				'description' => 'The password',
				'default' => '',
			],
		];
	}

	function sendEmail($to, $replyTo, $subject, $body, $htmlBody, $attachments){

		require_once ('PHPMailer-6.9.1/src/PHPMailer.php');
		require_once ('PHPMailer-6.9.1/src/SMTP.php');
		require_once ('PHPMailer-6.9.1/src/Exception.php');

		$mail = new PHPMailer();

		$mail->isSMTP();
		// $mail->SMTPDebug = SMTP::DEBUG_SERVER;
		$mail->Host = $this->host;
		$mail->SMTPAuth = true;
		$mail->Username = $this->user_name;
		$mail->Password = $this->password;

		if($this->ssl_mode != 'disabled'){
			$mail->SMTPSecure = $this->ssl_mode;
		}

		$mail->From = $this->from_address;
		$mail->FromName = $this->from_name;
		$mail->addAddress($to);

		for($i = 0; $i < sizeof($attachments['name']); $i++){
			$mail->addAttachment($attachments['tmp_name'][$i], $attachments['name'][0]);
		}

		$mail->Subject = $subject;
		$mail->Body    = $htmlBody ?: $body;

		if(!$mail->send()) {
			echo 'Message could not be sent.';
			echo 'Mailer Error: ' . $mail->ErrorInfo;
			return false;
		} else {
			echo 'Message has been sent';
			return true;
		}
	}

	function getActiveAdminSection(): string {
		return 'system_admin';
	}
}