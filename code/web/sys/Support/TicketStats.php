<?php

class TicketStats extends DataObject
{
	public $__table = 'ticket_stats';
	public $id;
	public $year;
	public $month;
	public $day;
	public $queue;
	public $status;
	public $severity;
	public $count;
	//TODO: components
}