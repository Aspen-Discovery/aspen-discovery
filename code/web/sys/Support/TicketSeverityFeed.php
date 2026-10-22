<?php /** @noinspection PhpMissingFieldTypeInspection */

class TicketSeverityFeed extends DataObject {
	public $__table = 'ticket_severity_feed';
	public $id;
	public $name;
	public $rssFeed;

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
				'description' => 'The name of the Severity',
				'maxLength' => 50,
				'required' => true,
			],
			'rssFeed' => [
				'property' => 'rssFeed',
				'type' => 'url',
				'label' => 'RSS Feed',
				'description' => 'The RSS Feed with all active tickets',
				'hideInLists' => true,
				'required' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}
}