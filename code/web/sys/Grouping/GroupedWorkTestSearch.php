<?php

class GroupedWorkTestSearch extends DataObject {
	public $__table = 'grouped_work_test_search';

	public $id;
	public $description;
	public $searchIndex;
	public $searchTerm;
	public $expectedGroupedWorks;
	public $unexpectedGroupedWorks;
	public $status;
	public $notes;

	public function getNumericColumnNames(): array {
		return [
			'status'
		];
	}

	public static function getObjectStructure($context = '') {
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchIndexes = $searchObject->getSearchIndexes();
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id of the in the system',
			],
			'description' => [
				'property' => 'description',
				'type' => 'textarea',
				'label' => 'Description',
				'description' => 'A brief description of the test.',
			],
			'searchIndex' => [
				'property' => 'searchIndex',
				'type' => 'enum',
				'values' => $searchIndexes,
				'label' => 'Search Index',
				'description' => 'The index to search in.',
				'default' => $searchObject->getDefaultIndex(),
				'required' => true,
			],
			'searchTerm' => [
				'property' => 'searchTerm',
				'type' => 'textarea',
				'label' => 'Search Term',
				'description' => 'The term to search for.',
				'required' => true,
			],
			'expectedGroupedWorks' => [
				'property' => 'expectedGroupedWorks',
				'type' => 'textarea',
				'label' => 'Expected Grouped Works',
				'description' => 'Grouped Works that should be shown on the first page.',
			],
			'unexpectedGroupedWorks' => [
				'property' => 'unexpectedGroupedWorks',
				'type' => 'textarea',
				'label' => 'Unexpected Grouped Works',
				'description' => 'Grouped Works that should not be shown on the first page.',
			],
			'status' => [
				'property' => 'status',
				'type' => 'enum',
				'label' => 'Status',
				'values' => [
					'0' => 'Not tested',
					'1' => 'Running',
					'2' => 'Passed',
					'3' => 'Failed',
				],
				'description' => 'The status of the test',
				'default' => '0',
				'readOnly' => true,
			],
			'notes' => [
				'property' => 'notes',
				'type' => 'textarea',
				'label' => 'Notes',
				'description' => 'Notes related to the last run.',
				'readOnly' => true,
			],
		];
	}

	public function runTest() {
		$this->status = 1;
		$this->notes = '';
		$this->update();
		$terms = preg_split("/\\r\\n|\\r|\\n/", $this->searchTerm);
		$allPass = true;
		foreach ($terms as $searchTerm) {
			/** @var SearchObject_AbstractGroupedWorkSearcher $searchObject */
			$searchObject = SearchObjectFactory::initSearchObject();
			$searchObject->init('local');
			$searchObject->setSearchTermWithIndex($this->searchIndex, $searchTerm);
			$searchObject->setFieldsToReturn('id,display_title');
			$searchObject->disableSpelling();
			$searchObject->setPrimarySearch(false);
			$result = $searchObject->processSearch(true, false);
			$this->notes .= $searchTerm . ': ';
			if ($result == null) {
				$this->status = '3';
				$this->notes .= 'Search Timed Out';
				$allPass = false;
			} else {
				$expectedWorks = preg_split("/\\r\\n|\\r|\\n/", $this->expectedGroupedWorks);
				$unexpectedWorks = preg_split("/\\r\\n|\\r|\\n/", $this->unexpectedGroupedWorks);
				$unexpectedWorksFound = [];
				foreach ($result['response']['docs'] as $doc) {
					if (($key = array_search($doc['id'], $expectedWorks)) !== false) {
						unset($expectedWorks[$key]);
					}
					if (in_array($doc['id'], $unexpectedWorks)) {
						$unexpectedWorksFound[] = $doc['id'];
					}
				}
				if (count($unexpectedWorksFound) > 0 || count($expectedWorks) > 0) {
					$allPass = false;
					if (count($expectedWorks) > 0) {
						$this->notes .= 'Expected works were not found: ';
						$this->notes .= implode(',', $expectedWorks);
					}
					if (count($unexpectedWorksFound) > 0) {
						$this->notes .= 'Unexpected works were found: ';
						$this->notes .= implode(',', $unexpectedWorksFound);
					}
				} else {
					$this->notes .= 'Passed';
				}
				$this->notes .= "\r\n";
			}
		}
		if ($allPass) {
			$this->status = 2;
		} else {
			$this->status = 3;
		}


		$this->update();
	}

	public function update($context = '') {
		if (!empty($this->_changedFields) && (in_array('searchIndex', $this->_changedFields) || in_array('searchTerm', $this->_changedFields) || in_array('expectedGroupedWorks', $this->_changedFields) || in_array('unexpectedGroupedWorks', $this->_changedFields))) {
			$this->status = 0;
			$this->notes = '';
		}
		return parent::update();
	}
}