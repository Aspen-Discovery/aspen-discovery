<?php /** @noinspection PhpMissingFieldTypeInspection */


class SummonSettings extends DataObject {
	public $__table = 'summon_settings';
	public $id;
	public $name;
	public $summonApiId;
	public $summonApiPassword;
	public $summonBaseApi;
	public $filterOutBooksAndEbooks;

	function getEncryptedFieldNames(): array {
		return ['summonApiPassword'];
	}

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
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'maxLength' => 50,
				'description' => 'A name for these settings',
				'required' => true,
			],
			'summonBaseApi' => [
				'property' => 'summonBaseApi',
				'type' => 'text',
				'label' => 'Summon Base API',
				'description' => 'The API to use when connecting to Summon',
				'hideInLists' => true,
			],
			'summonApiId' => [
				'property' => 'summonApiId',
				'type' => 'text',
				'label' => 'Summon API ID',
				'description' => 'The ID to use when connecting to the Summon API',
				'hideInLists' => true,
			],
			'summonApiPassword' => [
				'property' => 'summonApiPassword',
				'type' => 'storedPassword',
				'label' => 'Summon API Password',
				'description' => 'The password to use when connecting to the Summon API',
				'hideInLists' => true,
			],
			'filterOutBooksAndEbooks' => [
				'property' => 'filterOutBooksAndEbooks',
				'type' => 'checkbox',
				'default' => false,
				'label' => 'Filter Out Books And eBooks',
				'description' => 'Whether or not to include books and ebooks in the summon search results',
				'hideInLists' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}
}