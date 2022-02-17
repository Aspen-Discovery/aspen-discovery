<?php

class GroupedWorkTestSearch extends DataObject
{
	public $__table = 'grouped_work_test_search';

	public $id;
	public $searchTerm;
	public $expectedGroupedWorks;
	public $unexpectedGroupedWorks;
	public $status;
	public $notes;

	public static function getObjectStructure(){
		return [
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of the in the system'),
			'searchTerm' => array('property'=>'searchTerm', 'type'=>'text', 'label'=>'Search Term', 'description'=>'The term to search for.'),
			'expectedGroupedWorks' => array('property'=>'expectedGroupedWorks', 'type'=>'textarea', 'label'=>'Expected Grouped Works', 'description'=>'Grouped Works that should be shown on the first page.', 'hideInLists'=>true),
			'unexpectedGroupedWorks' => array('property'=>'unexpectedGroupedWorks', 'type'=>'textarea', 'label'=>'Unexpected Grouped Works', 'description'=>'Grouped Works that should not be shown on the first page.', 'hideInLists'=>true),
			'status'  => array('property' => 'status', 'type' => 'enum', 'label' => 'Status', 'values' => ['0' => 'Not tested', '1' => 'Running', '2' => 'Passed', '3' => 'Failed'], 'description' => 'The status of the test', 'required' => true, 'default' => '0', 'readonly'=>true),
			'notes' => array('property'=>'notes', 'type'=>'text', 'label'=>'Notes', 'description'=>'Notes related to the last run.', 'readonly'=>true),
		];
	}

	public function runTest()
	{
		$this->status = 1;
		$this->notes = '';
		$this->update();
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init('local', $this->searchTerm);
		$searchObject->setFieldsToReturn('id,display_title');
		$searchObject->setPrimarySearch(false);
		$result = $searchObject->processSearch(true, false);
		if ($result == null) {
			$this->status = '3';
			$this->notes = 'Search Timed Out';
		}else{
			$expectedWorks = preg_split("/\\r\\n|\\r|\\n/", $this->expectedGroupedWorks);
			$unexpectedWorks = preg_split("/\\r\\n|\\r|\\n/", $this->unexpectedGroupedWorks);
			$unexpectedWorksFound = [];
			foreach ($result['response']['docs'] as $doc){
				if (($key = array_search($doc['id'], $expectedWorks)) !== false) {
					unset($expectedWorks[$key]);
				}
				if (in_array($doc['id'], $unexpectedWorks)){
					$unexpectedWorksFound[] = $doc['id'];
				}
			}
			if (count($unexpectedWorksFound) > 0 || count($expectedWorks) > 0){
				$this->status = 3;
				if (count($expectedWorks) > 0){
					$this->notes = 'Expected works were not found: ';
					$this->notes .= implode(',', $expectedWorks);
				}
				if (count($unexpectedWorksFound) > 0){
					$this->notes = 'Unexpected works were found: ';
					$this->notes .= implode(',', $unexpectedWorksFound);
				}

			}else{
				$this->status = 2;
			}
		}

		$this->update();
	}
}