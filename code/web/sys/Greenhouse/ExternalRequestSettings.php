<?php /** @noinspection PhpMissingFieldTypeInspection */


class ExternalRequestSettings extends DataObject {
	public $__table = 'external_request_settings';
	public $id;
	public $requestType;
	public $enabled;
	public $expireDate;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'RequestType' => [
				'property' => 'requestType',
				'type' => 'text',
				'label' => 'Request Type',
				'required' => true,
				'description' => 'Request Type to match requests against. Could be just api or api.method'

			],
			'enabled' => [
				'property' => 'enabled',
				'type' => 'checkbox',
				'label' => 'Enabled?',
				'description' => 'Whether or not to always log requests when they start with the given type',
				'default' => '1',
			],
			'expireDate' => [
				'property' => 'expireDate',
				'type' => 'date',
				'min' => date('Y-m-d', strtotime('1 day')),
				'default' => date('Y-m-d', strtotime('1 day')),
				'label' => 'Log all requests until',
				'description' => 'Log all requests for the specified type until this Date',

			]
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}
}