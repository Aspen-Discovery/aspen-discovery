<?php


class SystemVariables extends DataObject
{
	public $__table = 'system_variables';
	public $id;
	public $errorEmail;
	public $ticketEmail;
	public $searchErrorEmail;
	public $loadCoversFrom020z;
	public $runNightlyFullIndex;

	static function getObjectStructure() {
		return [
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'errorEmail' => array('property' => 'errorEmail', 'type' => 'text', 'label' => 'Error Email Address', 'description' => 'Email Address to send errors to', 'maxLength' => 128),
			'ticketEmail' => array('property' => 'ticketEmail', 'type' => 'text', 'label' => 'Ticket Email Address', 'description' => 'Email Address to send tickets from administrators to', 'maxLength' => 128),
			'searchErrorEmail' => array('property' => 'searchErrorEmail', 'type' => 'text', 'label' => 'Search Error Email Address', 'description' => 'Email Address to send errors to', 'maxLength' => 128),
			'runNightlyFullIndex' => array('property' => 'runNightlyFullIndex', 'type' => 'checkbox', 'label' => 'Run full index tonight', 'description' => 'Whether or not a full index should be run in the middle of the night', 'default' => false),
			'loadCoversFrom020z' => array('property' => 'loadCoversFrom020z', 'type' => 'checkbox', 'label' => 'Load covers from cancelled & invalid ISBNs (020$z)', 'description' => 'Whether or not covers can be loaded from the 020z', 'default' => false),
		];
	}

	public static function forceNightlyIndex()
	{
		$variables = new SystemVariables();
		if ($variables->find(true)){
			if ($variables->runNightlyFullIndex == 0) {
				$variables->runNightlyFullIndex = 1;
				$variables->update();
			}
		}
	}
}