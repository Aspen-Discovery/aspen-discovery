<?php

class TicketQueueFeed extends DataObject
{
	public $__table = 'ticket_queue_feed';
	public $id;
	public $name;
	public $rssFeed;

	public static function getObjectStructure(): array
	{
		return [
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'name' => array('property' => 'name', 'type' => 'text', 'label' => 'Name', 'description' => 'The name of the queue', 'maxLength' => 50, 'required' => true),
			'rssFeed' => array('property' => 'rssFeed', 'type' => 'url', 'label' => 'RSS Feed', 'description' => 'The RSS Feed with all active tickets', 'hideInLists' => true, 'required' => true),
		];
	}
}