<?php


class SystemVariables extends DataObject
{
	public $__table = 'system_variables';
	public $id;
	public $errorEmail;
	public $ticketEmail;
	public $searchErrorEmail;

	static function getObjectStructure() {
		$structure = [
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'errorEmail' => array('property' => 'errorEmail', 'type' => 'text', 'label' => 'Error Email Address', 'description' => 'Email Address to send errors to', 'maxLength' => 128),
			'ticketEmail' => array('property' => 'ticketEmail', 'type' => 'text', 'label' => 'Ticket Email Address', 'description' => 'Email Address to send tickets from administrators to', 'maxLength' => 128, 'required' => true),
			'searchErrorEmail' => array('property' => 'searchErrorEmail', 'type' => 'text', 'label' => 'Search Error Email Address', 'description' => 'Email Address to send errors to', 'maxLength' => 128, 'required' => true),
		];
		return $structure;
	}
}