<?php /** @noinspection PhpMissingFieldTypeInspection */

class MaterialsRequestHoldCandidateGenerationLogEntry extends DataObject {
	public $__table = 'materials_request_hold_candidate_generation_log';
	public $id;
	public $startTime;
	public $endTime;
	public $numRequestsChecked;
	public $numRequestsWithNewSuggestions;
	public $numSearchErrors;
	public $notes;

	/** @noinspection PhpUnusedParameterInspection */
	static function getObjectStructure($context = ''): array{
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
			'startTime' => [
				'property' => 'startTime',
				'type' => 'timestamp',
				'label' => 'Start Time',
				'description' => 'The time the process started',
				'readOnly' => true,
			],
			'endTime' => [
				'property' => 'endTime',
				'type' => 'timestamp',
				'label' => 'End Time',
				'description' => 'The time the process ended',
				'readOnly' => true,
			],
			'numRequestsChecked' => [
				'property' => 'numRequestsChecked',
				'type' => 'integer',
				'label' => 'Num Requests Checked',
				'description' => 'The number of requests checked during the process',
				'readOnly' => true,
			],
			'numRequestsWithNewSuggestions' => [
				'property' => 'numRequestsWithNewSuggestions',
				'type' => 'integer',
				'label' => 'Num Requests With New Suggestions',
				'description' => 'The number of requests with new suggestions during the process',
				'readOnly' => true,
			],
			'numSearchErrors' => [
				'property' => 'numSearchErrors',
				'type' => 'integer',
				'label' => 'Num Search Errors',
				'description' => 'The number of search errors during the process',
				'readOnly' => true,
			],
			'notes' => [
				'property' => 'notes',
				'type' => 'textarea',
				'label' => 'Notes',
				'description' => 'Notes entered while the process ran',
				'readOnly' => true,
			]
		];
	}

	public function initializeCounters() {
		$this->numRequestsChecked = 0;
		$this->numRequestsWithNewSuggestions = 0;
		$this->numSearchErrors = 0;
	}

	public function addNote($note) : void {
		if (!empty($this->notes)) {
			$this->notes .= '<br/>';
		}
		$this->notes .= $note;
		$this->update();
	}

	public function incrementRequestsChecked() : void {
		$this->numRequestsChecked++;
		if ($this->numRequestsChecked % 50 == 0) {
			$this->update();
		}
	}

	public function incrementSearchErrors(AspenError $error) : void {
		$this->numSearchErrors++;
		$this->addNote($error->message);
	}

	public function incrementRequestsWithNewSuggestions() : void {
		$this->numRequestsWithNewSuggestions++;
		if ($this->numRequestsChecked % 50 == 0) {
			$this->update();
		}
	}
}
