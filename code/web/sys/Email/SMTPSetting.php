<?php


class SMTPSetting extends DataObject {
	public $__table = 'smtp_settings';
	public $id;
	public $name;
	public $host;
	public $port;
	public $ssl_mode;
	public $from_address;
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
				'default' => 'heyo',
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
				'label' => 'From',
				'description' => 'The \'From:\' e-mail address',
				'default' => '',
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

	function getActiveAdminSection(): string {
		return 'system_admin';
	}
}