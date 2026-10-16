<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/Enrichment/GoogleApiSetting.php';

class LibraryGoogleAnalytics extends DataObject {
	public $__table = 'library_google_analytics';    // table name
	public $id;
	public $libraryId;
	public $googleApiSettingId;
	public $googleAnalyticsTrackingId;

	static $_objectStructure = [];

	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
		$libraryList[-1] = 'No Library Selected';
		$googleApiSetting = new GoogleApiSetting();
		$googleApiSettings = $googleApiSetting->fetchAll('id', 'id');
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'libraryId' => [
				'property' => 'libraryId',
				'type' => 'enum',
				'values' => $libraryList,
				'label' => 'Library',
				'description' => 'A link to the library which the location belongs to',
			],
			'googleApiSettingId' => [
				'property' => 'googleApiSettingId',
				'type' => 'enum',
				'values' => $googleApiSettings,
				'label' => 'Google API Setting',
				'description' => 'The API Setting this is attached to',
			],
			'googleAnalyticsTrackingId' => [
				'property' => 'googleAnalyticsTrackingId',
				'type' => 'text',
				'label' => 'Google Analytics Measurement ID',
				'description' => 'The Google Analytics Measurement ID to use',
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}
}