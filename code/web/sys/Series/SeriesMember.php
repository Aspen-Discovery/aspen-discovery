<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class SeriesMember extends DataObject {
	public $__table = 'series_member';
	public $id;
	public $seriesId;
	public $groupedWorkPermanentId;
	public $isPlaceholder;
	public $displayName;
	public $author;
	public $description;
	public $volume;
	public $pubDate;
	public $weight;
	public $cover;
	public $userAdded;

	public static function getObjectStructure($context = ''): array {
		global $configArray;
		$coverPath = $configArray['Site']['coverPath'];
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'displayName' => [
				'property' => 'displayName',
				'type' => 'text',
				'label' => 'Title',
				'readOnly' => true,
			],
			'author' => [
				'property' => 'author',
				'type' => 'text',
				'label' => 'Author',
				'readOnly' => true,
			],
			'description' => [
				'property' => 'description',
				'type' => 'text',
				'label' => 'Description',
				'description' => 'Series description',
				'note' => 'Title description',
				'readOnly' => true,
			],
			'volume' => [
				'property' => 'volume',
				'type' => 'text',
				'label' => 'Volume',
				'description' => 'Modify to correct sorting by volume',
			],
			'pubDate' => [
				'property' => 'pubDate',
				'type' => 'integer',
				'label' => 'Earliest Publication Date',
				'description' => 'Modify to correct sorting by date',
				'default' => (int) Date("Y"),
				'min' => 0,
				'max' => (int) Date("Y") + 1, // No books from too far in the future
			],
			'groupedWorkPermanentId' => [
				'property' => 'groupedWorkPermanentId',
				'type' => 'text',
				'label' => 'Permanent Id',
				'readOnly' => true,
			],
			'cover' => [
				'property' => 'cover',
				'type' => 'image',
				'label' => 'Cover',
				'description' => 'Replacement cover',
				'maxWidth' => 280,
				'maxHeight' => 280,
				'path' => "$coverPath/original/seriesMember",
				'hideInLists' => true,
			],
			'isPlaceholder' => [
				'property' => 'isPlaceholder',
				'type' => 'hidden',
				'label' => 'Placeholder',
				'readOnly' => true,
			],
			'weight' => [
				'property' => 'weight',
				'type' => 'numeric',
				'label' => 'Weight',
				'weight' => 'Defines how items are sorted.  Lower weights are displayed higher.',
			],
			'userAdded' => [
				'property' => 'userAdded',
				'type' => 'hidden',
				'label' => 'User Added',
				'readOnly' => true,
			],
		];
		return $structure;
	}

	public function update($context = '') {
		if ($this->groupedWorkPermanentId) {
			$this->isPlaceholder = false;
		} else {
			$this->isPlaceholder = true;
		}
		return parent::update();
	}

	public function insert($context = '') {
		if ($this->groupedWorkPermanentId) {
			$this->isPlaceholder = false;
		} else {
			$this->isPlaceholder = true;
		}
		$this->userAdded = true;
		return parent::insert();
	}

	public function getNumericColumnNames(): array {
		return [
			'pubDate',
			'isPlaceholder',
			'userAdded',
		];
	}

	public function getRecordDriver() {
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$recordDriver = new GroupedWorkDriver($this->groupedWorkPermanentId);
		if (!$recordDriver->isValid()) {
			return null;
		}
		return $recordDriver;
	}

}