<?php /** @noinspection PhpMissingFieldTypeInspection */

class MaterialsRequestTitle extends DataObject
{
	public $__table = 'materials_request_title';   // table name

	// Note: if table column names are changed, data for class MaterialsRequestFieldsToDisplay will need updated.
	public $id;
	public $title;
	public $season;
	public $author;
	public $format;
	public $formatId;
	public $isbn;
	public $upc;
	public $issn;
	public $comments;
	public $hasExistingRecord;
	public $lastCheckForExistingRecord;
	public $existingRecordUrl;
	public $dateFirstRequested;
	public $dateLastRequested;

	public ?int $numRequests = null;

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
			'title' => [
				'property' => 'title',
				'type' => 'text',
				'label' => 'Title',
				'description' => 'The title of the materials request.',
			],
			'author' => [
				'property' => 'author',
				'type' => 'text',
				'label' => 'Author',
				'description' => 'The author of the title/request.',
			],
			'format' => [
				'property' => 'format',
				'type' => 'text',
				'label' => 'Format',
				'description' => 'The format of the title/request.',
			],
			'dateFirstRequested' => [
				'property' => 'dateFirstRequested',
				'type' => 'date',
				'label' => 'Date First Requested',
				'description' => 'The date the first materials request was made for this title.',
			],
			'dateLastRequested' => [
				'property' => 'dateLastRequested',
				'type' => 'date',
				'label' => 'Date Last Requested',
				'description' => 'The date the last materials request for this title was made.',
			],
			'numRequests' => [
				'property' => 'numRequests',
				'type' => 'calculatedInteger',
				'label' => 'Number of Requests',
				'description' => 'The number of requests for the title',
				'readOnly' => true,
				'canFilter' => true
			]
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function getUniquenessFields(): array
	{
		return ['id'];
	}

	public function __get($name)
	{
		return parent::__get($name);
	}

	public function getNumRequests() : ?array {
		if (!isset($this->_numRequests) && $this->id) {
			$materialsRequestCount = 0;
			$materialsRequest = new MaterialsRequest();
			$materialsRequest->materialsRequestTitleId = $this->id;
			while ($materialsRequest->fetch()){
				$this->_numRequests[] = $materialsRequestCount++;
			}
		}
		return $this->_numRequests;
	}

	public function okToExport(array $selectedFilters): bool
	{
		$okToExport = parent::okToExport($selectedFilters);
		if (in_array($this->libraryId, $selectedFilters['libraries'])) {
			$okToExport = true;
		}
		return $okToExport;
	}

	/** @noinspection PhpUnused */
	public function automaticCheckForExistingRecordNeedsToBeDone(): bool
	{
		//Automatically check for existing records if it has been an hour and an existing record with the same format has not been found.
		return ((time() - $this->lastCheckForExistingRecord) > 60 * 60) && !$this->hasExistingRecord;
	}
}
