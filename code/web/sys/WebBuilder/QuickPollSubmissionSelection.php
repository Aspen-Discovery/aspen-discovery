<?php


class QuickPollSubmissionSelection extends DataObject {
	public $__table = 'web_builder_quick_poll_submission_selection';
	public $id;
	public $pollSubmissionId;
	public $pollOptionId;

	static function getObjectStructure($context = ''): array {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
//			'pollSubmissionId' => [
//				'property' => 'pollSubmissionId',
//				'type' => 'label',
//				'label' => 'Poll Submission',
//				'description' => 'The parent Quick Poll Submission',
//			],
			'pollOption' => [
				'property' => 'pollOption',
				'type' => 'text',
				'label' => 'Poll Submission',
				'description' => 'The parent Quick Poll Submission',
				'readOnly' => true,
			],
		];
	}

	private $_pollOption = null;
	public function __get($name) {
		if ($name == 'pollOption') {
			if ($this->_pollOption == null) {
				$pollOption = new QuickPollOption();
				$pollOption->id = $this->pollOptionId;
				if ($pollOption->find(true)) {
					$this->_pollOption = $pollOption->label;
				} else {
					$this->_pollOption = '';
				}
			}
			return $this->_pollOption;
		}
		return parent::__get($name);
	}
}