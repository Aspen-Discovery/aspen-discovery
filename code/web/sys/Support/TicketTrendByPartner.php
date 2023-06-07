<?php

class TicketTrendByPartner extends DataObject {
	public $__table = 'ticket_trend_by_partner';
	public $id;
	public $year;
	public $month;
	public $day;
	public $requestingPartner;
	public $queue;
	public $count;
}