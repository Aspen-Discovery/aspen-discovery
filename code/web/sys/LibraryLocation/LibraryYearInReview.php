<?php /** @noinspection PhpMissingFieldTypeInspection */

class LibraryYearInReview extends DataObject {
	public $__table = 'library_year_in_review';
	public $id;
	public $yearInReviewId;
	public $libraryId;

	public function getNumericColumnNames(): array {
		return [
			'libraryId',
			'yearInReviewId',
			'weight',
		];
	}

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}

			//Load Libraries for lookup values
		$allLibraryList = Library::getLibraryList(false);
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));

		require_once ROOT_DIR . '/sys/YearInReview/YearInReviewSetting.php';
		$yearInReview = new YearInReviewSetting();
		$availableYearInReviewSettings = [];
		$yearInReview->orderBy('name');
		$yearInReview->find();
		while ($yearInReview->fetch()) {
			$availableYearInReviewSettings[$yearInReview->id] = $yearInReview->name;
		}

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id of the hours within the database',
			],
			'libraryId' => [
				'property' => 'libraryId',
				'type' => 'enum',
				'values' => $libraryList,
				'allValues' => $allLibraryList,
				'label' => 'Library',
				'description' => 'A link to the library which the theme belongs to',
			],
			'yearInReviewId' => [
				'property' => 'yearInReviewId',
				'type' => 'enum',
				'label' => 'Year in review setting',
				'values' => $availableYearInReviewSettings,
				'description' => 'The year in review settings which should be used for the library',
				'permissions' => ['Administer Year in Review for All Libraries', 'Administer Year in Review for Home Library'],
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}
}