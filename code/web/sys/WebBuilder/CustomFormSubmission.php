<?php


class CustomFormSubmission extends DataObject
{
	public $__table = 'web_builder_custom_from_submission';
	public $id;
	public $formId;
	public $libraryId;
	public $userId;
	public $dateSubmitted;
	public $submission;

	public function getObjectStructure() : array {
		return [
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id within the database'),
			'libraryName' =>  array('property'=>'libraryName', 'type'=>'label', 'label'=>'Library', 'description'=>'The name of the library for the submission'),
			'userName' =>  array('property'=>'userName', 'type'=>'label', 'label'=>'User Name', 'description'=>'The name of the user who made the submission'),
			'dateSubmitted' => array('property' => 'dateSubmitted', 'type' => 'timestamp', 'label' => 'Date Submitted', 'description' => 'The date of the form submission'),
			'isRead' => array('property' => 'isRead', 'type' => 'checkbox', 'label' => 'Mark as Read', 'description' => 'If the submission has been read, archive it'),
			'submission' => array('property' => 'submission', 'type' => 'html', 'label' => 'Submission contents', 'description' => 'The information that was submitted by the user', 'hideInLists' => true),
		];
	}

	public function __get($name){
		if (isset($this->_data[$name])){
			return $this->_data[$name];
		}elseif ($name == 'libraryName'){
			$library = new Library();
			$library->id = $this->libraryId;
			if ($library->find(true)){
				$this->_data[$name] = $library->displayName;
			}
			$library->__destruct();
			return $this->_data[$name];
		}elseif ($name == 'userName'){
			$user = new User();
			$user->id = $this->userId;
			if ($user->find(true)){
				$this->_data[$name] = empty($user->displayName) ? ($user->firstname . ' ' . $user->lastname) : $user->displayName;
			}
			$user->__destruct();
			return $this->_data[$name];
		}
		return false;
	}
}