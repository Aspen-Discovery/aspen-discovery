<?php

class TicketTrendByComponent extends DataObject {
	public $__table = 'ticket_trend_by_component';
	public $id;
	public $year;
	public $month;
	public $day;
	public $component;
	public $queue;
	public $count;
}