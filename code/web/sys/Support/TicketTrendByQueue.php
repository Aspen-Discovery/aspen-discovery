<?php

class TicketTrendByQueue extends DataObject {
	public $__table = 'ticket_trend_by_queue';
	public $id;
	public $year;
	public $month;
	public $day;
	public $queue;
	public $count;
}