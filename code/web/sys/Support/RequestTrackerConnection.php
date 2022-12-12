<?php

class RequestTrackerConnection extends DataObject {
	public $__table = 'request_tracker_connection';
	public $id;
	public $baseUrl;
	public $activeTicketFeed;

	public static function getObjectStructure($context = '') {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'baseUrl' => [
				'property' => 'baseUrl',
				'type' => 'url',
				'label' => 'Base URL',
				'description' => 'The base URL of the Request Tracker System',
				'maxLength' => 255,
				'required' => true,
			],
			'activeTicketFeed' => [
				'property' => 'activeTicketFeed',
				'type' => 'url',
				'label' => 'Ticket Feed',
				'description' => 'The RSS Feed with all active tickets',
				'hideInLists' => true,
				'required' => false,
			],
		];
	}

	public function getActiveTickets() {
		$activeTickets = [];
		if (!empty($this->activeTicketFeed)) {
			$rssFeed = $this->activeTicketFeed;
			$rssDataRaw = @file_get_contents($rssFeed);
			$rssData = new SimpleXMLElement($rssDataRaw);
			if (!empty($rssData->item)) {
				foreach ($rssData->item as $item) {
					$matches = [];
					preg_match('/.*id=(\d+)/', $item->link, $matches);
					$activeTickets[$matches[1]] = [
						'id' => $matches[1],
						'title' => (string)$item->title,
						'description' => (string)$item->description,
						'link' => (string)$item->link,
					];
				}
			}
		}
		return $activeTickets;
	}
}